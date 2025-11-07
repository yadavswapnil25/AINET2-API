<?php

namespace App\Http\Controllers\Client;

use App\Models\User;
use App\Traits\Response;
use App\Enums\GenderEnum;
use App\Mail\Mail;
use App\Mails\ForgotPasswordMail;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Enum;

class LoginController extends Controller
{
    use Response;
    public function login(Request $request): JsonResponse|\Symfony\Component\HttpFoundation\Response|RedirectResponse
    {
        $this->validateLogin($request);

        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        return $this->sendFailedLoginResponse($request);
    }
    protected function validateLogin(Request $request): void
    {
        $request->validate([
            $username = $this->username() =>
            'required|string',
            'password' => 'required|string',
        ]);
    }

    public function username()
    {
        return 'email';
    }
    protected function attemptLogin(Request $request)
    {
        return $this->guard()->attempt(
            $this->credentials($request),
            $request->boolean('remember')
        );
    }

    protected function guard()
    {
        return Auth::guard();
    }
    protected function credentials(Request $request)
    {
        return $request->only($this->username(), 'password');
    }
    protected function sendLoginResponse(Request $request)
    {
        if ($response = $this->authenticated($request, $this->guard()->user())) {
            return $response;
        }

        return $request->wantsJson()
            ? new JsonResponse([], 204)
            : redirect()->intended($this->redirectPath());
    }

    protected function authenticated(Request $request, $user)
    {
        try {
            $fingerPrint = $request->fingerprint();
            $accessGrant = $user->createToken($fingerPrint);

            return $this->success('Successful authentication', 200, [
                'user' => $user,
                'token' => $accessGrant->accessToken,
                'two_factor_auth_methods' => null
            ]);
        } catch (\Exception $exception) {
            dd($exception);
            Log::error($exception);
            return $this->error('An error occurred while you were trying to login. Please try again in a bit.', 400);
        }
    }

    public function redirectPath()
    {
        // Return a specific path or fallback to a default
        return '/login'; // or any route you want to redirect to after login
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => "The provided credentials do not match our records.",
        ], 401);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        // Add image URL if image exists
        if ($user->image) {
            $user->image_url = asset('storage/' . $user->image);
        }
        return $this->success('User profile fetched successfully.', 200, [
            'user' => $user
        ]);
    }

    public function updateProfile(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Use $request->all() for validation to support form-data
        $validated = validator($request->all(), [
            'name' => 'sometimes|string|max:255',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'mobile' => 'sometimes|string|max:20',
            'gender' => ['sometimes', 'nullable', new Enum(GenderEnum::class)],
            'dob' => 'sometimes|date_format:Y-m-d',
            'address' => 'sometimes|string|max:255',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ])->validate();
        
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $path = $image->store('profile_images', 'public');
            $validated['image'] = $path;
        }

        $user->fill($validated);
        $user->save();

        return $this->success('Profile updated successfully.', 200, [
            'user' => $user
        ]);
    }

    /**
     * Send password reset link to user's email
     *
     * @param ForgotPasswordRequest $request
     * @return JsonResponse
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return $this->error('We could not find a user with that email address.', 404);
            }

            // Generate password reset token
            // Delete any existing tokens for this user
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            
            // Create new token
            $token = Str::random(64);
            DB::table('password_reset_tokens')->insert([
                'email' => $user->email,
                'token' => Hash::make($token),
                'created_at' => now()
            ]);

            // Send password reset email using Mailgun
            try {
                Mail::site()->send(new ForgotPasswordMail($user, $token));
            } catch (\Exception $mailException) {
                Log::error('Failed to send password reset email: ' . $mailException->getMessage());
                return $this->error('Failed to send password reset email. Please try again later.', 500);
            }

            return $this->success('Password reset link has been sent to your email address.', 200, [
                'message' => 'If the email exists in our system, you will receive a password reset link.'
            ]);
        } catch (\Exception $exception) {
            Log::error('Forgot password error: ' . $exception->getMessage());
            return $this->error('An error occurred while processing your request. Please try again later.', 500);
        }
    }

    /**
     * Reset user password using token
     *
     * @param ResetPasswordRequest $request
     * @return JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return $this->error('We could not find a user with that email address.', 404);
            }

            // Get the password reset token from database
            $passwordReset = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$passwordReset) {
                return $this->error('Invalid or expired reset token. Please request a new password reset link.', 400);
            }

            // Check if token is expired (60 minutes)
            $tokenAge = now()->diffInMinutes($passwordReset->created_at);
            if ($tokenAge > 60) {
                DB::table('password_reset_tokens')->where('email', $request->email)->delete();
                return $this->error('Reset token has expired. Please request a new password reset link.', 400);
            }

            // Verify the token
            if (!Hash::check($request->token, $passwordReset->token)) {
                return $this->error('Invalid reset token. Please request a new password reset link.', 400);
            }

            // Update user password
            $user->password = Hash::make($request->password);
            $user->save();

            // Delete the used token
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return $this->success('Your password has been reset successfully. You can now login with your new password.', 200);
        } catch (\Exception $exception) {
            Log::error('Reset password error: ' . $exception->getMessage());
            return $this->error('An error occurred while resetting your password. Please try again later.', 500);
        }
    }
}

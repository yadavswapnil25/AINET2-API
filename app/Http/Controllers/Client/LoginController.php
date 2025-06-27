<?php

namespace App\Http\Controllers\Client;

use App\Models\User;
use App\Traits\Response;
use App\Enums\GenderEnum;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
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
}

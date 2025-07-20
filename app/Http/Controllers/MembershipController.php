<?php

namespace App\Http\Controllers;

use App\Mail\Mail;
use App\Models\User;
use App\Traits\Response;
use App\Mails\WelcomeMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\MembershipSignupRequest;
use Str;
class MembershipController extends Controller
{
    use Response;
    public function signup(MembershipSignupRequest $request): JsonResponse
    {
        try {
            \DB::beginTransaction();
            $data = $request->validated();
            // Convert dob to Y-m-d format if needed
            if (isset($data['dob']) && preg_match('/\d{2}\/\d{2}\/\d{4}/', $data['dob'])) {
                $dob = \DateTime::createFromFormat('d/m/Y', $data['dob']);
                $data['dob'] = $dob ? $dob->format('Y-m-d') : null;
            }
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'name' => $data['first_name'] . ' ' . $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'qualification'   => is_array($data['qualification']) ? json_encode($data['qualification']) : $data['qualification'],
                'area_of_work'    => is_array($data['area_of_work']) ? json_encode($data['area_of_work']) : $data['area_of_work'],
                'gender' => $data['gender'],
                'mobile' => $data['mobile'],
                'whatsapp_no' => $data['whatsapp_no'],
                'dob' => $data['dob'],
                'address' => $data['address'],
                'state' => $data['state'],
                'district' => $data['district'],
                'teaching_exp' => $data['teaching_exp'],
                'membership_type' => $data['membership_type'],
                'type'  => $data['membership_type'],
                'membership_plan' => $data['membership_plan'],
                'pin' => $data['pin'],
                'ref' => Str::uuid(), 
                'has_member_any' => $data['has_member_any'],
                'association ' => $data['name_association'],
                'expectations' => $data['expectation'],
                'receive' => $data['has_newsletter'],
                'title' => $data['title'],
                'address' => $data['address_institution'],
                'name' => $data['name_institution'],
                'inst_type' => $data['type_institution'],
                'othertype' => $data['other_institution'],
                'person' => $data['contact_person'],
                'emailperson ' => $data['emailperson'],
                'mobileperson' => $data['mobileperson'],
                'collaborate' => $data['collaborate'],
            ]);
            // Send welcome email after successful user creation
            try {
                Mail::site()->send(new WelcomeMail($user));
            } catch (\Exception $mailException) {
                \Log::error('Failed to send welcome email: ' . $mailException->getMessage());
            }
            \DB::commit();

            return $this->success('Signup successful', 201, [
                'user' => $user,
                'message' => 'Welcome to AINET! Your account has been created successfully.'
            ]);
        } catch (\Exception $e) {
            dd($e);
            \DB::rollBack();
            return $this->error('Signup failed: ' . $e->getMessage(), 500, [
                'error' => $e->getMessage()
            ]);
        }
    }
}

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

            // Safely get values with null coalescing to avoid undefined array key errors
            $firstName = $data['first_name'] ?? '';
            $lastName = $data['last_name'] ?? '';
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';
            $qualification = $data['qualification'] ?? null;
            $areaOfWork = $data['area_of_work'] ?? null;
            $gender = $data['gender'] ?? null;
            $mobile = $data['mobile'] ?? null;
            $whatsappNo = $data['whatsapp_no'] ?? null;
            $dob = $data['dob'] ?? null;
            $address = $data['address'] ?? null;
            $state = $data['state'] ?? null;
            $district = $data['district'] ?? null;
            $teachingExp = $data['teaching_exp'] ?? null;
            $membershipType = $data['membership_type'] ?? null;
            $membershipPlan = $data['membership_plan'] ?? null;
            $pin = $data['pin'] ?? null;
            $hasMemberAny = $data['has_member_any'] ?? null;
            $nameAssociation = $data['name_association'] ?? null;
            $expectation = $data['expectation'] ?? null;
            $hasNewsletter = $data['has_newsletter'] ?? null;
            $title = $data['title'] ?? null;
            $addressInstitution = $data['address_institution'] ?? null;
            $nameInstitution = $data['name_institution'] ?? null;
            $typeInstitution = $data['type_institution'] ?? null;
            $otherInstitution = $data['other_institution'] ?? null;
            $contactPerson = $data['contact_person'] ?? null;
            $emailPerson = $data['emailperson'] ?? null;
            $mobilePerson = $data['mobileperson'] ?? null;
            $collaborate = $data['collaborate'] ?? null;

            // Convert dob to Y-m-d format if needed
            if ($dob && preg_match('/\d{2}\/\d{2}\/\d{4}/', $dob)) {
                $dobObj = \DateTime::createFromFormat('d/m/Y', $dob);
                $dob = $dobObj ? $dobObj->format('Y-m-d') : null;
            }

            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => trim($firstName . ' ' . $lastName),
                'email' => $email,
                'password' => Hash::make($password),
                'qualification'   => is_array($qualification) ? json_encode($qualification) : $qualification,
                'area_of_work'    => is_array($areaOfWork) ? json_encode($areaOfWork) : $areaOfWork,
                'gender' => $gender,
                'mobile' => $mobile,
                'whatsapp_no' => $whatsappNo,
                'dob' => $dob,
                'address' => $address,
                'state' => $state,
                'district' => $district,
                'teaching_exp' => $teachingExp,
                'membership_type' => $membershipType,
                'type'  => $membershipType,
                'membership_plan' => $membershipPlan,
                'pin' => $pin,
                'ref' => Str::uuid(), 
                'has_member_any' => $hasMemberAny,
                'association ' => $nameAssociation,
                'expectations' => $expectation,
                'receive' => $hasNewsletter,
                'title' => $title,
                'address' => $addressInstitution,
                'name' => $nameInstitution,
                'inst_type' => $typeInstitution,
                'othertype' => $otherInstitution,
                'person' => $contactPerson,
                'emailperson ' => $emailPerson,
                'mobileperson' => $mobilePerson,
                'collaborate' => $collaborate,
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

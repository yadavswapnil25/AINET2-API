<?php

namespace App\Http\Controllers;

use App\Mail\Mail;
use App\Models\User;
use App\Traits\Response;
use App\Mails\WelcomeMail;
use App\Mails\MembershipRenewalUserMail;
use App\Mails\MembershipRenewalAdminMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\MembershipSignupRequest;
use App\Http\Requests\ConfirmMembershipPaymentRequest;
use App\Services\RazorpayService;
use App\Services\MembershipPricingService;
use Illuminate\Support\Str;
class MembershipController extends Controller
{
    use Response;
    public function __construct(
        protected RazorpayService $razorpay,
        protected MembershipPricingService $pricing
    ) {
    }

    private function composeFullName(
        ?string $firstName,
        ?string $lastName,
        ?string $title,
        ?string $membershipType,
        ?string $institutionName
    ): string {
        $parts = [];

        if (!empty($title)) {
            $parts[] = trim($title);
        }

        if (!empty($firstName) || !empty($lastName)) {
            $name = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));
            if (!empty($name)) {
                $parts[] = $name;
            }
        }

        if (in_array($membershipType, ['Institutional']) && !empty($institutionName)) {
            $parts[] = trim($institutionName);
        }

        $fullName = trim(implode(' ', array_filter($parts)));

        if ($fullName === '') {
            return 'AINET Member';
        }

        return $fullName;
    }
    public function signup(MembershipSignupRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();

            // Safely get values with null coalescing to avoid undefined array key errors
            $firstName = $data['first_name'] ?? '';
            $lastName = $data['last_name'] ?? '';
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';
            $qualification = $data['qualification'] ?? null;
            $areaOfWork = $data['area_of_work'] ?? null;
            $otherAreaOfWork = $data['other_area_of_work'] ?? null;
            $gender = $data['gender'] ?? null;
            $mobile = $data['mobile'] ?? null;
            $whatsappNo = $data['whatsapp_no'] ?? null;
            $ageGroup = $data['age_group'] ?? null;
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
            // Store age_group value in the dob column (backward compatibility)
            $dob = $ageGroup;

            $fullName = $this->composeFullName(
                $firstName,
                $lastName,
                $title,
                $membershipType,
                $nameInstitution
            );
            
            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => $fullName,
                'email' => $email,
                'password' => Hash::make($password),
                'qualification'   => is_array($qualification) ? json_encode($qualification) : $qualification,
                'area_of_work'    => is_array($areaOfWork) ? json_encode($areaOfWork) : $areaOfWork,
                'other_area_of_work' => $otherAreaOfWork,
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
                'name_association' => $nameAssociation,
                'expectation' => $expectation,
                'has_newsletter' => $hasNewsletter,
                'title' => $title,
                'address_institution' => $addressInstitution,
                'inst_type' => $typeInstitution,
                'othertype' => $otherInstitution,
                'person' => $contactPerson,
                'emailperson' => $emailPerson,
                'mobileperson' => $mobilePerson,
                'collaborate' => $collaborate,
                'payment_status' => 'pending',
            ]);
            // Send welcome email after successful user creation
            try {
                Mail::site()->send(new WelcomeMail($user));
            } catch (\Exception $mailException) {
                Log::error('Failed to send welcome email: ' . $mailException->getMessage());
            }
            DB::commit();

            return $this->success('Signup successful', 201, [
                'user' => $user,
                'message' => 'Membership details saved. Please complete the payment to activate membership.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Signup failed: ' . $e->getMessage(), 500, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Public pricing catalogue.
     *
     * Lets the website render live prices and the promotional discount
     * without hardcoding any amounts, and makes the promo cut-off date take
     * effect on its own without a redeploy.
     */
    public function showPricing(): JsonResponse
    {
        return $this->success(
            'Membership pricing fetched successfully.',
            200,
            $this->pricing->publicCatalogue()
        );
    }

    /**
     * Create the Razorpay order for a pending signup.
     *
     * The amount is derived server side from the plan stored against the user
     * at signup, so the client can neither pick its own price nor pay after
     * the promotional cut-off at promotional rates.
     */
    public function signupOrder(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = User::find($request->input('user_id'));

        if (!$user) {
            return $this->error('User not found.', 404);
        }

        if ($user->payment_status === 'paid') {
            return $this->error('Payment already confirmed for this membership.', 409);
        }

        $type = (string) $user->membership_type;
        $plan = (string) $user->membership_plan;

        if (!config("membership.plans.{$type}.{$plan}")) {
            return $this->error('This membership record does not have a valid plan.', 422);
        }

        $breakdown = $this->pricing->breakdown($type, $plan, MembershipPricingService::CONTEXT_NEW);

        $order = $this->razorpay->createOrder(
            $breakdown['price'],
            'INR',
            'AINET-NEW-' . $user->id . '-' . time(),
            [
                'user_id' => (string) $user->id,
                'plan'    => $plan,
                'type'    => $type,
                'context' => MembershipPricingService::CONTEXT_NEW,
            ]
        );

        $user->razorpay_order_id = $order['id'] ?? null;
        $user->save();

        return $this->success('Order created successfully', 200, [
            'order'    => $order,
            'amount'   => $order['amount'] ?? $breakdown['price_in_paise'],
            'currency' => $order['currency'] ?? 'INR',
            'pricing'  => $breakdown,
            'customer' => [
                'name'    => $user->name,
                'email'   => $user->email,
                'contact' => $user->mobile,
            ],
        ]);
    }

    public function renewalOrder(Request $request): JsonResponse
    {
        $request->validate([
            'membership_plan' => 'required|in:Annual,LongTerm,Overseas',
            'membership_type' => 'required|in:Individual,Institutional',
        ]);

        $user = $request->user();
        $plan = $request->input('membership_plan');
        $type = $request->input('membership_type');

        $breakdown = $this->pricing->breakdown($type, $plan, MembershipPricingService::CONTEXT_RENEWAL);

        $order = $this->razorpay->createOrder(
            $breakdown['price'],
            'INR',
            'AINET-RENEW-' . $user->id . '-' . time(),
            [
                'user_id' => (string) $user->id,
                'plan'    => $plan,
                'type'    => $type,
                'context' => MembershipPricingService::CONTEXT_RENEWAL,
            ]
        );

        return $this->success('Renewal order created', 200, [
            'order'    => $order,
            'amount'   => $order['amount'] ?? $breakdown['price_in_paise'],
            'currency' => $order['currency'] ?? 'INR',
            'pricing'  => $breakdown,
            'customer' => [
                'name'    => $user->name,
                'email'   => $user->email,
                'contact' => $user->mobile,
            ],
        ]);
    }

    public function confirmRenewal(Request $request): JsonResponse
    {
        $request->validate([
            'razorpay_order_id'   => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature'  => 'required|string',
            'membership_plan'     => 'required|in:Annual,LongTerm,Overseas',
            'membership_type'     => 'required|in:Individual,Institutional',
        ]);

        $razorpayOrderId   = $request->input('razorpay_order_id');
        $razorpayPaymentId = $request->input('razorpay_payment_id');
        $razorpaySignature = $request->input('razorpay_signature');
        $plan              = $request->input('membership_plan');
        $type              = $request->input('membership_type');
        $paymentCaptured   = false;

        try {
            DB::beginTransaction();

            $user = User::lockForUpdate()->find($request->user()->id);

            if (!$user) {
                return $this->error('User not found.', 404);
            }

            if (!$this->razorpay->verifySignature($razorpayOrderId, $razorpayPaymentId, $razorpaySignature)) {
                return $this->error('Payment verification failed.', 422, [
                    'message' => 'The payment signature could not be verified.',
                ]);
            }

            if (User::where('payment_id', $razorpayPaymentId)->exists()) {
                return $this->error('Duplicate payment detected.', 409, [
                    'message' => 'This payment has already been processed.',
                ]);
            }

            $payment       = $this->razorpay->fetchPayment($razorpayPaymentId);
            $paymentStatus = $payment['status'] ?? null;
            $paymentAmount = (int) ($payment['amount'] ?? 0);

            if (($payment['order_id'] ?? null) !== $razorpayOrderId) {
                return $this->error('Payment verification failed.', 422, [
                    'message' => 'The payment is not linked with the provided order.',
                ]);
            }

            $order = $this->razorpay->fetchOrder($razorpayOrderId);
            $orderNotes = $order['notes'] ?? [];

            if ((string) ($orderNotes['user_id'] ?? '') !== (string) $user->id
                || (string) ($orderNotes['plan'] ?? '') !== $plan
                || (string) ($orderNotes['type'] ?? '') !== $type) {
                return $this->error('Payment verification failed.', 422, [
                    'message' => 'This order was not created for the selected plan on this account.',
                ]);
            }

            if ((int) ($order['amount'] ?? 0) !== $paymentAmount) {
                return $this->error('Payment verification failed.', 422, [
                    'message' => 'The amount paid does not match the order.',
                ]);
            }

            if ($paymentStatus === 'authorized') {
                $payment       = $this->razorpay->capturePayment($razorpayPaymentId, $paymentAmount);
                $paymentStatus = $payment['status'] ?? null;
                $paymentCaptured = $paymentStatus === 'captured';
            } else {
                $paymentCaptured = $paymentStatus === 'captured';
            }

            if (!$paymentCaptured) {
                return $this->error('Payment is not captured.', 422, [
                    'message' => 'The payment could not be captured. Please contact support.',
                    'payment_status' => $paymentStatus,
                ]);
            }

            $months    = $this->pricing->months($type, $plan);
            $paidAt    = Carbon::now();
            $expiresAt = $paidAt->copy()->addMonths($months)->endOfMonth();

            $user->membership_plan   = $plan;
            $user->membership_type   = $type;
            $user->type              = $type;
            $user->member_date       = $paidAt;
            $user->addMonths         = $months;
            $user->payment_id        = $razorpayPaymentId;
            $user->payment_status    = 'paid';
            $user->razorpay_order_id = $razorpayOrderId;
            $user->status            = 1;
            $user->save();

            DB::commit();

            $amount = $paymentAmount / 100;

            // Email to member
            try {
                Mail::site()->send(new MembershipRenewalUserMail(
                    $user, $plan, $type, $amount,
                    $razorpayPaymentId, $razorpayOrderId,
                    $paidAt, $months, $expiresAt
                ));
            } catch (\Exception $mailException) {
                Log::error('Failed to send renewal confirmation email to user: ' . $mailException->getMessage());
            }

            // Email to admin
            try {
                Mail::site()->send(new MembershipRenewalAdminMail(
                    $user, $plan, $type, $amount,
                    $razorpayPaymentId, $razorpayOrderId,
                    $paidAt, $months, $expiresAt
                ));
            } catch (\Exception $mailException) {
                Log::error('Failed to send renewal notification email to admin: ' . $mailException->getMessage());
            }

            return $this->success('Membership renewed successfully.', 200, [
                'user'    => $user,
                'payment' => [
                    'payment_id' => $razorpayPaymentId,
                    'order_id'   => $razorpayOrderId,
                    'status'     => $paymentStatus,
                    'amount'     => $paymentAmount,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            if ($paymentCaptured && $razorpayPaymentId) {
                try {
                    $this->razorpay->refundPayment($razorpayPaymentId);
                    Log::info('Auto-refunded renewal payment after confirmation failure', [
                        'payment_id' => $razorpayPaymentId,
                        'order_id'   => $razorpayOrderId,
                    ]);
                } catch (\Throwable $refundException) {
                    Log::error('Refund failed after renewal confirmation failure', [
                        'payment_id' => $razorpayPaymentId,
                        'order_id'   => $razorpayOrderId,
                        'exception'  => $refundException->getMessage(),
                    ]);
                }
            }

            Log::error('Renewal payment confirmation failed', [
                'order_id'   => $razorpayOrderId,
                'payment_id' => $razorpayPaymentId,
                'exception'  => $e->getMessage(),
                'line'       => $e->getLine(),
                'file'       => $e->getFile(),
            ]);

            return $this->error('Renewal confirmation failed: ' . $e->getMessage(), 500);
        }
    }

    public function confirmPayment(ConfirmMembershipPaymentRequest $request): JsonResponse
    {
        $razorpayOrderId = $request->get('razorpay_order_id');
        $razorpayPaymentId = $request->get('razorpay_payment_id');
        $razorpaySignature = $request->get('razorpay_signature');
        $paymentCaptured = false;

        try {
            DB::beginTransaction();

            $user = User::lockForUpdate()->find($request->get('user_id'));

            if (!$user) {
                return $this->error('User not found for payment confirmation.', 404);
            }

            if ($user->payment_status === 'paid') {
                return $this->error('Payment already confirmed for this membership.', 409);
            }

            if ($user->razorpay_order_id !== $razorpayOrderId) {
                return $this->error('Payment verification failed.', 422, [
                    'message' => 'The payment is not linked with this membership registration.',
                ]);
            }

            if (!$this->razorpay->verifySignature($razorpayOrderId, $razorpayPaymentId, $razorpaySignature)) {
                return $this->error('Payment verification failed.', 422, [
                    'message' => 'The payment signature could not be verified.',
                ]);
            }

            if (User::where('payment_id', $razorpayPaymentId)->exists()) {
                return $this->error('Duplicate payment detected.', 409, [
                    'message' => 'This payment has already been processed.',
                ]);
            }

            $payment = $this->razorpay->fetchPayment($razorpayPaymentId);
            $paymentStatus = $payment['status'] ?? null;
            $paymentAmount = (int) ($payment['amount'] ?? 0);

            if (($payment['order_id'] ?? null) !== $razorpayOrderId) {
                return $this->error('Payment verification failed.', 422, [
                    'message' => 'The payment is not linked with the provided order.',
                ]);
            }

            $order = $this->razorpay->fetchOrder($razorpayOrderId);
            $orderNotes = $order['notes'] ?? [];

            if ((string) ($orderNotes['user_id'] ?? '') !== (string) $user->id) {
                return $this->error('Payment verification failed.', 422, [
                    'message' => 'This order was not created for this account.',
                ]);
            }

            if ((int) ($order['amount'] ?? 0) !== $paymentAmount) {
                return $this->error('Payment verification failed.', 422, [
                    'message' => 'The amount paid does not match the order.',
                ]);
            }

            if ($paymentStatus === 'authorized') {
                $payment = $this->razorpay->capturePayment($razorpayPaymentId, $paymentAmount);
                $paymentStatus = $payment['status'] ?? null;
                $paymentCaptured = $paymentStatus === 'captured';
            } else {
                $paymentCaptured = $paymentStatus === 'captured';
            }

            if (!$paymentCaptured) {
                return $this->error('Payment is not captured.', 422, [
                    'message' => 'The payment could not be captured. Please contact support.',
                    'payment_status' => $paymentStatus,
                ]);
            }

            $user->payment_id = $razorpayPaymentId;
            $user->payment_status = 'paid';
            $user->razorpay_order_id = $razorpayOrderId;
            $user->save();

            DB::commit();

            return $this->success('Payment confirmed successfully.', 200, [
                'user' => $user,
                'payment' => [
                    'payment_id' => $razorpayPaymentId,
                    'order_id' => $razorpayOrderId,
                    'status' => $paymentStatus,
                    'amount' => $paymentAmount,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            if ($paymentCaptured && $razorpayPaymentId) {
                try {
                    $this->razorpay->refundPayment($razorpayPaymentId);
                    Log::info('Auto-refunded membership payment after confirmation failure', [
                        'payment_id' => $razorpayPaymentId,
                        'order_id' => $razorpayOrderId,
                    ]);
                } catch (\Throwable $refundException) {
                    Log::error('Refund failed after confirmation failure', [
                        'payment_id' => $razorpayPaymentId,
                        'order_id' => $razorpayOrderId,
                        'exception' => $refundException->getMessage(),
                    ]);
                }
            }

            Log::error('Payment confirmation failed', [
                'order_id' => $razorpayOrderId,
                'payment_id' => $razorpayPaymentId,
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return $this->error('Payment confirmation failed: ' . $e->getMessage(), 500, [
                'error' => $e->getMessage(),
                'payment_id' => $razorpayPaymentId,
                'order_id' => $razorpayOrderId,
            ]);
        }
    }
}

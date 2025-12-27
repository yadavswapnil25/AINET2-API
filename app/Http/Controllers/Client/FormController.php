<?php

namespace App\Http\Controllers\Client;

use App\Models\Drf;
use App\Models\Ppf;
use App\Models\User;
use App\Traits\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePpfRequest;
use App\Http\Requests\StoreDrfRequest;
use Illuminate\Support\Facades\DB;
use App\Services\RazorpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mails\DrfPaymentReceiptMail;
use App\Mails\DrfSubmissionConfirmationMail;
use App\Mails\PpfSubmissionConfirmationMail;

class FormController extends Controller
{
    use Response;

    public function __construct(protected RazorpayService $razorpay)
    {
    }

    public function storePpfs(StorePpfRequest $request){
        try {
            return DB::transaction(function () use ($request) {
                $ppf = new Ppf;

            $ppf->main_title = $request->main_title;
            $ppf->main_name = $request->main_name;
            $ppf->main_work = $request->main_work;
            $ppf->main_country_code = $request->presenter_main_country_code;
            $ppf->main_phone = $request->main_phone;
            $ppf->main_email = $request->presenter_main_email;
            $ppf->co1_title = $request->co1_title;
            $ppf->co1_name = $request->co1_name;
            $ppf->co1_work = $request->co1_work;
            $ppf->co1_country_code = $request->co1_country_code;
            $ppf->co1_phone = $request->co1_phone;
            $ppf->co1_email = $request->co1_email;

            $ppf->co2_title = $request->co2_title;
            $ppf->co2_name = $request->co2_name;
            $ppf->co2_work = $request->co2_work;
            $ppf->co2_country_code = $request->co2_country_code;
            $ppf->co2_phone = $request->co2_phone;
            $ppf->co2_email = $request->co2_email;

            $ppf->co3_title = $request->co3_title;
            $ppf->co3_name = $request->co3_name;
            $ppf->co3_work = $request->co3_work;
            $ppf->co3_country_code = $request->co3_country_code;
            $ppf->co3_phone = $request->co3_phone;
            $ppf->co3_email = $request->co3_email;

            $ppf->sub_theme = $request->pr_area;
            $ppf->sub_theme_other = $request->pr_area_specify;

            $ppf->pr_nature = $request->pr_nature;

            $ppf->pr_title = $request->pr_title;
            $ppf->pr_abstract = $request->pr_abstract;
            $ppf->pr1_bio = $request->presenter_bio;
            $ppf->pr2_bio = $request->co_presenter_1_bio;
            $ppf->pr3_bio = $request->co_presenter_2_bio;
            // Save remaining copresenters (pr3_bio from request) to pr4_bio in database
            // The frontend sends this field as 'pr3_bio', and we save it to 'pr4_bio' column
            $remainingCopresenters = trim($request->pr3_bio ?? '');
            $ppf->pr4_bio = !empty($remainingCopresenters) ? $remainingCopresenters : null;
            $ppf->save();

            // Send confirmation email
            try {
                Mail::send(new PpfSubmissionConfirmationMail($ppf));
            } catch (\Throwable $mailException) {
           
                // Continue even if email fails - the submission was successful
            }
 
                return $this->success('PPF submitted successfully', 201, [ 'id' => $ppf->id ]);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Transaction automatically rolls back on exception
            return $this->error('Database error occurred while saving PPF', 500, [ 
                'exception' => 'Database transaction failed',
                'error_code' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
        } catch (\Throwable $e) {
            // Transaction automatically rolls back on exception
            return $this->error('Unable to submit PPF', 500, [ 
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }


    public function storeDrfs(StoreDrfRequest $request){
        try {
            return DB::transaction(function () use ($request) {
                $drf = Drf::where('email', $request->email)
                ->where('conference_attendance', '9th_conference')
                    ->orderByDesc('created_at')
                    ->first();

                $isUpdate = (bool) $drf;

                if (!$drf) {
                    $drf = new Drf;
                }
 
                $drf->member = $request->member;
            $drf->you_are_register_as = $request->you_are_register_as;
            $drf->pre_title = $request->pre_title;
            $drf->name = $request->name;
            $drf->gender = $request->gender;
            $drf->age = $request->age;
            $drf->institution = $request->institution;
            $drf->address = $request->address;
            $drf->city = $request->city;
            $drf->pincode = $request->pincode;
            $drf->state = $request->state;
			$drf->country_code = $request->country_code;
            $drf->phone_no = $request->phone_no;
            $drf->email = $request->email;

            // Handle areas array - ensure it's saved properly
            if ($request->has('areas') && is_array($request->areas) && count($request->areas) > 0) {
                $areas = array_filter(array_map('trim', $request->areas), function($value) {
                    return $value !== '' && $value !== null;
                }); // Remove empty values and trim
                $drf->areas = !empty($areas) ? implode(',', $areas) : null;
            } else {
                $drf->areas = null;
            }

            // Handle other field - convert empty string to null, otherwise trim and save
            if ($request->has('other')) {
                $otherValue = trim($request->other ?? '');
                $drf->other = $otherValue !== '' ? $otherValue : null;
            } else {
                $drf->other = null;
            }

            // Handle areas_of_interest - convert empty string to null
            if ($request->has('areas_of_interest')) {
                $interestValue = trim($request->areas_of_interest ?? '');
                $drf->areas_of_interest = $interestValue !== '' ? $interestValue : null;
            } else {
                $drf->areas_of_interest = null;
            }

            $drf->experience = $request->experience;

            // Link to user if member and membership_id is provided
            if ($request->member === 'Yes' && !empty($request->membership_id)) {
                $membershipId = trim($request->membership_id);
                $user = User::whereRaw("TRIM(m_id) = ?", [$membershipId])
                    ->orWhere('m_id', 'LIKE', $membershipId . '%')
                    ->orWhere('m_id', $membershipId)
                    ->first();
                
                if ($user) {
                    $drf->user_id = $user->id;
                }
            } else {
                $drf->user_id = null;
            }

            if($request->conference === "Yes"){
                $drf->conference = 'YES';
                $drf->types = implode(',', $request->types ?? []);
            }else{
                $drf->conference = 'NO';
                $drf->types = null;
            }

            $drf->conference_attendance = '9th_conference';

            // Handle sponsor_id - preserve if already set, or set from request if provided
            if ($request->has('sponsor_id') && !empty($request->sponsor_id)) {
                $drf->sponsor_id = $request->sponsor_id;
            }
            // If sponsor_id is already set (e.g., from bulk import), it will be preserved automatically
            // This ensures sponsored users keep their sponsor_id when updating their registration

            // For sponsored users, mark as paid immediately
            if ($drf->sponsor_id) {
                $drf->payment_status = 'paid';
                $drf->payment_id = 'SPONSORED-' . $drf->sponsor_id . '-' . now()->timestamp;
                $drf->razorpay_order_id = null;
            } elseif ($drf->payment_status !== 'paid') {
                // For non-sponsored users, set to pending if not already paid
                $drf->payment_status = 'pending';
                $drf->payment_id = null;
                $drf->razorpay_order_id = null;
            }
 
                $drf->save();
                
                // Refresh the model to ensure we have the latest data
                $drf->refresh();
 
                // Send confirmation email after form submission
                try {
                    // Use the smtp mailer explicitly to ensure emails are sent
                    Mail::mailer('smtp')->send(new DrfSubmissionConfirmationMail($drf));
                    Log::info('DRF submission confirmation email sent', [
                        'drf_id' => $drf->id,
                        'email' => $drf->email,
                        'is_update' => $isUpdate,
                        'mailer' => config('mail.default'),
                    ]);
                } catch (\Throwable $mailException) {
                    Log::error('Failed to send DRF submission confirmation email', [
                        'drf_id' => $drf->id,
                        'email' => $drf->email,
                        'error' => $mailException->getMessage(),
                    ]);
                    report($mailException);
                    // Continue even if email fails
                }

                $message = $isUpdate ? 'DRF updated successfully' : 'DRF submitted successfully';

                return $this->success($message, $isUpdate ? 200 : 201, [ 'id' => $drf->id, 'updated' => $isUpdate ]);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Transaction automatically rolls back on exception
            return $this->error('Database error occurred while saving DRF', 500, [ 
                'exception' => 'Database transaction failed',
                'error_code' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
        } catch (\Throwable $e) {
            // Transaction automatically rolls back on exception
            return $this->error('Unable to submit DRF', 500, [ 
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    public function getDrfByEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $drf = Drf::where('email', $request->email)
        ->where('conference_attendance', '9th_conference')
            ->orderByDesc('created_at')
            ->first();

        if (!$drf) {
            return $this->success('DRF record not found', 200, [
                'exists' => false
            ]);
        }

        $areas = $drf->areas ? array_values(array_filter(array_map('trim', explode(',', $drf->areas)))) : [];

        return $this->success('DRF record found', 200, [
            'exists' => true,
            'drf' => [
                'member' => $drf->member,
                'delegate_type' => $drf->you_are_register_as,
                'title' => $drf->pre_title,
                'full_name' => $drf->name,
                'gender' => $drf->gender,
                'age' => $drf->age,
                'institution_address' => $drf->institution,
                'correspondence_address' => $drf->address,
                'city' => $drf->city,
                'pincode' => $drf->pincode,
                'state' => $drf->state,
                'country_code' => $drf->country_code,
                'mobile_no' => $drf->phone_no,
                'email' => $drf->email,
                'area_of_work' => $areas,
                'areas_of_interest' => $drf->areas_of_interest,
                'other_work_area' => $drf->other,
                'teaching_experience' => $drf->experience,
                'is_presenting' => '',
                'presentation_type' => [],
            ],
        ]);
    }

    public function createDrfOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'drf_id' => 'required|integer|exists:drves,id',
            'membership_id' => 'nullable|string',
        ]);

        $drf = Drf::findOrFail($validated['drf_id']);
        
        // Check if user is sponsored (has sponsor_id) FIRST - skip payment check for sponsored users
        // Allow sponsored users to proceed even if already marked as paid
        if ($drf->sponsor_id) {
            // If already paid and sponsored, just return success (no need to send email again)
            if ($drf->payment_status === 'paid') {
                return response()->json([
                    'status' => true,
                    'message' => 'Registration confirmed. Payment not required for sponsored participants.',
                    'data' => [
                        'sponsored' => true,
                        'sponsor_id' => $drf->sponsor_id,
                        'payment_required' => false,
                        'amount' => 0,
                        'currency' => 'INR',
                        'original_amount' => 0,
                        'discounted_amount' => 0,
                        'amount_in_rupees' => 0,
                    ],
                ]);
            }
            
            // If sponsored but not yet marked as paid, mark as paid and send email
            // Mark as paid without payment
            $drf->payment_status = 'paid';
            $paymentId = 'SPONSORED-' . $drf->sponsor_id . '-' . now()->timestamp;
            $drf->payment_id = $paymentId;
            $drf->razorpay_order_id = null;
            $drf->save();

            // Send confirmation email to sponsored user
            try {
                $invoiceNumber = sprintf('AINET-DRF26-%06d', $drf->id);
                $paidAt = now();
                $amountRupees = 0;
                $originalAmountRupees = 0;
                $discountAmount = 0;
                $discountPercentage = 0;

                // Generate PDF receipt for sponsored user
                $pdf = Pdf::loadView('pdf.drf-invoice', [
                    'drf' => $drf,
                    'invoiceNumber' => $invoiceNumber,
                    'paidAt' => $paidAt,
                    'paymentId' => $paymentId,
                    'orderId' => 'SPONSORED',
                    'amount' => $amountRupees,
                    'originalAmount' => $originalAmountRupees,
                    'discountAmount' => $discountAmount,
                    'discountPercentage' => $discountPercentage,
                ]);

                $pdfData = $pdf->output();

                // Use the smtp mailer explicitly to ensure emails are sent
                Mail::mailer('smtp')->send(new DrfPaymentReceiptMail(
                    $drf,
                    $invoiceNumber,
                    $amountRupees,
                    $paidAt,
                    $paymentId,
                    'SPONSORED',
                    $pdfData,
                    $originalAmountRupees,
                    $discountAmount,
                    $discountPercentage
                ));
                
                Log::info('Sponsored user email sent successfully', [
                    'drf_id' => $drf->id,
                    'email' => $drf->email,
                    'sponsor_id' => $drf->sponsor_id,
                    'mailer' => config('mail.default'),
                ]);
            } catch (\Throwable $mailException) {
                Log::error('Failed to send email to sponsored user', [
                    'drf_id' => $drf->id,
                    'email' => $drf->email,
                    'sponsor_id' => $drf->sponsor_id,
                    'error' => $mailException->getMessage(),
                    'trace' => $mailException->getTraceAsString(),
                ]);
                report($mailException);
                // Continue even if email fails
            }

            return response()->json([
                'status' => true,
                'message' => 'Registration confirmed. Payment not required for sponsored participants.',
                'data' => [
                    'sponsored' => true,
                    'sponsor_id' => $drf->sponsor_id,
                    'payment_required' => false,
                    'amount' => 0,
                    'currency' => 'INR',
                    'original_amount' => 0,
                    'discounted_amount' => 0,
                    'amount_in_rupees' => 0,
                ],
            ]);
        }

        // Check for membership discount and link user_id
        $discountPercentage = 0;
        $membershipValid = false;
        $originalAmount = 0;
        
        if (!empty($validated['membership_id'])) {
            $membershipId = trim($validated['membership_id']);
            $user = User::whereRaw("TRIM(m_id) = ?", [$membershipId])
                ->orWhere('m_id', 'LIKE', $membershipId . '%')
                ->orWhere('m_id', $membershipId)
                ->first();

            if ($user) {
                // Link user_id to DRF if not already linked
                if (!$drf->user_id) {
                    $drf->user_id = $user->id;
                    $drf->save();
                }

                // Use member_date only; if missing, membership not valid
                if (!empty($user->member_date)) {
                    $memberDate = $user->member_date;
                    $addMonths = $user->addMonths ?? 12;
                    // Calculate expiry: add months, set to last day of that month with original time
                    $expiryDate = $memberDate->copy()->addMonths($addMonths);
                    $lastDayOfMonth = $expiryDate->copy()->endOfMonth()->day;
                    $expiryDate = $expiryDate->setDate($expiryDate->year, $expiryDate->month, $lastDayOfMonth)
                        ->setTime($memberDate->hour, $memberDate->minute, $memberDate->second);
                    
                    if (now()->lessThanOrEqualTo($expiryDate)) {
                        $membershipValid = true;
                        $discountPercentage = 10;
                    }
                }
            }
        }

        // Calculate original amount first (without discount)
        $originalAmount = $this->calculateDrfAmount($drf, 0);
        // Calculate final amount with discount
        $amountRupees = $this->calculateDrfAmount($drf, $discountPercentage);
  
        
        if ($amountRupees <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to determine delegate fee amount.',
            ], 422);
        }

        try {
            $order = $this->razorpay->createOrder(
                $amountRupees, // This should be the discounted amount (e.g., 1080 for 1200 - 10%)
                'INR',
                'DRF-' . $drf->id . '-' . now()->timestamp,
                [
                    'drf_id' => (string) $drf->id,
                    'email' => $drf->email,
                    'membership_id' => $validated['membership_id'] ?? null,
                    'discount_applied' => $membershipValid ? '10%' : '0%',
                    'original_amount' => (string) $originalAmount,
                    'discounted_amount' => (string) $amountRupees,
                ]
            );

            $drf->razorpay_order_id = $order['id'] ?? null;
            $drf->payment_status = 'pending';
            $drf->save();

            // Get the amount from Razorpay order response (already in paise)
            // The order was created with $amountRupees (discounted amount), so this should match
            $razorpayOrderAmount = (int) ($order['amount'] ?? ($amountRupees * 100));
    
            
            return response()->json([
                'status' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'order' => $order,
                    'amount' => $razorpayOrderAmount, // Amount in paise (already discounted)
                    'currency' => $order['currency'] ?? 'INR',
                    'key' => env('RAZORPAY_KEY_ID', null),
                    'discount_applied' => $membershipValid,
                    'discount_percentage' => $discountPercentage,
                    'original_amount' => $originalAmount,
                    'discounted_amount' => $amountRupees, // Final amount to pay in rupees
                    'amount_in_rupees' => $amountRupees, // For clarity
                ],
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function confirmDrfPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'drf_id' => 'required|integer|exists:drves,id',
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $drf = Drf::findOrFail($validated['drf_id']);

        if ($drf->payment_status === 'paid') {
            return response()->json([
                'status' => true,
                'message' => 'Payment already confirmed.',
            ]);
        }

        if ($drf->razorpay_order_id !== $validated['razorpay_order_id']) {
            return response()->json([
                'status' => false,
                'message' => 'The payment is not linked with this registration.',
            ], 422);
        }

        $isSignatureValid = $this->razorpay->verifySignature(
            $validated['razorpay_order_id'],
            $validated['razorpay_payment_id'],
            $validated['razorpay_signature']
        );

        if (!$isSignatureValid) {
            return response()->json([
                'status' => false,
                'message' => 'The payment signature could not be verified.',
            ], 422);
        }

        try {
            $payment = $this->razorpay->fetchPayment($validated['razorpay_payment_id']);
        } catch (RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }

        $paymentStatus = $payment['status'] ?? null;

        if (($payment['order_id'] ?? null) !== $validated['razorpay_order_id']) {
            return response()->json([
                'status' => false,
                'message' => 'The payment is not linked with this registration order.',
            ], 422);
        }

        // Get actual payment amount from Razorpay (in paise)
        $actualPaymentAmountPaise = (int) ($payment['amount'] ?? 0);
        $actualPaymentAmountRupees = $actualPaymentAmountPaise / 100;

        // Calculate original amount (without discount)
        $originalAmountRupees = $this->calculateDrfAmount($drf, 0);
        
        // Check if discount was applied
        $discountAmount = 0;
        $discountPercentage = 0;
        if ($actualPaymentAmountRupees < $originalAmountRupees) {
            $discountAmount = $originalAmountRupees - $actualPaymentAmountRupees;
            $discountPercentage = round(($discountAmount / $originalAmountRupees) * 100, 2);
        }

        if (!in_array($paymentStatus, ['captured', 'authorized'], true)) {
            return response()->json([
                'status' => false,
                'message' => 'The payment could not be captured. Please contact support.',
                'payment_status' => $paymentStatus,
            ], 422);
        }

        if ($paymentStatus === 'authorized') {
            try {
                // Use actual payment amount for capture
                $payment = $this->razorpay->capturePayment($validated['razorpay_payment_id'], $actualPaymentAmountPaise);
                $paymentStatus = $payment['status'] ?? null;
            } catch (RuntimeException $e) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unable to capture payment: ' . $e->getMessage(),
                ], 500);
            }

            if ($paymentStatus !== 'captured') {
                return response()->json([
                    'status' => false,
                    'message' => 'The payment could not be captured. Please contact support.',
                    'payment_status' => $paymentStatus,
                ], 422);
            }
        }

        $drf->payment_status = 'paid';
        $drf->payment_id = $validated['razorpay_payment_id'];
        $drf->save();

        $paidAt = now();
        $invoiceNumber = sprintf('AINET-DRF26-%06d', $drf->id);
        
        // Use actual payment amount (discounted amount)
        $amountRupees = $actualPaymentAmountRupees;

        try {
            $pdf = Pdf::loadView('pdf.drf-invoice', [
                'drf' => $drf,
                'invoiceNumber' => $invoiceNumber,
                'paidAt' => $paidAt,
                'paymentId' => $validated['razorpay_payment_id'],
                'orderId' => $validated['razorpay_order_id'],
                'amount' => $amountRupees,
                'originalAmount' => $originalAmountRupees,
                'discountAmount' => $discountAmount,
                'discountPercentage' => $discountPercentage,
            ]);

            $pdfData = $pdf->output();

            // Use the smtp mailer explicitly to ensure emails are sent
            Mail::mailer('smtp')->send(new DrfPaymentReceiptMail(
                $drf,
                $invoiceNumber,
                $amountRupees,
                $paidAt,
                $validated['razorpay_payment_id'],
                $validated['razorpay_order_id'],
                $pdfData,
                $originalAmountRupees,
                $discountAmount,
                $discountPercentage
            ));
            
            Log::info('DRF payment confirmation email sent successfully', [
                'drf_id' => $drf->id,
                'email' => $drf->email,
                'payment_id' => $validated['razorpay_payment_id'],
                'mailer' => config('mail.default'),
            ]);
        } catch (\Throwable $mailException) {
            Log::error('Failed to send DRF payment confirmation email', [
                'drf_id' => $drf->id,
                'email' => $drf->email,
                'payment_id' => $validated['razorpay_payment_id'] ?? null,
                'error' => $mailException->getMessage(),
                'trace' => $mailException->getTraceAsString(),
            ]);
            report($mailException);
        }

        return response()->json([
            'status' => true,
            'message' => 'Payment confirmed successfully.',
        ]);
    }

    protected function calculateDrfAmount(Drf $drf, float $discountPercentage = 0): float
    {
        $delegateType = trim((string) $drf->you_are_register_as);
        $now = now();
        $cutoff = now()->setDate(2025, 12, 30)->endOfDay();
        $isEarlyBird = $now->lessThanOrEqualTo($cutoff);

        $delegateTypeLower = strtolower($delegateType);

        $baseAmount = 0.0;

        if (str_contains($delegateTypeLower, 'overseas')) {
            $baseAmount = 5000.0;
        } elseif (str_contains($delegateTypeLower, 'research') || str_contains($delegateTypeLower, 'student')) {
            $baseAmount = $isEarlyBird ? 1200.0 : 2000.0;
        } elseif (str_contains($delegateTypeLower, 'other')) {
            $baseAmount = $isEarlyBird ? 2500.0 : 3500.0;
        } else {
            $baseAmount = $isEarlyBird ? 2500.0 : 3500.0;
        }

        // Apply discount if applicable
        if ($discountPercentage > 0) {
            $discountAmount = ($baseAmount * $discountPercentage) / 100;
            return round($baseAmount - $discountAmount, 2);
        }

        return $baseAmount;
    }

    /**
     * Check if user exists by membership ID
     */
    public function checkUserExists(Request $request)
    {
        try {
            $request->validate([
                'membership_id' => 'required|string'
            ]);

            $membershipId = trim($request->membership_id);
            
            $user = User::whereRaw("TRIM(m_id) = ?", [$membershipId])->first();
            
            if (!$user) {
                $user = User::where('m_id', 'LIKE', $membershipId . '%')->first();
            }
            
            if (!$user) {
                $user = User::where('m_id', $request->membership_id)->first();
            }

            if ($user) {
                return $this->success('User found', 200, [
                    'exists' => true,
                    'user' => $user,
                ]);
            }

            // Let's see what we have in the database around that ID
            $nearbyUsers = User::whereRaw("TRIM(m_id) LIKE ?", ['%' . $membershipId . '%'])
                ->orWhereRaw("m_id LIKE ?", ['%' . $membershipId . '%'])
                ->limit(5)
                ->get(['id', 'm_id', 'name']);

            return $this->success('User not found', 200, [
                'exists' => false,
                'message' => 'No user found with this membership ID',
                'debug' => [
                    'searched_for' => $membershipId,
                    'original_input' => $request->membership_id,
                    'nearby_users' => $nearbyUsers->toArray()
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Throwable $e) {
            return $this->error('Unable to check user', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Validate membership ID and check if discount is applicable
     */
    public function validateMembershipForDiscount(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'membership_id' => 'required|string'
            ]);

            $membershipId = trim($request->membership_id);
            
            // Find user by membership ID
            $user = User::whereRaw("TRIM(m_id) = ?", [$membershipId])
                ->orWhere('m_id', 'LIKE', $membershipId . '%')
                ->orWhere('m_id', $membershipId)
                ->first();

            if (!$user) {
                return $this->success('Membership not found', 200, [
                    'valid' => false,
                    'discount_applicable' => false,
                    'message' => 'Membership ID not found',
                ]);
            }

            // Check if payment is completed
            // if ($user->payment_status !== 'paid') {
            //     return $this->success('Membership payment pending', 200, [
            //         'valid' => false,
            //         'discount_applicable' => false,
            //         'message' => 'Membership payment is pending',
            //     ]);
            // }

            // Calculate expiry date: member_date + addMonths (must have member_date)
            if (empty($user->member_date)) {
                return $this->success('Membership date not set', 200, [
                    'valid' => false,
                    'discount_applicable' => false,
                    'message' => 'Membership start date is missing',
                ]);
            }
            $memberDate = $user->member_date;
            $addMonths = $user->addMonths ?? 12; // Default to 12 months if not set
            
            // Calculate expiry date: add months and set to last day of that month with original time
            $expiryDate = $memberDate->copy()->addMonths($addMonths);
            // Get the last day of the expiry month
            $lastDayOfMonth = $expiryDate->copy()->endOfMonth()->day;
            // Set to last day of month but keep original time
            $expiryDate = $expiryDate->setDate($expiryDate->year, $expiryDate->month, $lastDayOfMonth)
                ->setTime($memberDate->hour, $memberDate->minute, $memberDate->second);
            
            // Check if membership is still valid
            $isValid = now()->lessThanOrEqualTo($expiryDate);
            
            if (!$isValid) {
                return $this->success('Membership expired', 200, [
                    'valid' => false,
                    'discount_applicable' => false,
                    'message' => 'Your membership has expired',
                    'expiry_date' => $expiryDate->format('Y-m-d H:i:s'),
                ]);
            }

            // Membership is valid - discount applicable
            return $this->success('Membership valid', 200, [
                'valid' => true,
                'discount_applicable' => true,
                'discount_percentage' => 10,
                'message' => '10% discount will be applied to your registration fee',
                'expiry_date' => $expiryDate->format('Y-m-d H:i:s'),
                'member_name' => $user->name,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Throwable $e) {
            return $this->error('Unable to validate membership', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }
}

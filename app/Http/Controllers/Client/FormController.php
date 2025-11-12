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
use App\Mails\DrfPaymentReceiptMail;

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
            $ppf->pr4_bio = $request->pr3_bio;
            $ppf->save();

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

            if(!empty($request->areas)){
                $areas = $request->areas;
                $drf->areas = implode(',', $areas);
            } else {
                $drf->areas = null;
            }

            $drf->other = $request->other;
            $drf->areas_of_interest = $request->areas_of_interest;

            $drf->experience = $request->experience;

            if ($drf->payment_status !== 'paid') {
                $drf->payment_status = 'pending';
                $drf->payment_id = null;
                $drf->razorpay_order_id = null;
            }

            if($request->conference === "Yes"){
                $drf->conference = 'YES';
                $drf->types = implode(',', $request->types ?? []);
            }else{
                $drf->conference = 'NO';
                $drf->types = null;
            }

            $drf->conference_attendance = '9th_conference';
 
                $drf->save();
 
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
        ]);

        $drf = Drf::findOrFail($validated['drf_id']);
        
        if ($drf->payment_status === 'paid' && $drf->conference_attendance === '9th_conference') {
            return response()->json([
                'status' => false,
                'message' => 'Payment has already been completed for this registration.',
            ], 409);
        }

        $amountRupees = $this->calculateDrfAmount($drf);
        if ($amountRupees <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to determine delegate fee amount.',
            ], 422);
        }

        try {
            $order = $this->razorpay->createOrder(
                $amountRupees,
                'INR',
                'DRF-' . $drf->id . '-' . now()->timestamp,
                [
                    'drf_id' => (string) $drf->id,
                    'email' => $drf->email,
                ]
            );

            $drf->razorpay_order_id = $order['id'] ?? null;
            $drf->payment_status = 'pending';
            $drf->save();

            return response()->json([
                'status' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'order' => $order,
                    'amount' => (int) ($order['amount'] ?? ($amountRupees * 100)),
                    'currency' => $order['currency'] ?? 'INR',
                    'key' => env('RAZORPAY_KEY_ID', null),
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

        $amountPaise = (int) round($this->calculateDrfAmount($drf) * 100);

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

        if (!in_array($paymentStatus, ['captured', 'authorized'], true)) {
            return response()->json([
                'status' => false,
                'message' => 'The payment could not be captured. Please contact support.',
                'payment_status' => $paymentStatus,
            ], 422);
        }

        if ($paymentStatus === 'authorized') {
            try {
                $payment = $this->razorpay->capturePayment($validated['razorpay_payment_id'], $amountPaise);
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
        $invoiceNumber = sprintf('AINET-DRF-%06d', $drf->id);
        $amountRupees = $this->calculateDrfAmount($drf);

        try {
            $pdf = Pdf::loadView('pdf.drf-invoice', [
                'drf' => $drf,
                'invoiceNumber' => $invoiceNumber,
                'paidAt' => $paidAt,
                'paymentId' => $validated['razorpay_payment_id'],
                'orderId' => $validated['razorpay_order_id'],
                'amount' => $amountRupees,
            ]);

            $pdfData = $pdf->output();

            Mail::send(new DrfPaymentReceiptMail(
                $drf,
                $invoiceNumber,
                $amountRupees,
                $paidAt,
                $validated['razorpay_payment_id'],
                $validated['razorpay_order_id'],
                $pdfData
            ));
        } catch (\Throwable $mailException) {
            report($mailException);
        }

        return response()->json([
            'status' => true,
            'message' => 'Payment confirmed successfully.',
        ]);
    }

    protected function calculateDrfAmount(Drf $drf): float
    {
        $delegateType = trim((string) $drf->you_are_register_as);
        $now = now();
        $cutoff = now()->setDate(2025, 12, 25)->endOfDay();
        $isEarlyBird = $now->lessThanOrEqualTo($cutoff);

        $delegateTypeLower = strtolower($delegateType);

        if (str_contains($delegateTypeLower, 'overseas')) {
            return 5000.0;
        }

        if (str_contains($delegateTypeLower, 'research') || str_contains($delegateTypeLower, 'student')) {
            return $isEarlyBird ? 1200.0 : 2000.0;
        }

        if (str_contains($delegateTypeLower, 'other')) {
            return $isEarlyBird ? 2500.0 : 3500.0;
        }

        return $isEarlyBird ? 2500.0 : 3500.0;
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
}

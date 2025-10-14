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

class FormController extends Controller
{
    use Response;

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
                $drf = new Drf;

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
                if(in_array("Other",$areas)){
                    array_push($areas,$request->other);
                }
                $drf->areas = implode(',', $areas);
            }

            $drf->experience = $request->experience;

            if($request->conference === "Yes"){
                $drf->conference = implode(',', $request->types ?? []);
            }else{
                $drf->conference = $request->conference;
            }
            
                $drf->save();

                return $this->success('DRF submitted successfully', 201, [ 'id' => $drf->id ]);
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

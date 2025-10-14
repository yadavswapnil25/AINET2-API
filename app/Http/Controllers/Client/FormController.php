<?php

namespace App\Http\Controllers\Client;

use App\Models\Drf;
use App\Models\Ppf;
use App\Traits\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePpfRequest;
use App\Http\Requests\StoreDrfRequest;

class FormController extends Controller
{
    use Response;

    public function storePpfs(StorePpfRequest $request){
        try {
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

            if ($request->expectsJson()) {
                return $this->success('PPF submitted successfully', 201, [ 'id' => $ppf->id ]);
            }

           return redirect()->route('form.ppf')->with('status','success');
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return $this->error('Unable to submit PPF', 500, [ 'exception' => $e->getMessage() ]);
            }
            return $this->error('Unable to submit PPF', 500, [ 'exception' => $e->getMessage() ]);
        }
    }


    public function storeDrfs(StoreDrfRequest $request){
        try {
            $drf = new Drf;

            $drf->is_member = $request->member;
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

            if ($request->expectsJson()) {
                return $this->success('DRF submitted successfully', 201, [ 'id' => $drf->id ]);
            }
            return redirect('ainet2020drf')->with('status','success');
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return $this->error('Unable to submit DRF', 500, [ 'exception' => $e->getMessage() ]);
            }
            return $this->error('Unable to submit DRF', 500, [ 'exception' => $e->getMessage() ]);
        }
    }
}

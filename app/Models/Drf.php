<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Drf extends Model
{
    protected $table ='drves';
    protected $fillable = [
        'member', 'name', 'gender', 'age', 'institution',
        'address', 'city', 'pincode', 'state', 'country_code', 'phone_no', 'email',
        'areas', 'experience', 'conference', 'types','you_are_register_as','pre_title'
    ];
 
}

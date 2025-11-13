<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Drf extends Model
{
    protected $table ='drves';
    protected $fillable = [
        'member', 'name', 'gender', 'age', 'institution',
        'address', 'city', 'pincode', 'state', 'country_code', 'phone_no', 'email',
        'areas', 'areas_of_interest', 'other', 'experience', 'conference', 'conference_attendance', 'types','you_are_register_as','pre_title',
        'payment_status', 'payment_id', 'razorpay_order_id', 'user_id'
    ];

    /**
     * Get the user that owns this DRF registration.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
 
}

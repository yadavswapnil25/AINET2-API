<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens,HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ref',
        'first_name',
        'last_name',
        'email',
        'password',
        'gender',
        'name',
        'mobile',
        'whatsapp_no',
        'dob',
        'address',
        'state',
        'district',
        'teaching_exp',
        'qualification',
        'area_of_work',
        'membership_type',
        'membership_plan',
        'pin',
        'image',
        'name_association',
        'expectation',
        'has_newsletter',
        'title',
        'address_institution',
        'name_institution',
        'type_institution',
        'other_institution',
        'contact_person',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Add this accessor to always return the full image URL
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }
}

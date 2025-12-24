<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $fillable = [
        'drf_id',
        'rating',
        'comment',
        'email',
        'name',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * Get the DRF registration associated with this feedback.
     */
    public function drf()
    {
        return $this->belongsTo(Drf::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'location',
        'event_date',
        'event_date_end',
        'description',
        'link_url',
        'event_type',
        'is_active',
        'sort_order',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'event_date' => 'date',
        'event_date_end' => 'date',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
}


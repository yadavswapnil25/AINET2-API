<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'location',
        'publisher_name',
        'publisher_logo_path',
        'conference_name',
        'title',
        'summary',
        'has_video',
        'view_count',
        'link_url',
        'published_date',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'has_video' => 'boolean',
        'view_count' => 'integer',
        'published_date' => 'date',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}


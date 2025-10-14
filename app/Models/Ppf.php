<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ppf extends Model
{
    protected $table ='ppfs';
    protected $fillable = [
        'main_title',
        'main_name',
        'main_work',
        'main_phone',
        'main_country_code',
        'main_email',
        'co1_title', 'co1_name', 'co1_work', 'co1_country_code', 'co1_phone', 'co1_email',
        'co2_title', 'co2_name', 'co2_work', 'co2_country_code', 'co2_phone', 'co2_email',
        'co3_title', 'co3_name', 'co3_work', 'co3_country_code', 'co3_phone', 'co3_email',
        'sub_theme', 'sub_theme_other',
        'pr_nature', 'pr_title', 'pr_abstract',
        'pr1_bio', 'pr2_bio', 'pr3_bio', 'pr4_bio',
    ];
}

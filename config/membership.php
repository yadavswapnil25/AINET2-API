<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Membership Plan Catalogue
    |--------------------------------------------------------------------------
    |
    | Base (undiscounted) price in INR and the membership duration in months
    | for every membership type / plan combination. This is the single source
    | of truth for membership pricing - the website reads it through the
    | public `GET /membership/pricing` endpoint rather than hardcoding amounts.
    |
    */

    'plans' => [
        'Individual' => [
            'Annual'   => ['price' => 500.0,  'months' => 12],
            'LongTerm' => ['price' => 1200.0, 'months' => 36],
            'Overseas' => ['price' => 1725.0, 'months' => 12],
        ],
        'Institutional' => [
            'Annual'   => ['price' => 1000.0, 'months' => 12],
            'LongTerm' => ['price' => 2500.0, 'months' => 36],
            'Overseas' => ['price' => 5000.0, 'months' => 12],
        ],
    ],

    'default_type' => 'Individual',
    'default_plan' => 'Annual',

    /*
    |--------------------------------------------------------------------------
    | Promotional Discount
    |--------------------------------------------------------------------------
    |
    | A time limited discount applied on top of the base prices above. New
    | signups and renewals get different rates. Once `ends_at` passes the
    | promotion switches itself off and base prices apply again - no code
    | change or deploy is needed on the cut-off date.
    |
    | `ends_at` is inclusive and is interpreted in `timezone`, so the default
    | below means "up to and including 12 September 2026, 23:59:59 IST".
    |
    */

    'promo' => [
        'enabled'  => env('MEMBERSHIP_PROMO_ENABLED', true),
        'label'    => env('MEMBERSHIP_PROMO_LABEL', 'Limited period offer'),
        'ends_at'  => env('MEMBERSHIP_PROMO_ENDS_AT', '2026-09-12 23:59:59'),
        'timezone' => env('MEMBERSHIP_PROMO_TIMEZONE', 'Asia/Kolkata'),

        'discounts' => [
            'new'     => (float) env('MEMBERSHIP_PROMO_NEW_DISCOUNT', 50),
            'renewal' => (float) env('MEMBERSHIP_PROMO_RENEWAL_DISCOUNT', 25),
        ],
    ],
];

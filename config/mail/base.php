<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address & Other Addreses
    |--------------------------------------------------------------------------
    |
    | You may wish for all e-mails sent by your application to be sent from
    | the same address. Here, you may specify a name and address that is
    | used globally for all e-mails that are sent by your application.
    | Addresses to whom some special emails are to be sent are defined here too.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'theainet@gmail.com'),
        'name' => env('MAIL_FROM_NAME', 'AINET'),
    ],


];

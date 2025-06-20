<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;

use Exception;
use App\Traits\Response;
use App\Traits\SiteTrait;
use Illuminate\Queue\SerializesModels;
use App\Modules\Setting\Models\Setting\Site;
use Illuminate\Support\Facades\Mail as ParentMail;

class Mail extends ParentMail
{
    use  Response,
        Queueable,
        SerializesModels;

    public $site;

    /**
     * Get the site from which the email should be sent.
     *
     * @param  Site  $site
     * @return \Illuminate\Contracts\Mail\Mailer
     */
    public static function site(): \Illuminate\Contracts\Mail\Mailer
    {
        return ParentMail::mailer();
    }
}

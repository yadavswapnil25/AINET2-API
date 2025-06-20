<?php

namespace App\Http\Helpers;

use App\Traits\Response;

class MailHelper
{
    use Response;

    public $site;

    /**
     * @var string
     */
    public string $logo;

    /**
     * @var array
     */
    public array $footerTitles;

    public string $copyrightText;

    public function __construct()
    {

        $this->setLogo();
        $this->setFooterTitles();
        $this->setCopyright();
    }

    public function portalLink($route): string
    {
        
        return "https://theainet.net/";
    }
    /**
     * Get the mail name
     * 
     * @param  string  $address
     * @return string
     */
    public function name(string $address = 'from'): string
    {
        return config('mail.base.from.name');
    }

    /**
     * Get the mail address
     * 
     * @param  string  $address
     * @return string
     */
    public function address(string $address = 'from'): string
    {
        return config('mail.base.from.address');
    }


    /**
     * get a user detail
     * @param $role
     * @param bool $all
     * @param null $emails
     * @return mixed
     */
    private function getUsers($role, bool $all = false, $emails = null): mixed
    {
        $role = $this->site->roles()->where('name', $role)->first();
        $users = $role->users();

        if ($all) {
            if ($emails) {
                $users = $users->whereIn('email', $emails);
            }
        } else {
            $users = $users->limit(1);
        }

        return $users->select(['email', 'first_name', 'last_name'])->get();
    }

    /**
     * set the mail logo
     * @return void
     */
    private function setLogo(): void
    {
        $this->logo = "https://theainet.net/logo.svg";
    }

    /**
     * set the mail footer
     * @return void
     */
    private function setFooterTitles(): void
    {
        $url = "https://theainet.net";
    }

    /**
     * @return void
     */
    private function setCopyright(): void
    {
        $url = "https://theainet.net";
        $name = "AINET";

        $this->copyrightText = "ⓒ All rights reserved <a href='$url/events-timeline' target='_blank' style='font-weight: bold;'
                class='text__primary'>$name Events</a>.We appreciate you!";
    }
}

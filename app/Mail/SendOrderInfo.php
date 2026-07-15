<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;


class SendOrderInfo extends Mailable
{
    use SerializesModels;

    public $emailData;
    protected $mailName;
    public $subject;

    public function __construct($emailData, $mailName = null, $subject = 'Actualización en tu cuenta ')
    {
        $this->emailData = $emailData;
        $this->mailName = $mailName;
        $this->subject = $subject;
    }

    public function build()
    {
        if ($this->mailName) {
            $mailName = strtoupper($this->mailName);

            Config::set('mail.mailers.smtp.host', env("MAIL_HOST_{$mailName}", env('MAIL_HOST')));
            Config::set('mail.mailers.smtp.port', env("MAIL_PORT_{$mailName}", env('MAIL_PORT', 587)));
            Config::set('mail.mailers.smtp.encryption', env("MAIL_ENCRYPTION_{$mailName}", env('MAIL_ENCRYPTION', 'tls')));
            Config::set('mail.mailers.smtp.username', env("MAIL_USERNAME_{$mailName}"));
            Config::set('mail.mailers.smtp.password', env("MAIL_PASSWORD_{$mailName}"));

            Config::set('mail.from.address', env("MAIL_FROM_ADDRESS_{$mailName}", env('MAIL_FROM_ADDRESS')));
            Config::set('mail.from.name', env("MAIL_FROM_NAME_{$mailName}", env('MAIL_FROM_NAME')));

            Mail::purge('smtp');
        }

        return $this->view('Mail.template')
            ->with('data', $this->emailData);
    }
}

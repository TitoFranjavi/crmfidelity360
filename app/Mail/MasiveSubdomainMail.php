<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MasiveSubdomainMail extends Mailable
{
    use Queueable, SerializesModels;

    public  $subject;
    public  $message;
    public  $docs;
    public $recipient;
    public $emailId;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject, $message, $docs, $recipient, $emailId)
    {
        $this->subject = $subject;
        $this->message = $message;
        $this->docs = $docs;
        $this->recipient = $recipient;
        $this->emailId = $emailId;
    }

    public function build()
    {

        try {

            $email = $this->subject($this->subject)
                ->html($this->message);

            // Cabeceras de baja
            $email->withSymfonyMessage(function ($message) {
                $headers = $message->getHeaders();

                $headers->addTextHeader('X-Mailgun-Track', 'yes');
                $headers->addTextHeader('List-Unsubscribe', '<https://crm.zocoenergia.com/unsubscribe?email=' . urlencode($this->recipient) . '>');
                $headers->addTextHeader('X-Mailgun-Unsubscribe', 'yes');
                $headers->addTextHeader('X-Mailgun-Variables', '{"email_id":"' . (string) $this->emailId . '"}');
            });



            // Si se han pasado archivos adjuntos, los agregamos al correo
            foreach ($this->docs as $file) {

                // Asegúrate de que la ruta del archivo es válida
                $filePath = public_path('assets/emails/' . $file['value']);

                $email->attach($filePath, [
                    'as' => $file['title']
                ]);
            }

        } catch (\Throwable $e) {
            Log::channel('cronlog')->error('💥 Error en MasiveSubdomainMail', [
                'mensaje' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // permite que Laravel lo marque como failed
        }


        return $email;
    }
}

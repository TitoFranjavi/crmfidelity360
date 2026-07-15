<?php

namespace App\Jobs;

use App\Mail\MasiveSubdomainMail;
use Illuminate\Support\Facades\Http;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MassiveSendJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $emailData;
    protected $emailId;
    protected $enterprise;
    protected $userLogged;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($emailData, $emailId, $enterprise, $userLogged)
    {
        $this->emailData = $emailData;
        $this->emailId = $emailId;
        $this->enterprise = $enterprise;
        $this->userLogged = $userLogged;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            foreach ($this->emailData['recipients'] as $recipient) {
                if (!isset($recipient['email'])) {
                    Log::warning('❌ Receptor sin email: ' . json_encode($recipient));
                    continue;
                }

                $unsubscribeUrl = 'https://' . $this->enterprise['url'] . '/unsubscribe?email=' . urlencode($recipient['email']);


                $data = [
                    [
                        'name' => 'from',
                        'contents' => $this->userLogged['firstName'] . ' ' . $this->userLogged['lastName'] . '<' . $this->userLogged['email'] .'>',
                    ],
                    [
                        'name' => 'to',
                        'contents' => $recipient['email'],
                    ],
                    [
                        'name' => 'subject',
                        'contents' => $this->emailData['subject']
                    ],
                    [
                        'name' => 'html',
                        'contents' => $this->emailData['message'],
                    ],
                    [
                        'name' => 'v:email_id',
                        'contents' => $this->emailId,
                    ],
                    [
                        'name' => 'o:tracking',
                        'contents' => 'yes',
                    ],
                    [
                        'name' => 'o:tag',
                        'contents' => 'crm-envio',
                    ],
                    [
                        'name' => 'h:List-Unsubscribe',
                        'contents' => '<' . $unsubscribeUrl . '>',
                    ]
                ];

                // Adjuntos si existen
                foreach ($this->emailData['docs'] as $doc) {
                    $filePath = public_path('assets/emails/' . $doc['value']);
                    if (file_exists($filePath)) {
                        $data[] = [
                            'name' => 'attachment',
                            'contents' => fopen($filePath, 'r'),
                            'filename' => $doc['title'] ?? basename($filePath),
                        ];
                    }
                }

                // Enviar
                $response = Http::withBasicAuth('api', config('services.mailgun.secret'))
                    ->asMultipart()
                    ->post('https://api.eu.mailgun.net/v3/' . config('services.mailgun.domain') . '/messages', $data);


                if (!$response->successful()) {
                    Log::channel('cronlog')->error('❌ Mailgun API error', [
                        'recipient' => $recipient['email'],
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                } else {
                    Log::channel('cronlog')->info('✅ Correo enviado por API', [
                        'recipient' => $recipient['email'],
                        'message_id' => $response->json()['id'] ?? null,
                    ]);
                }


                //Por SMTP
                //Mail::mailer('mailgun')->to($recipient['email'])->send(new MasiveSubdomainMail($this->emailData['subject'], $this->emailData['message'], $this->emailData['docs'], $recipient['email'], $this->emailId));

            }
        } catch (\Throwable $e) {
            Log::channel('cronlog')->error('💥 Error en MassiveSendJob', [
                'mensaje' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'emailData' => $this->emailData,
            ]);
            throw $e; // permite que Laravel lo marque como failed
        }
    }
}

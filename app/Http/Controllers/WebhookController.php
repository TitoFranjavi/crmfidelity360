<?php

namespace App\Http\Controllers;

use App\Http\Models\Email;
use App\Http\Models\Opportunity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WebhookController extends Controller
{
    public function wordpressOpportunity(Request $request)
{
    $payload = $request->all();

    $normalizeValue = function ($value) {
        if (is_array($value)) {
            $value = array_filter($value, function ($item) {
                return $item !== null && $item !== '';
            });

            return !empty($value) ? implode(', ', $value) : null;
        }

        if (is_string($value)) {
            $value = trim($value);
            return $value !== '' ? $value : null;
        }

        return $value ?: null;
    };

    $name = $normalizeValue($payload['name'] ?? $payload['nombre'] ?? null);
    $email = $normalizeValue(
        $payload['email']
        ?? $payload['correo']
        ?? $payload['correo_electronico']
        ?? $payload['correo-electronico']
        ?? null
    );
    $phone = $normalizeValue(
        $payload['phone']
        ?? $payload['telefono']
        ?? $payload['tel']
        ?? null
    );

    // Coger el primer valor NO vacío
    $fileValue = null;
    foreach ([
        $payload['file_url'] ?? null,
        $payload['file'] ?? null,
        $payload['factura'] ?? null,
        $payload['archivo'] ?? null,
    ] as $candidate) {
        $candidate = $normalizeValue($candidate);
        if (!empty($candidate)) {
            $fileValue = $candidate;
            break;
        }
    }

    $source = $normalizeValue($payload['source'] ?? null) ?? 'Web-CargadorElectricoCoche';
    $campaign = $normalizeValue($payload['campaign'] ?? null) ?? 'cargador-electrico';
    $origin = $normalizeValue($payload['origin'] ?? null) ?? 'Web-CargadorElectricoCoche';

    Log::info('Webhook WP payload', $payload);
    Log::info('Webhook WP normalized', [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'fileValue' => $fileValue,
        'source' => $source,
        'campaign' => $campaign,
        'origin' => $origin,
    ]);

    if (empty($email) && empty($phone)) {
        return response()->json([
            'success' => false,
            'message' => 'Email or phone required',
        ], 400);
    }

    $expectedToken = env('WORDPRESS_WEBHOOK_TOKEN');
    if (!empty($expectedToken) && (($payload['api_token'] ?? null) !== $expectedToken)) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized',
        ], 401);
    }

    $defaultWebhookUserId = env('WORDPRESS_WEBHOOK_USER_ID');

    try {
        $customFields = [];
        $comments = '';

        $diskName = config('filesystems.disks.opportunities') ? 'opportunities' : 'opportunity';

        $buildCustomField = function ($fileName, $mimeType = '') {
            $fileType = str_starts_with((string)$mimeType, 'application/')
                ? 'application'
                : (explode('/', (string)$mimeType)[0] ?? 'application');

            return [
                'title' => 'Factura',
                'type' => 'image',
                'fileType' => $fileType ?: 'application',
                'value' => $fileName,
            ];
        };

        $uploadedFile = null;
        foreach (['file', 'factura', 'archivo'] as $fileField) {
            if ($request->hasFile($fileField) && $request->file($fileField)->isValid()) {
                $uploadedFile = $request->file($fileField);
                break;
            }
        }

        if ($uploadedFile) {
            $allowedMimeTypes = [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/jpg',
            ];

            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

            $mimeType = $uploadedFile->getMimeType() ?: '';
            $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: '');

            if (!in_array($mimeType, $allowedMimeTypes, true) || !in_array($extension, $allowedExtensions, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se permiten archivos PDF o imágenes JPG, JPEG, PNG y WEBP.',
                ], 422);
            }

            $maxSizeBytes = 5 * 1024 * 1024;
            if (($uploadedFile->getSize() ?? 0) > $maxSizeBytes) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo no puede superar los 5 MB.',
                ], 422);
            }

            $generatedFileName = time() . '_0.' . $extension;

            Storage::disk($diskName)->put(
                $generatedFileName,
                file_get_contents($uploadedFile->getRealPath())
            );

            $customFields[] = $buildCustomField($generatedFileName, $mimeType);
            $comments = 'Factura subida';
        } elseif (!empty($fileValue) && filter_var($fileValue, FILTER_VALIDATE_URL)) {
            $path = parse_url($fileValue, PHP_URL_PATH);
            $extension = strtolower(pathinfo($path ?? '', PATHINFO_EXTENSION) ?: 'pdf');
            $generatedFileName = time() . '_0.' . $extension;

            $remoteFile = Http::timeout(30)->get($fileValue);

            if ($remoteFile->successful()) {
                Storage::disk($diskName)->put($generatedFileName, $remoteFile->body());

                $mimeType = $remoteFile->header('Content-Type') ?: '';
                $customFields[] = $buildCustomField($generatedFileName, $mimeType);
                $comments = 'Factura subida';
            } else {
                Log::warning('No se pudo descargar la factura remota del webhook', [
                    'fileValue' => $fileValue,
                ]);
            }
        }

        $opportunityData = [
            'name' => $name,
            'CIF' => null,
            'email' => $email,
            'phone' => $phone,
            'landLinePhone' => '',
            'comments' => $comments,
            'website' => '',
            'sector' => '',

            'source' => $source,
            'status' => '',
            'campaign' => $campaign,
            'origin' => $origin,

            'contact' => [
                'value' => '',
                'isFromContacts' => false,
            ],

            'position' => '',
            'observations' => $comments,

            'billingInfo' => [
                'community' => '',
                'province' => '',
                'locality' => '',
                'address' => '',
                'postal' => '',
            ],

            'customFields' => $customFields,

            'order' => [
                'name' => '',
                'productType' => 'sp',
                'marketer' => 'Sin Comercializadora',
                'fee' => 'Sin Tarifa',
                'product' => '',
                'CUPS' => '',
                'province' => '',
                'town' => '',
                'direc' => '',
                'zip' => '',
                'extras' => [],
                'usersIds' => [],
                'feeSecondary' => null,
                'productSecondary' => null,
                'CUPSSecondary' => null,
            ],

            'createdAt' => Carbon::now()->format('Y-m-d H:i:s'),
        ];

        if (!empty($defaultWebhookUserId)) {
            $opportunityData['createdBy'] = $defaultWebhookUserId;
            $opportunityData['usersIds'] = [$defaultWebhookUserId];
        }

        Log::info('Webhook WP opportunityData', $opportunityData);

        $opportunity = Opportunity::create($opportunityData);

        Cache::increment('opportunities:index:version');

        Log::info('Webhook WP opportunity created', [
            'id' => $opportunity->_id ?? $opportunity->id ?? null,
            'name' => $opportunity->name ?? null,
            'createdBy' => $opportunity->createdBy ?? null,
            'usersIds' => $opportunity->usersIds ?? [],
            'customFields' => $customFields,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Opportunity created',
        ]);
    } catch (\Exception $e) {
        Log::error('Webhook WP error', [
            'error' => $e->getMessage(),
            'payload' => $payload,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Server error',
        ], 500);
    }
}

    // Función para el trackeo de correos masivos de mailgun
    public function mailgun(Request $request)
    {
        $data = $request->all();

        $event = $data['event-data'] ?? null;

        if (!isset($event['recipient']) || !isset($event['event']) || !isset($event['user-variables']['email_id'])) {
            return response('Bad request', 400);
        }

        $email = Email::where('_id', $event['user-variables']['email_id'])->first();

        $emailRecipients = $email->recipients;

        foreach ($emailRecipients as &$recipient) {
            if ($recipient['email'] === $event['recipient']) {
                switch ($event['event']) {
                    case 'delivered':
                        $recipient['delivered_at'] = Carbon::createFromTimestamp($event['timestamp'])->toDateTimeString();
                        break;

                    case 'opened':
                        $recipient['opened_at'] = [
                            'date' => Carbon::createFromTimestamp($event['timestamp'])->toDateTimeString(),
                            'proxy' => $event['client-info']['client-name'] === 'GmailImageProxy',
                        ];
                        break;

                    case 'clicked':
                        $recipient['clicked_at'] = Carbon::createFromTimestamp($event['timestamp'])->toDateTimeString();
                        break;

                    case 'unsubscribed':
                        $recipient['unsubscribed_at'] = Carbon::createFromTimestamp($event['timestamp'])->toDateTimeString();
                        break;
                }
            }
        }

        $email->recipients = $emailRecipients;
        $email->save();

        return response('¡Email actualizado!');
    }
}
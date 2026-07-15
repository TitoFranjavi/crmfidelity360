<?php

namespace App\Http\Controllers;

use App\Http\Models\Opportunity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class FacebookLeadController extends Controller
{
    public function verify(Request $request)
    {
        $mode      = $request->query('hub.mode');
        $token     = $request->query('hub.verify_token');
        $challenge = $request->query('hub.challenge');

        Log::info('FACEBOOK WEBHOOK - Verificación recibida', [
            'mode'      => $mode,
            'token'     => $token,
            'challenge' => $challenge,
        ]);

        if ($mode === 'subscribe' && $token === env('META_VERIFY_TOKEN')) {
            return response($challenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request)
    {
        Log::info('FACEBOOK WEBHOOK - Payload recibido', [
            'payload' => $request->all(),
        ]);

        foreach ($request->input('entry', []) as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') !== 'leadgen') {
                    continue;
                }

                $value  = $change['value'] ?? [];
                $leadId = $value['leadgen_id'] ?? null;

                if (!$leadId) {
                    Log::warning('FACEBOOK WEBHOOK - leadgen_id no encontrado', [
                        'change' => $change,
                    ]);
                    continue;
                }

                try {
                    $this->importLead($leadId, $value);
                } catch (\Throwable $e) {
                    Log::error('FACEBOOK WEBHOOK - Error importando lead', [
                        'leadId' => $leadId,
                        'error'  => $e->getMessage(),
                        'trace'  => $e->getTraceAsString(),
                    ]);
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    public function testImport(string $leadId)
    {
        $this->importLead($leadId, []);

        return response()->json([
            'success' => true,
            'message' => 'Lead importado en modo test',
            'leadId'  => $leadId,
        ]);
    }

    private function importLead(string $leadId, array $webhookValue = []): void
    {
        if (Opportunity::where('facebookId', $leadId)->exists()) {
            Log::info('FACEBOOK LEAD - Duplicado por facebookId', [
                'leadId' => $leadId,
            ]);
            return;
        }

        $version = env('META_GRAPH_VERSION', 'v25.0');

        $response = Http::get("https://graph.facebook.com/{$version}/{$leadId}", [
            'access_token' => env('META_PAGE_ACCESS_TOKEN'),
            'fields'       => 'id,created_time,field_data,ad_id,form_id',
        ]);

        if (!$response->successful()) {
            Log::error('FACEBOOK LEAD - Error consultando Graph API', [
                'leadId' => $leadId,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return;
        }

        $lead = $response->json();

        Log::info('FACEBOOK LEAD - Lead recuperado desde Graph API', [
            'lead' => $lead,
        ]);

        $fields = $this->normalizeFieldData($lead['field_data'] ?? []);

        $name = $this->getField($fields, [
            'full_name',
            'nombre',
            'nombre_y_apellidos',
            'name',
            'Nombre',
        ]);

        $email = $this->getField($fields, [
            'email',
            'correo_electronico',
            'correo_electrónico',
            'Correo electrónico',
        ]);

        $phone = $this->getField($fields, [
            'phone_number',
            'telefono',
            'teléfono',
            'Teléfono',
            'numero_de_telefono',
            'número_de_teléfono',
        ]);

        $city = $this->getField($fields, [
            'city',
            'ciudad',
            'localidad',
        ]);

        $province = $this->getField($fields, [
            'province',
            'provincia',
        ]);

        $postal = $this->getField($fields, [
            'postal_code',
            'codigo_postal',
            'código_postal',
            'cp',
        ]);

        $phoneNormalized = $this->normalizePhone($phone);

        if ($email !== '' && Opportunity::where('email', $email)->exists()) {
            Log::info('FACEBOOK LEAD - Duplicado por email', [
                'leadId' => $leadId,
                'email'  => $email,
            ]);
            return;
        }

        if ($phoneNormalized !== '' && Opportunity::where('phone', $phoneNormalized)->exists()) {
            Log::info('FACEBOOK LEAD - Duplicado por teléfono', [
                'leadId' => $leadId,
                'phone'  => $phoneNormalized,
            ]);
            return;
        }

        $createdBy = env('FACEBOOK_DEFAULT_USER_ID');

        $opportunity = Opportunity::create([
            'name'          => $name !== '' ? $name : 'Lead Facebook ' . $leadId,
            'CIF'           => '',
            'phone'         => $phoneNormalized,
            'landLinePhone' => '',
            'email'         => $email,
            'website'       => '',
            'sector'        => '',
            'source'        => 'Facebook Lead Ads',
            'status'        => 'Pendiente',

            'contact'       => [
                'value'          => $name,
                'isFromContacts' => false,
            ],

            'position'      => '',
            'observations'  => 'Oportunidad creada automáticamente desde Facebook Lead Ads',

            'billingInfo'   => [
                'community' => '',
                'province'  => $province,
                'locality'  => $city,
                'address'   => '',
                'postal'    => $postal,
            ],

            'customFields'  => [
                [
                    'title' => 'Fecha lead Facebook',
                    'type'  => 'text',
                    'value' => $lead['created_time'] ?? '',
                ],
                [
                    'title' => 'ID Facebook',
                    'type'  => 'text',
                    'value' => $leadId,
                ],
                [
                    'title' => 'Form ID',
                    'type'  => 'text',
                    'value' => $lead['form_id'] ?? ($webhookValue['form_id'] ?? ''),
                ],
                [
                    'title' => 'Ad ID',
                    'type'  => 'text',
                    'value' => $lead['ad_id'] ?? ($webhookValue['ad_id'] ?? ''),
                ],
            ],

            'order'         => [
                'productType' => 'sp',
                'marketer'    => 'Sin Comercializadora',
                'fee'         => 'Sin Tarifa',
                'product'     => '',
                'CUPS'        => '',
                'province'    => $province,
                'town'        => $city,
                'direc'       => null,
                'zip'         => $postal,
                'extras'      => [],
            ],

            'facebookId'    => $leadId,
            'usersIds'      => $createdBy ? [$createdBy] : [],
            'createdBy'     => $createdBy,
            'createdAt'     => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        Log::info('FACEBOOK LEAD - Opportunity creada', [
            'opportunityId' => $opportunity->_id ?? null,
            'leadId'        => $leadId,
            'name'          => $name,
            'email'         => $email,
            'phone'         => $phoneNormalized,
        ]);
    }

    private function normalizeFieldData(array $fieldData): array
    {
        $fields = [];

        foreach ($fieldData as $field) {
            $name = $field['name'] ?? '';
            $values = $field['values'] ?? [];

            if ($name === '') {
                continue;
            }

            $fields[$name] = $values[0] ?? '';
            $fields[$this->normalizeKey($name)] = $values[0] ?? '';
        }

        return $fields;
    }

    private function getField(array $fields, array $possibleNames): string
    {
        foreach ($possibleNames as $name) {
            if (isset($fields[$name]) && trim((string)$fields[$name]) !== '') {
                return trim((string)$fields[$name]);
            }

            $normalized = $this->normalizeKey($name);

            if (isset($fields[$normalized]) && trim((string)$fields[$normalized]) !== '') {
                return trim((string)$fields[$normalized]);
            }
        }

        return '';
    }

    private function normalizeKey(string $value): string
    {
        $value = mb_strtolower($value);
        $value = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', ' '],
            ['a', 'e', 'i', 'o', 'u', 'n', '_'],
            $value
        );

        return preg_replace('/[^a-z0-9_]/', '', $value);
    }

    private function normalizePhone(?string $phone): string
    {
        $phone = preg_replace('/\D+/', '', trim((string) $phone));

        if (str_starts_with($phone, '34') && strlen($phone) > 9) {
            $phone = substr($phone, 2);
        }

        return $phone;
    }
}
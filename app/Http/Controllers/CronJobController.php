<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ContractsExport;
use App\Http\Models\User;
use App\Http\Models\Account;
use App\Http\Models\Order;
use Carbon\Carbon;
use App\Helpers\UserHelper;
use Illuminate\Support\Facades\Log;
use MongoDB\BSON\ObjectId;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

class CronJobController extends Controller
{
    /**
     * Obtiene los contratos a renovar del mes actual (o futuro)
     * buscando directamente en ORDERS (no en Event).
     */
public function collection()
{
    $ref   = Carbon::now('Europe/Madrid');
    $start = $ref->copy()->startOfDay();
    $end   = $ref->copy()->endOfDay();

    $output = [];

    $subdomains = User::where('label', 'Usuario subdominio')->get();

    foreach ($subdomains as $sub) {

        // === Enterprise ===
        $enterprise = DB::connection('mongodb')
            ->collection('enterprises')
            ->where('subdomainUser', (string)$sub->_id)
            ->first();

        if (!$enterprise) {
            continue;
        }

        $enterpriseArr = (array) $enterprise;

        $mailName = strtoupper($enterpriseArr['mailConfig'] ?? '');
        $isZoco   = $this->isZocoEnterprise($enterpriseArr);

        $hasMailer =
            $mailName &&
            env("MAIL_USERNAME_{$mailName}") &&
            env("MAIL_PASSWORD_{$mailName}");

        // ❌ NO mailer y NO ZOCO → fuera
        if (!$hasMailer && !$isZoco) {
            continue;
        }

        // === Jerarquía ===
        $agentIds = collect(UserHelper::hierarchy($sub->_id))->flatten()->toArray();
        array_unshift($agentIds, (string)$sub->_id);

        // === Contratos ===
        $orders = Order::where('isReminderOn', true)
            ->whereIn('createdBy', $agentIds)
            ->whereNotNull('renewalDate')
            ->get()
            ->filter(function ($order) use ($start, $end) {
                try {
                    $date = Carbon::parse($order->renewalDate, 'Europe/Madrid');
                    return $date->between($start, $end);
                } catch (\Throwable $e) {
                    return false;
                }
            });

        foreach ($orders as $order) {

            $userIds = $order->usersIds ?? [];

            $agents = User::whereIn('_id', $userIds)
                ->where('label', '!=', 'Usuario subdominio')
                ->get();

            if ($agents->isEmpty()) {
                $fallback = User::find($order->createdBy);
                if ($fallback && $fallback->label !== 'Usuario subdominio') {
                    $agents = collect([$fallback]);
                }
            }

            if ($agents->isEmpty()) {
                continue;
            }

            foreach ($agents as $agent) {

                if (empty($agent->email)) {
                    continue;
                }

                $activation = null;
                try {
                    if (!empty($order->activationDate)) {
                        $activation = Carbon::parse($order->activationDate, 'Europe/Madrid')->format('Y-m-d');
                    }
                } catch (\Throwable $e) {}

                $output[] = [
                    'subdomain_id'   => (string)$sub->_id,
                    'enterprise'     => $enterpriseArr['name'] ?? '',
                    'is_zoco'        => $isZoco,
                    'mailer_name'    => $isZoco ? 'BASE' : $mailName,

                    'agent_id'       => (string)$agent->_id,
                    'agent_name'     => "{$agent->firstName} {$agent->lastName}",
                    'agent_email'    => $agent->email,

                    'nombre_contrato'=> $order->name ?? '(sin nombre)',
                    'cups'           => $order->CUPS ?? '',
                    'comercializadora'=> $order->marketer ?? '',
                    'fecha_caducidad'=> Carbon::parse($order->renewalDate)->toDateString(),
                    'fecha_activacion'=> $activation,
                ];
            }
        }
    }

    return collect($output)->groupBy('subdomain_id');
}

public function sendEmailOrders()
{
    $grouped = $this->collection();

    if ($grouped->flatten(1)->count() === 0) {
        Log::info('No hay contratos hoy. No se envían correos.');
        return response()->json([
            'sent' => 0,
            'skipped' => [],
            'message' => 'Sin contratos hoy'
        ], 200);
    }

    $sent = 0;
    $skipped = [];

    /**
     * 🔁 Resolver mailer fallback (primer enterprise con mailConfig válido)
     */
    $resolveFallbackMailer = function () {
        $enterprises = DB::connection('mongodb')
            ->collection('enterprises')
            ->whereNotNull('mailConfig')
            ->get();

        foreach ($enterprises as $ent) {
            $mailName = strtoupper($ent['mailConfig'] ?? '');

            if (
                $mailName &&
                env("MAIL_USERNAME_{$mailName}") &&
                env("MAIL_PASSWORD_{$mailName}")
            ) {
                return $mailName;
            }
        }

        return null;
    };

    foreach ($grouped as $subdomainId => $contracts) {

        // === Enterprise del contrato
        $enterprise = DB::connection('mongodb')
            ->collection('enterprises')
            ->where('subdomainUser', (string)$subdomainId)
            ->first();

        if (!$enterprise) {
            $skipped[] = "Subdominio {$subdomainId}: sin enterprise.";
            continue;
        }

        $enterpriseArr = (array) $enterprise;

        // === MailConfig propio
        $ownMailName = strtoupper($enterpriseArr['mailConfig'] ?? '');
        $hasOwnMailer =
            $ownMailName &&
            env("MAIL_USERNAME_{$ownMailName}") &&
            env("MAIL_PASSWORD_{$ownMailName}");

        // === Resolver mailer final
        if ($hasOwnMailer) {
            $mailerUsed = $ownMailName;
            Log::info("📧 Usando mailer PROPIO {$mailerUsed} para {$enterpriseArr['name']}");
        } else {
            $mailerUsed = $resolveFallbackMailer();

            if (!$mailerUsed) {
                $msg = "Enterprise '{$enterpriseArr['name']}' sin mailConfig y sin fallback.";
                Log::info($msg);
                $skipped[] = $msg;
                continue;
            }

            Log::info("📧 Usando mailer FALLBACK {$mailerUsed} para {$enterpriseArr['name']}");
        }

        // === Aplicar configuración mailer
        Config::set('mail.mailers.smtp.host',     env("MAIL_HOST_{$mailerUsed}") ?: env('MAIL_HOST'));
        Config::set('mail.mailers.smtp.username', env("MAIL_USERNAME_{$mailerUsed}"));
        Config::set('mail.mailers.smtp.password', env("MAIL_PASSWORD_{$mailerUsed}"));
        Config::set('mail.from.address',          env("MAIL_FROM_ADDRESS_{$mailerUsed}") ?: env('MAIL_FROM_ADDRESS'));
        Config::set('mail.from.name',             env("MAIL_FROM_NAME_{$mailerUsed}")    ?: env('MAIL_FROM_NAME'));

        // === Agrupar por agente
        $byAgent = $contracts->groupBy('agent_id');

        foreach ($byAgent as $agentId => $items) {

            $agentEmail = $items->first()['agent_email'] ?? null;
            $agentName  = $items->first()['agente'] ?? 'Agente';

            if (!$agentEmail) {
                $skipped[] = "Agente {$agentName} sin email.";
                continue;
            }

            $count = $items->count();
            $subject = "CRM – CONTRATOS A RENOVAR HOY ({$count})";

            // === Render simple de lista
            $rows = '';
            foreach ($items as $c) {
                $rows .= '<tr>'
                    . '<td style="padding:8px;border-bottom:1px solid #eee;">' . htmlspecialchars($c['nombre_contrato']) . '</td>'
                    . '<td style="padding:8px;border-bottom:1px solid #eee;"><code>' . htmlspecialchars($c['cups']) . '</code></td>'
                    . '<td style="padding:8px;border-bottom:1px solid #eee;">' . htmlspecialchars($c['comercializadora']) . '</td>'
                    . '</tr>';
            }

            $html = <<<HTML
<!doctype html>
<html>
<body style="font-family:Arial,sans-serif;background:#f6f7f9;padding:20px;">
  <div style="max-width:640px;margin:auto;background:#fff;padding:20px;border-radius:10px;">
    <h2>Contratos a renovar hoy</h2>
    <p>Total: <strong>{$count}</strong></p>
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px;">
      <thead>
        <tr style="background:#f0f0f0;">
          <th style="padding:8px;text-align:left;">Nombre</th>
          <th style="padding:8px;text-align:left;">CUPS</th>
          <th style="padding:8px;text-align:left;">Comercializadora</th>
        </tr>
      </thead>
      <tbody>{$rows}</tbody>
    </table>
  </div>
</body>
</html>
HTML;

            try {
                Mail::send([], [], function ($msg) use ($agentEmail, $subject, $html) {
                    $msg->to($agentEmail)
                        ->subject($subject)
                        ->html($html);
                });

                $sent++;
                Log::info("📨 Enviado a {$agentName} <{$agentEmail}> usando {$mailerUsed}");

            } catch (\Throwable $e) {
                $error = "❌ Error enviando a {$agentEmail}: {$e->getMessage()}";
                Log::error($error);
                $skipped[] = $error;
            }
        }
    }

    return response()->json([
        'sent' => $sent,
        'skipped' => $skipped,
        'message' => 'Proceso finalizado'
    ], 200);
}


public function dryRunEmailOrders(): JsonResponse
{
    // Usa la misma colección que sendEmailOrders()
    $grouped = $this->collection();

    if ($grouped->flatten(1)->count() === 0) {
        \Log::info('DRY RUN: No hay contratos en el rango. No se enviaría nada.');
        return response()->json([
            'would_send' => [],
            'skipped'    => [],
            'message'    => 'Sin contratos en el rango (dry run)',
            'total_contracts' => 0,
        ], 200);
    }

    $wouldSend = [];
    $skipped   = [];

    foreach ($grouped as $subdomainId => $contracts) {

        // === Enterprise del subdominio
        $enterprise = \DB::connection('mongodb')
            ->collection('enterprises')
            ->where('subdomainUser', (string)$subdomainId)
            ->first();

        if (!$enterprise) {
            // 🔴 Si no hay enterprise, guardamos todos sus contratos en "skipped"
            foreach ($contracts as $c) {
                $skipped[] = [
                    'reason'      => 'no_enterprise',
                    'subdomain_id'=> $subdomainId,
                    'usuario'     => $c['usuario'] ?? '(sin usuario)',
                    'nombre_contrato'  => $c['nombre_contrato'] ?? '(sin nombre)',
                    'cups'             => $c['cups'] ?? '',
                    'comercializadora' => $c['comercializadora'] ?? '',
                    'fecha_caducidad'  => $c['fecha_caducidad'] ?? null,
                    'agente'           => $c['agente'] ?? '(sin agente)',
                    'agent_email'      => $c['agent_email'] ?? '(sin email)',
                ];
            }
            continue;
        }

        $enterpriseArr = is_array($enterprise) ? $enterprise : (array)$enterprise;
        $mailName = strtoupper($enterpriseArr['mailConfig'] ?? '');
        $isZoco   = $this->isZocoEnterprise($enterpriseArr);

        $canSendWithMailConfig = $mailName
            && env("MAIL_USERNAME_{$mailName}")
            && env("MAIL_PASSWORD_{$mailName}");

        if (!$canSendWithMailConfig && !$isZoco) {
            // 🔴 Enterprise sin mailConfig ni ser Zoco → todos sus contratos fallan
            foreach ($contracts as $c) {
                $skipped[] = [
                    'reason'      => 'no_mailer_config',
                    'enterprise'  => $enterpriseArr['name'] ?? '(sin nombre)',
                    'subdomain_id'=> $subdomainId,
                    'nombre_contrato'  => $c['nombre_contrato'] ?? '(sin nombre)',
                    'cups'             => $c['cups'] ?? '',
                    'comercializadora' => $c['comercializadora'] ?? '',
                    'fecha_caducidad'  => $c['fecha_caducidad'] ?? null,
                    'agente'           => $c['agente'] ?? '(sin agente)',
                    'agent_email'      => $c['agent_email'] ?? '(sin email)',
                ];
            }
            continue;
        }

        // === Agrupar por agente
        $byAgent = $contracts->groupBy('agent_id');

        foreach ($byAgent as $agentId => $items) {
            $first      = $items->first();
            $agentEmail = $first['agent_email'] ?? null;
            $agentName  = $first['agente']      ?? 'Agente';

            if (!$agentEmail) {
                // 🔴 Sin email → se lista cada contrato que quedaría fuera
                foreach ($items as $c) {
                    $skipped[] = [
                        'reason'      => 'no_agent_email',
                        'enterprise'  => $enterpriseArr['name'] ?? '(sin nombre)',
                        'subdomain_id'=> $subdomainId,
                        'agente'      => $agentName,
                        'nombre_contrato'  => $c['nombre_contrato'] ?? '(sin nombre)',
                        'cups'             => $c['cups'] ?? '',
                        'comercializadora' => $c['comercializadora'] ?? '',
                        'fecha_caducidad'  => $c['fecha_caducidad'] ?? null,
                    ];
                }
                continue;
            }

            // ✅ Envío correcto → se agregan a "would_send"
            $count = $items->count();
            $contractsList = $items->map(function ($c) {
                return [
                    'nombre_contrato'  => $c['nombre_contrato'] ?? '(sin nombre)',
                    'cups'             => $c['cups'] ?? '',
                    'comercializadora' => $c['comercializadora'] ?? '',
                    'fecha_caducidad'  => $c['fecha_caducidad'] ?? null,
                    'fecha_activacion' => $c['fecha_activacion'] ?? null,
                ];
            })->values()->all();

            $wouldSend[] = [
                'enterprise'      => $enterpriseArr['name'] ?? '(sin nombre)',
                'subdomain_id'    => $subdomainId,
                'mailer'          => $mailName ?: 'default',
                'agent_id'        => $agentId,
                'agent_name'      => $agentName,
                'agent_email'     => $agentEmail,
                'contracts_count' => $count,
                'contracts'       => $contractsList,
            ];
        }
    }

    return response()->json([
        'total_contracts' => $grouped->flatten(1)->count(),
        'would_send'      => $wouldSend,
        'skipped'         => $skipped, // ← ahora incluye también los contratos fallidos
        'message'         => 'Dry run completo: simulación sin envío de correos',
    ], 200);
}


private function isZocoEnterprise(array $enterprise): bool
{
    $name   = strtoupper(trim($enterprise['name'] ?? ''));
    $folder = strtolower(trim($enterprise['asset_folder'] ?? ''));
    $url    = strtolower(trim($enterprise['url'] ?? ''));

    if (str_contains($name, 'ZOCO ENERG')) return true;
    if ($folder === 'zocoenergia') return true;
    if (str_contains($url, 'zocoenergia')) return true;

    return false;
}


public function changeStatusKuvi()
{
    $rootId = "65cb57489c2c285441086a43"; // ID del usuario principal
    
    // 1. Jerarquía de agentes
    $agentIds = collect(UserHelper::hierarchy($rootId))->flatten()->toArray();
    array_unshift($agentIds, $rootId);

    // 2. Fecha HOY
    $today = Carbon::now('Europe/Madrid')->subDay(20);
    $start = $today->copy()->startOfDay();
    $end   = $today->copy()->endOfDay();

    // 3. Buscar contratos de la jerarquía que caducan HOY
    $orders = Order::whereIn('createdBy', $agentIds)
        ->whereNotNull('renewalDate')
        ->get()
        ->filter(function ($order) use ($start, $end) {
            try {
                $date = Carbon::parse($order->renewalDate, 'Europe/Madrid');
                return $date->between($start, $end);
            } catch (\Throwable $e) {
                return false;
            }
        });

    // 4. Añadir estado "Pendiente de Renovación"
        foreach ($orders as $order) {
    // Asegurar que es un array normal
    $statuses = (array) $order->statuses;

    $statuses[] = [
        "code"    => "pendiente_de_renovacin",
        "date"    => Carbon::now('Europe/Madrid')->format("Y-m-d H:i:s"),
        "creator" => $rootId,
    ];

    // Reasignar la propiedad de golpe
    $order->statuses = array_values($statuses);
    $order->save();
}

return response()->json([
    "updated" => $orders->count(),
    "orders"  => $orders->pluck('_id'),
]);
}


}




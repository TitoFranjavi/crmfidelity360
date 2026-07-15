<?php

namespace App\Console\Commands;

use App\Helpers\UserHelper;
use App\Http\Models\Order;
use App\Http\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FidelityRenewalStatusCommand extends Command
{
    // Comando interno ejecutado por scheduler para el flujo automático de renovación en Fidelity.
    protected $signature = 'orders:fidelity-renewal-flow';
    // Flujo: en fecha de renovación pasa a Renovar; tras 30 días en Renovar pasa a Anulado.
    protected $description = 'Actualiza estados de contratos en Fidelity: renewalDate -> Renovar y Renovar +30d -> Anulado';

    // ID fijo del subdominio Fidelity360.
    private const FIDELITY_SUBDOMAIN_ID = '6909faa9232c09035a03f3b2';

    public function handle(): int
    {
        // 1) Obtener el usuario subdominio de Fidelity.
        $subdomain = User::where('_id', self::FIDELITY_SUBDOMAIN_ID)->first();

        if (!$subdomain) {
            $this->warn('No existe el subdominio Fidelity.');
            Log::warning('[FidelityRenewalStatus] Subdominio no encontrado.', [
                'subdomain_id' => self::FIDELITY_SUBDOMAIN_ID,
            ]);
            return self::SUCCESS;
        }

        // 2) Resolver los estados objetivo desde la configuración del subdominio.
        $statusList = collect($subdomain->statuses ?? []);
        $renovarStatus = $statusList->first(fn ($status) => $this->isStatusTitle($status, 'renovar'));
        $anuladoStatus = $statusList->first(fn ($status) => $this->isStatusTitle($status, 'caducado'));

        if (!$renovarStatus || !$anuladoStatus) {
            $this->warn('No se encontraron los estados Renovar y/o Anulado en Fidelity.');
            Log::warning('[FidelityRenewalStatus] Estados requeridos no encontrados.', [
                'subdomain_id' => self::FIDELITY_SUBDOMAIN_ID,
                'has_renovar' => (bool) $renovarStatus,
                'has_anulado' => (bool) $anuladoStatus,
            ]);
            return self::SUCCESS;
        }

        // 3) Construir el alcance de contratos: Fidelity + su jerarquía de usuarios.
        $agentIds = collect(UserHelper::hierarchy(self::FIDELITY_SUBDOMAIN_ID))
            ->flatten()
            ->map(fn ($id) => (string) $id)
            ->toArray();

        array_unshift($agentIds, self::FIDELITY_SUBDOMAIN_ID);
        $agentIds = array_values(array_unique($agentIds));

        // 4) Fechas base y contadores para resumen de ejecución.
        $today = Carbon::now('Europe/Madrid')->startOfDay();
        $now = Carbon::now('Europe/Madrid');

        $toRenovar = 0;
        $toAnulado = 0;
        $failed = 0;

        // 5) Procesar contratos del ámbito Fidelity y aplicar transiciones de estado.
        $orders = Order::whereIn('createdBy', $agentIds)->get();

        foreach ($orders as $order) {
            try {
                $statuses = is_array($order->statuses ?? null) ? $order->statuses : [];
                if (count($statuses) === 0) {
                    continue;
                }

                // Usamos lastStatus (campo mantenido por el sistema) en lugar de recalcular
                // desde el historial, evitando errores cuando algún estado tiene fecha inválida.
                $latestStatus = is_array($order->lastStatus ?? null) ? $order->lastStatus : null;
                if (!$latestStatus || empty($latestStatus['code'])) {
                    continue;
                }

                // Si ya está en Renovar, al cumplir 30 días pasa a Anulado.
                if ($latestStatus['code'] === ($renovarStatus['code'] ?? null)) {
                    $renovarDate = $this->safeParseDate($latestStatus['date'] ?? null);
                    if ($renovarDate && $renovarDate->copy()->addDays(30)->startOfDay()->lte($today)) {
                        $this->appendStatus($order, $statuses, $anuladoStatus['code'], $now);
                        $toAnulado++;
                    }
                    continue;
                }

                // No forzar cambios si ya está anulado.
                if ($latestStatus['code'] === ($anuladoStatus['code'] ?? null)) {
                    continue;
                }

                // RenewalDate sólo aplica cuando el recordatorio está activado.
                if (!($order->isReminderOn ?? false) || empty($order->renewalDate)) {
                    continue;
                }

                // Solo aplica si el contrato está en estado "liquidado".
                if (($latestStatus['code'] ?? null) !== 'liquidado') {
                    continue;
                }

                $renewalDate = $this->safeParseDate($order->renewalDate);
                if ($renewalDate && $renewalDate->startOfDay()->equalTo($today)) {
                    $this->appendStatus($order, $statuses, $renovarStatus['code'], $now);
                    $toRenovar++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::error('[FidelityRenewalStatus] Error procesando contrato.', [
                    'order_id' => (string) ($order->_id ?? ''),
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $summary = "Fidelity renewal flow: {$toRenovar} -> Renovar, {$toAnulado} -> Anulado, {$failed} errores.";
        $this->info($summary);

        Log::info('[FidelityRenewalStatus] Proceso completado.', [
            'to_renovar' => $toRenovar,
            'to_anulado' => $toAnulado,
            'failed' => $failed,
        ]);

        return self::SUCCESS;
    }

    private function isStatusTitle($status, string $expected): bool
    {
        // Comparación de títulos sin distinguir mayúsculas/minúsculas.
        $title = strtolower(trim((string) ($status['title'] ?? '')));
        return $title === strtolower($expected);
    }

    private function safeParseDate(?string $date): ?Carbon
    {
        // Parseo seguro para evitar que una fecha inválida rompa el flujo completo.
        if (!$date) {
            return null;
        }

        try {
            return Carbon::parse($date, 'Europe/Madrid');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function appendStatus(Order $order, array $statuses, string $statusCode, Carbon $now): void
    {
        // Inserta un nuevo evento de estado y sincroniza campos de último estado/actualización.
        $entry = [
            'code' => $statusCode,
            'date' => $now->format('Y-m-d H:i:s'),
            'creator' => self::FIDELITY_SUBDOMAIN_ID,
        ];

        $statuses[] = $entry;

        $order->statuses = array_values($statuses);
        $order->lastStatus = $entry;
        $order->lastUpdate = $now->format('Y-m-d H:i:s');
        $order->save();
    }
}

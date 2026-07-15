<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Models\Order;
use App\Http\Controllers\AuthController;

class CommissionMigrationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate_commissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates the commissions to the new structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando migración...");

        $total = Order::count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $noUserId                    = 0;
        $emptyHierarchyBeforeFilter  = [];
        $emptyHierarchyAfterDeveloper = [];

        Order::chunk(1000, function ($orders) use ($bar, &$noUserId, &$emptyHierarchyBeforeFilter, &$emptyHierarchyAfterDeveloper) {

                foreach ($orders as $order) {

                    $userId = $order->usersIds[0] ?? null;

                    if (!$userId) {
                        $noUserId++;
                        $bar->advance();
                        continue;
                    }

                    $hierarchy = AuthController::getAllSuperiors($userId);

                    if (empty($hierarchy) || !is_array($hierarchy)) {
                        $emptyHierarchyBeforeFilter[] = $userId;
                        $bar->advance();
                        continue;
                    }

                    $hierarchy = array_reverse($hierarchy);

                    $hierarchy = array_filter($hierarchy, function ($user) {
                        return $user['label'] !== 'Tramitador'
                            && $user['firstName'] !== 'Visualizador';
                    });

                    $hierarchy = array_values($hierarchy);

                    array_shift($hierarchy); // quitar developer

                    if (empty($hierarchy)) {
                        $emptyHierarchyAfterDeveloper[] = $userId;
                        $bar->advance();
                        continue;
                    }

                    $isZoco = $order->assignedTo == '65cb57489c2c285441086a43';

                    if (!$isZoco) {
                        // quitar subdomain
                        array_shift($hierarchy);
                    }

                    $hasDecommission =
                        (isset($order->asercordDecommision) && (float) $order->asercordDecommision > 0) ||
                        (isset($order->salesDecommision)    && (float) $order->salesDecommision > 0);

                    $commissionBreakdown   = [];
                    $decommissionBreakdown = [];

                    $level = 0;

                    foreach ($hierarchy as $superior) {

                        $commissionBreakdown[] = [
                            'userId'     => $superior['_id'],
                            'level'      => $level,
                            'commission' => $level === 0
                                ? (float) ($order->salesCommision ?? 0)
                                : 0,
                        ];

                        if ($hasDecommission) {
                            $decommissionBreakdown[] = [
                                'userId'     => $superior['_id'],
                                'level'      => $level,
                                'commission' => $level === 0
                                    ? (float) ($order->salesDecommision ?? 0)
                                    : 0,
                            ];
                        }

                        $level++;
                    }

                    $set = [
                        'commissions' => [
                            'subdomain' => (float) ($order->asercordCommision ?? 0),
                            'breakdown' => $commissionBreakdown,
                        ],
                    ];

                    $unset = [
                        'asercordCommision' => "",
                        'salesCommision'    => "",
                    ];

                    // Procesar installmentCommissions
                    $installmentCommissions = [];

                    if (!empty($order->installmentCommission) && is_array($order->installmentCommission)) {
                        foreach ($order->installmentCommission as $installment) {

                            $instBreakdown = [];
                            $level = 0;

                            foreach ($hierarchy as $superior) {
                                $instBreakdown[] = [
                                    'userId'     => $superior['_id'],
                                    'level'      => $level,
                                    'commission' => $level === 0
                                        ? (float) ($installment['salesCommision'] ?? 0)
                                        : 0,
                                ];
                                $level++;
                            }

                            $installmentCommissions[] = [
                                'date'        => $installment['date'],
                                'commissions' => [
                                    'subdomain' => (float) ($installment['asercordCommision'] ?? 0),
                                    'breakdown' => $instBreakdown,
                                ],
                            ];
                        }
                    }

                    if (!empty($installmentCommissions)) {
                        $set['installmentCommissions'] = $installmentCommissions;
                        $unset['installmentCommission'] = ""; // eliminar el campo viejo solo si había
                    }

                    if ($hasDecommission) {
                        $set['decommissions'] = [
                            'subdomain' => (float) ($order->asercordDecommision ?? 0),
                            'breakdown' => $decommissionBreakdown,
                        ];

                        $unset['asercordDecommision'] = "";
                        $unset['salesDecommision']    = "";
                    }

                    $order->update([
                        '$set'   => $set,
                        '$unset' => $unset,
                    ]);

                    $bar->advance();
                }
            });

        $bar->finish();

        $this->info("\n--- Resumen ---");
        $this->info("Contratos sin usersIds:                          {$noUserId}");
        $this->info("Contratos con jerarquía vacía antes de filtrar:  " . count($emptyHierarchyBeforeFilter));
        $this->info(implode(', ', $emptyHierarchyBeforeFilter));

        $this->info("Contratos con jerarquía vacía tras developer:    " . count($emptyHierarchyAfterDeveloper));
        $this->info(implode(', ', $emptyHierarchyAfterDeveloper));
        $this->info("Migración completada.");
    }
}

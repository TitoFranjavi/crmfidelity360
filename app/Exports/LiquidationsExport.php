<?php

namespace App\Exports;

use App\Helpers\UserHelper;
use App\Http\Models\Marketer;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class LiquidationsExport implements FromArray, WithHeadings, WithEvents
{
    private $dataFromController;

    public function __construct($dataFromController)
    {
        $this->dataFromController = $dataFromController;
    }

    public function array(): array
    {

        $data = [];

        // helper para comisión numérica (negativa si es 'b')
        $commissionValue = function(array $o, array $last, string $type = 'sales'): float {
            if (($last['code'] ?? null) === 'b' || ($last['code'] ?? null) === 'pendiente_de_retrocomisin') {
                $dec = $type === 'sales'
                    ? ($o['agentDecommission'] ?? 0)
                    : ($o['subdomainDecommission'] ?? 0);

                return - (float) $dec;
            }

            $norm = $type === 'sales'
                ? ($o['agentCommission'] ?? 0)
                : ($o['subdomainCommission'] ?? 0);

            return (float) $norm;
        };

        $globalSalesTotal = 0.0;

        // ====== PROPIOS ======
        foreach ($this->dataFromController['orders']['own'] as $order) {
            $lastStatus = $this->getLastState($order['statuses'] ?? []);
            $salesNum = $commissionValue($order, $lastStatus, 'sales');

            $globalSalesTotal += $salesNum;

            $data[] = $this->buildOrderRow(
                $order,
                $salesNum,
                $this->getOwnerName()
            );
        }

        // ====== POR AGENTE ======
        foreach ($this->dataFromController['orders']['others'] as $key => $agentBlock) {
            $agentNameRaw = explode('/', (string) $key)[0];
            $agentName = trim(preg_replace('/\s+/', ' ', $agentNameRaw));

            $ordersList = is_array($agentBlock) ? $agentBlock : [];

            foreach ($ordersList as $orderAgent) {
                $lastStatus = $this->getLastState($orderAgent['statuses'] ?? []);
                $salesNum = $commissionValue($orderAgent, $lastStatus, 'sales');

                $globalSalesTotal += $salesNum;

                $data[] = $this->buildOrderRow(
                    $orderAgent,
                    $salesNum,
                    $agentName
                );
            }
        }

        // ====== EXTRAS MANUALES DE LIQUIDACIÓN ======
        if (!empty($this->dataFromController['extras'])) {
            foreach ($this->dataFromController['extras'] as $agent => $extras) {
                $agentNameRaw = explode('/', (string) $agent)[0];
                $agentName = trim(preg_replace('/\s+/', ' ', $agentNameRaw));

                foreach ($extras as $extra) {
                    $amount = (float) ($extra['amount'] ?? 0);

                    if (($extra['type'] ?? '') === 'penalizacion') {
                        $amount = -$amount;
                    }

                    $globalSalesTotal += $amount;

                    $data[] = [
                        '', // ID
                        'EXTRA - ' . ($extra['concept'] ?? ''), // Nombre contrato
                        '', // CUPS
                        '', // Fecha creación
                        '', // Provincia
                        '', // DNI/NIF
                        $extra['type'] ?? '', // Producto
                        '', // Productos extra
                        '', // Tarifa
                        '', // Potencia
                        '', // Fec. activación
                        '', // Fec. baja
                        '', // Consumo
                        $amount, // Comisión
                        $agentName, // Agente
                    ];
                }
            }
        }

        // ====== TOTAL FINAL ======
        $data[] = [
            '', // ID
            'TOTAL', // Nombre contrato
            '', // CUPS
            '', // Fecha creación
            '', // Provincia
            '', // DNI/NIF
            '', // Producto
            '', // Productos extra
            '', // Tarifa
            '', // Potencia
            '', // Fec. activación
            '', // Fec. baja
            '', // Consumo
            $globalSalesTotal, // Comisión
            '', // Agente
        ];

        return $data;
    }

    private function buildOrderRow(array $order, float $salesNum, string $agentName): array
    {
        return [
            $order['identifier'] ?? '', // ID
            $order['name'] ?? '', // Nombre contrato
            $order['CUPS'] ?? '', // CUPS
            $this->formatDate($order['createdAt'] ?? null, true), // Fecha creación
            $order['province'] ?? '', // Provincia
            $order['accountCIF'] ?? '', // DNI/NIF
            trim(($order['marketer'] ?? '') . ' ' . ($order['product'] ?? '')), // Producto
            $this->formatOrderExtras($order), // Productos extra
            $order['fee'] ?? '', // Tarifa
            $order['hiredPotency'] ?? '', // Potencia
            $this->formatDate($order['activationDate'] ?? null), // Fec. activación
            $this->formatDate($order['lowDate'] ?? null), // Fec. baja
            floor((float) ($order['consumption'] ?? 0)), // Consumo
            $salesNum, // Comisión
            $agentName, // Agente
        ];
    }

    private function getOwnerName(): string
    {
        return trim(
            ($this->dataFromController['owner']['firstName'] ?? '') .
            ' ' .
            ($this->dataFromController['owner']['lastName'] ?? '')
        );
    }

    private function formatDate($date, bool $withTime = false): string
    {
        if (empty($date)) {
            return '';
        }

        $format = $withTime ? 'd/m/Y H:i' : 'd/m/Y';

        return date($format, strtotime($date));
    }


    private function formatOrderExtras(array $order): string
    {
        $extras = $order['extras'] ?? [];

        if (empty($extras) || !is_array($extras)) {
            return '';
        }

        $userSubdomain = UserHelper::getUserSubdomain($order['createdBy'] ?? '');

        $createdBy = (isset($order['assignedTo']) && $order['assignedTo'] === '65cb57489c2c285441086a43') ? $order['assignedTo'] : $userSubdomain['_id'];

        $marketer = Marketer::where('name', $order['marketer'] ?? '')->where('createdBy', $createdBy)->first();


        // Fallback por si no coincide createdBy
        if (!$marketer)
            $marketer = Marketer::where('name', $order['marketer'] ?? '')->first();

        if (!$marketer)
            return collect($extras)->map(fn ($extra) => $this->normalizeId($extra))->filter()->implode(', ');

        $marketerExtras = $marketer->extras ?? [];

        return collect($extras)->map(function ($extra) use ($marketerExtras) {
            $extraId = $this->normalizeId($extra);

            if (!$extraId) {
                return '';
            }

            $foundExtra = collect($marketerExtras)->first(function ($marketerExtra) use ($extraId) {
                $marketerExtraId = $this->normalizeId(
                    $marketerExtra['id']
                    ?? $marketerExtra['_id']
                    ?? null
                );

                return $marketerExtraId === $extraId;
            });

            if (!$foundExtra) {
                return $extraId;
            }

            return $foundExtra['name']
                ?? $foundExtra['concept']
                ?? $foundExtra['title']
                ?? $foundExtra['productName']
                ?? $extraId;
        })->filter()->implode(', ');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                foreach (range('A', 'O') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $sheet->getStyle("A1:O1")->getFont()->setBold(true);

                $sheet->getStyle("A1:O{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                    'alignment' => [
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);
            },
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre contrato',
            'CUPS',
            'Fecha creación',
            'Provincia',
            'DNI/NIF',
            'Producto',
            'Productos extra',
            'Tarifa',
            'Potencia',
            'Fec. activación',
            'Fec. baja',
            'Consumo',
            'Comisión',
            'Agente',
        ];
    }

    public function getLastState($statuses)
    {
        if (empty($statuses) || !is_array($statuses)) {
            return [];
        }

        usort($statuses, function ($a, $b) {
            return strtotime($b['date'] ?? '') <=> strtotime($a['date'] ?? '');
        });

        return $statuses[0] ?? [];
    }

    private function normalizeId($id): string
    {
        if (empty($id)) {
            return '';
        }

        if (is_array($id)) {
            return (string) (
                $id['$oid']
                ?? $id['id']
                ?? $id['_id']
                ?? ''
            );
        }

        if (is_object($id) && method_exists($id, '__toString')) {
            return (string) $id;
        }

        return (string) $id;
    }
}

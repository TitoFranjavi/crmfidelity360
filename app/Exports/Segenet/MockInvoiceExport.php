<?php

namespace App\Exports\Segenet;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MockInvoiceExport implements FromArray, WithStyles, WithEvents
{
    protected $resume;

    public function __construct($resume)
    {
        $this->resume = $resume;
    }

    public function array(): array
    {
        $rows = [];


        // SECCIÓN POTENCIA
        $rows[] = [''];
        $rows[] = ['POTENCIA'];
        $rows[] = ['Periodo', 'Contratada', 'Máxima', 'Facturada', 'Penalización', 'Precio/kWd', 'TOTAL'];

        $totalPotency = 0;

        foreach ($this->resume->potency->periods as $index => $period) {
            $hired = $period->hired[0] ?? 0;
            $registered = $period->registered[0] ?? 0;
            $billed = $period->billed[0] ?? 0;
            $penalized = $period->penalized[0] ?? 0;
            $term = ($period->term[0] ?? 0) / 365; // precio kW/día
            $days = Carbon::parse($this->resume->first_date)
                ->diffInDays(Carbon::parse($this->resume->last_date)) ?: 1;

            // Calculamos el total: Facturada × Precio × Días
            $price = ($billed * $term * $days) + $penalized;
            $totalPotency += $price;


            $rows[] = [
                'P' . $index,
                $hired . ' kW',
                $registered . ' kW',
                $billed . ' kW',
                '+ ' . number_format($penalized, 2, ',', "'") . ' €',
                number_format($term, 4, ',', "'") . ' €',
                number_format($price, 2, ',', "'") . ' €'
            ];
        }

        $rows[] = ['TOTAL', '', '', '', '', '', number_format($totalPotency, 2, ',', "'") . ' €'];



        // SECCIÓN CONSUMO
        $rows[] = ['']; // espacio
        $rows[] = ['CONSUMO'];
        $rows[] = ['Periodo', 'Registrada', 'Pérdidas', 'Precio/kWh', 'TOTAL'];

        $totalConsumption = 0;

        foreach ([3, 4, 6] as $p) {
            $period = $this->resume->active->periods[$p];
            $registered = $period->registered[0] ?? 0;
            $lost = $period->lost[0] ?? 0;
            $term = $period->term[0] ?? 0;   // Precio unitario
            $price = $period->price[0] ?? 0; // Total del periodo

            $totalConsumption += $price;

            $rows[] = [
                'P' . $p,
                $registered . ' kWh',
                number_format($lost, 2, ',', "'") . ' kWh',
                number_format($term, 4, ',', "'") . ' €',
                number_format($price, 2, ',', "'") . ' €',
            ];
        }

        $rows[] = ['TOTAL', '', '', '', number_format($totalConsumption, 2, ',', "'") . ' €']; // ejemplo



        // SECCIÓN INDUCTIVA
        $rows[] = ['']; // espacio
        $rows[] = ['INDUCTIVA'];
        $rows[] = ['Periodo', 'Registrada', 'Coseno Phi', 'Facturada', 'Precio/kVArh', 'TOTAL'];

        $totalInductive = 0;

        foreach ([3, 4, 6] as $p) {
            $period = $this->resume->inductive->periods[$p];
            $registered = $period->registered[0] ?? 0;
            $cosPhi = $period->cosine[0] ?? 1;
            $penalized = $period->penalized[0] ?? 0;
            $term = $period->term[0] ?? 0;   // Precio unitario
            $price = $period->price[0] ?? 0; // Total del periodo


            $totalInductive += $price;

            $rows[] = [
                'P' . $p,
                $registered . ' kVArh',
                number_format($cosPhi, 4, ',', "'"),
                number_format($penalized, 2, ',', "'") . ' kVArh',
                number_format($term, 4, ',', "'") . ' €',
                number_format($price, 2, ',', "'") . ' €',
            ];
        }

        $rows[] = ['TOTAL', '', '', '', '', number_format($totalInductive, 2, ',', "'") . ' €'];



        // FACTURA TOTAL
        $rows[] = ['']; // espacio
        $rows[] = ['FACTURA TOTAL'];
        $rows[] = ['Concepto', 'Importe'];
        $rows[] = ['Consumo, potencia y ajustes', number_format($this->resume->total->consumption[0], 2, ',', "'") . ' €'];
        $rows[] = ['Impuestos', number_format($this->resume->total->taxes[0], 2, ',', "'") . ' €'];
        $rows[] = ['Base imponible', number_format($this->resume->total->taxBase[0], 2, ',', "'") . ' €'];
        $rows[] = ['IVA', number_format($this->resume->total->iva[0], 2, ',', "'") . ' €'];
        $rows[] = ['TOTAL', number_format($this->resume->total->total[0], 2, ',', "'") . ' €'];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A:C')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(14);
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;


                // Fusionar y centrar títulos
                $sheet->mergeCells('A2:G2');  // POTENCIA
                $sheet->mergeCells('A12:E12'); // CONSUMO
                $sheet->mergeCells('A19:F19'); // INDUCTIVA
                $sheet->mergeCells('A26:B26'); // TOTAL

                // Centrar y aplicar estilo de título
                foreach (['A2', 'A12', 'A19', 'A26'] as $cell) {
                    $sheet->getStyle($cell)->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    $sheet->getStyle($cell)->getFont()->setBold(true)->setSize(12);
                }


                // Autoajuste de columnas
                foreach (range('A', 'G') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }


                // Centramos todo el contenido
                $sheet->getStyle('A1:G100')->getAlignment()
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


                // Bordes y fondos de cabecera
                $headerStyle = [
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => 'FF000000'],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFEFEFEF'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'FFBBBBBB'],
                        ],
                    ],
                ];

                // Cabeceras de cada bloque
                $sheet->getStyle('A3:G3')->applyFromArray($headerStyle);   // Cabecera POTENCIA
                $sheet->getStyle('A13:E13')->applyFromArray($headerStyle); // Cabecera CONSUMO
                $sheet->getStyle('A20:F20')->applyFromArray($headerStyle); // Cabecera INDUCTIVA
                $sheet->getStyle('A27:B27')->applyFromArray($headerStyle); // Cabecera INDUCTIVA


                // Aplica negrita a las filas de totales
                $sheet->getStyle('A10:G10')->getFont()->setBold(true); // TOTAL de potencia
                $sheet->getStyle('A17:E17')->getFont()->setBold(true); // TOTAL de consumo
                $sheet->getStyle('A24:F24')->getFont()->setBold(true); // TOTAL de inductiva
                $sheet->getStyle('A32:B32')->getFont()->setBold(true); // TOTAL factura total

            }

        ];
    }

}

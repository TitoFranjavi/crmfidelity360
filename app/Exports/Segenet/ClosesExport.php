<?php

namespace App\Exports\Segenet;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClosesExport implements FromArray, WithStyles, WithColumnWidths, WithTitle
{
    protected $close;

    public function __construct($close)
    {
        $this->close = $close;
    }

    public function array(): array
    {
        $rows = [];

        // 🔹 Encabezados principales
        $rows[] = [
            '', 'En. Activa', '', 'En. Inductiva', '', '', 'En. Capacitiva', '', '', 'Potencia', ''
        ];

        // 🔹 Subencabezados
        $rows[] = [
            '', 'Absoluta', 'Incremental',
            'Absoluta', 'Incremental', 'Coseno',
            'Absoluta', 'Incremental', 'Coseno',
            'Máximetro', 'Ocurrencia'
        ];

        // 🔹 Helpers de formato
        $fmt = function ($val, $unit) {
            $num = (float)($val ?? 0);
            // si es entero, sin decimales
            if (fmod($num, 1) == 0) {
                $formatted = number_format($num, 0, ',', '.'); // puntos para miles
            } else {
                $formatted = number_format($num, 3, ',', '.'); // 3 decimales si hay parte decimal
            }
            return "{$formatted} {$unit}";
        };

        $fmtCos = fn($val) => number_format((float)($val ?? 1.0), 3, ',', '.'); // solo coseno con 3 decimales

        // 🔹 Periodos (P1–P6)
        foreach ($this->close->periods as $p => $d) {
            $rows[] = [
                "P{$p}",
                $fmt($d['active_abs'] ?? 0, 'kWh'),
                $fmt($d['active_inc'] ?? 0, 'kWh'),

                $fmt($d['inductive_abs'] ?? 0, 'kVArh'),
                $fmt($d['inductive_inc'] ?? 0, 'kVArh'),
                $fmtCos($d['inductive_cosine_of_phi'] ?? 1.0),

                $fmt($d['capacitive_abs'] ?? 0, 'kVArh'),
                $fmt($d['capacitive_inc'] ?? 0, 'kVArh'),
                $fmtCos($d['capacitive_cosine_of_phi'] ?? 1.0),

                $fmt($d['maximeter'] ?? 0, 'kW'),
                $d['maximeter_ocurrency'] ?? ''
            ];
        }

        // 🔹 Fila TOTAL
        $rows[] = [
            'TOTAL',
            $fmt($this->close->general['active_abs'] ?? 0, 'kWh'),
            $fmt($this->close->general['active_inc'] ?? 0, 'kWh'),

            $fmt($this->close->general['inductive_abs'] ?? 0, 'kVArh'),
            $fmt($this->close->general['inductive_inc'] ?? 0, 'kVArh'),
            $fmtCos($this->close->general['inductive_cosine_of_phi'] ?? 1.0),

            $fmt($this->close->general['capacitive_abs'] ?? 0, 'kVArh'),
            $fmt($this->close->general['capacitive_inc'] ?? 0, 'kVArh'),
            $fmtCos($this->close->general['capacitive_cosine_of_phi'] ?? 1.0),

            $fmt($this->close->general['maximeter'] ?? 0, 'kW'),
            $this->close->general['maximeter_ocurrency'] ?? ''
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Fusionar encabezados principales
        $sheet->mergeCells('B1:C1');
        $sheet->mergeCells('D1:F1');
        $sheet->mergeCells('G1:I1');
        $sheet->mergeCells('J1:K1');

        // Negrita encabezados
        $sheet->getStyle('A1:K2')->getFont()->setBold(true);

        // Centrados
        $sheet->getStyle('A1:K2')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1:K2')->getAlignment()->setVertical('center');

        // Bordes
        $sheet->getStyle('A1:K9')->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Fondo gris encabezado
        $sheet->getStyle('A1:K2')->getFill()
            ->setFillType('solid')
            ->getStartColor()->setARGB('EEEEEE');

        // Negrita para la fila TOTAL
        $sheet->getStyle('A9:K9')->getFont()->setBold(true);

        // Alinear texto a la derecha para números
        $sheet->getStyle('B3:J9')->getAlignment()->setHorizontal('right');

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 14,
            'C' => 14,
            'D' => 14,
            'E' => 14,
            'F' => 10,
            'G' => 14,
            'H' => 14,
            'I' => 10,
            'J' => 14,
            'K' => 22,
        ];
    }

    public function title(): string
    {
        return 'Resumen cierre';
    }
}

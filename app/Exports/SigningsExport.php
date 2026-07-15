<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Http\Models\Signin;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SigningsExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{

    protected $startDate;
    protected $endDate;
    protected $employeeIds;
    protected $activity_sections;
    public function __construct($startDate = null, $endDate = null, $employeeIds = [], $activity_sections = [])
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->employeeIds = $employeeIds;
        $this->activity_sections = $activity_sections;
    }

    public function collection()
    {
        $query = Signin::with('user');

        if ($this->startDate) {
            $query->where('date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->where('date', '<=', $this->endDate);
        }

        if (!empty($this->activity_sections)) {
            $query->whereIn('activity_section', $this->activity_sections);
        }

        if (!empty($this->employeeIds)) {
            $query->whereIn('user_id', $this->employeeIds);
        }

        $signins = $query->get();

        $totalMinutes = 0;

        $data = $signins->map(function ($signin) use (&$totalMinutes) {
            $mapped = $this->map($signin);

            // Calcular minutos totales (para la suma)
            if ($signin->entry && $signin->exit) {
                try {
                    $entry = Carbon::createFromFormat('H:i', $signin->entry);
                    $exit = Carbon::createFromFormat('H:i', $signin->exit);

                    if ($exit->lt($entry)) {
                        $exit->addDay();
                    }

                    $totalMinutes += $entry->diffInMinutes($exit);
                } catch (\Exception $e) {
                    // ignorar errores de formato
                }
            }

            return $mapped;
        });

        // 🧾 Calcular horas totales acumuladas
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        $formattedTotal = sprintf('%d h %02d min', $hours, $minutes);

        // ➕ Añadir fila final de total
        $data->push([
            '', '', '', 'TOTAL HORAS', $formattedTotal, '', ''
        ]);

        return $data;
    }

    public function headings(): array
    {
        return [
            'Empleado',
            'Fecha',
            'Hora de entrada',
            'Hora de salida',
            'Horas totales',
            'Tramos horarios',
            'Notas',
        ];
    }

    public function map($signin): array
    {

        $totalHours = '';
        if ($signin->entry && $signin->exit) {
            try {
                $entry = Carbon::createFromFormat('H:i', $signin->entry);
                $exit = Carbon::createFromFormat('H:i', $signin->exit);

                // Si la salida es menor que la entrada → cruzó medianoche
                if ($exit->lt($entry)) {
                    $exit->addDay();
                }

                $diffMinutes = $entry->diffInMinutes($exit);
                $hours = floor($diffMinutes / 60);
                $minutes = $diffMinutes % 60;

                $totalHours = sprintf('%d h %02d min', $hours, $minutes);
            } catch (\Exception $e) {
                $totalHours = '-';
            }
        } else {
            $totalHours = '-';
        }
        
        return [
            ($signin->user->firstName ?? '') . ' ' . ($signin->user->lastName ?? ''),
            $signin->date,
            $signin->entry,
            $signin->exit,
            $totalHours,
            str_replace("\n", ' | ', implode(', ', $this->parseActivitySections($signin->activity_sections ?? []))),
            $signin->notes ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Encabezado en negrita y centrado
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => '4F81BD']
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center'
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => '999999']
                ]
            ],
        ]);

        // Bordes y formato para todas las celdas con datos
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $sheet->getStyle("A2:{$highestColumn}{$highestRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => 'DDDDDD']
                ]
            ],
            'alignment' => [
                'vertical' => 'center',
            ],
        ]);

        // Fila de encabezado más alta
        $sheet->getRowDimension(1)->setRowHeight(22);

        return [];
    }

    private function parseActivitySections($rawSections)
    {
        // Si viene como string, lo convertimos en array separando por coma
        if (is_string($rawSections)) {
            $parts = array_map('trim', explode(',', $rawSections));
        } elseif (is_array($rawSections)) {
            $parts = [];
            foreach ($rawSections as $s) {
                if (is_string($s)) {
                    $parts = array_merge($parts, array_map('trim', explode(',', $s)));
                }
            }
        } else {
            return [];
        }

        $tramos = [];
        for ($i = 0; $i < count($parts); $i += 2) {
            $hora = $parts[$i] ?? '';
            $desc = $parts[$i + 1] ?? '';
            if ($hora || $desc) {
                $tramos[] = trim($hora . ', ' . $desc);
            }
        }

        return $tramos;
    }
}

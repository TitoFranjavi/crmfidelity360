<?php

namespace App\Exports\Segenet;


use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProbeValuesQuartersExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    // 🔹 Filas de datos
    public function array(): array
    {
        // Aseguramos que los valores están en el mismo orden que los encabezados
        return array_map(function ($row) {
            return [
                $row['id'],
                $row['probe_serial'],
                $row['active'],
                $row['active_quality'],
                $row['active_out'],
                $row['active_out_quality'],
                $row['inductive'],
                $row['inductive_quality'],
                $row['capacitive'],
                $row['capacitive_quality'],
                $row['reactive2'],
                $row['reactive2_quality'],
                $row['reactive3'],
                $row['reactive3_quality'],
                $row['relative_active'],
                $row['relative_inductive'],
                $row['relative_capacitive'],
                $row['cosphi_inductive'],
                $row['cosphi_capacitive'],
                $row['inserted_at'],
                $row['real_inserted_at'],
                $row['exported_absolute'],
                $row['exported_relative'],
                $row['reserve1'],
                $row['reserve1_quality'],
                $row['reserve2'],
                $row['reserve2_quality'],
            ];
        }, $this->data);
    }

    // 🔹 Encabezados personalizados (en el orden exacto)
    public function headings(): array
    {
        return [
            'ID',
            'Probe Serial',
            'Active',
            'Active Quality',
            'Active Out',
            'Active Out Quality',
            'Inductive',
            'Inductive Quality',
            'Capacitive',
            'Capacitive Quality',
            'Reactive 2',
            'Reactive 2 Quality',
            'Reactive 3',
            'Reactive 3 Quality',
            'Relative Active',
            'Relative Inductive',
            'Relative Capacitive',
            'CosPhi Inductive',
            'CosPhi Capacitive',
            'Inserted At',
            'Real Inserted At',
            'Exported Absolute',
            'Exported Relative',
            'Reserve 1',
            'Reserve 1 Quality',
            'Reserve 2',
            'Reserve 2 Quality',
        ];
    }

    // 🔹 Estilos (negrita en la cabecera)
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

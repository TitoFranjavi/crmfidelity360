<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ContractsExport implements FromCollection, WithHeadings, WithEvents
{
    protected $rows;
    protected $headings;

    public function __construct($rows, array $headings)
    {
        $this->rows     = $rows;
        $this->headings = $headings;
    }

    public function collection()
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // A1:E1 asume que tienes 5 columnas; ajústalo si cambian
                $event->sheet->getStyle('A1:E1')->applyFromArray([
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E90FF'], // azul
                    ],
                ]);
            },
        ];
    }
}

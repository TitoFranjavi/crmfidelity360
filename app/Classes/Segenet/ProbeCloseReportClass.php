<?php

namespace App\Classes\Segenet;

/**
 * Clase para representar un "Close" con sus periodos agrupados.
 * Cada instancia contiene los datos generales del cierre + sus periodos.
 */
class ProbeCloseReportClass
{
    public $close_id;
    public $serial;
    public $file_path;
    public $from_date;
    public $to_date;
    public $status_name;
    public $status_color;
    public $status_icon;
    public $status_id;
    public $general = [];
    public $periods = [];

    public function __construct()
    {
        // No hace falta inicializar nada por defecto,
        // se irá llenando con addClosePeriod().
    }

    /**
     * Añade un registro de close_period al objeto actual.
     * El primer registro que llega también inicializa los datos generales del cierre.
     */
    public function addClosePeriod($close)
    {
        // 🟢 Inicializar datos generales (solo una vez)
        $this->serial = $this->serial ?? $close['probe_serial'];
        $this->from_date = $this->from_date ?? $close['from_date'];
        $this->to_date = $this->to_date ?? $close['to_date'];
        $this->file_path = $this->file_path ?? $close['file_path'];
        $this->status_name = $this->status_name ?? $close['status_name'];
        $this->status_color = $this->status_color ?? $close['status_color'];
        $this->status_icon = $this->status_icon ?? $close['status_icon'];
        $this->status_id = $this->status_id ?? $close['status_id'];
        $this->close_id = $this->close_id ?? $close['close_id'];

        // 🧹 Quitamos los campos generales del array del periodo
        unset(
            $close['probe_serial'],
            $close['close_id'],
            $close['from_date'],
            $close['to_date'],
            $close['file_path'],
            $close['status_color'],
            $close['status_icon'],
            $close['status_id'],
            $close['status_name']
        );

        // 🧩 Clasificamos los datos: general o por periodo
        if (isset($close['period']) && $close['period'] == 0) {
            // Periodo 0 → Totales generales
            $this->general = $close;
        } else {
            // Periodos P1, P2, P3...
            $this->periods[$close['period']] = $close;
            ksort($this->periods); // orden ascendente de periodos
        }
    }
}

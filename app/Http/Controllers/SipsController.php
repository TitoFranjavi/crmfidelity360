<?php

namespace App\Http\Controllers;

use App\Http\Models\ConsumosGas;
use App\Http\Models\ConsumosLuz;
use App\Http\Models\PsGas;
use App\Http\Models\PsLuz;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SipsController extends Controller
{
    public static function getGasCupsByAddress(Request $request){
        $zipCode = str_pad($request['zipCode'], 5, "0", STR_PAD_LEFT);

        //Normalizo la dirección
        $address = mb_strtolower($request['address'], 'UTF-8');
        $address = preg_replace('/[áàäâ]/u', 'a', $address);
        $address = preg_replace('/[éèëê]/u', 'e', $address);
        $address = preg_replace('/[íìïî]/u', 'i', $address);
        $address = preg_replace('/[óòöô]/u', 'o', $address);
        $address = preg_replace('/[úùüû]/u', 'u', $address);
        $address = preg_replace('/[ñ]/u', 'n', $address);
        $address = preg_replace('/[.,]/', '', $address);
        $address = preg_replace('/\s+/', '', $address);
        $address = trim($address);

        $supplies = PsGas::where('codigoPostalPS', $zipCode)->get();

        //Compruebo que suministros cumplen la dirección
        $results = [];
        foreach ($supplies as $supplyInd => $supply) {
            $supplyAddress = $supply['viaPS'].ltrim($supply['numFincaPS'],'0').",".ltrim($supply['portalPS'], '0').$supply['escaleraPS'].ltrim($supply['pisoPS'],'0').ltrim($supply['puertaPS'],'0');

            //Normalizo la dirección
            $supplyAddress = html_entity_decode($supplyAddress, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $supplyAddress = mb_strtolower($supplyAddress, 'UTF-8');
            $supplyAddress = preg_replace('/[áàäâ]/u', 'a', $supplyAddress);
            $supplyAddress = preg_replace('/[éèëê]/u', 'e', $supplyAddress);
            $supplyAddress = preg_replace('/[íìïî]/u', 'i', $supplyAddress);
            $supplyAddress = preg_replace('/[óòöô]/u', 'o', $supplyAddress);
            $supplyAddress = preg_replace('/[úùüû]/u', 'u', $supplyAddress);
            $supplyAddress = preg_replace('/[ñ]/u', 'n', $supplyAddress);
            $supplyAddress = preg_replace('/[., ]/', '', $supplyAddress);
            $supplyAddress = preg_replace('/\s+/', '', $supplyAddress);

            //Compruebo que la direccion introducida coincida con la del suministro
            if (str_contains($supplyAddress, $address)) {
                $results[] = $supply;
            }
        }

        return $results;
    }

    public static function getGasConsumption(Request $request){
        $cups = $request['CUPS'];

        $boundaryPoint = [
            ["boundary" => "0F", "checked" => false],
            ["boundary" => "1P", "checked" => false],
            ["boundary" => "", "checked" => false],
        ];

        $data = [];
        $boundary = null;

        while(array_filter($boundaryPoint, fn($boundary) => !$boundary['checked'])){

            foreach ($boundaryPoint as $index => $point) {
                if (!$point['checked']) {
                    $boundaryPoint[$index]['checked'] = true;
                    $boundary = $cups . $boundaryPoint[$index]['boundary'];
                    break;
                }
            }

            //  Obtengo los datos
            $data = ConsumosGas::where('cups', $boundary)->orderBy('fechaFinMesConsumo', 'desc')->get()->toArray();

            //Acabo el bucle si recibo datos
            if(count($data) > 0) break;
        }

        //Calculo el consumo total anual
        $minDate = Carbon::createFromFormat('Y-m-d',$data[0]['fechaFinMesConsumo'])->subYear();
        $consumption = [0,0];
        $consumptionIntervals = [];
        foreach($data as $consumptionRead){
            $endDate = Carbon::createFromFormat("Y-m-d",$consumptionRead["fechaFinMesConsumo"]);
            $startDate = Carbon::createFromFormat("Y-m-d",$consumptionRead["fechaInicioMesConsumo"]);

            //Cambio los consumos de Wh a kWh
            $consumptionRead['consumoEnWhP1'] = $consumptionRead['consumoEnWhP1'] / 1000;
            $consumptionRead['consumoEnWhP2'] = $consumptionRead['consumoEnWhP2'] / 1000;

            //Si la lectura está entera en el año la sumo completa
            if($endDate->gt($minDate) && $startDate->gt($minDate)){
                $consumption[0] += $consumptionRead['consumoEnWhP1'];
                $consumption[1] += $consumptionRead['consumoEnWhP2'];
                $consumptionIntervals[] = $consumptionRead;
            //Si está parcial sumo la parte proporcional
            }else if($endDate->gt($minDate)){
                $readInterval = $endDate->diffInDays($startDate);
                $interval = $endDate->diffInDays($minDate);
                $consumption[0] += $consumptionRead['consumoEnWhP1'] / $readInterval * $interval;
                $consumption[1] += $consumptionRead['consumoEnWhP2'] / $readInterval * $interval;
                $consumptionIntervals[] = $consumptionRead;
            };
        }

        $supply = PsGas::where('cups', $boundary)->first();

        return response()->json(["consumptionData" => ["consumption" => $consumption, "consumptionIntervals" => $consumptionIntervals], "fee" => $consumptionIntervals[0]['codigoTarifaPeaje'], "supply" => $supply ], 200);
    }

    public static function getElectricityConsumption(Request $request){
        $cups = $request['CUPS'];

        $boundaryPoint = [
            ["boundary" => "0F", "checked" => false],
            ["boundary" => "1P", "checked" => false],
            ["boundary" => "", "checked" => false],
        ];

        $data = [];
        $boundary = null;

        while(array_filter($boundaryPoint, fn($boundary) => !$boundary['checked'])){

            foreach ($boundaryPoint as $index => $point) {
                if (!$point['checked']) {
                    $boundaryPoint[$index]['checked'] = true;
                    $boundary = $cups . $boundaryPoint[$index]['boundary'];
                    break;
                }
            }

            //  Obtengo los datos
            $data = ConsumosLuz::where('cups', $boundary)->orderBy('fechaFinMesConsumo', 'desc')->get()->toArray();

            //Acabo el bucle si recibo datos
            if(count($data) > 0) break;
        }

        $supply = PsLuz::where('cups', $boundary)->first()->toArray();

        $tarifaATR = match ($supply['codigoTarifaATREnVigor']) {
            '018' => '2.0TD',
            '019' => '3.0TD',
            '020' => '6.1TD',
            default => null,
        };

        return[
            'potenciasContratadasEnWP1' => $supply['potenciasContratadasEnWP1'],
            'potenciasContratadasEnWP2' => $supply['potenciasContratadasEnWP2'],
            'potenciasContratadasEnWP3' => $supply['potenciasContratadasEnWP3'],
            'potenciasContratadasEnWP4' => $supply['potenciasContratadasEnWP4'],
            'potenciasContratadasEnWP5' => $supply['potenciasContratadasEnWP5'],
            'potenciasContratadasEnWP6' => $supply['potenciasContratadasEnWP6'],
            'fechaUltimoCambioComercializador' => $supply['fechaUltimoCambioComercializador'],
            'tarifaATR' => $tarifaATR,
            'consumos' => $data
        ];

    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Models\Enterprise;
use App\Http\Resources\EnterpriseResource;
use App\Mail\SubscriptionConfirmed;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use MongoDB\BSON\UTCDateTime;

class EnterpriseController extends Controller
{
    public function show($id){
        $enterprise = Enterprise::where('subdomainUser', $id)->first();

        return new EnterpriseResource($enterprise);
    }

    //función para guardar en la bbdd
    public static function getSubscription(Request $request) {

        $plan = $request['plan'];
        $plans = config('plans');
        $isAnnual = filter_var($request['isAnnual'], FILTER_VALIDATE_BOOL);
        $iban = strtoupper(str_replace(' ', '', $request['iban']));
        $userSubdomain = $request['userSubdomain'];

        $enterprise = Enterprise::where('subdomainUser', $userSubdomain['_id'])->first();

        if (!$enterprise)
            return response()->json(['error' => 'Empresa no encontrada'], 404);


        // Guardar suscripción
        $enterprise->subscription = [
            'plan' => $plan['id'],
            'isAnnual' => $isAnnual,
            'startedAt' => new UTCDateTime(),
            'basic' => [
                'scans' => $plans[$plan['id']]['included']['scans']['amount'] ?? 0,
                'calls' => $plans[$plan['id']]['included']['calls']['amount'] ?? 0,
            ]
        ];


        // IBAN
        $shouldUpdate = false;

        if (!$enterprise->iban) {
            $shouldUpdate = true;
        }
        else {
            try {
                $existingIban = Crypt::decryptString($enterprise->iban);

                if ($existingIban !== $iban)
                    $shouldUpdate = true;

            } catch (\Exception $e) {
                $shouldUpdate = true;
            }
        }

        if ($shouldUpdate)
            $enterprise->iban = Crypt::encryptString($iban);

        $enterprise->save();


        //Parte PDF
        try {
            $billingCycle  = $isAnnual ? 'anual' : 'mensual';
            $startDate     = Carbon::now();
            $baseAmount    = $isAnnual ? ($plan['annualPrice'] ?? 0) : ($plan['price'] ?? 0);
            $vatAmount     = round($baseAmount * 0.21, 2);
            $totalAmount   = round($baseAmount + $vatAmount, 2);
            $invoiceNumber = 'ZOC-' . Carbon::now()->format('Ymd') . '-' . strtoupper(substr(md5($enterprise->_id), 0, 6));

            $features = array_values(array_map(fn($item) => $item['title'] ?? null, $plan['included']?? []));

            $pdf = Pdf::loadView('PDFs.zocoInvoice', [
                'companyName'   => $enterprise->name,
                'plan'          => $plan['name'],
                'billingCycle'  => $billingCycle,
                'startDate'     => $startDate,
                'isAnnual'      => $isAnnual,
                'baseAmount'    => $baseAmount,
                'vatAmount'     => $vatAmount,
                'totalAmount'   => $totalAmount,
                'invoiceNumber' => $invoiceNumber,
                'features'      => $features ?? [],
                'iban'          => Crypt::decryptString($enterprise->iban)
            ])->setPaper('a4', 'portrait');

            $filename = 'factura_' . $invoiceNumber . '.pdf';
            Storage::disk('zocoInvoice')->put($filename, $pdf->output());

            // Obtener la ruta — usa el mismo disco
            $pdfPath = Storage::disk('zocoInvoice')->path($filename);

            Mail::to('franperez@segenet.es')//administracion@zocoenergia.com
            ->send(new SubscriptionConfirmed(
                companyName:  $enterprise->name,
                plan:         $plan['name'],
                billingCycle: $billingCycle,
                startDate:    $startDate,
                amount:       $totalAmount,
                pdfPath:      $pdfPath,
                iban:         Crypt::decryptString($enterprise->iban)
            ));

        } catch (\Exception $e) {
            // El correo/PDF no bloquea la respuesta al usuario
            dd('Error al generar PDF: ' . $e->getMessage());
        }


        return response()->json(['message' => 'Suscripción actualizada'], 200);
    }

    //función para comprobar si el subdominio tiene un iban ya guardado
    public static function checkHasIBAN(Request $request) {

        $userSubdomain = $request['userSubdomain'];

        $enterprise = Enterprise::where('subdomainUser', $userSubdomain)->first();

        return response()->json(['iban' => isset($enterprise->iban) ? Crypt::decryptString($enterprise->iban) : null], 200);
    }
}

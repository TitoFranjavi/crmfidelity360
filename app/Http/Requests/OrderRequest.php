<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Sabberworm\CSS\Rule\Rule;

class OrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }


    /**
     * Convierte los campos JSON (enviados dentro de FormData) a arrays
     * ANTES de aplicar las reglas.
     */
    protected function prepareForValidation(): void
    {
        $decode = function ($key) {
            $val = $this->input($key);
            if (is_string($val)) {
                $decoded = json_decode($val, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }
            return $val;
        };

        $order       = $decode('order');
        $account     = $decode('account');
        $userSubdom  = $decode('userSubdomain');
        $colors      = $decode('colors');
        $enterprise  = $decode('enterprise');


        $this->merge([
            'order'         => $order,
            'account'       => $account,
            'userSubdomain' => $userSubdom,
            'colors'        => $colors,
            'enterprise'    => $enterprise,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {

        $statusCode = (string) data_get($this->all(), 'order.newStatus.code', '');

        // Si es borrador -> no validamos nada
        if ($statusCode === 'bo' || $statusCode === 'an' || $statusCode === 's') {
            return [];
        }

        return [
            //Datos básicos del contrato
            'order.name'     => ['bail','required','string'],

            // Campos que pueden o no venir pero conviene tipar
            'order.commissions' => ['nullable', 'array'],
            'order.commissions.subdomain' => ['nullable', 'numeric', 'min:0'],
            'order.commissions.breakdown' => ['nullable', 'array'],
            'order.commissions.breakdown.*.userId' => ['required_with:order.commissions.breakdown', 'string'],
            'order.commissions.breakdown.*.level' => ['required_with:order.commissions.breakdown', 'numeric'],
            'order.commissions.breakdown.*.commission' => ['nullable', 'numeric', 'min:0'],

            // Estructuras
            'order.statuses'          => ['array'],
            'order.statuses.*.code'   => ['required','string'],
            'order.statuses.*.date'   => ['required','date'],
            'order.newStatus.code'    => ['nullable','string'],
            'order.verifications'     => ['nullable','array'],
            'order.verifications.*'   => ['string'],

            // Opcionales según producto
            'order.productType' => ['required','string'], // se activará condicional
            'order.product'     => ['required','string'],
            'order.fee'         => ['nullable','string'],
            'order.marketer'    => ['nullable','string'],
            'order.IBAN'        => ['nullable','string'],
            'order.CUPS'        => ['nullable','string'],// tu lógica marca 20
        ];
    }



    /**
     * Condiciones según estado, settings y tipo de producto.
     */
    public function withValidator($validator): void
    {

        $statusCode = (string) data_get($this->all(), 'order.newStatus.code', '');

        // Si es borrador, anulado o scoring -> no añadir reglas condicionales ni post-validación
        if (in_array($statusCode, ['bo','an','s'], true)) {
            return;
        }
        $productType  = (string) data_get($this->all(), 'order.productType', '');
        $isEnergy     = in_array($productType, ['cl','cg'], true);

        $settings     = (array) data_get($this->all(), 'userSubdomain.settings', []);
        $reqAddr      = !empty($settings['orderAddress']);
        $reqTown      = !empty($settings['orderTown']);
        $reqProv      = !empty($settings['orderProvince']);
        $reqPostal    = !empty($settings['orderPostal']);
        $reqIBANFlag  = !empty($settings['orderIBAN']);

        $verifs = (array) data_get($this->all(), 'order.verifications', []);
        $hasPC  = in_array('pc', $verifs, true); // cambio de potencia
        $hasNW  = in_array('nw', $verifs, true); // alta nueva

        // Campos base según settings
        $validator->sometimes('order.direc',    ['required','string'], fn() => $reqAddr);
        $validator->sometimes('order.town',     ['required','string'], fn() => $reqTown);
        $validator->sometimes('order.province', ['required','string'], fn() => $reqProv);
        $validator->sometimes('order.zip',      ['required','digits:5'], fn() => $reqPostal);

        // Activación
        $validator->sometimes('order.activationDate', ['required','date'], fn() => ($statusCode === 'a' || $statusCode === 'b'));
        $validator->sometimes('order.lowDate', ['required','date'], fn() => $statusCode === 'b');

        // Luz/Gas
        $validator->sometimes('order.fee',      ['required','string'], fn() => $isEnergy);
        $validator->sometimes('order.marketer', ['required','string'], fn() => $isEnergy);
        $validator->sometimes('order.CUPS', ['required','string'], fn() => $isEnergy);


        // IBAN si setting y es energía (no exigir en 'an')
        $validator->sometimes('order.IBAN', [
            'required',
            'string',
            function ($attribute, $value, $fail) {
                $iban = str_replace(' ', '', strtoupper($value));

                // Solo permitir exactamente ES0000
                if ($iban === 'ES0000') {
                    return;
                }

                // Validación IBAN real
                if (!\Illuminate\Support\Facades\Validator::make([$attribute => $value], [
                    $attribute => 'iban'
                ])->passes()) {
                    $fail('IBAN no válido');
                }
            }
        ], fn() => $isEnergy && $reqIBANFlag);

        // Cambio de potencia
        $validator->sometimes('order.currentPotencyVerification',  ['required','numeric'], fn() => $isEnergy && $hasPC);
        $validator->sometimes('order.requestedPotencyVerification', ['required','numeric'], fn() => $isEnergy && $hasPC);

        // Alta nueva
        $fee = (string) data_get($this->all(), 'order.fee', '');
        if ($hasNW && $productType === 'cl') {
            $maxP = ($fee === 'Tarifa 2.0TD') ? 2 : 6;
            for ($i = 1; $i <= $maxP; $i++) {
                $validator->sometimes("order.newRegistrationPeriods.p{$i}", ['required','numeric'], fn() => true);
            }
        }
        /*if ($hasNW && $productType === 'cg') {
            $validator->sometimes('order.newRegistrationPrices.fixedPrice',    ['required','numeric'], fn() => true);
            $validator->sometimes('order.newRegistrationPrices.variablePrice', ['required','numeric'], fn() => true);
        }*/

        // Decomisiones en baja
        $statusCodes = ['b', 'pendiente_de_retrocomisin'];
        $inValidStatuses = fn() => in_array($statusCode, $statusCodes);

        $validator->sometimes('order.decommissions', ['required', 'array'], $inValidStatuses);
        $validator->sometimes('order.decommissions.subdomain', ['nullable', 'numeric', 'min:0'], $inValidStatuses);
        $validator->sometimes('order.decommissions.breakdown', ['nullable', 'array'], $inValidStatuses);
        $validator->sometimes('order.decommissions.breakdown.*.userId', ['nullable', 'string'], $inValidStatuses);
        $validator->sometimes('order.decommissions.breakdown.*.level', ['nullable', 'numeric'], $inValidStatuses);
        $validator->sometimes('order.decommissions.breakdown.*.commission', ['nullable', 'numeric', 'min:0'], $inValidStatuses);
    }

    public function messages(): array
    {
        return [
            'order.name.required'     => 'El nombre del contrato es obligatorio.',
            'order.name.string'       => 'El nombre del contrato debe ser texto válido.',

            'order.direc.required'    => 'La dirección de suministro es obligatoria.',
            'order.town.required'     => 'La población es obligatoria.',
            'order.province.required' => 'La provincia es obligatoria.',

            'order.zip.required'      => 'El código postal es obligatorio.',
            'order.zip.digits'        => 'El código postal debe tener exactamente 5 dígitos.',

            'order.commissions.subdomain.numeric' => 'La comisión del subdominio debe ser un número.',
            'order.commissions.subdomain.min' => 'La comisión del subdominio debe ser un positiva.',
            'order.commissions.breakdown.*.commission.numeric' => 'La comisión de ventas debe ser un número.',
            'order.commissions.breakdown.*.commission.min' => 'La comisión de ventas debe ser positiva.',

            'order.statuses.array'            => 'Los estados deben enviarse en formato de lista.',
            'order.statuses.*.code.required'  => 'Cada estado debe incluir un código.',
            'order.statuses.*.date.required'  => 'Cada estado debe incluir una fecha válida.',

            'order.productType.required' => 'El tipo de producto es obligatorio.',
            'order.product.required'     => 'El producto es obligatorio.',

            'order.CUPS.size'            => 'El CUPS debe tener exactamente 20 caracteres.',
            'order.IBAN.size'            => 'El IBAN debe tener 29 caracteres válidos.',
        ];
    }

}

<?php

namespace App\Helpers;

use App\Http\Models\Marketer;

use App\Http\Controllers\AuthController;
use Carbon\Carbon;
class ProductHelper
{



    public static function getPricesForContract(array $contract)
    {
        $marketerName = $contract['marketer'] ?? null;
        $productName  = $contract['product'] ?? null;
        $feeName      = $contract['fee'] ?? null;

        if (!$marketerName || !$productName) {
            return null;
        }

        // Usuario subdominio del usuario logueado
        $userListTop = AuthController::getAllSuperiors(auth()->user()->_id);

        $userSubdomain = collect($userListTop)
            ->first(fn ($user) => ($user['label'] ?? '') === 'Usuario subdominio');

        if (!$userSubdomain || empty($userSubdomain['_id'])) {
            return null;
        }

        $subdomainId = $userSubdomain['_id'];

        // Marketer del subdominio
        $marketer = Marketer::where('name', $marketerName)
            ->where('createdBy', $subdomainId)
            ->first();

        if (!$marketer) {
            return null;
        }

        // Luz o gas
        $productType = strtolower($contract['productType'] ?? '');
        $energyType  = ($productType === 'cg' || $productType === 'gas')
            ? 'gas'
            : 'electricity';

        $products = $marketer->products[$energyType] ?? [];
        $product  = collect($products)->firstWhere('name', $productName);

        if (!$product) {
            return null;
        }

        // Mapear nombre de tarifa -> índice
        $feeIndexes = [
            'Tarifa 2.0TD' => 0,
            'Tarifa 3.0TD' => 1,
            'Tarifa 6.1TD' => 2,
        ];

        if (!isset($feeIndexes[$feeName])) {
            // No coincide ninguna tarifa conocida
            return null;
        }

        $indexFees = $feeIndexes[$feeName];

        $fees = $product['fees'] ?? [];
        $fee  = $fees[$indexFees] ?? null;

        if (!$fee) {
            return null;
        }

        return [
            'prices' => $fee['prices'] ?? null, // aquí dentro están power y consume
        ];
    }


    public static function getRenewalDateFromMarketer( array $order, ?array $oldOrder = null, ?array $userSubdomain = null): ?string {
        $marketerName = $order['marketer'] ?? ($oldOrder['marketer'] ?? null);
        $activation   = $order['activationDate'] ?? ($oldOrder['activationDate'] ?? null);

        if (!$marketerName || !$activation) {
            return null;
        }

        $subdomainId = $userSubdomain['_id'] ?? (auth()->user()->userSubdomain['_id'] ?? null);

        $query = Marketer::where('name', $marketerName);
        if ($subdomainId) {
            $query->where('createdBy', (string) $subdomainId);
        }

        $marketer = $query->first();
        if (!$marketer) {
            return null;
        }

        $clientType = $order['clientType'] ?? ($oldOrder['clientType'] ?? 'residencial');

        if ($clientType === 'pyme') {
            $days = (int) ($marketer->rCommissionPyme ?? 0);
        } else {
            $days = (int) ($marketer->rCommissionRes ?? 0);
        }

        if ($days <= 0) {
            return null;
        }

        $activationDate = Carbon::parse($activation)->startOfDay();
        $renewalDate    = $activationDate->copy()->addDays($days);

        return $renewalDate->format('Y-m-d');
    }
}


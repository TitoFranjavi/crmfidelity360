<?php

namespace App\Http\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Jenssegers\Mongodb\Eloquent\Model as Model;
use Laravel\Sanctum\HasApiTokens;
use Database\Factories\OrderFactory;

class Order extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    use Authenticatable;

    protected $connection = "mongodb";
    public $timestamps = false;


    protected $guarded = [];
    /*protected $fillable = [
        'name',
        'direc',
        'zip',
        'town',
        'province',
        'source',
        'observations',
        'incidence',
        'transferDate',
        'processingDate',
        'activationDate',
        'lowDate',
        'liquidationStatus',
        'productType',
        'marketer',
        'fee',
        'product',
        'CUPS',
        'consumption',
        'IBAN',
        'docs',
        'statuses',
        'usersIds',
        'consumptionData',
        'hiredPotency',
        'isReminderOnBool',
        'isReminderOn',
        'remainderChanged',
        'renewalOption',
        'renewalDate',
        'lastUpdate',
        'account',
        'createdBy',
        'eventId',
        'assignedToZoco',
        'marketerPercentage',
        'naturgyCallSID',
        'naturgyOrderNumber',
        'potencyFees',
        'consumptionFee',
        'energyFees',
        'potencyFee',
        'verifications',
        'whatsPhone',
        'versions',
        'requestedPotencyVerification',
        'currentPotencyVerification',
        'newRegistrationPeriods',
        'errors'
    ];*/

    protected static function newFactory()
    {
        return OrderFactory::new();
    }


}

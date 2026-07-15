<?php

namespace App\Console\Commands;

use App\Http\Models\Account;
use App\Http\Models\Comparative;
use App\Http\Models\Event;
use App\Http\Models\Liquidation;
use App\Http\Models\Log;
use App\Http\Models\Opportunity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Http\Models\User;
use App\Http\Models\Enterprise;
use App\Http\Models\Marketer;
use App\Http\Models\Order;
use App\Helpers\UserHelper;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class ExportFidelity360Data extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:export-fidelity-360-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exportar datos filtrados de Fidelity360 de MongoDB en archivos JSON';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $mainUserId = '6909faa9232c09035a03f3b2';

        $mainUser = User::where('_id', $mainUserId)->first();

        if (!$mainUser) {
            $this->error('No se ha encontrado el usuario principal');
            return Command::FAILURE;
        }

        $mainUserArray = $mainUser->toArray();

        /*
        * Carpeta donde se guardarán los JSON
        */
        $folder = 'exports/fidelity360';


        /*
         * 1. Saco usuarios del subdominio
         */
        $userList = UserHelper::getSubdomainUserList($mainUserArray);

        $userIds = collect($userList)
            ->pluck('_id')
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->values()
            ->toArray();

        $this->exportJson($folder . '/crm_users.json', $userList);


        /*
         * 2. Exportar enterprise
         */
        $enterprise = Enterprise::where('subdomainUser', $mainUserId)->first()->toArray();

        if (!$enterprise) {
            $this->warn('No se ha encontrado enterprise para el usuario principal: ' . $mainUserId);
        } else {
            $this->exportJson($folder . '/crm_enterprises.json', [$enterprise]);
        }


        /*
         * 3.   Exportar comercializadoras
         * */

        $marketers = Marketer::where('createdBy', $mainUserId)->get()->toArray();
        $this->exportJson($folder . '/crm_marketers.json', $marketers);


        /*
         * 4. Exportar cuentas
         * */

        $accounts = Account::whereIn('usersIds', $userIds)->orWhereIn('createdBy', $userIds)->get()->toArray();
        $this->exportJson($folder . '/crm_accounts.json', $accounts);


        /*
         * 5. Exporto contratos
         * */

        $orders = Order::whereIn('usersIds', $userIds)->orWhereIn('createdBy', $userIds)->get()->toArray();
        $this->exportJson($folder . '/crm_orders.json', $orders);

        /*
         * 6. Exporto oportunidades
         * */

        $opportunities = Opportunity::whereIn('usersIds', $userIds)->orWhereIn('createdBy', $userIds)->get()->toArray();
        $this->exportJson($folder . '/crm_opportunities.json', $opportunities);


        /*
         * 7. Exporto logs
         * */

        $logs = Log::whereIn('createdBy', $userIds)->get()->toArray();
        $this->exportJson($folder . '/crm_logs.json', $logs);


        /*
         * 8. Exporto liquidaciones
         * */

        $liquidations = Liquidation::whereIn('owner', $userIds)->get()->toArray();
        $this->exportJson($folder . '/crm_liquidations.json', $liquidations);


        /*
         * 9. Exporto eventos
         * */

        $events = Event::whereIn('createdBy', $userIds)->get()->toArray();
        $this->exportJson($folder . '/crm_events.json', $events);


        /*
         * 10. Exporto comparativas
         * */

        $comparatives = Comparative::whereIn('createdBy', $userIds)->get()->toArray();
        $this->exportJson($folder . '/crm_comparatives.json', $comparatives);


        return Command::SUCCESS;
    }

    private function exportJson(string $path, array $data): void
    {
        $normalizedData = $this->normalizeForJson($data);

        Storage::disk('local')->put(
            $path,
            json_encode(
                $normalizedData,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );

        $this->info('Archivo creado: storage/app/' . $path . ' | Registros: ' . count($data));
    }

    private function normalizeForJson($value)
    {
        if ($value instanceof ObjectId) {
            return (string) $value;
        }

        if ($value instanceof UTCDateTime) {
            return $value->toDateTime()->format('Y-m-d H:i:s');
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->normalizeForJson($item), $value);
        }

        return $value;
    }
}

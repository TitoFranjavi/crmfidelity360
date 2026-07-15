<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Models\User;
use App\Http\Models\Account;
use App\Http\Models\Order;
use App\Http\Models\Contact;
use App\Http\Models\Opportunity;

class GenerateDemoData extends Command
{
    protected $signature = 'demo:generate';
    protected $description = 'Genera datos demo para demo@zocoenergia.com';

    public function handle()
    {
        $this->info('Generando demo avanzada...');

        // 1. Usuario demo (jefe)
        $demo = User::where('email', 'demo@zocoenergia.com')->first();

        if (!$demo) {
            $this->error(' No existe el usuario demo');
            return;
        }

        $demoId = (string) $demo->_id;

        //  LIMPIAR SOLO DATOS DEL DEMO
        Order::where('createdBy', $demoId)->delete();
        Account::where('createdBy', $demoId)->delete();
        Opportunity::where('createdBy', $demoId)->delete();
        Contact::where('createdBy', $demoId)->delete();
        User::where('createdBy', $demoId)->delete();

        $demo->save();

        $this->info('🧹 Datos antiguos eliminados');

        //  2. CREAR USUARIOS (comerciales)
        $users = User::factory()
            ->count(5)
            ->create()
            ->each(function ($user) use ($demoId) {

                $user->label = 'Comercial';

                //  CLAVE → demo es su responsable
                $user->responsibles = [$demoId];

                $user->save();
            });

        $this->info('👥 Usuarios creados: ' . $users->count());

        //  3. CREAR CUENTAS
        $accounts = Account::factory()->count(20)->create();

        foreach ($accounts as $account) {

            $accountId = (string) $account->_id;

            //  usuario principal aleatorio
            $assignedUser = $users->random();
            $assignedUserId = (string) $assignedUser->_id;

            //  a veces también demo (visión global)
            $usersIds = [$assignedUserId];

            /*if (rand(0, 1)) {
                $usersIds[] = $demoId;
            }*/

            //  ACCOUNT
            $account->usersIds = $usersIds;
            $account->createdBy = $demoId;
            $account->save();

            //  USER → ACCOUNT (comercial)
            $accountsUser = $assignedUser->accounts ?? [];
            if (!is_array($accountsUser)) {
                $accountsUser = [];
            }

            $accountsUser[] = $accountId;
            $assignedUser->accounts = array_values(array_unique($accountsUser));
            $assignedUser->save();

            //  DEMO → ACCOUNT
            $accountsDemo = $demo->accounts ?? [];
            if (!is_array($accountsDemo)) {
                $accountsDemo = [];
            }

            $accountsDemo[] = $accountId;
            $demo->accounts = array_values(array_unique($accountsDemo));
            $demo->save();

            //  4. CONTRATOS
            Order::factory()
                ->count(rand(2, 5))
                ->create([
                    'account' => $accountId,

                    //  usuarios del contrato
                    'usersIds' => $usersIds,

                    //  quien lo crea (demo = jefe)
                    'createdBy' => $demoId,
                ]);
        }

        $this->info(' Demo generada correctamente');
        $this->line(" Demo: {$demo->email}");
        $this->line(" Comerciales: 5");
        $this->line(" Cuentas: 20");
        $this->line(" Contratos: ~60-100");

        return Command::SUCCESS;
    }
}

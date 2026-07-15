<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Http\Models\Signin;
use Illuminate\Console\Command;

class CloseOldSignings extends Command
{
    protected $signature = 'signings:close-old';
    protected $description = 'Marca como "No registrada" las salidas olvidadas después de 12 horas.';

    public function handle()
    {
        $now = Carbon::now();

        // Buscar fichajes con entrada pero sin salida
        $signins = Signin::whereNull('exit')
            ->whereNotNull('entry')
            ->get();

        $count = 0;

        foreach ($signins as $signin) {
            $entryTime = Carbon::parse($signin->date . ' ' . $signin->entry);
            $diff = $entryTime->diffInHours($now);

            if ($diff >= 12) {
                $signin->exit = 'No registrada';
                $signin->exit_location = null;
                $signin->save();
                $count++;
            }
        }

        $this->info("✅ $count fichajes antiguos actualizados correctamente.");
    }
}

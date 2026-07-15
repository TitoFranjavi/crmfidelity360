<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sips')->create('consumos_gas', function (Blueprint $table) {
            $table->string('cups', 30);
            $table->date('fechaInicioMesConsumo');
            $table->date('fechaFinMesConsumo');
            $table->char('codigoTarifaPeaje', 2);
            $table->string('consumoEnWhP1', 30);
            $table->string('consumoEnWhP2', 30);
            $table->integer('caudalMedioEnWhdia');
            $table->integer('caudaMinimoDiario');
            $table->string('caudaMaximoDiario', 30);
            $table->smallInteger('porcentajeConsumoNocturno');
            $table->smallInteger('codigoTipoLectura');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('sips')->dropIfExists('consumos_gas');
    }
};

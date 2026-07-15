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
        Schema::connection('sips')->create('ps_gas', function (Blueprint $table) {
            $table->char('codigoEmpresaDistribuidora', 4);
            $table->string('nombreEmpresaDistribuidora', 50);
            $table->string('cups', 30);
            $table->char('codigoProvinciaPS', 2);
            $table->string('desProvinciaPS', 50);
            $table->char('codigoPostalPS', 5);
            $table->char('municipioPS', 5);
            $table->string('desMunicipioPS', 50);
            $table->string('tipoViaPS', 5)->nullable();
            $table->string('viaPS', 255)->nullable();
            $table->string('numFincaPS', 5)->nullable();
            $table->string('portalPS', 3)->nullable();
            $table->string('escaleraPS', 5)->nullable();
            $table->string('pisoPS', 3)->nullable();
            $table->string('puertaPS', 10)->nullable();
            $table->char('codigoPresion', 2);
            $table->char('codigoPeajeEnVigor', 2);
            $table->string('caudalMaximoDiarioEnWh', 15);
            $table->string('caudalHorarioEnWh', 15);
            $table->boolean('derechoTUR');
            $table->date('fechaUltimaInspeccion');
            $table->char('codigoResultadoInspeccion', 2);
            $table->char('tipoPerfilConsumo', 2);
            $table->string('codigoContador', 10)->nullable();
            $table->string('calibreContador', 15);
            $table->string('tipoContador', 15);
            $table->tinyInteger('propiedadEquipoMedida')->nullable();
            $table->tinyInteger('codigoTelemedida');
            $table->date('fechaUltimoMovimientoContrato')->nullable();
            $table->date('fechaUltimoCambioComercializador')->nullable();
            $table->char('informacionImpagos', 1)->nullable();
            $table->char('idTipoTitular', 2);
            $table->tinyInteger('idTitular')->nullable();
            $table->string('nombreTitular', 100)->nullable();
            $table->string('apellido1Titular', 100)->nullable();
            $table->string('apellido2Titular', 100)->nullable();
            $table->char('codigoProvinciaTitular', 2);
            $table->string('desProvinciaTitular', 30);
            $table->char('codigoPostalTitular', 5);
            $table->char('municipioTitular', 5);
            $table->string('desMunicipioTitular', 50);
            $table->string('tipoViaTitular', 5)->nullable();
            $table->string('viaTitular', 255)->nullable();
            $table->string('numFincaTitular', 5)->nullable();
            $table->string('portalTitular', 3)->nullable();
            $table->string('escaleraTitular', 5)->nullable();
            $table->string('pisoTitular', 3)->nullable();
            $table->string('puertaTitular', 10)->nullable();
            $table->boolean('esViviendaHabitual')->nullable();
            $table->tinyInteger('cnae')->nullable();
            $table->char('tipoCorrector', 2)->nullable();
            $table->tinyInteger('codigoAccesibilidadContador')->nullable();
            $table->tinyInteger('conectadoPlantaSatelite');
            $table->tinyInteger('pctd')->nullable();
            $table->tinyInteger('presionMedida')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('sips')->dropIfExists('ps_gas');
    }
};

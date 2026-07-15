<template>
  <section>

    <!-- Formulario CUPS -->
    <form class="form" @submit.prevent="runOptimizer">
      <div class="d-flex align-center" data-gap="20">
        <div class="d-flex column align-center">
          <div class="form-group d-flex align-center" data-gap="10">
            <label for="cups-optimizer">CUPS</label>
            <div class="input-group">
              <input
                id="cups-optimizer"
                ref="cupsInput"
                type="text"
                name="cups"
                placeholder="ES0000XXXXXXXXXXXXAB0F"
                v-model="cups"
                :disabled="loading"
              />
            </div>
          </div>
        </div>
        <button class="custom-button my-5 desktop-item h-40-px" data-size="medium" :disabled="loading">
          <span v-if="!loading">Optimizar</span>
          <span v-else><i class="fa-solid fa-spinner fa-spin"></i> Analizando...</span>
        </button>
      </div>
    </form>

    <div class="separator"></div>

    <!-- Resultado -->
    <div v-if="result" class="pb-40">

      <!-- Cabecera: Ahorro -->
      <!-- Selector de propuesta -->
      <div class="mt-20 mb-10 form">
        <p class="text text-center" data-weight="600">
          <i class="fa-solid fa-sliders opacity-6"></i>
          Tipo de optimización
        </p>

        <p class="text text-center opacity-6 mt-5" data-size="12">
          Seleccione qué propuesta quiere visualizar en la comparativa.
        </p>

        <div class="simulation-selector mt-15">
          <button
            type="button"
            :class="{ active: selectedOptimizationScenario === 'recommended' }"
            @click="setOptimizationScenario('recommended')"
          >
            Recomendada
          </button>

          <button
            type="button"
            :class="{ active: selectedOptimizationScenario === 'economic' }"
            @click="setOptimizationScenario('economic')"
          >
            Óptima económica
          </button>
        </div>

        <div class="selected-optimization-saving mt-15">
          <p class="text opacity-6" data-size="12">
            {{ selectedOptimizationTitle }}
          </p>

          <p
            v-if="selectedOptimization"
            class="text"
            data-size="28"
            data-weight="700"
            :style="{ color: selectedOptimizationColor }"
          >
            {{ fmt(selectedOptimization.saving) }} €
          </p>

          <p v-if="selectedOptimization" class="text opacity-6" data-size="12">
            {{ fmt(selectedOptimization.saving / Math.max(1, _monthlyReadings.length)) }} €/mes
          </p>

          <p class="text opacity-6 mt-8" data-size="11">
            {{ selectedOptimizationSubtitle }}
          </p>
        </div>
      </div>

      <div
        v-if="selectedOptimization.risk === 'high'"
        class="calc-warning mt-10 mb-10"
      >
        <i class="fa-solid fa-triangle-exclamation"></i>
        La óptima económica es agresiva: reduce mucho el término fijo, pero acepta más excesos de potencia.
      </div>

      <!-- Botón de informe -->
      <div class="d-flex justify-center mt-10 mb-10">
        <button
          class="custom-button"
          data-size="medium"
          data-variant="outline"
          :disabled="generatingPdf"
          @click="generateReport"
        >
          <span v-if="!generatingPdf">
            <i class="fa-solid fa-file-pdf"></i> Descargar informe PDF
          </span>
          <span v-else>
            <i class="fa-solid fa-spinner fa-spin"></i> Generando PDF...
          </span>
        </button>
      </div>

      <!-- Comparativa de costes -->
      <div class="d-flex" data-gap="20">

        <!-- Situación actual -->
        <div class="w-50 form">
          <p class="text text-center" data-weight="600">
            <i class="fa-solid fa-circle-xmark opacity-6"></i> Situación actual
          </p>

          <p class="text opacity-6 mt-10" data-size="12">Término de potencia (kW contratados × precio × meses)</p>
          <div class="period-breakdown mt-8">
            <div
              v-for="p in PERIODS"
              :key="'cur-breakdown-' + p"
              class="period-breakdown-row"
            >
              <span class="period-breakdown-label">{{ p }}</span>
              <span class="period-breakdown-formula">
                {{ fmtKw(result.current.powers[p]) }} kW
                × {{ fmtPrice(POWER_PRICE[p] * 30) }} €/kW·mes
                × 1,019 meses
              </span>
              <span class="period-breakdown-amount">
                {{ fmt(result.current.powers[p] * POWER_PRICE[p] * 30 * 1.019) }} €
              </span>
            </div>
          </div>

          <div class="mt-15 d-flex column" data-gap="6">
            <div class="d-flex justify-between">
              <span class="text opacity-6" data-size="13">Término fijo</span>
              <span class="text" data-size="13">{{ fmt(result.current.cost.fixed) }} €</span>
            </div>
            <div class="d-flex justify-between">
              <span class="text opacity-6" data-size="13">Excesos de potencia</span>
              <span class="text" data-size="13" style="color: var(--rojo, #c62828);">
                {{ fmt(result.current.cost.excess) }} €
              </span>
            </div>
            <div class="separator"></div>
            <div class="d-flex justify-between">
              <span class="text" data-weight="600" data-size="14">Total</span>
              <span class="text" data-weight="700" data-size="14">{{ fmt(result.current.cost.total) }} €</span>
            </div>
          </div>
        </div>

        <!-- Separador -->
        <div class="separator mx-10" data-position="vertical"></div>

        <!-- Situación optimizada -->
        <!-- Propuesta seleccionada -->
        <div class="w-50 form">
          <p class="text text-center" data-weight="600">
            <i :class="selectedOptimizationIcon" :style="{ color: selectedOptimizationColor }"></i>
            {{ selectedOptimizationTitle }}
          </p>

          <p class="text text-center opacity-6 mt-5" data-size="11">
            {{ selectedOptimizationSubtitle }}
          </p>

          <p class="text opacity-6 mt-10" data-size="12">Término de potencia (kW contratados × precio × meses)</p>
          <div class="period-breakdown mt-8">
            <div
              v-for="p in PERIODS"
              :key="'opt-breakdown-' + p"
              class="period-breakdown-row"
            >
              <span class="period-breakdown-label">{{ p }}</span>
              <span class="period-breakdown-formula">
                {{ fmtKw(selectedOptimization.powers[p]) }} kW
                × {{ fmtPrice(POWER_PRICE[p] * 30) }} €/kW·mes
                × 1,019 meses
              </span>
              <span
                class="period-breakdown-amount"
                :style="getDeltaStyle(result.current.powers[p], selectedOptimization.powers[p])"
              >
                {{ fmt(selectedOptimization.powers[p] * POWER_PRICE[p] * 30 * 1.019) }} €
              </span>
            </div>
          </div>

          <div class="mt-15 d-flex column" data-gap="6">
            <div class="d-flex justify-between">
              <span class="text opacity-6" data-size="13">Término fijo</span>
              <span class="text" data-size="13">{{ fmt(selectedOptimization.cost.fixed) }} €</span>
            </div>
            <div class="d-flex justify-between">
              <span class="text opacity-6" data-size="13">Excesos de potencia</span>
              <span class="text" data-size="13" style="color: var(--rojo, #c62828);">
                {{ fmt(selectedOptimization.cost.excess) }} €
              </span>
            </div>
            <div class="separator"></div>
            <div class="d-flex justify-between">
              <span class="text" data-weight="600" data-size="14">Total</span>
              <span class="text" data-weight="700" data-size="14" style="color: var(--verde, #2e7d32);">
                {{ fmt(selectedOptimization.cost.total) }} €
              </span>
            </div>
          </div>
        </div>
      </div>


        <div
          v-if="selectedOptimization.risk === 'high'"
          class="calc-warning mt-10"
        >
          <i class="fa-solid fa-triangle-exclamation"></i>
          Optimización agresiva: el ahorro se consigue aceptando más excesos de potencia.
        </div>

      <!-- ── Calculadora interactiva de potencias ──────────────────────────── -->
      <div class="separator mt-20"></div>
      <div class="mt-20 form">
        <p class="text text-center" data-weight="600">
          <i class="fa-solid fa-sliders opacity-6"></i> Simulador de potencias
        </p>
        <p class="text text-center opacity-6 mt-5" data-size="12">
          Edita la potencia óptima de cada periodo y ve el impacto en tiempo real
        </p>

        <!-- Cabecera -->
        <div
          class="d-grid p-10 round mt-12"
          data-round="12"
          data-bg="azul-claro"
          style="grid-template-columns: 52px 1fr 100px 140px 100px 100px; gap: 8px; align-items: center;"
        >
          <div class="text text-center opacity-6" data-size="12" data-weight="600">Periodo</div>
          <div class="text text-center opacity-6" data-size="12" data-weight="600">Demanda máx. (kW)</div>
          <div class="text text-center opacity-6" data-size="12" data-weight="600">Contratada (kW)</div>
          <div class="text text-center opacity-6" data-size="12" data-weight="600">Potencia a contratar (kW)</div>
          <div class="text text-center opacity-6" data-size="12" data-weight="600">Coste/mes</div>
          <div class="text text-center opacity-6" data-size="12" data-weight="600">Ahorro/mes</div>
        </div>

        <!-- Filas editables -->
        <div
          v-for="p in PERIODS"
          :key="'calc-' + p"
          class="d-grid calc-row"
          style="grid-template-columns: 52px 1fr 100px 140px 100px 100px; gap: 8px; align-items: center;"
        >
          <!-- Periodo -->
          <div class="text text-center" data-weight="700" data-size="14">{{ p }}</div>

          <!-- Demanda máxima histórica -->
          <div
            class="text text-center"
            data-size="13"
            :style="result.maxDemand[p] > result.current.powers[p] ? { color: 'var(--rojo, #c62828)', fontWeight: 700 } : {}"
          >
            {{ fmtKw(result.maxDemand[p]) }} kW
            <span v-if="result.maxDemand[p] > result.current.powers[p]" style="font-size:15px;"> ⚠</span>
          </div>

          <!-- Potencia contratada actual (fija, referencia) -->
          <div class="text text-center" data-size="13">
            {{ fmtKw(result.current.powers[p]) }} kW
          </div>

          <!-- Input editable: potencia a contratar -->
          <div class="d-flex align-center" style="gap: 6px; justify-content: center;">
            <button class="calc-btn" @click="adjustPower(p, -1)">−</button>
            <input
              class="calc-input"
              type="number"
              min="1"
              step="1"
              v-model.number="customPowers[p]"
              @input="onPowerInput(p)"
            />
            <button class="calc-btn" @click="adjustPower(p, +1)">+</button>
          </div>

          <!-- Coste mensual estimado con esa potencia -->
          <div class="text text-center" data-size="13" data-weight="600">
            {{ fmt(calcCustomCost(p)) }} €
          </div>

          <!-- Ahorro vs situación actual -->
          <div
            class="text text-center"
            data-size="13"
            data-weight="700"
            :style="calcCustomSaving(p) >= 0 ? { color: 'var(--verde, #2e7d32)' } : { color: 'var(--rojo, #c62828)' }"
          >
            {{ calcCustomSaving(p) >= 0 ? '' : '-' }}{{ fmt(Math.abs(calcCustomSaving(p))) }} €
          </div>
        </div>

        <!-- Separador totales -->
        <div class="separator mt-10"></div>
        <div
          class="d-grid mt-8"
          style="grid-template-columns: 52px 1fr 100px 140px 100px 100px; gap: 8px; align-items: center;"
        >
          <div class="text text-center" data-weight="700" data-size="13">Total</div>
          <div></div>
          <div class="text text-center" data-weight="700" data-size="14">
            {{ fmt(totalCurrentMonthly) }} €
          </div>
          <div></div>
          <div class="text text-center" data-weight="700" data-size="14">
            {{ fmt(totalCustomMonthly) }} €
          </div>
          <div
            class="text text-center"
            data-weight="700"
            data-size="14"
            :style="totalCustomSaving >= 0 ? { color: 'var(--verde, #2e7d32)' } : { color: 'var(--rojo, #c62828)' }"
          >
            {{ totalCustomSaving >= 0 ? '' : '-' }}{{ fmt(Math.abs(totalCustomSaving)) }} €
          </div>
        </div>

        <!-- Ahorro anual estimado con la configuración personalizada -->
        <div class="calc-saving-banner mt-16" :class="customAnnualSaving >= 0 ? 'banner-green' : 'banner-red'">
          <div>
            <p class="text opacity-7" data-size="12">Ahorro anual estimado con esta configuración</p>
            <p class="text" data-size="22" data-weight="700">
              {{ customAnnualSaving >= 0 ? '' : '-' }}{{ fmt(Math.abs(customAnnualSaving)) }} €/año
            </p>
          </div>
          <button class="calc-reset-btn" @click="resetCustomPowers">
            <i class="fa-solid fa-rotate-left"></i> Restaurar recomendadas
          </button>
        </div>

        <!-- Aviso si rompe el orden reglamentario P1≤P2≤...≤P6 -->
        <div v-if="powersOutOfOrder" class="calc-warning mt-10">
          <i class="fa-solid fa-triangle-exclamation"></i>
          La normativa 3.0TD exige P1 ≤ P2 ≤ P3 ≤ P4 ≤ P5 ≤ P6. Revisa los valores.
        </div>
      </div>
      <!-- ── Fin calculadora ────────────────────────────────────────────────── -->


      <!-- ── Simulación mensual ─────────────────────────────────────────────── -->
      <div class="separator mt-20"></div>

      <div v-if="monthlySimulationRows.length" class="mt-20 form">
        <p class="text text-center" data-weight="600">
          <i class="fa-solid fa-calendar-days opacity-6"></i>
          Simulación mensual
        </p>

        <p class="text text-center opacity-6 mt-5" data-size="12">
          Compare mes a mes la situación actual frente a la recomendada, la económica o la personalizada.
        </p>

        <div class="simulation-selector mt-15">
          <button
            type="button"
            :class="{ active: selectedSimulationScenario === 'recommended' }"
            @click="selectedSimulationScenario = 'recommended'"
          >
            Recomendada
          </button>

          <button
            type="button"
            :class="{ active: selectedSimulationScenario === 'economic' }"
            @click="selectedSimulationScenario = 'economic'"
          >
            Económica
          </button>

          <button
            type="button"
            :class="{ active: selectedSimulationScenario === 'custom' }"
            @click="selectedSimulationScenario = 'custom'"
          >
            Personalizada
          </button>
        </div>

        <div class="monthly-simulation-table mt-15">
          <div class="monthly-simulation-header">
            <div>Mes</div>
            <div>Días</div>
            <div>Actual</div>
            <div>{{ monthlySimulationRows[0]?.selectedLabel || 'Simulada' }}</div>
            <div>Excesos</div>
            <div>Ahorro</div>
          </div>

          <div
            v-for="row in monthlySimulationRows"
            :key="'monthly-simulation-' + row.label"
            class="monthly-simulation-row"
          >
            <div class="month-label">
              {{ row.label }}
            </div>

            <div>
              {{ row.days }}
            </div>

            <div>
              {{ fmt(row.currentCost.total) }} €
            </div>

            <div>
              {{ fmt(row.selectedCost.total) }} €
            </div>

            <div
              :style="row.selectedCost.excess > 0
                ? { color: 'var(--rojo, #c62828)', fontWeight: 700 }
                : { color: 'var(--verde, #2e7d32)', fontWeight: 700 }"
            >
              {{ fmt(row.selectedCost.excess) }} €
            </div>

            <div
              :style="row.selectedSaving >= 0
                ? { color: 'var(--verde, #2e7d32)', fontWeight: 700 }
                : { color: 'var(--rojo, #c62828)', fontWeight: 700 }"
            >
              {{ row.selectedSaving >= 0 ? '' : '-' }}{{ fmt(Math.abs(row.selectedSaving)) }} €
            </div>
          </div>

          <div class="monthly-simulation-footer">
            <div>Total</div>
            <div></div>
            <div>{{ fmt(monthlySimulationTotals.current) }} €</div>
            <div>{{ fmt(monthlySimulationTotals.selected) }} €</div>
            <div>{{ fmt(monthlySimulationTotals.excess) }} €</div>
            <div
              :style="monthlySimulationTotals.saving >= 0
                ? { color: 'var(--verde, #2e7d32)', fontWeight: 700 }
                : { color: 'var(--rojo, #c62828)', fontWeight: 700 }"
            >
              {{ monthlySimulationTotals.saving >= 0 ? '' : '-' }}{{ fmt(Math.abs(monthlySimulationTotals.saving)) }} €
            </div>
          </div>
        </div>

        <div
          v-if="monthlySimulationRows.some(row => row.selectedSaving < 0)"
          class="calc-warning mt-10"
        >
          <i class="fa-solid fa-circle-info"></i>
          Algunos meses pueden salir más caros por excesos de potencia, aunque el total del periodo analizado sea favorable.
        </div>
      </div>
      <!-- ── Fin simulación mensual ─────────────────────────────────────────── -->

      <!-- ── Gráficas mensuales por periodo ────────────────────────────────── -->
      <div class="separator mt-20"></div>
      <div class="mt-20 form">

        <!-- Cabecera de sección -->
        <p class="text text-center" data-weight="600">
          <i class="fa-solid fa-chart-column opacity-6"></i>
          Evolución mensual de potencia por periodo
        </p>

        <!-- Leyenda compartida — colores idénticos a los del gráfico -->
        <div class="chart-shared-legend">
          <!-- Azul: demanda dentro de lo contratado → 0x6384c7 = rgb(99,132,199) -->
          <div class="chart-legend-item">
            <span class="chart-legend-dot" style="background: #6384c7;"></span>
            <span class="text" data-size="12">Demanda normal</span>
          </div>
          <!-- Rojo: mes con exceso de potencia → 0xc62828 = rgb(198,40,40) -->
          <div class="chart-legend-item">
            <span class="chart-legend-dot" style="background: #c62828;"></span>
            <span class="text" data-size="12">Exceso (penalización)</span>
          </div>
          <!-- Gris discontinua: potencia actualmente contratada → 0x999999 -->
          <div class="chart-legend-item">
            <span class="chart-legend-line" style="border-top: 2px dashed #999999;"></span>
            <span class="text" data-size="12">Potencia actual</span>
          </div>
          <!-- Verde sólida: potencia óptima recomendada → 0x2e7d32 -->
          <div class="chart-legend-item">
            <span class="chart-legend-line" style="border-top: 2.5px solid #388e3c;"></span>
            <span class="text" data-size="12">Potencia óptima</span>
          </div>
        </div>

        <!-- Grid de 6 tarjetas -->
        <div class="period-charts-grid">
          <div
            v-for="p in PERIODS"
            :key="'chart-' + p"
            class="period-chart-card"
          >
            <!-- Cabecera de tarjeta -->
            <div class="d-flex justify-between align-center" style="margin-bottom: 10px;">
              <p class="text" data-weight="700" data-size="14">{{ p }}</p>
              <span
                class="text"
                data-size="12"
                data-weight="600"
                :style="getDeltaStyle(result.current.powers[p], result.recommended.powers[p])"
              >
                {{ formatDelta(result.current.powers[p], result.recommended.powers[p]) }} kW
              </span>
            </div>

            <!-- Contenedor amCharts (necesita id único, no canvas) -->
            <div :id="'amchart-' + p" style="width: 100%; height: 170px;"></div>

            <!-- Etiqueta de estado -->
            <p class="text opacity-6 mt-5" data-size="11" style="text-align: center;">
              <span v-if="result.maxDemand[p] > result.current.powers[p]">
                🔴 Excesos → estás pagando penalización
              </span>
              <span v-else-if="result.maxDemand[p] < result.current.powers[p] * 0.6">
                🟢 Potencia sobredimensionada → estás pagando de más
              </span>
              <span v-else>
                ⚖️ Ajuste correcto
              </span>
            </p>
          </div>
        </div>
      </div>
      <!-- ── Fin gráficas ──────────────────────────────────────────────────── -->

    </div>
    <!-- Fin resultado -->

  </section>
</template>

<script>
// ── amCharts 5 ──────────────────────────────────────────────────────────────
import * as am5         from '@amcharts/amcharts5';
import * as am5xy       from '@amcharts/amcharts5/xy';
import am5themes_Animated from '@amcharts/amcharts5/themes/Animated';

// ─── Precios reales tarifa 3.0TD ─────────────────────────────────────────────
const POWER_PRICE = { P1: 0.055827, P2: 0.029089, P3: 0.012278, P4: 0.010647, P5: 0.006887, P6: 0.003951 };
const TEPP        = { P1: 0.171373, P2: 0.090584, P3: 0.028721, P4: 0.021891, P5: 0.006142, P6: 0.006142 };
const PERIODS     = ['P1', 'P2', 'P3', 'P4', 'P5', 'P6'];
const MONTH_NAMES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

// ── Lógica de optimización ──────────────────────────────────────────────────

const POWER_STEP = 0.1;
const MAX_OPTIMIZER_POWER = 50;

const OPTIMIZATION_MODES = {
    ECONOMIC: 'economic',
    RECOMMENDED: 'recommended',
};

// Económica: busca pagar lo mínimo.
// Recomendada: mantiene una potencia más defendible comercialmente.
const SAFETY_MARGIN_BY_MODE = {
    [OPTIMIZATION_MODES.ECONOMIC]: 0,
    [OPTIMIZATION_MODES.RECOMMENDED]: 0.6,
};

// Mantengo P1-P5 mínimo 1 y P6 mínimo 15.
// Si quiere que la recomendada nunca baje de 15 en todos los periodos,
// se controla más abajo en getMinimumCandidatePower().
const MIN_POWER_BY_PERIOD = {
    P1: 1,
    P2: 1,
    P3: 1,
    P4: 1,
    P5: 1,
    P6: 15,
};

const COMMERCIAL_MIN_POWER_BY_PERIOD = {
    P1: 1,
    P2: 1,
    P3: 1,
    P4: 1,
    P5: 1,
    P6: 15,
};

function roundPower(value) {
    const num = Number(value);
    if (!Number.isFinite(num)) return 0;

    return +(Math.round(num / POWER_STEP) * POWER_STEP).toFixed(1);
}

function toPowerObject(raw) {
    return Object.fromEntries(
        PERIODS.map(p => [p, roundPower(raw?.[p] ?? 0)])
    );
}

function isPowerOrderValid(powers) {
    return PERIODS.every((p, index) => {
        if (index === 0) return true;

        const previous = Number(powers[PERIODS[index - 1]]) || 0;
        const current = Number(powers[p]) || 0;

        return previous <= current;
    });
}

function buildCandidates(min, max) {
    const values = [];

    const start = Math.ceil((min - 1e-9) / POWER_STEP);
    const end = Math.floor((max + 1e-9) / POWER_STEP);

    for (let i = start; i <= end; i++) {
        values.push(roundPower(i * POWER_STEP));
    }

    return values;
}

function calculateCost(readings, powers) {
    let fixed = 0;
    let excess = 0;

    for (const row of readings) {
        const days = Number(row.days) || 30;

        for (const p of PERIODS) {
            const demand = Number(row.demand?.[p]) || 0;
            const contracted = Number(powers?.[p]) || 0;

            fixed += contracted * POWER_PRICE[p] * days;

            if (demand > contracted) {
                excess += TEPP[p] * (demand - contracted) * days;
            }
        }
    }

    return {
        fixed: +fixed.toFixed(4),
        excess: +excess.toFixed(4),
        total: +(fixed + excess).toFixed(4),
    };
}

function calculateRowCost(row, powers) {
    let fixed = 0;
    let excess = 0;

    const days = Number(row.days) || 30;

    for (const p of PERIODS) {
        const demand = Number(row.demand?.[p]) || 0;
        const contracted = Number(powers?.[p]) || 0;

        fixed += contracted * POWER_PRICE[p] * days;

        if (demand > contracted) {
            excess += TEPP[p] * (demand - contracted) * days;
        }
    }

    return {
        fixed: +fixed.toFixed(4),
        excess: +excess.toFixed(4),
        total: +(fixed + excess).toFixed(4),
    };
}

function calcPeriodCost(readings, p, contracted) {
    let cost = 0;

    for (const row of readings) {
        const days = Number(row.days) || 30;
        const demand = Number(row.demand?.[p]) || 0;

        cost += contracted * POWER_PRICE[p] * days;

        if (demand > contracted) {
            cost += TEPP[p] * (demand - contracted) * days;
        }
    }

    return cost;
}

function getMaxDemandByPeriod(readings, period) {
    return Math.max(
        0,
        ...readings.map(row => Number(row.demand?.[period]) || 0)
    );
}

function getMinimumCandidatePower(period, readings, mode) {
    const maxDemand = getMaxDemandByPeriod(readings, period);

    const baseMin = mode === OPTIMIZATION_MODES.RECOMMENDED
        ? COMMERCIAL_MIN_POWER_BY_PERIOD[period]
        : MIN_POWER_BY_PERIOD[period];

    const safetyMargin = SAFETY_MARGIN_BY_MODE[mode] ?? 0;
    const safetyMin = maxDemand * safetyMargin;

    return roundPower(Math.max(baseMin, safetyMin));
}

/**
 * Optimización conjunta respetando:
 *
 * P1 <= P2 <= P3 <= P4 <= P5 <= P6
 *
 * mode = economic:
 *   busca el mínimo coste total.
 *
 * mode = recommended:
 *   aplica margen comercial sobre maxímetros.
 */
function findOptimalPowers(readings, currentPowers, mode = OPTIMIZATION_MODES.ECONOMIC) {
    const maxDemandFound = Math.max(
        0,
        ...PERIODS.flatMap(p => readings.map(row => Number(row.demand?.[p]) || 0))
    );

    const maxCurrentPower = Math.max(
        0,
        ...PERIODS.map(p => Number(currentPowers[p]) || 0)
    );

    const maxMinPower = Math.max(
        ...PERIODS.map(p => getMinimumCandidatePower(p, readings, mode))
    );

    const maxCandidate = roundPower(
        Math.min(
            MAX_OPTIMIZER_POWER,
            Math.max(
                maxDemandFound + 2,
                maxCurrentPower,
                maxMinPower
            )
        )
    );

    const candidatesByPeriod = PERIODS.map(p => {
        const min = getMinimumCandidatePower(p, readings, mode);
        return buildCandidates(min, maxCandidate);
    });

    const costsByPeriod = PERIODS.map((p, periodIndex) => {
        return candidatesByPeriod[periodIndex].map(candidate => {
            return calcPeriodCost(readings, p, candidate);
        });
    });

    const dp = [];
    const previousIndex = [];

    dp[0] = costsByPeriod[0].slice();
    previousIndex[0] = candidatesByPeriod[0].map(() => -1);

    for (let periodIndex = 1; periodIndex < PERIODS.length; periodIndex++) {
        dp[periodIndex] = [];
        previousIndex[periodIndex] = [];

        for (let currentCandidateIndex = 0; currentCandidateIndex < candidatesByPeriod[periodIndex].length; currentCandidateIndex++) {
            const currentCandidate = candidatesByPeriod[periodIndex][currentCandidateIndex];

            let bestPreviousCost = Infinity;
            let bestPreviousIndex = -1;

            for (let prevCandidateIndex = 0; prevCandidateIndex < candidatesByPeriod[periodIndex - 1].length; prevCandidateIndex++) {
                const prevCandidate = candidatesByPeriod[periodIndex - 1][prevCandidateIndex];

                if (prevCandidate > currentCandidate) continue;

                if (dp[periodIndex - 1][prevCandidateIndex] < bestPreviousCost) {
                    bestPreviousCost = dp[periodIndex - 1][prevCandidateIndex];
                    bestPreviousIndex = prevCandidateIndex;
                }
            }

            dp[periodIndex][currentCandidateIndex] =
                bestPreviousCost + costsByPeriod[periodIndex][currentCandidateIndex];

            previousIndex[periodIndex][currentCandidateIndex] = bestPreviousIndex;
        }
    }

    const lastPeriodIndex = PERIODS.length - 1;

    let bestLastCandidateIndex = -1;
    let bestTotalCost = Infinity;

    for (let i = 0; i < dp[lastPeriodIndex].length; i++) {
        if (dp[lastPeriodIndex][i] < bestTotalCost) {
            bestTotalCost = dp[lastPeriodIndex][i];
            bestLastCandidateIndex = i;
        }
    }

    const optimized = {};
    let selectedIndex = bestLastCandidateIndex;

    for (let periodIndex = PERIODS.length - 1; periodIndex >= 0; periodIndex--) {
        const period = PERIODS[periodIndex];

        optimized[period] = candidatesByPeriod[periodIndex][selectedIndex];
        selectedIndex = previousIndex[periodIndex][selectedIndex];
    }

    return optimized;
}

function calculateMonthlySimulation(readings, currentPowers, recommendedPowers, economicPowers, customPowers = null) {
    return readings.map(row => {
        const currentCost = calculateRowCost(row, currentPowers);
        const recommendedCost = calculateRowCost(row, recommendedPowers);
        const economicCost = calculateRowCost(row, economicPowers);
        const customCost = customPowers ? calculateRowCost(row, customPowers) : null;

        return {
            label: row.label,
            days: Number(row.days) || 30,
            demand: row.demand,

            currentCost,
            recommendedCost,
            economicCost,
            customCost,

            recommendedSaving: +(currentCost.total - recommendedCost.total).toFixed(4),
            economicSaving: +(currentCost.total - economicCost.total).toFixed(4),
            customSaving: customCost ? +(currentCost.total - customCost.total).toFixed(4) : null,
        };
    });
}

function calculateExcessStats(readings, powers) {
    return Object.fromEntries(
        PERIODS.map(p => {
            const monthsWithExcess = readings.filter(row => {
                const demand = Number(row.demand?.[p]) || 0;
                const contracted = Number(powers?.[p]) || 0;

                return demand > contracted;
            }).length;

            const maxExcess = Math.max(
                0,
                ...readings.map(row => {
                    const demand = Number(row.demand?.[p]) || 0;
                    const contracted = Number(powers?.[p]) || 0;

                    return demand - contracted;
                })
            );

            return [
                p,
                {
                    monthsWithExcess,
                    totalMonths: readings.length,
                    maxExcess: +maxExcess.toFixed(1),
                },
            ];
        })
    );
}

function getOptimizationRisk(currentCost, optimizedCost) {
    const excessIncrease = optimizedCost.excess - currentCost.excess;
    const fixedSaving = currentCost.fixed - optimizedCost.fixed;

    if (excessIncrease <= 0) return 'low';

    const excessRatio = excessIncrease / Math.max(1, fixedSaving);

    if (excessRatio < 0.35) return 'low';
    if (excessRatio < 0.75) return 'medium';

    return 'high';
}

function optimizePower(readings, contractedRaw) {
    const current = toPowerObject(contractedRaw);
    const currentCost = calculateCost(readings, current);

    const economicPowers = findOptimalPowers(readings, current, OPTIMIZATION_MODES.ECONOMIC);
    const economicCost = calculateCost(readings, economicPowers);

    const recommendedPowers = findOptimalPowers(readings, current, OPTIMIZATION_MODES.RECOMMENDED);
    const recommendedCost = calculateCost(readings, recommendedPowers);

    const economicScenario = economicCost.total < currentCost.total
        ? { powers: economicPowers, cost: economicCost }
        : { powers: current, cost: currentCost };

    const recommendedScenario = recommendedCost.total < currentCost.total
        ? { powers: recommendedPowers, cost: recommendedCost }
        : { powers: current, cost: currentCost };

    return {
        current: {
            powers: current,
            cost: currentCost,
            orderValid: isPowerOrderValid(current),
        },

        recommended: {
            ...recommendedScenario,
            saving: +(currentCost.total - recommendedScenario.cost.total).toFixed(4),
            risk: getOptimizationRisk(currentCost, recommendedScenario.cost),
        },

        economic: {
            ...economicScenario,
            saving: +(currentCost.total - economicScenario.cost.total).toFixed(4),
            risk: getOptimizationRisk(currentCost, economicScenario.cost),
        },

        // Mantengo optimized para no romper partes antiguas del componente.
        // A partir de ahora apunta a la recomendada comercial.
        optimized: recommendedScenario,

        saving: +(currentCost.total - recommendedScenario.cost.total).toFixed(4),
    };
}

export default {
    name: 'PowerOptimizerComponent',
    props: ['basicData'],

    data() {
        return {
            cups: '',
            loading: false,
            result: null,
            PERIODS,
            POWER_PRICE,
            MIN_POWER_BY_PERIOD,
            OPTIMIZATION_MODES,
            selectedOptimizationScenario: 'recommended',
            selectedSimulationScenario: 'custom',
            _monthlyReadings: [],
            _chartRoots: {},
            generatingPdf: false,
            customPowers: { P1: 0, P2: 0, P3: 0, P4: 0, P5: 0, P6: 0 },
        };
    },

    computed: {
        // Coste mensual total situación actual (término fijo + excesos sobre histórico real)
        totalCurrentMonthly() {
            if (!this.result || !this._monthlyReadings.length) return 0;
            // Usamos el coste total anual ya calculado con días reales y excesos reales,
            // dividido entre el número de lecturas para obtener la media mensual.
            return +(this.result.current.cost.total / this._monthlyReadings.length).toFixed(2);
        },

        // Coste mensual total con las potencias personalizadas (media sobre histórico real)
        totalCustomMonthly() {
            if (!this.result || !this._monthlyReadings.length) return 0;
            return +(PERIODS.reduce((acc, p) => acc + this.calcCustomCost(p), 0)).toFixed(2);
        },

        // Ahorro mensual total vs situación actual
        totalCustomSaving() {
            return +(this.totalCurrentMonthly - this.totalCustomMonthly).toFixed(2);
        },

        // Ahorro ANUAL real: usa calculateCost con los datos reales de cada lectura,
        // exactamente igual que hace el bloque "Situación optimizada".
        customAnnualSaving() {
            if (!this.result || !this._monthlyReadings.length) return 0;

            const customCost = calculateCost(this._monthlyReadings, this.customPowers);

            return +(this.result.current.cost.total - customCost.total).toFixed(2);
        },

        // Alerta si el orden P1≤P2≤...≤P6 no se cumple
        powersOutOfOrder() {
            return !isPowerOrderValid(this.customPowers);
        },



        recommendedAnnualSaving() {
            if (!this.result) return 0;
            return this.result.recommended?.saving ?? 0;
        },

        economicAnnualSaving() {
            if (!this.result) return 0;
            return this.result.economic?.saving ?? 0;
        },
        customMonthlySimulation() {
            if (!this.result || !this._monthlyReadings.length) return [];

            return calculateMonthlySimulation(
                this._monthlyReadings,
                this.result.current.powers,
                this.result.recommended.powers,
                this.result.economic.powers,
                this.customPowers
            );
        },

        selectedOptimization() {
            if (!this.result) return null;

            return this.selectedOptimizationScenario === 'economic'
                ? this.result.economic
                : this.result.recommended;
        },

        selectedOptimizationTitle() {
            return this.selectedOptimizationScenario === 'economic'
                ? 'Óptima económica'
                : 'Recomendación segura';
        },

        selectedOptimizationSubtitle() {
            return this.selectedOptimizationScenario === 'economic'
                ? 'Menor coste total según histórico, aceptando más excesos si compensa.'
                : 'Propuesta más prudente y defendible para cliente.';
        },

        selectedOptimizationIcon() {
            return this.selectedOptimizationScenario === 'economic'
                ? 'fa-solid fa-bolt'
                : 'fa-solid fa-circle-check';
        },

        selectedOptimizationColor() {
            return this.selectedOptimizationScenario === 'economic'
                ? 'var(--azul, #003b7a)'
                : 'var(--verde, #2e7d32)';
        },

        monthlySimulationRows() {
            if (!this.customMonthlySimulation.length) return [];

            return this.customMonthlySimulation.map(row => {
                if (this.selectedSimulationScenario === 'recommended') {
                    return {
                        ...row,
                        selectedCost: row.recommendedCost,
                        selectedSaving: row.recommendedSaving,
                        selectedLabel: 'Recomendada',
                    };
                }

                if (this.selectedSimulationScenario === 'economic') {
                    return {
                        ...row,
                        selectedCost: row.economicCost,
                        selectedSaving: row.economicSaving,
                        selectedLabel: 'Económica',
                    };
                }

                return {
                    ...row,
                    selectedCost: row.customCost,
                    selectedSaving: row.customSaving,
                    selectedLabel: 'Personalizada',
                };
            });
        },

        monthlySimulationTotals() {
            return this.monthlySimulationRows.reduce((acc, row) => {
                acc.current += row.currentCost.total;
                acc.selected += row.selectedCost?.total ?? 0;
                acc.excess += row.selectedCost?.excess ?? 0;
                acc.saving += row.selectedSaving ?? 0;

                return acc;
            }, {
                current: 0,
                selected: 0,
                excess: 0,
                saving: 0,
            });
        },

        recommendedMonthlySaving() {
            if (!this.result || !this._monthlyReadings.length) return 0;
            return +(this.result.recommended.saving / this._monthlyReadings.length).toFixed(2);
        },

        economicMonthlySaving() {
            if (!this.result || !this._monthlyReadings.length) return 0;
            return +(this.result.economic.saving / this._monthlyReadings.length).toFixed(2);
        },
    },

    methods: {

        isValidCups(value) {
            return /^ES\w{16,20}$/i.test(value.trim());
        },

        async runOptimizer() {
            const cups = this.cups.trim().toUpperCase();
            if (!this.isValidCups(cups)) {
                await Swal.fire({ icon: 'warning', title: 'CUPS inválido', text: 'Por favor introduce un CUPS válido (formato ES...).' });
                return;
            }

            this.loading = true;
            this.result  = null;
            this._monthlyReadings = [];
            this._destroyAllCharts();

            try {
                const res = await axios.get('/api/tools/getAPIConsumption', { params: { CUPS: cups } });

                const fee = res.data.fee ?? '';
                if (!fee.toString().includes('3.0')) {
                    await Swal.fire({
                        icon: 'warning', title: 'Tarifa no compatible',
                        text: `Este CUPS tiene la tarifa ${fee || 'desconocida'}. El optimizador solo es compatible con la tarifa 3.0TD.`,
                    });
                    return;
                }

                const contractedArray = Array.isArray(res.data?.cupsData?.power)
                    ? res.data.cupsData.power
                    : [];

                const consumptionData = Array.isArray(res.data?.consumptionData)
                    ? res.data.consumptionData
                    : [];

                if (contractedArray.length < 6) {
                    await Swal.fire({
                        icon: 'error',
                        title: 'Datos incompletos',
                        text: 'La API no ha devuelto las 6 potencias contratadas del CUPS.',
                    });
                    return;
                }

                if (!consumptionData.length) {
                    await Swal.fire({
                        icon: 'warning',
                        title: 'Sin histórico suficiente',
                        text: 'No hay datos de consumo/potencia suficientes para calcular la optimización.',
                    });
                    return;
                }

                const contractedPowers = Object.fromEntries(
                    PERIODS.map((p, i) => [p, roundPower(Number(contractedArray[i]) || 0)])
                );

                if (Object.values(contractedPowers).some(power => power > MAX_OPTIMIZER_POWER)) {
                    await Swal.fire({
                        icon: 'warning',
                        title: 'Potencia no compatible',
                        text: `Este CUPS tiene una potencia contratada superior a ${MAX_OPTIMIZER_POWER} kW. El optimizador no está disponible para potencias de más de ${MAX_OPTIMIZER_POWER} kW.`,
                    });
                    return;
                }

                const readings = consumptionData
                    .map(row => {
                        const dateFrom = moment(row.startDate, ['DD/MM/YYYY', 'YYYY-MM-DD'], true);
                        const dateTo = moment(row.endDate, ['DD/MM/YYYY', 'YYYY-MM-DD'], true);

                        const days = dateFrom.isValid() && dateTo.isValid()
                            ? Math.max(1, dateTo.diff(dateFrom, 'days'))
                            : 30;

                        const powers = Array.isArray(row.powers) ? row.powers : [];

                        const demand = Object.fromEntries(
                            PERIODS.map((p, i) => [p, Number(powers[i]) || 0])
                        );

                        const label = dateFrom.isValid()
                            ? `${MONTH_NAMES[dateFrom.month()]} ${String(dateFrom.year()).slice(2)}`
                            : 'Periodo';

                        return { days, demand, label };
                    })
                    .filter(row => Object.values(row.demand).some(value => value > 0));

                if (!readings.length) {
                    await Swal.fire({
                        icon: 'warning',
                        title: 'Sin maxímetros válidos',
                        text: 'No se han encontrado demandas de potencia válidas para optimizar.',
                    });
                    return;
                }

                this._monthlyReadings = readings;

                const maxDemand = Object.fromEntries(
                    PERIODS.map(p => [p, Math.max(...readings.map(r => r.demand[p] ?? 0))])
                );

                const optimized = optimizePower(readings, contractedPowers);

                const recommendedExcessStats = calculateExcessStats(
                    readings,
                    optimized.recommended.powers
                );

                const economicExcessStats = calculateExcessStats(
                    readings,
                    optimized.economic.powers
                );

                const monthlySimulation = calculateMonthlySimulation(
                    readings,
                    optimized.current.powers,
                    optimized.recommended.powers,
                    optimized.economic.powers
                );

                this.result = {
                    ...optimized,
                    maxDemand,
                    recommendedExcessStats,
                    economicExcessStats,
                    monthlySimulation,
                };

                // Inicializar simulador con los valores óptimos calculados
                PERIODS.forEach(p => {
                    this.customPowers[p] = this.result.recommended.powers[p];
                });

                this.$nextTick(() => { this._renderAllCharts(); });

            } catch (error) {
                console.error('[PowerOptimizer] Error:', error);
                await Swal.fire({
                    icon: 'error', title: 'Error al optimizar',
                    text: error?.response?.data?.message || error?.message || 'No se han podido obtener los datos del CUPS.',
                });
            } finally {
                this.loading = false;
            }
        },

        // ── Simulador interactivo ────────────────────────────────────────────

        calcCustomCost(p) {
            if (!this.result || !this._monthlyReadings.length) return 0;
            const contracted = this.customPowers[p] ?? 0;
            // Acumular coste real de cada lectura (días reales) y dividir para obtener media mensual
            const totalCost = this._monthlyReadings.reduce((acc, r) => {
                const days   = r.days ?? 30;
                const demand = r.demand[p] ?? 0;
                let cost = contracted * POWER_PRICE[p] * days;
                if (demand > contracted) cost += TEPP[p] * (demand - contracted) * days;
                return acc + cost;
            }, 0);
            return +(totalCost / Math.max(1, this._monthlyReadings.length)).toFixed(2);
        },

        calcCustomSaving(p) {
            if (!this.result || !this._monthlyReadings.length) return 0;
            // Coste mensual medio actual para este periodo (término fijo + excesos reales)
            const totalCurrentPeriod = this._monthlyReadings.reduce((acc, r) => {
                const days       = r.days ?? 30;
                const demand     = r.demand[p] ?? 0;
                const contracted = this.result.current.powers[p];
                let cost = contracted * POWER_PRICE[p] * days;
                if (demand > contracted) cost += TEPP[p] * (demand - contracted) * days;
                return acc + cost;
            }, 0);
            const currentMonthlyPeriod = totalCurrentPeriod / Math.max(1, this._monthlyReadings.length);
            return +(currentMonthlyPeriod - this.calcCustomCost(p)).toFixed(2);
        },

        adjustPower(p, step) {
            const regMin = MIN_POWER_BY_PERIOD[p];
            const current = Number(this.customPowers[p]);

            const next = Number.isFinite(current)
                ? current + step
                : regMin;

            this.customPowers[p] = roundPower(Math.max(regMin, next));
        },

        onPowerInput(p) {
            const regMin = MIN_POWER_BY_PERIOD[p];
            const value = Number(this.customPowers[p]);

            if (!Number.isFinite(value) || value < regMin) {
                this.customPowers[p] = regMin;
                return;
            }

            this.customPowers[p] = roundPower(value);
        },

        setOptimizationScenario(scenario) {
            if (!this.result || !['recommended', 'economic'].includes(scenario)) return;

            this.selectedOptimizationScenario = scenario;

            PERIODS.forEach(p => {
                this.customPowers[p] = roundPower(this.result[scenario].powers[p]);
            });

            this.selectedSimulationScenario = scenario;
        },

        resetCustomPowers() {
            if (!this.result || !this.selectedOptimization) return;

            PERIODS.forEach(p => {
                this.customPowers[p] = roundPower(this.selectedOptimization.powers[p]);
            });
        },

        async generateReport() {
          if (!this.result) return;

          if (this.powersOutOfOrder) {
              await Swal.fire({
                  icon: 'warning',
                  title: 'Potencias no válidas',
                  text: 'La configuración personalizada no cumple P1 ≤ P2 ≤ P3 ≤ P4 ≤ P5 ≤ P6.',
              });
              return;
          }

          this.generatingPdf = true;
          try {
              const res = await axios.post(
                  '/api/tools/generatePowerOptimizerReport',
                  {
                      cups:              this.cups,
                      currentPowers:     this.result.current.powers,
                      optimizedPowers:   this.result.recommended.powers,
                      customPowers:      this.customPowers,
                      currentCost:       this.result.current.cost,
                      optimizedCost:     this.result.recommended.cost,
                      saving:            this.result.saving,
                      customAnnualSaving: this.customAnnualSaving,
                      maxDemand:         this.result.maxDemand,
                      monthlyReadings:   this._monthlyReadings,
                      basicData:         this.basicData,
                  },
                  { responseType: 'blob' }
              );
      
              const url = URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }));
              const a   = document.createElement('a');
              a.href     = url;
              a.download = `optimizacion_potencia_${this.cups}_${new Date().toISOString().slice(0, 10)}.pdf`;
              a.click();
              URL.revokeObjectURL(url);
      
          } catch (e) {
              console.error('[PowerOptimizer] Error generando informe:', e);
              Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo generar el informe de optimización.' });
          } finally {
              this.generatingPdf = false;
          }
      },

        // ── amCharts helpers ────────────────────────────────────────────────

        _destroyAllCharts() {
            for (const p of PERIODS) {
                if (this._chartRoots[p]) {
                    this._chartRoots[p].dispose();
                    delete this._chartRoots[p];
                }
            }
        },

        _renderAllCharts() {
            if (!this.result || !this._monthlyReadings.length) return;

            const currentPowers   = this.result.current.powers;
            const optimizedPowers = this.result.recommended.powers;

            for (const p of PERIODS) {
                const containerId = `amchart-${p}`;
                const container   = document.getElementById(containerId);
                if (!container) continue;

                // Destruir root previo si existe
                if (this._chartRoots[p]) {
                    this._chartRoots[p].dispose();
                }

                // ── Crear root ──────────────────────────────────────────────
                const root = am5.Root.new(containerId);
                this._chartRoots[p] = root;

                // Tema con animaciones
                root.setThemes([am5themes_Animated.new(root)]);

                // Quitar logo amCharts (requiere licencia; si tienes licencia, usa root.license())
                root._logo?.dispose();

                // ── Crear chart XY ──────────────────────────────────────────
                const chart = root.container.children.push(
                    am5xy.XYChart.new(root, {
                        panX: false, panY: false,
                        wheelX: 'none', wheelY: 'none',
                        paddingTop: 8, paddingBottom: 0,
                        paddingLeft: 0, paddingRight: 8,
                    })
                );

                // ── Ejes ────────────────────────────────────────────────────
                const xRenderer = am5xy.AxisRendererX.new(root, {
                    minGridDistance: 20,
                    cellStartLocation: 0.1,
                    cellEndLocation: 0.9,
                });
                xRenderer.labels.template.setAll({
                    fontSize: 10,
                    fill: am5.color(0x666666),
                    rotation: -35,
                    centerY: am5.p50,
                    centerX: am5.p100,
                });
                xRenderer.grid.template.set('visible', false);

                const xAxis = chart.xAxes.push(
                    am5xy.CategoryAxis.new(root, {
                        categoryField: 'month',
                        renderer: xRenderer,
                    })
                );

                const yRenderer = am5xy.AxisRendererY.new(root, {});
                yRenderer.labels.template.setAll({ fontSize: 9, fill: am5.color(0x888888) });
                yRenderer.grid.template.setAll({ stroke: am5.color(0x000000), strokeOpacity: 0.05 });

                const yAxis = chart.yAxes.push(
                    am5xy.ValueAxis.new(root, {
                        renderer: yRenderer,
                        // Pequeño padding arriba para que las líneas no queden cortadas
                        extraMax: 0.15,
                    })
                );

                // ── Dataset base ────────────────────────────────────────────
                const currentVal   = currentPowers[p]   ?? 0;
                const optimizedVal = optimizedPowers[p] ?? 0;

                const chartData = this._monthlyReadings.map(r => ({
                    month:    r.label,
                    demand:   +(r.demand[p] ?? 0).toFixed(2),
                    actual:   currentVal,
                    optimal:  optimizedVal,
                    // Rojo = supera la potencia contratada actual → genera penalización
                    isExcess: (r.demand[p] ?? 0) > currentVal,
                }));

                xAxis.data.setAll(chartData);

                // Colores centralizados — deben coincidir exactamente con la leyenda del template
                const COLOR_NORMAL  = am5.color(0x6384c7); // azul
                const COLOR_EXCESS  = am5.color(0xc62828); // rojo
                const COLOR_ACTUAL  = am5.color(0x999999); // gris
                const COLOR_OPTIMAL = am5.color(0x388e3c); // Verde más intenso // verde

                // ── Serie de barras: demanda real con color condicional ───────
                const barSeries = chart.series.push(
                    am5xy.ColumnSeries.new(root, {
                        name: 'Demanda real',
                        xAxis, yAxis,
                        valueYField:    'demand',
                        categoryXField: 'month',
                        tooltip: am5.Tooltip.new(root, {
                            labelText: '{categoryX}: [bold]{valueY.formatNumber("#.0")} kW[/]',
                        }),
                    })
                );
                barSeries.columns.template.setAll({
                    width: am5.percent(75),
                    cornerRadiusTL: 4, cornerRadiusTR: 4,
                    strokeOpacity: 0,
                });
                // Color por barra según si hay exceso o no
                barSeries.columns.template.adapters.add('fill', (_fill, target) => {
                    return target.dataItem?.dataContext?.isExcess ? COLOR_EXCESS : COLOR_NORMAL;
                });
                barSeries.columns.template.adapters.add('stroke', (_stroke, target) => {
                    return target.dataItem?.dataContext?.isExcess ? COLOR_EXCESS : COLOR_NORMAL;
                });
                barSeries.data.setAll(chartData);

                // ── Helper para series de línea ──────────────────────────────
                const makeLineSeries = (field, color, dash, strokeWidth, name) => {
                    const series = chart.series.push(
                        am5xy.LineSeries.new(root, {
                            name,
                            xAxis, yAxis,
                            valueYField:    field,
                            categoryXField: 'month',
                            tooltip: am5.Tooltip.new(root, {
                                labelText: `[bold]{name}[/]: {valueY.formatNumber("#.0")} kW`,
                            }),
                            stroke:          color,
                        })
                    );
                    series.strokes.template.setAll({
                        strokeWidth,
                        // [] = línea sólida, [n,m] = discontinua. No usar null/undefined.
                        strokeDasharray: dash,
                    });
                    // Sin bullets — línea limpia
                    series.data.setAll(chartData);
                    return series;
                };

                // Línea gris discontinua — potencia actualmente contratada
                makeLineSeries('actual',  COLOR_ACTUAL,  [6, 4], 2,   'Potencia actual');
                // Línea verde sólida — potencia óptima recomendada ([] = sin dash en amCharts 5)
                makeLineSeries('optimal', COLOR_OPTIMAL, [],     2.5, 'Potencia óptima');

                // ── Cursor ──────────────────────────────────────────────────
                chart.set('cursor', am5xy.XYCursor.new(root, {
                    behavior: 'none',
                    xAxis,
                }));
                chart.getTooltip()?.set('visible', false);

                // Animar entrada
                chart.appear(600, 50);
                barSeries.appear();
            }
        },

        // ── Desglose término de potencia ────────────────────────────────────

        /**
         * Ahorro mensual del término fijo para un periodo concreto.
         * Positivo = se ahorra, negativo = sube (no debería pasar tras optimizar).
         */
        savingPerPeriod(p) {
            const cur = this.result.current.powers[p]   * POWER_PRICE[p] * 30;
            const opt = this.result.recommended.powers[p] * POWER_PRICE[p] * 30;
            return +(cur - opt).toFixed(4);
        },

        /**
         * Total mensual del término fijo de potencia (suma de los 6 periodos).
         * Se divide el coste anual entre 12 para obtener la media mensual.
         * @param {'current'|'optimized'} scenario
         */
        totalFixedMonthly(scenario) {
            const powers = this.result[scenario].powers;
            const annual = PERIODS.reduce((acc, p) => acc + powers[p] * POWER_PRICE[p] * 365, 0);
            return +(annual / 12).toFixed(4);
        },

        /**
         * Formatea un precio con 6 decimales para mostrar el €/kW·mes exacto.
         */
        fmtPrice(value) {
            return Intl.NumberFormat('es-ES', { minimumFractionDigits: 6, maximumFractionDigits: 6 }).format(value);
        },

        // ── Formatters generales ─────────────────────────────────────────────

        fmt(value) {
            const num = Number(value);
            if (isNaN(num)) return '—';
            return Intl.NumberFormat('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
        },

        fmtKw(value) {
            const num = Number(value);
            if (isNaN(num)) return '—';
            return Intl.NumberFormat('es-ES', { minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(num);
        },

        formatDelta(current, optimized) {
            const delta = Number(optimized) - Number(current);
            if (isNaN(delta)) return '—';
            if (delta === 0) return '=';
            return `${delta > 0 ? '+' : ''}${this.fmtKw(delta)}`;
        },

        getDeltaStyle(current, optimized) {
            const delta = Number(optimized) - Number(current);
            if (isNaN(delta) || delta === 0) return {};
            return { color: delta < 0 ? 'var(--verde, #2e7d32)' : 'var(--naranja, #e65100)' };
        },
    },

    beforeUnmount() {
        // Liberar todos los roots de amCharts al destruir el componente
        this._destroyAllCharts();
    },
};
</script>

<style scoped>
/* ── Grid de tarjetas: 3 col escritorio, 2 tablet, 1 móvil ── */
.period-charts-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
  margin-top: 16px;
}
@media (max-width: 900px) { .period-charts-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 560px) { .period-charts-grid { grid-template-columns: 1fr; } }

/* ── Tarjeta individual ── */
.period-chart-card {
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 12px;
  padding: 14px 12px 10px;
  background: rgba(255, 255, 255, 0.55);
}

/* ── Leyenda compartida ── */
.chart-shared-legend {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 16px 24px;
  margin-top: 12px;
  margin-bottom: 8px;
}
.chart-legend-item {
  display: flex;
  align-items: center;
  gap: 7px;
}
.chart-legend-dot {
  width: 13px;
  height: 13px;
  border-radius: 3px;
  flex-shrink: 0;
}
.chart-legend-line {
  width: 28px;
  height: 0;
  flex-shrink: 0;
}

.mt-8 { margin-top: 8px; }
.mt-12 { margin-top: 12px; }
.mt-16 { margin-top: 16px; }

/* ── Simulador interactivo ── */
.calc-row {
  padding: 6px 4px;
  border-radius: 8px;
  transition: background 0.15s;
}
.calc-row:hover { background: rgba(0,0,0,0.025); }

.calc-input {
  width: 64px;
  text-align: center;
  border: 1.5px solid rgba(0,0,0,0.15);
  border-radius: 8px;
  padding: 5px 6px;
  font-size: 13px;
  font-weight: 600;
  outline: none;
  transition: border-color 0.2s;
  -moz-appearance: textfield;
}
.calc-input::-webkit-inner-spin-button,
.calc-input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
.calc-input:focus { border-color: rgba(99,132,199,0.8); }

.calc-btn {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: 1.5px solid rgba(0,0,0,0.15);
  background: white;
  font-size: 16px;
  font-weight: 700;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s, border-color 0.15s;
  flex-shrink: 0;
}
.calc-btn:hover { background: rgba(99,132,199,0.1); border-color: rgba(99,132,199,0.6); }
.calc-btn:active { background: rgba(99,132,199,0.2); }

.calc-saving-banner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-radius: 14px;
  padding: 16px 20px;
}
.banner-green { background: rgba(46,125,50,0.08); border: 1.5px solid rgba(46,125,50,0.25); }
.banner-red   { background: rgba(198,40,40,0.07); border: 1.5px solid rgba(198,40,40,0.25); }

.selected-optimization-saving {
  max-width: 320px;
  margin-left: auto;
  margin-right: auto;
  border: 1px solid #dce7f5;
  border-radius: 14px;
  background: #f8fbff;
  padding: 18px;
  text-align: center;
}
.calc-reset-btn {
  background: white;
  border: 1.5px solid rgba(0,0,0,0.15);
  border-radius: 8px;
  padding: 7px 14px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s;
  white-space: nowrap;
}
.calc-reset-btn:hover { background: rgba(0,0,0,0.04); }

.calc-warning {
  background: rgba(230,81,0,0.08);
  border: 1.5px solid rgba(230,81,0,0.3);
  border-radius: 10px;
  padding: 10px 16px;
  font-size: 12px;
  font-weight: 600;
  color: #e65100;
  text-align: center;
}

/* ── Desglose término de potencia (estilo factura) ── */
.period-breakdown {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.period-breakdown-row {
  display: grid;
  grid-template-columns: 28px 1fr auto;
  align-items: center;
  gap: 8px;
  padding: 3px 4px;
  border-radius: 6px;
}

.period-breakdown-row:hover {
  background: rgba(0, 0, 0, 0.03);
}

.period-breakdown-label {
  font-size: 15px;
  font-weight: 700;
  color: #444;
  text-align: center;
}

.period-breakdown-formula {
  font-size: 15px;
  color: #888;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.period-breakdown-amount {
  font-size: 15px;
  font-weight: 600;
  text-align: right;
  white-space: nowrap;
  color: #333;
}

.optimization-summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(220px, 320px));
  gap: 18px;
  justify-content: center;
}

.optimization-summary-card {
  border-radius: 14px;
  padding: 18px;
  text-align: center;
  border: 1px solid #dce7f5;
  background: #f8fbff;
}

.optimization-summary-card.recommended {
  background: #e8f4ff;
}

.optimization-summary-card.economic {
  background: #f4f8ff;
}
.simulation-selector {
  display: flex;
  justify-content: center;
  gap: 8px;
  flex-wrap: wrap;
}

.simulation-selector button {
  border: 1px solid #d5e2f2;
  background: #fff;
  color: #002f6c;
  border-radius: 999px;
  padding: 7px 14px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.simulation-selector button.active {
  background: #002f6c;
  color: #fff;
  border-color: #002f6c;
}

.monthly-simulation-table {
  width: 100%;
  border: 1px solid #dce7f5;
  border-radius: 12px;
  overflow: hidden;
}

.monthly-simulation-header,
.monthly-simulation-row,
.monthly-simulation-footer {
  display: grid;
  grid-template-columns: 1fr 80px 130px 130px 130px 130px;
  gap: 10px;
  align-items: center;
}

.monthly-simulation-header {
  background: #e3f2ff;
  color: #55749d;
  font-size: 12px;
  font-weight: 700;
  padding: 12px 14px;
}

.monthly-simulation-row {
  padding: 11px 14px;
  border-top: 1px solid #edf2f8;
  font-size: 13px;
  color: #002f6c;
}

.monthly-simulation-row:nth-child(even) {
  background: #fbfdff;
}

.monthly-simulation-footer {
  padding: 12px 14px;
  border-top: 1px solid #dce7f5;
  background: #f7fbff;
  font-size: 13px;
  font-weight: 700;
  color: #002f6c;
}

.month-label {
  font-weight: 700;
}

@media (max-width: 900px) {
  .optimization-summary-grid,
  .economic-total-box {
    grid-template-columns: 1fr;
  }

  .economic-powers-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .monthly-simulation-header,
  .monthly-simulation-row,
  .monthly-simulation-footer {
    grid-template-columns: 1fr 60px 100px 100px 100px 100px;
    font-size: 11px;
  }
}



</style>
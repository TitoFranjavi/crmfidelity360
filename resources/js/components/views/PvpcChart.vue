<template>
    <section class="datadis-tool min-h-100-vh px-200 py-50 d-flex justify-center items-center pvpc-page">
        <div class="w-100">
          <button
                        type="button"
                        class="pvpc-back-button"
                        @click="goToDashboard"
                    >
                        <i class="fa-solid fa-arrow-left"></i>
                        Volver
                </button>

            <!-- CABECERA -->
            <div class="d-flex justify-between align-center pvpc-toolbar">
                <div>
                    <p class="text" data-color="principal" data-weight="600" data-size="24">
                        Precios de electricidad
                    </p>
                    <p class="text opacity-6" data-size="14">
                        PVPC 2.0TD e indexados estimados para 3.0TD y 6.1TD
                    </p>
                </div>

                <div class="d-flex align-center form pvpc-actions" data-gap="10">
                    <div class="form-group mb-0">
                        <div class="input-group">
                            <select v-model="selectedTariff" @change="loadCurrentView">
                                <option value="PVPC">PVPC 2.0TD</option>
                                <option value="3.0TD">Indexado 3.0TD</option>
                                <option value="6.1TD">Indexado 6.1TD</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group mb-0" v-if="viewMode === 'daily'">
                        <div class="input-group">
                            <input type="date" v-model="selectedDate">
                        </div>
                    </div>

                    <div class="form-group mb-0" v-if="viewMode === 'range'">
                        <div class="input-group">
                            <input type="date" v-model="rangeFrom">
                        </div>
                    </div>

                    <div class="form-group mb-0" v-if="viewMode === 'range'">
                        <div class="input-group">
                            <input type="date" v-model="rangeTo">
                        </div>
                    </div>

                    <button
                        v-if="viewMode === 'daily' || viewMode === 'range'"
                        class="custom-button"
                        data-size="medium"
                        @click="loadCurrentView"
                    >
                        Consultar
                    </button>
                </div>
            </div>

            <div class="pvpc-view-tabs">
                <button
                    type="button"
                    :class="{ active: viewMode === 'daily' }"
                    @click="changeViewMode('daily')"
                >
                    Diario
                </button>

                <button
                    type="button"
                    :class="{ active: viewMode === 'weekly' }"
                    @click="changeViewMode('weekly')"
                >
                    Semanal
                </button>

                <button
                    type="button"
                    :class="{ active: viewMode === 'monthly' }"
                    @click="changeViewMode('monthly')"
                >
                    Mensual
                </button>

                <button
                    type="button"
                    :class="{ active: viewMode === 'range' }"
                    @click="changeViewMode('range')"
                >
                    Periodo
                </button>
            </div>

            <div class="separator my-10"></div>

            <!-- CARGANDO -->
            <div v-if="loading" class="dashboard-card w-100 mt-10 pvpc-loading">
                <div class="text text-center" data-color="principal" data-weight="600">
                    <i class="fa-solid fa-spinner-third fa-spin mr-10"></i>
                    Cargando datos...
                </div>
            </div>

            <!-- ERROR -->
            <div v-if="error" class="mt-20 ml-5 text pvpc-error" data-size="16">
                <i class="far fa-circle-info mr-10"></i>{{ error }}
            </div>

            <template v-if="pvpc && !loading">

                <!-- RESUMEN SUPERIOR -->
                <div class="dashboard-card w-100 mt-10 d-flex justify-around align-center pvpc-summary-card">
                    <div class="pvpc-summary-item">
                        <p class="text text-center" data-color="principal" data-weight="600" data-size="32">
                           {{ formatPrice(getDisplayedAverage()) }}
                        </p>
                        <p class="text text-center" data-size="12">Precio medio</p>
                        <p class="text text-center opacity-6" data-size="12">c€/kWh</p>
                    </div>

                    <div class="pvpc-summary-item" v-if="pvpc.min">
                        <p class="text text-center pvpc-success" data-weight="600" data-size="32">
                            {{ getExtremeMainLabel(pvpc.min) }}
                        </p>
                        <p class="text text-center" data-size="12">{{ getCheapestTitle() }}</p>
                        <p class="text text-center opacity-6" data-size="12">
                            {{ formatPrice(getExtremePrice(pvpc.min)) }} c€/kWh
                        </p>
                    </div>

                    <div class="pvpc-summary-item" v-if="pvpc.max">
                        <p class="text text-center pvpc-danger" data-weight="600" data-size="32">
                            {{ getExtremeMainLabel(pvpc.max) }}
                        </p>
                        <p class="text text-center" data-size="12">{{ getExpensiveTitle() }}</p>
                        <p class="text text-center opacity-6" data-size="12">
                            {{ formatPrice(getExtremePrice(pvpc.max)) }} c€/kWh
                        </p>
                    </div>
                </div>

                <!-- INFORME PRINCIPAL -->
                <div class="dashboard-card w-100 mt-10 pvpc-report-card">

                    <!-- TITULO -->
                    <div class="pvpc-report-head">
                        <div>
                            <p class="text" data-color="principal" data-weight="600" data-size="24">
                                {{ getChartTitle() }}
                            </p>

                            <p class="text opacity-6 pvpc-description" data-size="12">
                                {{ getChartSubtitle() }}
                            </p>
                        </div>
                    </div>

                    <!-- COMPONENTES -->
                    <div class="pvpc-component-summary">
                        <span>
                            <strong>Precio medio total:</strong>
                            {{ formatPrice(getDisplayedAverage()) }} c€/kWh
                        </span>

                        <span v-if="hasComponentValue('futures')">
                            <strong>Mercado a plazo:</strong>
                            {{ formatPrice(pvpc.components.futures.average_c_kwh) }} c€/kWh
                            ({{ pvpc.components.futures.percentage }}%)
                        </span>

                        <span v-if="getMarketComponent()">
                            <strong>{{ getMarketLabel() }}:</strong>
                            {{ formatPrice(getMarketComponent().average_c_kwh) }} c€/kWh
                            ({{ getMarketComponent().percentage }}%)
                        </span>

                        <span v-if="hasComponentValue('adjustment')">
                            <strong>Ajustes:</strong>
                            {{ formatPrice(pvpc.components.adjustment.average_c_kwh) }} c€/kWh
                            ({{ pvpc.components.adjustment.percentage }}%)
                        </span>

                        <span v-if="hasComponentValue('regulated')">
                            <strong>Peajes y cargos:</strong>
                            {{ formatPrice(pvpc.components.regulated.average_c_kwh) }} c€/kWh
                            ({{ pvpc.components.regulated.percentage }}%)
                        </span>

                        <span v-if="hasComponentValue('others')">
                            <strong>Otros:</strong>
                            {{ formatPrice(pvpc.components.others.average_c_kwh) }} c€/kWh
                            ({{ pvpc.components.others.percentage }}%)
                        </span>

                        <span class="pvpc-fee-inline">
                            <strong>Fee comercial:</strong>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                v-model.number="feeEurMwh"
                                placeholder="0"
                            >

                            <em>€/MWh</em>

                            <small v-if="getFeeCKwh() > 0">
                                +{{ formatPrice(getFeeCKwh()) }} c€/kWh
                            </small>
                        </span>

                    </div>

                    <!-- MEDIA POR VENTANA HORARIA -->
                    <div
                        v-if="viewMode === 'daily' && pvpc && pvpc.hours && pvpc.hours.length"
                        class="pvpc-drag-selection-info"
                    >
                        <template v-if="hasHourSelection()">
                            <div>
                                <strong>Media tramo:</strong>
                                <span>{{ formatPrice(getHourSelectionAverage()) }} c€/kWh</span>
                            </div>

                            <small>
                                {{ getHourSelectionLabel() }} · {{ getHourSelectionCount() }} horas
                            </small>

                            <button type="button" @click="clearHourSelection">
                                Limpiar
                            </button>
                        </template>

                        <template v-else>
                            <span>Arrastra sobre las barras para calcular la media de un tramo horario</span>
                        </template>
                    </div>

                    <!-- GRAFICA COMPLETA SIN SCROLL -->

                  <!-- GRAFICA COMPLETA -->
                  <div class="pvpc-chart-scroll">
                        <div
                            ref="chartArea"
                            class="pvpc-chart-area"
                            :class="{
                                'historical-mode': viewMode !== 'daily',
                                'monthly-mode': viewMode === 'monthly',
                                'range-mode': viewMode === 'range',
                                'selection-enabled': viewMode === 'daily',
                                'is-selecting': hourSelection.dragging
                            }"
                            @mousedown="startHourSelection"
                            @dblclick.stop="clearHourSelection"
                        >
                            <div class="pvpc-y-axis">
                                Precio c€/kWh
                            </div>

                            <div class="pvpc-average-line" :style="{ bottom: getAverageLineBottom() + 'px' }">
                                <span>Media: {{ formatPrice(getDisplayedAverage()) }}</span>
                            </div>

                            <div
                                v-for="item in pvpc.hours"
                                :key="item.unique_key || item.hour"
                                class="pvpc-hour-column"
                                :class="{
                                    'window-selected': viewMode === 'daily' && hasHourSelection() && isHourSelected(item),
                                    'window-dimmed': viewMode === 'daily' && hasHourSelection() && !isHourSelected(item)
                                }"
                            >
                                <div
                                    class="pvpc-total-label"
                                    :class="{
                                        minLabel: isMinItem(item),
                                        maxLabel: isMaxItem(item)
                                    }"
                                >
                                    {{ formatPrice(getTotalValue(item)) }}
                                </div>

                                <div class="pvpc-positive-zone">
                                    <div
                                        class="pvpc-stack energy"
                                        v-if="getPositiveValue(getMarketValue(item)) > 0"
                                        :style="{ height: getStackHeight(getPositiveValue(getMarketValue(item))) + '%' }"
                                    >
                                        <span v-if="getPositiveValue(getMarketValue(item)) >= 3">
                                            {{ formatPrice(getMarketValue(item)) }}
                                        </span>
                                    </div>

                                    <div
                                        class="pvpc-stack adjustment"
                                        v-if="getPositiveValue(item.adjustment_c_kwh) > 0"
                                        :style="{ height: getStackHeight(getPositiveValue(item.adjustment_c_kwh)) + '%' }"
                                    >
                                        <span v-if="getPositiveValue(item.adjustment_c_kwh) >= 1">
                                            {{ formatPrice(item.adjustment_c_kwh) }}
                                        </span>
                                    </div>

                                    <div
                                        class="pvpc-stack regulated"
                                        v-if="getPositiveValue(item.regulated_c_kwh) > 0"
                                        :style="{ height: getStackHeight(getPositiveValue(item.regulated_c_kwh)) + '%' }"
                                    >
                                        <span v-if="getPositiveValue(item.regulated_c_kwh) >= 1">
                                            {{ formatPrice(item.regulated_c_kwh) }}
                                        </span>
                                    </div>

                                    <div
                                        class="pvpc-stack others"
                                        v-if="getPositiveValue(item.others_c_kwh) > 0"
                                        :style="{ height: getStackHeight(getPositiveValue(item.others_c_kwh)) + '%' }"
                                    >
                                        <span v-if="getPositiveValue(item.others_c_kwh) >= 1">
                                            {{ formatPrice(item.others_c_kwh) }}
                                        </span>
                                    </div>

                                    <div
                                        class="pvpc-stack futures-positive"
                                        v-if="getPositiveValue(item.futures_c_kwh) > 0"
                                        :style="{ height: getStackHeight(getPositiveValue(item.futures_c_kwh)) + '%' }"
                                    >
                                        <span v-if="getPositiveValue(item.futures_c_kwh) >= 0.5">
                                            {{ formatPrice(item.futures_c_kwh) }}
                                        </span>
                                    </div>

                                    <div
                                        class="pvpc-stack fee"
                                        v-if="getFeeCKwh() > 0"
                                        :style="{ height: getStackHeight(getFeeCKwh()) + '%' }"
                                    >
                                        <span v-if="getFeeCKwh() >= 0.4">
                                            {{ formatPrice(getFeeCKwh()) }}
                                        </span>
                                    </div>

                                </div>

                                <div class="pvpc-zero-line"></div>

                                <div class="pvpc-negative-zone">
                                    <div
                                        class="pvpc-stack futures-negative"
                                        v-if="getNegativeValue(item.futures_c_kwh) > 0"
                                        :style="{ height: getNegativeStackHeight(getNegativeValue(item.futures_c_kwh)) + '%' }"
                                    >
                                        <span>
                                            -{{ formatPrice(getNegativeValue(item.futures_c_kwh)) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="pvpc-hour-label">
                                    {{ getAxisLabel(item) }}
                                </div>
                            </div>
                        </div>
                  </div>

                    <!-- LEYENDA -->
                    <div class="pvpc-legend">
                        <span v-if="hasComponentValue('futures')"><i class="legend-futures"></i> Mercado a plazo</span>
                        <span><i class="legend-energy"></i> {{ getMarketLabel() }}</span>
                        <span v-if="hasComponentValue('adjustment')"><i class="legend-adjustment"></i> Ajustes</span>
                        <span v-if="hasComponentValue('regulated')"><i class="legend-regulated"></i> Peajes y cargos</span>
                        <span v-if="hasComponentValue('others')"><i class="legend-others"></i> Otros</span>
                        <span v-if="getFeeCKwh() > 0"><i class="legend-fee"></i> Fee comercial</span>
                    </div>
                </div>

                <!-- MAPA DE CALOR -->
                <div
                    v-if="viewMode === 'range' && heatmap && heatmap.rows && heatmap.rows.length"
                    class="dashboard-card w-100 mt-10 pvpc-heatmap-card"
                    :class="{ 'is-selecting': heatmapSelection.dragging }"
                >
                    <div class="pvpc-heatmap-head">
                        <div>
                            <p class="text" data-color="principal" data-weight="600" data-size="22">
                                Mapa de calor de precios
                            </p>

                            <p class="text opacity-6" data-size="13">
                                Precio horario por día · {{ formatDate(heatmap.from) }} - {{ formatDate(heatmap.to) }}
                            </p>
                        </div>

                        <div class="pvpc-heatmap-summary">
                            <span>
                                Media:
                                <strong>{{ formatPrice(getHeatmapAverage()) }}</strong>
                                c€/kWh
                            </span>

                            <span v-if="heatmap.min">
                                Mín:
                                <strong>{{ formatPrice(getHeatmapMinValue()) }}</strong>
                            </span>

                            <span v-if="heatmap.max">
                                Máx:
                                <strong>{{ formatPrice(getHeatmapMaxValue()) }}</strong>
                            </span>
                        </div>
                    </div>

                    <div class="pvpc-heatmap-legend">
                        <span><i class="cold"></i> Más barato</span>
                        <span><i class="medium"></i> Medio</span>
                        <span><i class="hot"></i> Más caro</span>
                    </div>

                    <div class="pvpc-heatmap-selection-info">
                        <template v-if="hasHeatmapSelection()">
                            <div>
                                <strong>Media selección:</strong>
                                <span>{{ formatPrice(getHeatmapSelectionAverage()) }} c€/kWh</span>
                            </div>

                            <small>
                                {{ getHeatmapSelectionLabel() }} · {{ getHeatmapSelectionCount() }} horas
                            </small>

                            <button type="button" @click="clearHeatmapSelection">
                                Limpiar
                            </button>
                        </template>

                        <template v-else>
                            <span>Arrastra sobre el mapa para calcular la media de un tramo horario</span>
                        </template>
                    </div>

                    <div class="pvpc-heatmap-scroll">
                        <div
                            class="pvpc-heatmap-grid"
                            :style="{ gridTemplateColumns: '64px repeat(' + heatmap.hours.length + ', minmax(32px, 1fr))' }"
                        >
                            <div class="pvpc-heatmap-corner"></div>

                            <div
                                v-for="hour in heatmap.hours"
                                :key="'heat-head-' + hour"
                                class="pvpc-heatmap-hour"
                            >
                                {{ hour.slice(0, 2) }}
                            </div>

                            <template
                                v-for="(row, rowIndex) in heatmap.rows"
                                :key="'heat-row-' + row.date"
                            >
                                <div class="pvpc-heatmap-day">
                                    {{ row.label }}
                                </div>

                                <div
                                    v-for="(cell, cellIndex) in row.cells"
                                    :key="'heat-cell-' + cell.date + '-' + cell.hour"
                                    class="pvpc-heatmap-cell"
                                    :class="[
                                        getHeatmapCellClass(cell),
                                        {
                                            'heat-selected': hasHeatmapSelection() && isHeatmapCellSelected(rowIndex, cellIndex, cell),
                                            'heat-dimmed': hasHeatmapSelection() && !isHeatmapCellSelected(rowIndex, cellIndex, cell)
                                        }
                                    ]"
                                    :style="getHeatmapCellStyle(cell)"
                                    :title="getHeatmapCellTitle(cell)"
                                    @mousedown.stop.prevent="startHeatmapSelection(rowIndex, cellIndex, cell, $event)"
                                    @mouseenter="moveHeatmapSelection(rowIndex, cellIndex)"
                                    @dblclick.stop.prevent="clearHeatmapSelection"
                                >
                                    <strong v-if="cell.value !== null && cell.value !== undefined">
                                        {{ formatPrice(getHeatmapCellValue(cell)) }}
                                    </strong>

                                    <small v-if="cell.period">
                                        {{ cell.period }}
                                    </small>

                                    <span v-if="cell.value === null || cell.value === undefined">—</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- DETALLE -->
                <div class="dashboard-card w-100 mt-10 pvpc-detail-card">
                    <div class="d-flex justify-between align-center mb-10">
                        <p class="text" data-color="principal" data-weight="600" data-size="18">
                            {{ getDetailTitle() }}
                        </p>
                    </div>

                    <div class="pvpc-table">
                        <table v-if="viewMode === 'daily'">
                            <thead>
                                <tr>
                                    <th>Hora</th>
                                    <th v-if="selectedTariff !== 'PVPC'">Periodo</th>
                                    <th>€/MWh</th>
                                    <th>c€/kWh</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="item in pvpc.hours"
                                    :key="'row-' + item.hour"
                                    :class="{
                                        rowMin: isMinItem(item),
                                        rowMax: isMaxItem(item)
                                    }"
                                >
                                    <td>{{ getAxisLabel(item) }}</td>
                                    <td v-if="selectedTariff !== 'PVPC'">{{ item.period }}</td>
                                    <td>{{ formatPrice(getPriceEurMwh(item)) }}</td>
                                    <td>{{ formatPrice(getPriceCKwh(item)) }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <table v-else>
                            <thead>
                                <tr>
                                    <th>Periodo</th>
                                    <th>Desde</th>
                                    <th>Hasta</th>
                                    <th>Media</th>
                                    <th>Mínimo</th>
                                    <th>Máximo</th>
                                    <th>Nº horas</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="item in pvpc.hours"
                                    :key="'history-row-' + item.unique_key"
                                    :class="{
                                        rowMin: isMinItem(item),
                                        rowMax: isMaxItem(item)
                                    }"
                                >
                                    <td>{{ item.title_label }}</td>
                                    <td>{{ formatDate(item.start_date) }}</td>
                                    <td>{{ formatDate(item.end_date) }}</td>
                                    <td>{{ formatPrice(item.total_c_kwh) }} c€/kWh</td>
                                    <td>
                                        {{ formatPrice(item.min_c_kwh) }} c€/kWh
                                        <small v-if="item.min">
                                            {{ formatDate(item.min.date) }} {{ item.min.hour_label }}
                                        </small>
                                    </td>
                                    <td>
                                        {{ formatPrice(item.max_c_kwh) }} c€/kWh
                                        <small v-if="item.max">
                                            {{ formatDate(item.max.date) }} {{ item.max.hour_label }}
                                        </small>
                                    </td>
                                    <td>{{ item.records }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </template>
        </div>
    </section>
</template>

<script>
import axios from 'axios'
import moment from 'moment'

export default {
    name: 'PvpcChart',

    data() {
        return {
            selectedDate: '',
            selectedTariff: 'PVPC',
            viewMode: 'daily',
            historyMonths: 6,
            rangeFrom: '',
            rangeTo: '',
            pvpc: null,
            heatmap: null,
            loading: false,
            heatmapLoading: false,
            error: null,
            heatmapError: null,
            feeEurMwh: 0,

            hourSelection: {
                active: false,
                dragging: false,
                startHour: null,
                endHour: null,
            },

            heatmapSelection: {
                active: false,
                dragging: false,
                startRow: null,
                startCol: null,
                endRow: null,
                endCol: null,
            },
        }
    },

    mounted() {
        this.selectedDate = this.getTodayDate()
        this.rangeFrom = moment().startOf('month').format('YYYY-MM-DD')
        this.rangeTo = moment().format('YYYY-MM-DD')
        this.loadCurrentView()
    },

    beforeDestroy() {
        this.removeHourSelectionListeners()
        this.removeHeatmapSelectionListeners()
    },

    methods: {
        goToDashboard() {
            const target = this.$router.hasRoute && this.$router.hasRoute('dashboard')
                ? { name: 'dashboard' }
                : '/'

            this.$router.push(target).catch(() => {})
        },

        changeViewMode(mode) {
            if (this.viewMode === mode) {
                return
            }

            this.viewMode = mode
            this.clearHourSelection()
            this.clearHeatmapSelection()
            this.loadCurrentView()
        },

        async loadCurrentView() {
            if (this.viewMode === 'daily') {
                return this.loadPvpc()
            }

            if (this.viewMode === 'range') {
                return this.loadDailyRangePvpc()
            }

            return this.loadHistoricalPvpc()
        },

        async loadPvpc() {
            this.loading = true
            this.error = null
            this.heatmap = null
            this.heatmapError = null
            this.clearHeatmapSelection()

            try {
                let response = null

                if (this.selectedTariff === 'PVPC') {
                    response = await axios.get('/api/pvpc/date', {
                        params: {
                            date: this.selectedDate,
                        },
                    })
                } else {
                    response = await axios.get('/api/pvpc/indexed/date', {
                        params: {
                            date: this.selectedDate,
                            tariff: this.selectedTariff,
                        },
                    })
                }

                this.pvpc = response.data
                this.clearHourSelection()
            } catch (error) {
                this.pvpc = null
                this.error = error.response?.data?.message || 'No se han podido cargar los datos'
            } finally {
                this.loading = false
            }
        },

        async loadDailyRangePvpc() {
            this.loading = true
            this.heatmapLoading = true
            this.error = null
            this.heatmapError = null
            this.clearHeatmapSelection()

            try {
                const [dailyResponse, heatmapResponse] = await Promise.all([
                    axios.get('/api/pvpc/historical/daily', {
                        params: {
                            from: this.rangeFrom,
                            to: this.rangeTo,
                            tariff: this.selectedTariff,
                        },
                    }),
                    axios.get('/api/pvpc/historical/heatmap', {
                        params: {
                            from: this.rangeFrom,
                            to: this.rangeTo,
                            tariff: this.selectedTariff,
                        },
                    }),
                ])

                this.pvpc = this.normalizeDailyRangePvpc(dailyResponse.data)
                this.heatmap = heatmapResponse.data
                this.clearHourSelection()
            } catch (error) {
                this.pvpc = null
                this.heatmap = null
                this.error = error.response?.data?.message || 'No se han podido cargar los datos del periodo'
            } finally {
                this.loading = false
                this.heatmapLoading = false
            }
        },

        async loadHistoricalPvpc() {
            this.loading = true
            this.error = null
            this.heatmap = null
            this.heatmapError = null
            this.clearHeatmapSelection()

            try {
                const endpoint = this.viewMode === 'weekly'
                    ? '/api/pvpc/historical/weekly'
                    : '/api/pvpc/historical/monthly'

                const response = await axios.get(endpoint, {
                    params: {
                        months: this.historyMonths,
                        tariff: this.selectedTariff,
                    },
                })

                this.pvpc = this.normalizeHistoricalPvpc(response.data)
            } catch (error) {
                this.pvpc = null
                this.error = error.response?.data?.message || 'No se han podido cargar los datos históricos'
            } finally {
                this.loading = false
            }
        },

        normalizeDailyRangePvpc(data) {
            const days = data.days || []

            const hours = days.map((item, index) => {
                const average = Number(item.average_c_kwh || 0)

                return {
                    unique_key: item.date || index,
                    hour: item.date || index,
                    hour_label: item.label || moment(item.date).format('DD/MM'),
                    axis_label: item.label || moment(item.date).format('DD/MM'),
                    title_label: item.date ? moment(item.date).format('DD/MM/YYYY') : item.label,
                    start_date: item.date,
                    end_date: item.date,

                    price_eur_mwh: average * 10,
                    price_c_kwh: average,
                    total_eur_mwh: average * 10,
                    total_c_kwh: average,

                    energy_eur_mwh: average * 10,
                    energy_c_kwh: average,
                    market_eur_mwh: average * 10,
                    market_c_kwh: average,

                    futures_eur_mwh: 0,
                    futures_c_kwh: 0,
                    adjustment_eur_mwh: 0,
                    adjustment_c_kwh: 0,
                    regulated_eur_mwh: 0,
                    regulated_c_kwh: 0,
                    others_eur_mwh: 0,
                    others_c_kwh: 0,

                    records: item.hours_count,
                }
            })

            const minDay = hours.reduce((min, item) => {
                if (!min || Number(item.total_c_kwh) < Number(min.total_c_kwh)) {
                    return item
                }

                return min
            }, null)

            const maxDay = hours.reduce((max, item) => {
                if (!max || Number(item.total_c_kwh) > Number(max.total_c_kwh)) {
                    return item
                }

                return max
            }, null)

            const tariffLabel = this.selectedTariff === 'PVPC'
                ? 'PVPC'
                : 'Indexado ' + this.selectedTariff

            return {
                success: true,
                view_mode: 'range',
                tariff: this.selectedTariff,
                title: tariffLabel + ': media diaria del ' + moment(data.from).format('DD/MM/YYYY') + ' al ' + moment(data.to).format('DD/MM/YYYY'),
                subtitle: 'Cada barra representa la media diaria del periodo seleccionado',
                average_c_kwh: data.average_c_kwh || 0,
                min: minDay
                    ? {
                        hour: minDay.hour,
                        unique_key: minDay.unique_key,
                        hour_label: minDay.axis_label,
                        price_c_kwh: minDay.total_c_kwh,
                        total_c_kwh: minDay.total_c_kwh,
                    }
                    : null,
                max: maxDay
                    ? {
                        hour: maxDay.hour,
                        unique_key: maxDay.unique_key,
                        hour_label: maxDay.axis_label,
                        price_c_kwh: maxDay.total_c_kwh,
                        total_c_kwh: maxDay.total_c_kwh,
                    }
                    : null,
                components: {
                    energy: {
                        label: this.selectedTariff === 'PVPC' ? 'PVPC total' : 'Indexado total',
                        average_c_kwh: data.average_c_kwh || 0,
                        percentage: 100,
                    },
                    futures: {
                        label: 'Mercado a plazo',
                        average_c_kwh: 0,
                        percentage: 0,
                    },
                    adjustment: {
                        label: 'Ajustes',
                        average_c_kwh: 0,
                        percentage: 0,
                    },
                    regulated: {
                        label: 'Peajes y cargos',
                        average_c_kwh: 0,
                        percentage: 0,
                    },
                    others: {
                        label: 'Otros',
                        average_c_kwh: 0,
                        percentage: 0,
                    },
                },
                hours: hours,
            }
        },

        normalizeHistoricalPvpc(data) {
            const periods = data.periods || []

            const hours = periods.map((item, index) => {
                const average = Number(item.average_c_kwh || 0)

                return {
                    unique_key: item.key || index,
                    hour: item.key || index,
                    hour_label: item.short_label,
                    axis_label: item.short_label,
                    title_label: item.label,
                    start_date: item.start_date,
                    end_date: item.end_date,

                    price_eur_mwh: average * 10,
                    price_c_kwh: average,
                    total_eur_mwh: average * 10,
                    total_c_kwh: average,

                    energy_eur_mwh: average * 10,
                    energy_c_kwh: average,
                    market_eur_mwh: average * 10,
                    market_c_kwh: average,

                    futures_eur_mwh: 0,
                    futures_c_kwh: 0,
                    adjustment_eur_mwh: 0,
                    adjustment_c_kwh: 0,
                    regulated_eur_mwh: 0,
                    regulated_c_kwh: 0,
                    others_eur_mwh: 0,
                    others_c_kwh: 0,

                    min_c_kwh: item.min_c_kwh,
                    max_c_kwh: item.max_c_kwh,
                    min: item.min,
                    max: item.max,
                    records: item.records,
                }
            })

            const minPeriod = hours.reduce((min, item) => {
                if (!min || Number(item.total_c_kwh) < Number(min.total_c_kwh)) {
                    return item
                }

                return min
            }, null)

            const maxPeriod = hours.reduce((max, item) => {
                if (!max || Number(item.total_c_kwh) > Number(max.total_c_kwh)) {
                    return item
                }

                return max
            }, null)

            const tariffLabel = this.selectedTariff === 'PVPC'
                ? 'PVPC'
                : 'Indexado ' + this.selectedTariff

            const title = this.viewMode === 'weekly'
                ? tariffLabel + ' semanal: evolución de los últimos ' + this.historyMonths + ' meses'
                : tariffLabel + ' mensual: evolución de los últimos ' + this.historyMonths + ' meses'

            return {
                success: true,
                view_mode: this.viewMode,
                tariff: this.selectedTariff,
                title: title,
                subtitle: 'Precio medio total horario agrupado por ' + (this.viewMode === 'weekly' ? 'semanas' : 'meses'),
                average_c_kwh: data.summary?.average_c_kwh || 0,
                min: minPeriod
                    ? {
                        hour: minPeriod.hour,
                        unique_key: minPeriod.unique_key,
                        hour_label: minPeriod.axis_label,
                        price_c_kwh: minPeriod.total_c_kwh,
                        total_c_kwh: minPeriod.total_c_kwh,
                    }
                    : null,
                max: maxPeriod
                    ? {
                        hour: maxPeriod.hour,
                        unique_key: maxPeriod.unique_key,
                        hour_label: maxPeriod.axis_label,
                        price_c_kwh: maxPeriod.total_c_kwh,
                        total_c_kwh: maxPeriod.total_c_kwh,
                    }
                    : null,
                components: {
                    energy: {
                        label: this.selectedTariff === 'PVPC' ? 'PVPC total' : 'Indexado total',
                        average_c_kwh: data.summary?.average_c_kwh || 0,
                        percentage: 100,
                    },
                    futures: {
                        label: 'Mercado a plazo',
                        average_c_kwh: 0,
                        percentage: 0,
                    },
                    adjustment: {
                        label: 'Ajustes',
                        average_c_kwh: 0,
                        percentage: 0,
                    },
                    regulated: {
                        label: 'Peajes y cargos',
                        average_c_kwh: 0,
                        percentage: 0,
                    },
                    others: {
                        label: 'Otros',
                        average_c_kwh: 0,
                        percentage: 0,
                    },
                },
                hours: hours,
            }
        },

        async downloadPvpc() {
            this.loading = true
            this.error = null

            try {
                await axios.get('/api/pvpc/download-test', {
                    params: {
                        date: this.selectedDate,
                        force: 1,
                    },
                })

                await this.loadPvpc()
            } catch (error) {
                this.error = error.response?.data?.message || 'No se ha podido descargar el PVPC'
            } finally {
                this.loading = false
            }
        },

        startHourSelection(event) {
            if (this.viewMode !== 'daily' || !this.pvpc?.hours?.length) {
                return
            }

            if (event.button !== 0) {
                return
            }

            const hour = this.getHourFromMouseEvent(event)

            if (hour === null) {
                return
            }

            event.preventDefault()

            this.hourSelection.active = true
            this.hourSelection.dragging = true
            this.hourSelection.startHour = hour
            this.hourSelection.endHour = hour

            document.addEventListener('mousemove', this.moveHourSelection)
            document.addEventListener('mouseup', this.endHourSelection)
        },

        moveHourSelection(event) {
            if (!this.hourSelection.dragging) {
                return
            }

            const hour = this.getHourFromMouseEvent(event)

            if (hour === null) {
                return
            }

            this.hourSelection.endHour = hour
        },

        endHourSelection() {
            if (!this.hourSelection.dragging) {
                return
            }

            this.hourSelection.dragging = false
            this.hourSelection.active = true

            this.removeHourSelectionListeners()
        },

        removeHourSelectionListeners() {
            document.removeEventListener('mousemove', this.moveHourSelection)
            document.removeEventListener('mouseup', this.endHourSelection)
        },

        clearHourSelection() {
            this.hourSelection.active = false
            this.hourSelection.dragging = false
            this.hourSelection.startHour = null
            this.hourSelection.endHour = null

            this.removeHourSelectionListeners()
        },

        startHeatmapSelection(rowIndex, cellIndex, cell, event) {
            if (this.viewMode !== 'range' || !this.heatmap?.rows?.length) {
                return
            }

            if (event && event.button !== 0) {
                return
            }

            if (!cell || cell.value === null || cell.value === undefined) {
                return
            }

            if (event) {
                event.preventDefault()
            }

            this.heatmapSelection.active = true
            this.heatmapSelection.dragging = true
            this.heatmapSelection.startRow = rowIndex
            this.heatmapSelection.startCol = cellIndex
            this.heatmapSelection.endRow = rowIndex
            this.heatmapSelection.endCol = cellIndex

            document.addEventListener('mouseup', this.endHeatmapSelection)
        },

        moveHeatmapSelection(rowIndex, cellIndex) {
            if (!this.heatmapSelection.dragging) {
                return
            }

            this.heatmapSelection.endRow = rowIndex
            this.heatmapSelection.endCol = cellIndex
        },

        endHeatmapSelection() {
            if (!this.heatmapSelection.dragging) {
                return
            }

            this.heatmapSelection.dragging = false
            this.heatmapSelection.active = true

            this.removeHeatmapSelectionListeners()
        },

        removeHeatmapSelectionListeners() {
            document.removeEventListener('mouseup', this.endHeatmapSelection)
        },

        clearHeatmapSelection() {
            this.heatmapSelection.active = false
            this.heatmapSelection.dragging = false
            this.heatmapSelection.startRow = null
            this.heatmapSelection.startCol = null
            this.heatmapSelection.endRow = null
            this.heatmapSelection.endCol = null

            this.removeHeatmapSelectionListeners()
        },

        hasHeatmapSelection() {
            return this.heatmapSelection.active
                && this.heatmapSelection.startRow !== null
                && this.heatmapSelection.startCol !== null
                && this.heatmapSelection.endRow !== null
                && this.heatmapSelection.endCol !== null
        },

        getHeatmapSelectionRange() {
            if (!this.hasHeatmapSelection()) {
                return null
            }

            const startRow = Number(this.heatmapSelection.startRow)
            const endRow = Number(this.heatmapSelection.endRow)
            const startCol = Number(this.heatmapSelection.startCol)
            const endCol = Number(this.heatmapSelection.endCol)

            return {
                fromRow: Math.min(startRow, endRow),
                toRow: Math.max(startRow, endRow),
                fromCol: Math.min(startCol, endCol),
                toCol: Math.max(startCol, endCol),
            }
        },

        isHeatmapCellSelected(rowIndex, cellIndex, cell) {
            if (!cell || cell.value === null || cell.value === undefined) {
                return false
            }

            const range = this.getHeatmapSelectionRange()

            if (!range) {
                return false
            }

            return rowIndex >= range.fromRow
                && rowIndex <= range.toRow
                && cellIndex >= range.fromCol
                && cellIndex <= range.toCol
        },

        getSelectedHeatmapCells() {
            const range = this.getHeatmapSelectionRange()

            if (!range || !this.heatmap?.rows?.length) {
                return []
            }

            const cells = []

            this.heatmap.rows.forEach((row, rowIndex) => {
                if (rowIndex < range.fromRow || rowIndex > range.toRow) {
                    return
                }

                ;(row.cells || []).forEach((cell, cellIndex) => {
                    if (cellIndex < range.fromCol || cellIndex > range.toCol) {
                        return
                    }

                    if (!cell || cell.value === null || cell.value === undefined) {
                        return
                    }

                    cells.push(cell)
                })
            })

            return cells
        },

        getHeatmapSelectionAverage() {
            const cells = this.getSelectedHeatmapCells()

            if (!cells.length) {
                return 0
            }

            const total = cells.reduce((sum, cell) => {
                return sum + this.getHeatmapCellValue(cell)
            }, 0)

            return total / cells.length
        },

        getHeatmapSelectionCount() {
            return this.getSelectedHeatmapCells().length
        },

        getHeatmapSelectionLabel() {
            const range = this.getHeatmapSelectionRange()

            if (!range || !this.heatmap?.rows?.length || !this.heatmap?.hours?.length) {
                return ''
            }

            const fromRow = this.heatmap.rows[range.fromRow]
            const toRow = this.heatmap.rows[range.toRow]
            const fromHour = this.heatmap.hours[range.fromCol]
            const toHour = this.heatmap.hours[range.toCol]

            const dayLabel = fromRow && toRow && fromRow.date !== toRow.date
                ? `${fromRow.label} - ${toRow.label}`
                : (fromRow?.label || '')

            const hourLabel = fromHour && toHour
                ? `${fromHour} - ${toHour}`
                : ''

            return [dayLabel, hourLabel].filter(Boolean).join(' · ')
        },

        getHourFromMouseEvent(event) {
            const chart = this.$refs.chartArea

            if (!chart) {
                return null
            }

            const columns = Array.from(chart.querySelectorAll('.pvpc-hour-column'))

            if (!columns.length) {
                return null
            }

            const mouseX = event.clientX
            let closestColumn = null
            let closestDistance = null

            columns.forEach((column, index) => {
                const rect = column.getBoundingClientRect()
                const centerX = rect.left + (rect.width / 2)
                const distance = Math.abs(mouseX - centerX)

                if (closestDistance === null || distance < closestDistance) {
                    closestDistance = distance
                    closestColumn = {
                        index,
                        element: column,
                    }
                }
            })

            if (!closestColumn || !this.pvpc.hours[closestColumn.index]) {
                return null
            }

            return this.parseHourValue(this.pvpc.hours[closestColumn.index].hour)
        },

        parseHourValue(value) {
            if (typeof value === 'number') {
                return value
            }

            if (value === null || value === undefined) {
                return null
            }

            const valueString = String(value)

            if (valueString.includes(':')) {
                const hour = Number(valueString.split(':')[0])
                return Number.isNaN(hour) ? null : hour
            }

            const hour = Number(valueString)
            return Number.isNaN(hour) ? null : hour
        },

        hasHourSelection() {
            return this.hourSelection.active
                && this.hourSelection.startHour !== null
                && this.hourSelection.endHour !== null
        },

        getHourSelectionRange() {
            if (!this.hasHourSelection()) {
                return null
            }

            const start = Number(this.hourSelection.startHour)
            const end = Number(this.hourSelection.endHour)

            return {
                from: Math.min(start, end),
                to: Math.max(start, end),
            }
        },

        isHourSelected(item) {
            const range = this.getHourSelectionRange()

            if (!range) {
                return false
            }

            const hour = this.parseHourValue(item.hour)

            if (hour === null) {
                return false
            }

            return hour >= range.from && hour <= range.to
        },

        getSelectedHourItems() {
            if (!this.pvpc?.hours?.length || !this.hasHourSelection()) {
                return []
            }

            return this.pvpc.hours.filter(item => this.isHourSelected(item))
        },

        getHourSelectionAverage() {
            const items = this.getSelectedHourItems()

            if (!items.length) {
                return 0
            }

            const total = items.reduce((sum, item) => {
                return sum + this.getTotalHourValue(item)
            }, 0)

            return total / items.length
        },

        getHourSelectionCount() {
            return this.getSelectedHourItems().length
        },

        getHourSelectionLabel() {
            const range = this.getHourSelectionRange()

            if (!range) {
                return ''
            }

            return this.formatHour(range.from) + ' - ' + this.formatHour(range.to)
        },

        formatHour(hour) {
            return String(hour).padStart(2, '0') + ':00'
        },

        getTotalHourValue(item) {
            return this.getTotalWithFeeCKwh(item)
        },

        getTodayDate() {
            const date = new Date()

            const year = date.getFullYear()
            const month = String(date.getMonth() + 1).padStart(2, '0')
            const day = String(date.getDate()).padStart(2, '0')

            return `${year}-${month}-${day}`
        },

        getTotalValue(item) {
            return this.getTotalWithFeeCKwh(item)
        },


        getFeeCKwh() {
            const fee = Number(this.feeEurMwh || 0)

            if (fee <= 0) {
                return 0
            }

            // Conversión: €/MWh a c€/kWh
            return fee / 10
        },

        getBaseTotalCKwh(item) {
            return Number(item.total_c_kwh ?? item.price_c_kwh ?? 0)
        },

        getTotalWithFeeCKwh(item) {
            return this.getBaseTotalCKwh(item) + this.getFeeCKwh()
        },

        getDisplayedAverage() {
            return Number(this.pvpc?.average_c_kwh || 0) + this.getFeeCKwh()
        },

        getFeePercentage() {
            const total = this.getDisplayedAverage()
            const fee = this.getFeeCKwh()

            if (total <= 0 || fee <= 0) {
                return 0
            }

            return Math.round((fee / total) * 100)
        },

        getPositiveValue(value) {
            value = Number(value || 0)
            return value > 0 ? value : 0
        },

        getNegativeValue(value) {
            value = Number(value || 0)
            return value < 0 ? Math.abs(value) : 0
        },

        getMaxPositiveStack() {
            if (!this.pvpc?.hours?.length) {
                return 1;
            }

            const max = Math.max(...this.pvpc.hours.map(item => {
                return this.getPositiveValue(item.futures_c_kwh)
                      + this.getPositiveValue(this.getMarketValue(item))
                      + this.getPositiveValue(item.adjustment_c_kwh)
                      + this.getPositiveValue(item.regulated_c_kwh)
                      + this.getPositiveValue(item.others_c_kwh)
                      + this.getPositiveValue(this.getFeeCKwh());
            }));

            return max > 0 ? max : 1;
        },

        getMaxNegativeStack() {
            if (!this.pvpc || !this.pvpc.hours?.length) {
                return 1
            }

            const values = this.pvpc.hours.map(item => {
                return this.getNegativeValue(item.futures_c_kwh)
            })

            return Math.max(...values, 1)
        },

        getStackHeight(value) {
            const max = this.getMaxPositiveStack()
            return Math.max((Number(value || 0) / max) * 100, 2)
        },

        getNegativeStackHeight(value) {
            const max = this.getMaxNegativeStack()
            return Math.max((Number(value || 0) / max) * 100, 8)
        },

        getAverageLineBottom() {
            if (!this.pvpc || !this.pvpc.hours?.length) {
                return 0
            }

            const max = this.getMaxPositiveStack()
            const avg = this.getDisplayedAverage()

            if (max <= 0 || avg <= 0) {
                return 0
            }

            /*
            * Estas medidas vienen del CSS actual:
            *
            * .pvpc-chart-area height: 560px
            * padding-top: 42px
            * padding-bottom: 42px
            *
            * .pvpc-hour-column:
            * grid-template-rows: 1fr 1px 68px 26px;
            *
            * 68px = zona negativa
            * 26px = etiquetas de horas
            * 1px  = línea cero
            */

            const chartHeight = 560
            const paddingTop = 42
            const paddingBottom = 42
            const negativeZoneHeight = 68
            const hourLabelHeight = 26
            const zeroLineHeight = 1

            const positiveZoneHeight =
                chartHeight
                - paddingTop
                - paddingBottom
                - negativeZoneHeight
                - hourLabelHeight
                - zeroLineHeight

            const zeroLineBottom =
                paddingBottom
                + hourLabelHeight
                + negativeZoneHeight
                + zeroLineHeight

            const position = zeroLineBottom + ((avg / max) * positiveZoneHeight)

            return Math.min(
                Math.max(position, zeroLineBottom),
                zeroLineBottom + positiveZoneHeight
            )
        },

        getMarketComponent() {
            if (!this.pvpc) {
                return null;
            }

            // Semanal / mensual
            if (this.viewMode !== 'daily') {
                return {
                    label: this.selectedTariff === 'PVPC' ? 'PVPC total' : 'Indexado total',
                    average_c_kwh: Number(this.pvpc.average_c_kwh || 0),
                    percentage: 100,
                };
            }

            // PVPC diario: recalculamos la energía como resto para que cuadre con las barras
            if (this.selectedTariff === 'PVPC' && this.pvpc.hours?.length) {
                const values = this.pvpc.hours.map(item => this.getMarketValue(item));
                const average = values.length
                    ? values.reduce((sum, value) => sum + value, 0) / values.length
                    : 0;

                const totalAverage = Number(this.pvpc.average_c_kwh || 0);

                return {
                    label: 'Energía',
                    average_c_kwh: average,
                    percentage: totalAverage > 0 ? Math.round((average / totalAverage) * 100) : 0,
                };
            }

            return this.pvpc.components?.energy || this.pvpc.components?.market || null;
        },

        getMarketLabel() {
            if (this.viewMode !== 'daily') {
                return this.selectedTariff === 'PVPC' ? 'PVPC total' : 'Indexado total';
            }

            return this.selectedTariff === 'PVPC' ? 'Energía' : 'Mercado';
        },

        getMarketValue(item) {
          const total = Number(item.total_c_kwh ?? item.price_c_kwh ?? 0);
          const energy = Number(item.energy_c_kwh ?? 0);
          const market = Number(item.market_c_kwh ?? 0);

          const futures = Number(item.futures_c_kwh ?? 0);
          const adjustment = Number(item.adjustment_c_kwh ?? 0);
          const regulated = Number(item.regulated_c_kwh ?? 0);
          const others = Number(item.others_c_kwh ?? 0);

          // Semanal / mensual: solo pintamos el total agrupado
          if (this.viewMode !== 'daily') {
              return total;
          }

          // PVPC diario con desglose:
          // el tramo principal debe ser el resto para que la suma cuadre con el total.
          if (this.selectedTariff === 'PVPC') {
              const hasBreakdown = futures > 0 || adjustment > 0 || regulated > 0 || others > 0;

              if (hasBreakdown) {
                  const residual = total - futures - adjustment - regulated - others;

                  return residual > 0 ? residual : 0;
              }

              // PVPC histórico diario sin desglose
              return total;
          }

          // 3.0TD / 6.1TD
          return market > 0 ? market : energy;
      },

        hasComponentValue(componentKey) {
            const component = this.pvpc?.components?.[componentKey]
            return Number(component?.average_c_kwh || 0) !== 0
        },

        getChartTitle() {
            if (!this.pvpc) {
                return ''
            }

            if (this.viewMode !== 'daily') {
                return this.pvpc.title
            }

            return this.pvpc.title || 'Tarifa ' + this.selectedTariff + ': precios para ' + moment(this.selectedDate).format('DD/MM/YYYY')
        },

        getChartSubtitle() {
            if (this.viewMode === 'daily') {
                return 'Energía: Mercado diario e intradiario · Mercado a plazo · Ajustes · Otros costes regulados'
            }

            return this.pvpc?.subtitle || 'Precio medio total horario agrupado'
        },

        getAxisLabel(item) {
            if (this.viewMode === 'daily') {
                return item.hour
            }

            return item.axis_label || item.hour_label || item.hour
        },

        getExtremeMainLabel(item) {
            if (!item) {
                return '-'
            }

            if (this.viewMode === 'daily') {
                return item.hour
            }

            return item.hour_label || item.hour
        },

        getCheapestTitle() {
            if (this.viewMode === 'daily') {
                return 'Hora más barata'
            }

            if (this.viewMode === 'range') {
                return 'Día más barato'
            }

            return 'Periodo más barato'
        },

        getExpensiveTitle() {
            if (this.viewMode === 'daily') {
                return 'Hora más cara'
            }

            if (this.viewMode === 'range') {
                return 'Día más caro'
            }

            return 'Periodo más caro'
        },

        getExtremePrice(item) {
            if (!item) {
                return 0
            }

            return Number(item.price_c_kwh ?? item.total_c_kwh ?? item.value ?? 0) + this.getFeeCKwh()
        },

        isMinItem(item) {
            if (!this.pvpc?.min) {
                return false
            }

            return String(item.unique_key ?? item.hour) === String(this.pvpc.min.unique_key ?? this.pvpc.min.hour)
        },

        isMaxItem(item) {
            if (!this.pvpc?.max) {
                return false
            }

            return String(item.unique_key ?? item.hour) === String(this.pvpc.max.unique_key ?? this.pvpc.max.hour)
        },

        getDetailTitle() {
            if (this.viewMode === 'weekly') {
                return 'Detalle semanal'
            }

            if (this.viewMode === 'monthly') {
                return 'Detalle mensual'
            }

            if (this.viewMode === 'range') {
                return 'Detalle diario'
            }

            return 'Detalle horario'
        },

        getHeatmapCellValue(cell) {
            if (!cell || cell.value === null || cell.value === undefined) {
                return null
            }

            return Number(cell.value || 0) + this.getFeeCKwh()
        },

        getHeatmapAverage() {
            if (!this.heatmap) {
                return 0
            }

            return Number(this.heatmap.average_c_kwh || 0) + this.getFeeCKwh()
        },

        getHeatmapMinValue() {
            if (!this.heatmap?.min) {
                return 0
            }

            return Number(this.heatmap.min.value || 0) + this.getFeeCKwh()
        },

        getHeatmapMaxValue() {
            if (!this.heatmap?.max) {
                return 0
            }

            return Number(this.heatmap.max.value || 0) + this.getFeeCKwh()
        },

        getHeatmapCellClass(cell) {
            if (!cell || cell.value === null || cell.value === undefined || !this.heatmap) {
                return 'empty'
            }

            const value = Number(cell.value)
            const min = Number(this.heatmap.min_value || 0)
            const max = Number(this.heatmap.max_value || 0)

            if (max <= min) {
                return 'medium'
            }

            const ratio = (value - min) / (max - min)

            if (ratio <= 0.25) {
                return 'cold'
            }

            if (ratio <= 0.6) {
                return 'medium'
            }

            return 'hot'
        },

        getHeatmapCellStyle(cell) {
            if (!cell || cell.value === null || cell.value === undefined || !this.heatmap) {
                return {}
            }

            const value = Number(cell.value)
            const min = Number(this.heatmap.min_value || 0)
            const max = Number(this.heatmap.max_value || 0)

            if (max <= min) {
                return {
                    backgroundColor: '#ffb45c',
                }
            }

            const ratio = Math.min(Math.max((value - min) / (max - min), 0), 1)

            let backgroundColor = '#ffb45c'

            if (ratio <= 0.25) {
                backgroundColor = '#61c0f8'
            } else if (ratio <= 0.45) {
                backgroundColor = '#ffd166'
            } else if (ratio <= 0.7) {
                backgroundColor = '#ff9f43'
            } else if (ratio <= 0.88) {
                backgroundColor = '#ff5a4f'
            } else {
                backgroundColor = '#d71920'
            }

            return {
                backgroundColor,
            }
        },

        getHeatmapCellTitle(cell) {
            if (!cell || cell.value === null || cell.value === undefined) {
                return 'Sin datos'
            }

            const period = cell.period ? ' · ' + cell.period : ''

            return cell.day_label + ' · ' + cell.hour + ' · ' + this.formatPrice(this.getHeatmapCellValue(cell)) + ' c€/kWh' + period
        },

        formatDate(date) {
            if (!date) {
                return ''
            }

            return moment(date).format('DD/MM/YYYY')
        },

        getPriceCKwh(item) {
            return this.getTotalWithFeeCKwh(item)
        },

        getPriceEurMwh(item) {
            return this.getPriceCKwh(item) * 10
        },

        formatPrice(value) {
            return Number(value || 0).toLocaleString('es-ES', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })
        },
    },
}
</script>

<style scoped>
.pvpc-toolbar {
    margin-top: 5px;
    margin-bottom: 10px;
}

.pvpc-actions {
    justify-content: flex-end;
}

.pvpc-loading {
    min-height: 110px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.pvpc-error {
    color: #d93025;
    font-weight: 600;
}

/* RESUMEN */
.pvpc-summary-card {
    min-height: 150px;
    padding: 28px 20px;
}

.pvpc-summary-item {
    min-width: 220px;
}

.pvpc-success {
    color: #159947;
}

.pvpc-danger {
    color: #d93025;
}

/* CARD PRINCIPAL */
.pvpc-report-card {
    display: block !important;
    padding: 26px;
    overflow: hidden;
}

.pvpc-report-head {
    width: 100%;
    margin-bottom: 10px;
}

.pvpc-description {
    margin-top: 6px;
    line-height: 1.4;
    max-width: 100%;
}

/* RESUMEN COMPONENTES */
.pvpc-component-summary {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px 14px;
}

.pvpc-component-summary span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.pvpc-component-summary span:not(:last-child)::after {
    content: "|";
    margin: 0 10px;
    color: #b7c0ce;
}

.pvpc-component-summary strong {
    color: #0b2e63;
}

/* GRAFICA COMPLETA SIN SCROLL */
.pvpc-chart-scroll {
    width: 100%;
    overflow-x: visible;
    overflow-y: hidden;
}
.pvpc-chart-area {
    position: relative;
    width: 100%;
    height: 560px;

    display: grid;
    grid-template-columns: repeat(24, minmax(0, 1fr));
    column-gap: 7px;

    align-items: end;
    padding: 42px 16px 42px 54px;

    border-bottom: 2px solid #0b2e63;

    background:
        repeating-linear-gradient(
            to top,
            transparent 0,
            transparent 78px,
            #e8edf5 80px
        );
}

.pvpc-y-axis {
    position: absolute;
    left: -10px;
    top: 255px;
    transform: rotate(-90deg);
    color: #0b2e63;
    font-size: 14px;
    font-weight: 600;
}

.pvpc-average-line {
    position: absolute;
    left: 54px;
    right: 16px;
    border-top: 1px dashed #d85252;
    z-index: 5;
    pointer-events: none;
}

.pvpc-fee-field {
    min-width: 140px;
}

.pvpc-fee-input {
    height: 38px;
    display: flex;
    align-items: center;
    border: 1px solid #d6e0ee;
    border-radius: 12px;
    background: #ffffff;
    overflow: hidden;
}

.pvpc-fee-input input {
    width: 82px;
    height: 100%;
    border: 0;
    padding: 0 10px;
    color: #0b2e63;
    font-weight: 800;
    outline: none;
    background: transparent;
}

.pvpc-fee-input span {
    height: 100%;
    padding: 0 10px;
    display: flex;
    align-items: center;
    color: #64748b;
    font-size: 12px;
    font-weight: 800;
    border-left: 1px solid #d6e0ee;
    background: #f8fafc;
}

.pvpc-stack.fee {
    background: #f59e0b;
    color: #ffffff;
}

.legend-fee {
    background: #f59e0b;
}

.pvpc-average-line span {
    position: absolute;
    right: -6px;
    top: -10px;
    background: white;
    color: #d85252;
    font-size: 13px;
    font-weight: 700;
    padding-left: 6px;
}

.pvpc-hour-column {
    height: 100%;
    min-width: 0;

    display: grid;
    grid-template-rows: 1fr 1px 68px 26px;
    align-items: end;

    position: relative;
}

.pvpc-total-label {
    position: absolute;
    top: -28px;
    left: 50%;
    transform: translateX(-50%);

    font-size: clamp(8px, 0.65vw, 13px);
    font-weight: 800;
    color: #0b2e63;
    white-space: nowrap;
}

.minLabel {
    background: #70c8ff;
    color: white;
    border-radius: 9px;
    padding: 3px 7px;
}

.maxLabel {
    background: #ff3b30;
    color: white;
    border-radius: 9px;
    padding: 3px 7px;
}

.pvpc-positive-zone {
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
}

.pvpc-negative-zone {
    height: 68px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}

.pvpc-zero-line {
    height: 1px;
    background: #0b2e63;
    width: 100%;
}

.pvpc-stack {
    width: 100%;
    min-height: 2px;

    display: flex;
    align-items: center;
    justify-content: center;

    overflow: hidden;
}

.pvpc-stack span {
    font-size: clamp(7px, 0.55vw, 10px);
    font-weight: 700;
    line-height: 1;
}

/* COLORES CRM */
.pvpc-stack.energy {
    background: #0b2e63;
    color: white;
}

.pvpc-stack.adjustment {
    background: #70c8ff;
    color: #0b2e63;
}

.pvpc-stack.regulated {
    background: #7aa6ff;
    color: white;
}

.pvpc-stack.others {
    background: #d9e2ef;
    color: #0b2e63;
}

.pvpc-stack.futures-positive,
.pvpc-stack.futures-negative {
    background: #6574db;
    color: white;
}

.pvpc-stack.futures-negative {
    align-items: flex-end;
    padding-bottom: 3px;
}

.pvpc-hour-label {
    text-align: center;
    font-size: clamp(7px, 0.55vw, 10px);
    color: #64748b;
    margin-top: 6px;
    white-space: nowrap;
}

/* LEYENDA */
.pvpc-legend {
    width: 100%;
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 28px;
    margin-top: 22px;
    color: #64748b;
    font-size: 14px;
}

.pvpc-legend span {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.pvpc-legend i {
    display: inline-block;
    width: 20px;
    height: 10px;
    border-radius: 3px;
}

.legend-futures {
    background: #6574db;
}

.legend-regulated {
    background: #7aa6ff;
}

.legend-energy {
    background: #0b2e63;
}

.legend-adjustment {
    background: #70c8ff;
}

.legend-others {
    background: #d9e2ef;
}

/* TABLA */
.pvpc-detail-card {
    padding: 25px;
}

.pvpc-table {
    width: 100%;
    overflow-x: auto;
}

.pvpc-table table {
    width: 100%;
    border-collapse: collapse;
}

.pvpc-table th,
.pvpc-table td {
    padding: 12px;
    border-bottom: 1px solid #edf1f7;
    text-align: left;
}

.pvpc-table th {
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
}

.pvpc-table td {
    color: #0b2e63;
    font-size: 13px;
}

.rowMin td {
    background: rgba(112, 200, 255, 0.18);
}

.rowMax td {
    background: rgba(255, 59, 48, 0.10);
}

.pvpc-fee-inline {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 8px;
    border-radius: 999px;
    background: #eef5ff;
    border: 1px solid #d7e7fb;
    color: #0b2e63;
}
.pvpc-chart-area.range-mode {
    grid-template-columns: repeat(auto-fit, minmax(30px, 1fr));
    column-gap: 6px;
}

.pvpc-chart-area.range-mode .pvpc-hour-label {
    font-size: 10px;
}

.pvpc-fee-inline strong {
    font-weight: 900;
}

.pvpc-fee-inline input {
    width: 62px;
    height: 24px;
    border: 1px solid #bfd4ef;
    border-radius: 8px;
    background: #ffffff;
    color: #0b2e63;
    font-size: 12px;
    font-weight: 800;
    text-align: center;
    outline: none;
    padding: 0 6px;
}

.pvpc-fee-inline input:focus {
    border-color: #0b2e63;
    box-shadow: 0 0 0 2px rgba(11, 46, 99, 0.12);
}

.pvpc-fee-inline em {
    font-style: normal;
    color: #607aa6;
    font-size: 12px;
    font-weight: 800;
}

.pvpc-fee-inline small {
    color: #d97706;
    font-size: 12px;
    font-weight: 900;
}


/* BOTONES DIARIO / SEMANAL / MENSUAL */
.pvpc-view-tabs {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin: 18px 0 16px;
}

.pvpc-view-tabs button {
    border: 1px solid #d7e2f1;
    background: #ffffff;
    color: #0b2e63;
    border-radius: 999px;
    padding: 10px 24px;
    font-weight: 800;
    font-size: 14px;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(0, 47, 108, 0.06);
    transition: all 0.18s ease;
}

.pvpc-view-tabs button:hover {
    border-color: #0b2e63;
}

.pvpc-view-tabs button.active {
    background: #0b2e63;
    border-color: #0b2e63;
    color: #ffffff;
    box-shadow: 0 12px 26px rgba(0, 47, 108, 0.18);
}

.pvpc-chart-area.historical-mode {
    grid-template-columns: repeat(auto-fit, minmax(38px, 1fr));
    column-gap: 8px;
}

.pvpc-chart-area.monthly-mode {
    grid-template-columns: repeat(auto-fit, minmax(70px, 1fr));
}

.pvpc-table td small {
    display: block;
    color: #64748b;
    margin-top: 3px;
}

/* RESPONSIVE */
@media (max-width: 1100px) {
    .pvpc-toolbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .pvpc-actions {
        width: 100%;
        justify-content: flex-start;
    }

    .pvpc-summary-card {
        flex-direction: column;
        gap: 24px;
    }

    .pvpc-summary-item {
        min-width: auto;
    }

    .pvpc-chart-area {
        column-gap: 3px;
        padding-left: 42px;
        padding-right: 8px;
    }

    .pvpc-average-line {
        left: 42px;
        right: 8px;
    }
}


.pvpc-fee-field {
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 180px;
}

.pvpc-fee-label {
    font-size: 12px;
    font-weight: 700;
    color: #6b7da1;
    margin-bottom: 6px;
    line-height: 1;
}

.pvpc-fee-control {
    height: 44px;
    display: flex;
    align-items: center;
    background: #ffffff;
    border: 1px solid #d9e4f2;
    border-radius: 14px;
    overflow: hidden;
    transition: all 0.18s ease;
    box-shadow: 0 2px 8px rgba(13, 44, 94, 0.04);
}

.pvpc-fee-control:focus-within {
    border-color: #1f5fbf;
    box-shadow: 0 0 0 3px rgba(31, 95, 191, 0.12);
}

.pvpc-fee-control input {
    height: 100%;
    width: 90px;
    border: 0;
    outline: none;
    padding: 0 14px;
    font-size: 15px;
    font-weight: 700;
    color: #0b2e63;
    background: transparent;
}

.pvpc-fee-control input::placeholder {
    color: #9aa9c2;
    font-weight: 600;
}

.pvpc-fee-unit {
    height: 100%;
    display: flex;
    align-items: center;
    padding: 0 14px;
    border-left: 1px solid #e3ebf6;
    background: #f7faff;
    color: #4e648a;
    font-size: 13px;
    font-weight: 700;
    white-space: nowrap;
}


.pvpc-back-button {
    height: 38px;
    border: 1px solid #d6e2f1;
    background: #ffffff;
    color: #00316e;
    border-radius: 999px;
    padding: 0 14px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.18s ease;
    box-shadow: 0 6px 16px rgba(0, 47, 108, 0.06);
}

.pvpc-back-button:hover {
    background: #00316e;
    border-color: #00316e;
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 10px 22px rgba(0, 47, 108, 0.14);
}

.pvpc-back-button i {
    font-size: 12px;
}
/* Seleccion discreta de tramo horario con el raton */
.pvpc-chart-area.selection-enabled {
    cursor: crosshair;
    user-select: none;
}

.pvpc-chart-area.is-selecting {
    cursor: grabbing;
}

.pvpc-hour-column {
    transition: opacity 0.15s ease, transform 0.15s ease, filter 0.15s ease;
}

.pvpc-hour-column.window-dimmed {
    opacity: 0.23;
    filter: grayscale(0.25);
}

.pvpc-hour-column.window-selected {
    opacity: 1;
    transform: translateY(-3px);
}

.pvpc-hour-column.window-selected .pvpc-total-label {
    background: #002f6c;
    color: #ffffff;
    border-radius: 999px;
    padding: 3px 7px;
}

.pvpc-drag-selection-info {
    min-height: 34px;
    margin: 10px 0 18px;
    padding: 8px 12px;
    border-radius: 999px;
    background: rgba(0, 47, 108, 0.05);
    color: #002f6c;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    font-size: 13px;
    font-weight: 600;
}

.pvpc-drag-selection-info > div {
    display: flex;
    align-items: center;
    gap: 6px;
}

.pvpc-drag-selection-info strong {
    font-weight: 800;
}

.pvpc-drag-selection-info span {
    font-weight: 800;
}

.pvpc-drag-selection-info small {
    color: #607aa6;
    font-weight: 600;
}

.pvpc-drag-selection-info button {
    border: 0;
    background: transparent;
    color: #d93025;
    font-weight: 800;
    cursor: pointer;
    padding: 0 4px;
}

.pvpc-drag-selection-info button:hover {
    text-decoration: underline;
}


/* MAPA DE CALOR PVPC */
.pvpc-heatmap-card {
    padding: 22px;
    border-radius: 22px;
    overflow: hidden;
    display: block !important;
}

.pvpc-heatmap-head {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 18px;
    margin-bottom: 12px;
}

.pvpc-heatmap-head > div:first-child {
    min-width: 0;
    flex: 1;
}

.pvpc-heatmap-head > div:first-child p:first-child {
    margin-bottom: 4px;
}

.pvpc-heatmap-summary {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.pvpc-heatmap-summary span {
    background: #f5f8fc;
    border: 1px solid #e0e9f4;
    color: #0b2e63;
    border-radius: 999px;
    padding: 7px 10px;
    font-size: 12px;
    font-weight: 700;
}

.pvpc-heatmap-summary strong {
    font-weight: 900;
}

.pvpc-heatmap-legend {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 16px;
    margin: 0 0 14px;
    color: #536987;
    font-size: 12px;
    font-weight: 700;
}

.pvpc-heatmap-legend span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.pvpc-heatmap-legend i {
    width: 18px;
    height: 10px;
    display: inline-block;
    border-radius: 999px;
}

.pvpc-heatmap-legend i.cold {
    background: #61c0f8;
}

.pvpc-heatmap-legend i.medium {
    background: #ffb45c;
}

.pvpc-heatmap-legend i.hot {
    background: #d71920;
}

.pvpc-heatmap-selection-info {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin: 0 0 14px;
    padding: 10px 12px;
    border: 1px solid #dde8f5;
    border-radius: 14px;
    background: #f7faff;
    color: #0b2e63;
}

.pvpc-heatmap-selection-info > div {
    display: flex;
    align-items: center;
    gap: 8px;
}

.pvpc-heatmap-selection-info strong {
    font-size: 13px;
    font-weight: 900;
}

.pvpc-heatmap-selection-info span {
    font-size: 13px;
    font-weight: 800;
}

.pvpc-heatmap-selection-info small {
    color: #607aa6;
    font-size: 12px;
    font-weight: 700;
}

.pvpc-heatmap-selection-info button {
    border: 0;
    background: #00316e;
    color: #ffffff;
    border-radius: 999px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
}

.pvpc-heatmap-scroll {
    width: 100%;
    overflow: visible;
    padding-bottom: 6px;
    display: flex;
    justify-content: center;
    margin-top: 10px;
}

.pvpc-heatmap-grid {
    display: grid;
    gap: 2px;
    width: 100%;
    max-width: 1180px;
    margin: 0 auto;
}

.pvpc-heatmap-corner,
.pvpc-heatmap-hour,
.pvpc-heatmap-day {
    color: #0b2e63;
    font-size: 12px;
    font-weight: 800;
}

.pvpc-heatmap-corner {
    position: sticky;
    left: 0;
    z-index: 4;
    background: #ffffff;
}

.pvpc-heatmap-hour {
    text-align: center;
    padding: 8px 4px;
}

.pvpc-heatmap-day {
    position: sticky;
    left: 0;
    z-index: 3;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 0 10px 0 0;
    min-height: 42px;
    white-space: nowrap;
}

.pvpc-heatmap-cell {
    width: 100%;
    min-height: 42px;
    border-radius: 6px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #061a35;
    border: 1px solid rgba(255, 255, 255, 0.75);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.12);
}

.pvpc-heatmap-cell strong {
    font-size: 11px;
    line-height: 1;
    font-weight: 900;
}

.pvpc-heatmap-cell small {
    font-size: 8px;
    line-height: 1;
    font-weight: 800;
    margin-top: 4px;
    opacity: 0.75;
}

.pvpc-heatmap-cell.empty {
    background: #eef3f9 !important;
    color: #8aa0bd;
}

.pvpc-heatmap-cell.empty span {
    font-size: 13px;
    font-weight: 900;
}


.pvpc-heatmap-cell:not(.empty) {
    cursor: crosshair;
    transition: opacity 0.12s ease, transform 0.12s ease, box-shadow 0.12s ease;
}

.pvpc-heatmap-card.is-selecting,
.pvpc-heatmap-card.is-selecting * {
    user-select: none;
}

.pvpc-heatmap-cell.heat-selected {
    opacity: 1;
    transform: translateY(-1px);
    box-shadow:
        0 0 0 2px #00316e,
        0 8px 18px rgba(0, 49, 110, 0.22),
        inset 0 0 0 1px rgba(255, 255, 255, 0.35);
    z-index: 2;
}

.pvpc-heatmap-cell.heat-dimmed:not(.empty) {
    opacity: 0.36;
}

@media (max-width: 768px) {
    .pvpc-page {
        padding: 18px 12px !important;
        align-items: flex-start !important;
    }

    .pvpc-back-button {
        height: 36px;
        font-size: 12px;
        margin-bottom: 10px;
    }

    .pvpc-toolbar {
        flex-direction: column;
        align-items: stretch !important;
        gap: 14px;
    }

    .pvpc-toolbar > div:first-child p:first-child {
        font-size: 24px !important;
        line-height: 1.15;
    }

    .pvpc-toolbar > div:first-child p:last-child {
        font-size: 13px !important;
        line-height: 1.35;
    }

    .pvpc-actions {
        width: 100%;
        display: grid !important;
        grid-template-columns: 1fr 1fr;
        gap: 8px !important;
    }

    .pvpc-actions .form-group {
        width: 100%;
        margin-bottom: 0 !important;
    }

    .pvpc-actions .input-group,
    .pvpc-actions select,
    .pvpc-actions input {
        width: 100%;
    }

    .pvpc-actions select,
    .pvpc-actions input {
        height: 42px;
        font-size: 13px;
    }

    .pvpc-actions .custom-button {
        grid-column: 1 / -1;
        width: 100%;
        height: 42px;
        justify-content: center;
    }

    .pvpc-view-tabs {
        justify-content: flex-start;
        overflow-x: auto;
        gap: 8px;
        padding-bottom: 6px;
        margin: 14px 0;
        -webkit-overflow-scrolling: touch;
    }

    .pvpc-view-tabs button {
        flex: 0 0 auto;
        padding: 10px 18px;
        font-size: 13px;
    }

    .pvpc-summary-card {
        display: grid !important;
        grid-template-columns: 1fr;
        gap: 10px;
        padding: 14px;
        min-height: unset;
    }

    .pvpc-summary-item {
        min-width: 0;
        width: 100%;
        background: #ffffff;
        border: 1px solid #e2eaf5;
        border-radius: 14px;
        padding: 14px 12px;
    }

    .pvpc-summary-item p[data-size="32"] {
        font-size: 25px !important;
    }

    .pvpc-report-card {
        padding: 14px;
        border-radius: 16px;
        overflow: hidden;
    }

    .pvpc-report-head p[data-size="24"] {
        font-size: 19px !important;
        line-height: 1.25;
    }

    .pvpc-description {
        font-size: 12px !important;
    }

    .pvpc-component-summary {
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
        margin-bottom: 12px;
    }

    .pvpc-component-summary span {
        width: 100%;
        justify-content: space-between;
        background: #f5f8fc;
        border: 1px solid #e1e9f4;
        border-radius: 12px;
        padding: 8px 10px;
        font-size: 12px;
    }

    .pvpc-component-summary span:not(:last-child)::after {
        display: none;
    }

    .pvpc-fee-inline {
        border-radius: 12px;
        justify-content: flex-start !important;
        flex-wrap: wrap;
    }

    .pvpc-drag-selection-info {
        display: none;
    }

    .pvpc-chart-scroll {
        width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        touch-action: pan-x;
    }

    .pvpc-chart-area {
        height: 430px;
        grid-template-columns: repeat(24, 28px);
        column-gap: 5px;
        padding: 36px 10px 34px 38px;
    }

    .pvpc-chart-area.historical-mode {
        grid-template-columns: repeat(auto-fit, minmax(36px, 1fr));
        min-width: 720px;
    }

    .pvpc-chart-area.range-mode {
        grid-template-columns: repeat(auto-fit, minmax(32px, 1fr));
        min-width: 920px;
    }

    .pvpc-chart-area.monthly-mode {
        min-width: 520px;
    }

    .pvpc-chart-scroll::-webkit-scrollbar {
        height: 4px;
    }

    .pvpc-chart-scroll::-webkit-scrollbar-thumb {
        background: rgba(11, 46, 99, 0.25);
        border-radius: 999px;
    }

    .pvpc-y-axis {
        display: none;
    }

    .pvpc-average-line {
        left: 38px;
        right: 10px;
    }

    .pvpc-average-line span {
        right: 0;
        font-size: 11px;
        top: -9px;
    }

    .pvpc-hour-column {
        grid-template-rows: 1fr 1px 58px 22px;
    }

    .pvpc-negative-zone {
        height: 58px;
    }

    .pvpc-total-label {
        top: -23px;
        font-size: 9px;
    }

    .pvpc-stack span {
        font-size: 8px;
    }

    .pvpc-hour-label {
        font-size: 9px;
    }

    .pvpc-legend {
        justify-content: flex-start;
        gap: 10px;
        margin-top: 16px;
        font-size: 12px;
    }

    .pvpc-legend span {
        width: calc(50% - 8px);
        gap: 6px;
    }

    .pvpc-legend i {
        width: 16px;
        height: 8px;
    }

    .pvpc-detail-card {
        padding: 14px;
        border-radius: 16px;
    }

    .pvpc-detail-card > div:first-child {
        align-items: flex-start !important;
    }

    .pvpc-table {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .pvpc-table table {
        min-width: 620px;
    }

    .pvpc-table th,
    .pvpc-table td {
        padding: 10px 8px;
        font-size: 12px;
    }

    .pvpc-heatmap-card {
        padding: 14px;
        border-radius: 16px;
    }

    .pvpc-heatmap-head {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }

    .pvpc-heatmap-summary {
        justify-content: flex-start;
    }

    .pvpc-heatmap-summary span {
        font-size: 11px;
        padding: 6px 9px;
    }

    .pvpc-heatmap-legend {
        justify-content: flex-start;
        gap: 10px;
        flex-wrap: wrap;
        font-size: 11px;
    }

    .pvpc-heatmap-selection-info {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
        padding: 9px 10px;
    }

    .pvpc-heatmap-selection-info > div {
        justify-content: space-between;
    }

    .pvpc-heatmap-selection-info button {
        width: 100%;
    }

    .pvpc-heatmap-cell {
        width: 100%;
        min-height: 36px;
        border-radius: 5px;
    }

    .pvpc-heatmap-cell strong {
        font-size: 11px;
    }

    .pvpc-heatmap-cell small {
        font-size: 8px;
    }

    .pvpc-heatmap-grid {
        max-width: 100%;
    }

    .pvpc-heatmap-scroll {
        overflow: visible;
    }

    .pvpc-heatmap-day {
        min-height: 38px;
        font-size: 11px;
    }

    .pvpc-heatmap-hour {
        font-size: 11px;
        padding: 7px 3px;
    }

}

@media (max-width: 420px) {
    .pvpc-actions {
        grid-template-columns: 1fr;
    }

    .pvpc-actions .custom-button {
        grid-column: auto;
    }

    .pvpc-summary-item p[data-size="32"] {
        font-size: 23px !important;
    }

    .pvpc-legend span {
        width: 100%;
    }

    .pvpc-chart-area {
        height: 400px;
    }
}


</style>
<template>
    <section class="datadis-tool">
        <form class="form" @submit.prevent="getSupplies">
            <div class="d-flex justify-between">
                <div class="form-group w-250-px-min">
                    <div class="input-group">
                        <select v-model="cif" :disabled="!accounts">
                            <option v-if="!accounts" value="">Cargando</option>
                            <option v-for="user in accounts" :key="user.CIF" :value="user.CIF">{{ user.name }}</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="custom-button my-5 mobile-item" data-size="medium" :disabled="!isTokenLoaded || !cif">
                    <i class="fa-solid fa-arrow-down"></i> Cargar
                </button>
                <button type="submit" class="custom-button my-5 desktop-item" data-size="medium" :disabled="!isTokenLoaded || !cif">
                    Cargar
                </button>
            </div>
        </form>

        <div class="separator"></div>

        <Teleport to=".boxBody" v-if="loadingSupplies">
            <div class="floating-box z-100">
                <div class="d-flex column justify-center register-pos w-auto h-auto h-98-max round" data-round="20">
                    <div class="text" data-color="principal" data-weight="600" data-size="36">Cargando suministros...</div>
                    <div class="text text-center" data-size="26"><i class="fa-solid fa-spinner-third fa-spin"></i></div>
                </div>
            </div>
        </Teleport>

        <Teleport to=".boxBody" v-if="bulkLoadingConsumptionData">
            <div class="floating-box z-100">
                <div class="d-flex column justify-center register-pos w-auto h-auto h-98-max round" data-round="20">
                    <div class="text" data-color="principal" data-weight="600" data-size="36">Cargando consumos y precios...</div>
                    <div class="text text-center" data-size="20">{{ bulkLoadIndex }} / {{ bulkLoadTotal }}</div>
                    <div class="text text-center" data-size="26"><i class="fa-solid fa-spinner-third fa-spin"></i></div>
                </div>
            </div>
        </Teleport>

        <Teleport to=".boxBody" v-if="generatingBulkReports">
            <div class="floating-box z-100">
                <div class="d-flex column justify-center register-pos w-auto h-auto h-98-max round" data-round="20">
                    <div class="text" data-color="principal" data-weight="600" data-size="36">Generando informes...</div>
                    <div class="text text-center" data-size="20">{{ bulkReportIndex }} / {{ bulkReportTotal }}</div>
                    <div class="text text-center" data-size="26"><i class="fa-solid fa-spinner-third fa-spin"></i></div>
                </div>
            </div>
        </Teleport>



        <div v-if="showSupplies">
            <template v-if="supplies.length > 0">
                <div class="datadis-bulk-toolbar mt-20 mb-15">
                    <div class="d-flex justify-between align-center mb-10">
                        <div class="d-flex align-center" data-gap="15">
                            <p class="text" data-weight="600">Fecha general</p>
                            <div class="slider justify-start">
                                <div :class="[{ selected: generalDateType === 'day' }, 'd-flex align-center']" @click="selectGeneralDateType('day')">Diario</div>
                                <div :class="[{ selected: generalDateType === 'isoWeek' }, 'd-flex align-center']" @click="selectGeneralDateType('isoWeek')">Semanal</div>
                                <div :class="[{ selected: generalDateType === 'month' }, 'd-flex align-center']" @click="selectGeneralDateType('month')">Mensual</div>
                            </div>
                        </div>
                        <div class="form">
                            <div class="form-group">
                                <div class="input-group">
                                    <input v-if="generalDateType === 'day'" type="date" v-model="generalDateInput" @change="updateGeneralDate" />
                                    <input v-if="generalDateType === 'isoWeek'" type="week" v-model="generalDateInput" @change="updateGeneralDate" />
                                    <input v-if="generalDateType === 'month'" type="month" v-model="generalDateInput" @change="updateGeneralDate" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-between align-center">
                        <div class="text">{{ selectedSupplies.length }} suministro(s) seleccionado(s)</div>
                        <div class="d-flex align-center" data-gap="10">


                            <label class="datadis-compare-option" :class="{ disabled: selectedLoadedReports.length === 0 }">
                                <input
                                    type="checkbox"
                                    v-model="includeBulkPriceComparisonInReport"
                                    :disabled="selectedLoadedReports.length === 0"
                                />
                                <span>Comparar con otro producto</span>
                            </label>

                            <button
                                class="custom-button"
                                data-size="medium"
                                @click="loadSelectedSuppliesData"
                                :disabled="selectedSupplies.length === 0 || bulkLoadingConsumptionData || generatingBulkReports || sendingBulkReportEmails || !isTokenLoaded"
                            >
                                <i v-if="bulkLoadingConsumptionData" class="fa-solid fa-spinner-third fa-spin"></i>
                                {{ bulkLoadingConsumptionData ? `Cargando ${bulkLoadIndex}/${bulkLoadTotal}` : 'Cargar datos seleccionados' }}
                            </button>



                            <button
                                class="custom-button"
                                data-size="medium"
                                @click="generateSelectedReports"
                                :disabled="selectedLoadedReports.length === 0 || bulkLoadingConsumptionData || generatingBulkReports || sendingBulkReportEmails"
                            >
                                <i v-if="generatingBulkReports" class="fa-solid fa-spinner-third fa-spin"></i>
                                <i v-else class="fa-solid fa-file-pdf"></i>
                                {{ generatingBulkReports ? `Generando ${bulkReportIndex}/${bulkReportTotal}` : `Generar informes (${selectedLoadedReports.length})` }}
                            </button>


                        </div>
                    </div>
                </div>

                <div class="datadis-table datadis-supplies-table table-header mt-30 mb-10">
                    <div data-color="principal">
                        <input type="checkbox" :checked="supplies.length > 0 && selectedSupplies.length === supplies.length" @change="toggleAllSupplies" />
                    </div>
                    <div data-color="principal"><p class="text mr-5 ellipsis noWidth" data-weight="600">CUPS</p></div>
                    <div data-color="principal"><p class="text mr-5 ellipsis noWidth" data-weight="600">Provincia</p></div>
                    <div data-color="principal"><p class="text mr-5 ellipsis noWidth" data-weight="600">Localidad</p></div>
                    <div data-color="principal"><p class="text mr-5 ellipsis noWidth" data-weight="600">Dirección</p></div>
                    <div data-color="principal"><p class="text mr-5 ellipsis noWidth" data-weight="600">C.P.</p></div>
                    <div data-color="principal"><p class="text mr-5 ellipsis noWidth" data-weight="600">Estado</p></div>
                    <div data-color="principal"><p class="text mr-5 ellipsis noWidth" data-weight="600">Periodo cargado</p></div>
                    <div data-color="principal"></div>
                </div>

                <div v-for="supply of supplies" :key="supply.cups">
                    <div class="datadis-table datadis-supplies-table">
                        <div>
                            <input type="checkbox" :checked="isSupplySelected(supply)" @change.stop="toggleSupply(supply)" />
                        </div>
                        <p class="text ellipsis pointer" data-color="azul" data-weight="600" @dblclick="selectSupply(supply)">{{ supply.cups }}</p>
                        <p class="text ellipsis">{{ supply.province }}</p>
                        <p class="text ellipsis">{{ supply.municipality }}</p>
                        <p class="text ellipsis">{{ supply.address }}</p>
                        <p class="text ellipsis">{{ supply.postalCode }}</p>
                        <p class="text ellipsis">
                            <span class="datadis-status-badge" :data-status="getSupplyLoadStatus(supply)">{{ getSupplyLoadLabel(supply) }}</span>
                        </p>
                        <p class="text ellipsis opacity-7">{{ getSupplyLoadedDateLabel(supply) }}</p>
                        <div class="text pointer" @click="selectSupply(supply)"><i class="far fa-eye"></i></div>
                    </div>
                    <div class="separator my-10"></div>
                </div>
            </template>
            <template v-else>
                <div class="mt-50 ml-30 text" data-size="21"><i class="far fa-circle-info mr-10"></i>No hay suministros autorizados para este NIF/CIF.</div>
            </template>
        </div>

        <Teleport to=".boxBody" v-if="loadingConsumptionData || loadingContractPrices">
            <div class="floating-box z-100">
                <div class="d-flex column justify-center register-pos w-auto h-auto h-98-max round" data-round="20">
                    <div class="text" data-color="principal" data-weight="600" data-size="36">Calculando consumo y precios...</div>
                    <div class="text text-center" data-size="26"><i class="fa-solid fa-spinner-third fa-spin"></i></div>
                </div>
            </div>
        </Teleport>

        <div v-if="supplySelected">
            <div class="d-flex justify-between">
                <div class="d-flex align-center" data-gap="10">
                    <button class="custom-button my-5" data-size="medium" @click="deselectSupply">
                        <i class="fa-solid fa-arrow-left"></i> Volver
                    </button>
                    <p class="text" data-weight="600" data-size="20">{{ supplySelected.address }}</p>
                </div>
                <div class="d-flex align-center" data-gap="10">
                    <p class="text opacity-6">{{ supplySelected.municipality }} {{ supplySelected.postalCode }} ({{ supplySelected.province }})</p>
                    <p class="text opacity-6">CUPS {{ supplySelected.cups }}</p>
                    <p class="text opacity-6">{{ formatDateLabel(dateType, dateSelected) }}</p>

                    <label class="datadis-compare-option" :class="{ disabled: !currentPriceComparison }">
                        <input
                            type="checkbox"
                            v-model="includePriceComparisonInReport"
                            :disabled="!currentPriceComparison"
                        />
                        <span>Comparar con otro producto</span>
                    </label>

                    <button
                        class="custom-button my-5"
                        data-size="medium"
                        @click="generateReport"
                        :disabled="!consumptions || stackedChartSeries.length <= 1 || generatingPdf || sendingReportEmail || sendingBulkReportEmails"
                    >
                        <i v-if="generatingPdf" class="fa-solid fa-spinner-third fa-spin"></i>
                        <i v-else class="fa-solid fa-file-pdf"></i>
                        {{ generatingPdf ? 'Generando...' : 'Exportar PDF' }}
                    </button>
                    <button
                        class="custom-button my-5"
                        data-size="medium"
                        data-bg="azul"
                        @click="openCurrentReportInMassiveEmail"
                        :disabled="!consumptions || stackedChartSeries.length <= 1 || generatingPdf || sendingReportEmail || sendingBulkReportEmails"
                    >
                        <i v-if="sendingReportEmail" class="fa-solid fa-spinner-third fa-spin"></i>
                        <i v-else class="fa-solid fa-envelope"></i>
                        {{ sendingReportEmail ? 'Preparando...' : 'Preparar email' }}
                    </button>
                </div>
            </div>
            <div class="separator my-10"></div>

            <div class="d-flex justify-between mt-10 mx-5 form">
                <div class="slider justify-start">
                    <div :class="[{ selected: dateType === 'day' }, 'd-flex align-center']" @click="selectDateType('day')">Diario</div>
                    <div :class="[{ selected: dateType === 'isoWeek' }, 'd-flex align-center']" @click="selectDateType('isoWeek')">Semanal</div>
                    <div :class="[{ selected: dateType === 'month' }, 'd-flex align-center']" @click="selectDateType('month')">Mensual</div>
                </div>
                <div class="form-group">
                    <div class="input-group">
                        <input v-if="dateType === 'day'" type="date" v-model="dateInput" @change="updateDate" />
                        <input v-if="dateType === 'isoWeek'" type="week" v-model="dateInput" @change="updateDate" />
                        <input v-if="dateType === 'month'" type="month" v-model="dateInput" @change="updateDate" />
                    </div>
                </div>
            </div>

            <template v-if="consumptions && stackedChartSeries.length > 1">
                <div class="dashboard-card w-100 mt-10 d-flex justify-around align-center">
                    <div>
                        <p v-if="totalConsumption" class="text text-center" data-weight="600" data-size="30">{{ formatNumber(totalConsumption) }}</p>
                        <p class="text text-center" data-size="12">Consumo total (kWh)</p>
                    </div>
                    <div>
                        <p v-if="consumptionPerInterval" class="text text-center" data-weight="600" data-size="30">{{ formatNumber(consumptionPerInterval) }}</p>
                        <p class="text text-center" data-size="12">Consumo medio diario (kWh)</p>
                    </div>
                    <chart-datadis-donut-component :series="donutSeries"></chart-datadis-donut-component>
                    <chart-datadis-summarybars-component :series="summaryBarsSeries"></chart-datadis-summarybars-component>
                </div>

                <chart-datadis-stackedbars-component :series="stackedChartSeries"></chart-datadis-stackedbars-component>

                <div v-if="heatmapSeries.length" class="dashboard-card w-100 mt-15 datadis-heatmap-card">
                    <div class="datadis-heatmap-title-row mb-10">
                        <p class="text" data-size="18" data-weight="700">Mapa de calor de consumos</p>
                        <p class="text opacity-6" data-size="13">
                            Consumo horario por día · {{ formatDateLabel(dateType, dateSelected) }}
                        </p>
                    </div>

                    <div class="datadis-heatmap-scroll">
                        <div class="datadis-heatmap-grid">
                            <div class="datadis-heatmap-row-label"></div>

                            <div
                                v-for="hour in heatmapHours"
                                :key="`heatmap-hour-${hour}`"
                                class="datadis-heatmap-hour"
                            >
                                {{ String(hour).padStart(2, "0") }}
                            </div>

                            <template v-for="row in heatmapSeries" :key="row.rowKey">
                                <div class="datadis-heatmap-row-label">
                                    {{ row.rowLabel }}
                                </div>

                                <div
                                    v-for="(value, hour) in row.values"
                                    :key="`${row.rowKey}-${hour}`"
                                    class="datadis-heatmap-cell"
                                    :style="getHeatmapCellStyle(value)"
                                    :title="`${row.rowLabel} ${String(hour).padStart(2, '0')}:00 · ${formatNumber(value, 2)} kWh · ${getHeatmapPeriodLabel(row.rowKey, hour)}`"
                                >
                                    <span class="datadis-heatmap-value">
                                        {{ value > 0 ? formatHeatmapValue(value) : "" }}
                                    </span>
                                    <span class="datadis-heatmap-period">
                                        {{ getHeatmapPeriodLabel(row.rowKey, hour) }}
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="datadis-heatmap-legend mt-12">
                        <span>{{ formatNumber(heatmapMinConsumption, 1) }}</span>
                        <div class="datadis-heatmap-gradient"></div>
                        <span>{{ formatNumber(heatmapMaxConsumption, 1) }}</span>
                    </div>
                </div>

                <div class="d-flex justify-between pb-10">
                    <div class="text pointer" @click="changeDateSelected('prev')">
                        <i class="fa-solid fa-arrow-left"/> {{ dateType === 'month' ? 'Mes' : dateType === 'isoWeek' ? 'Semana' : 'Día' }} anterior
                    </div>
                    <div class="text pointer" @click="changeDateSelected('next')">
                        {{ dateType === 'month' ? 'Mes' : dateType === 'isoWeek' ? 'Semana' : 'Día' }} siguiente <i class="fa-solid fa-arrow-right"/>
                    </div>
                </div>
            </template>
            <template v-else>
                <div v-if="getSelectedSupplyStatus() === 'error'" class="mt-50 ml-30 text" data-size="21">
                    <i class="far fa-circle-info mr-10"></i>No se pudo obtener la información de este suministro.
                </div>
                <div v-else class="mt-50 ml-30 text" data-size="21">
                    <i class="far fa-circle-info mr-10"></i>No hay datos para la fecha consultada.
                </div>
            </template>
        </div>
    </section>
</template>

<script>
export default {
    name: "DatadisComponent",
    props: ["basicData"],
    data() {
        return {
            token: "",
            isTokenLoaded: false,
            includePriceComparisonInReport: false,
            includeBulkPriceComparisonInReport: false,
            accounts: null,
            cif: "",
            supplies: [],
            showSupplies: false,
            loadingSupplies: false,
            supplySelected: null,

            selectedSupplies: [],
            loadedSuppliesData: {},
            bulkLoadingConsumptionData: false,
            bulkLoadIndex: 0,
            bulkLoadTotal: 0,
            generatingBulkReports: false,
            bulkReportIndex: 0,
            bulkReportTotal: 0,
            sendingReportEmail: false,
            sendingBulkReportEmails: false,
            bulkEmailIndex: 0,
            bulkEmailTotal: 0,

            generalDateType: null,
            generalDateSelected: null,
            generalDateInput: null,
            dateType: null,
            dateSelected: null,
            dateInput: null,
            consumptions: null,
            contract: null,
            loadingConsumptionData: false,
            loadingContractPrices: false,
            generatingPdf: false,

            currentContractOrder: null,
            currentContractStatus: null,
            priceComparisonByCups: {},

            marketers: [],

            totalConsumption: null,
            consumptionPerInterval: null,
            periodConsumption: { P1: 0, P2: 0, P3: 0, P4: 0, P5: 0, P6: 0 },

            dataFees: [
                { name: "2T", intervals: { low: [1,2,3,4,5,6,7,8], mid: [9,10,15,16,17,18,23,24], high: [11,12,13,14,19,20,21,22] } },
                {
                    name: "3T",
                    intervals: { low: [1,2,3,4,5,6,7,8], mid: [9,15,16,17,18,23,24], high: [10,11,12,13,14,19,20,21,22] },
                    months: [
                        { mid: "P2", high: "P1" }, { mid: "P2", high: "P1" }, { mid: "P3", high: "P2" },
                        { mid: "P5", high: "P4" }, { mid: "P5", high: "P4" }, { mid: "P4", high: "P3" },
                        { mid: "P2", high: "P1" }, { mid: "P4", high: "P3" }, { mid: "P4", high: "P3" },
                        { mid: "P5", high: "P4" }, { mid: "P3", high: "P2" }, { mid: "P2", high: "P1" }
                    ]
                }
            ],
            holidayDates: [
                "2026/12/25","2026/12/08","2026/12/06","2026/11/01","2026/10/12","2026/08/15","2026/05/01","2026/04/03","2026/01/06","2026/01/01",
                "2025/12/25","2025/12/08","2025/08/15","2025/05/01","2025/01/01",
                "2024/12/25","2024/12/06","2024/11/01","2024/10/12","2024/08/15","2024/05/01","2024/01/01",
                "2023/12/25","2023/12/08","2023/12/06","2023/11/01","2023/10/12","2023/08/15","2023/05/01"
            ],
            stackedChartSeries: [],
            donutSeries: [],
            summaryBarsSeries: [],
            heatmapSeries: []
        }
    },
    computed: {
        selectedLoadedReports() {
            return this.selectedSupplies
                .map(supply => this.loadedSuppliesData[supply.cups])
                .filter(item => item && item.status === "loaded" && item.stackedChartSeries && item.stackedChartSeries.length > 1);
        },
        currentPriceComparison() {
            if (!this.supplySelected) return null;
            return this.priceComparisonByCups[this.supplySelected.cups] ?? null;
        },
        sortedMarketers() {
            const logged = this.basicData?.userLogged ?? {};
            const subdomain = this.basicData?.userSubdomain ?? {};
            const visibleIds = [...(logged.marketers ?? []), ...(subdomain.marketers ?? [])];

            return this.marketers
                .filter(marketer => {
                    const isVisible = visibleIds.includes(marketer._id) || logged._id === marketer.createdBy;
                    const hasElectricity = Array.isArray(marketer.products?.electricity) && marketer.products.electricity.length > 0;
                    return isVisible && hasElectricity;
                })
                .sort((a, b) => a.name.localeCompare(b.name, "es", { sensitivity: "base" }));
        },
        heatmapHours() {
            return Array.from({ length: 24 }, (_, i) => i);
        },
        heatmapMaxConsumption() {
            const values = this.heatmapSeries.reduce((acc, row) => acc.concat(row.values ?? []), []);
            return Math.max(...values, 0);
        },
        heatmapMinConsumption() {
            const values = this.heatmapSeries
                .reduce((acc, row) => acc.concat(row.values ?? []), [])
                .filter(value => value > 0);

            return values.length ? Math.min(...values) : 0;
        }
    },
    watch: {
         '$route.query.CUPS': {
            immediate: true,
             async handler(newVal) {
                if (newVal && this.supplies.length === 0) {
                    await this.getAccounts()
                    await this.preloadCupsSelected()
                }
            }
        }
    },
    created() {
        this.initDefaultDates();
        this.getAccounts();
        this.getToken();
        this.fetchMarketers();
    },
    methods: {
        initDefaultDates() {
            const defaultDate = moment().subtract(2, "days").format("YYYY/MM/DD");
            if (!this.generalDateType) this.generalDateType = "month";
            if (!this.generalDateSelected) this.generalDateSelected = defaultDate;
            if (!this.generalDateInput) this.generalDateInput = this.formatInputForType(this.generalDateType, this.generalDateSelected);
            if (!this.dateType) this.dateType = this.generalDateType;
            if (!this.dateSelected) this.dateSelected = this.generalDateSelected;
            if (!this.dateInput) this.dateInput = this.formatInputForType(this.dateType, this.dateSelected);
        },
        clone(value) {
            return JSON.parse(JSON.stringify(value ?? null));
        },
        parseInputDate(dateType, inputValue) {
            switch (dateType) {
                case "day": return moment(inputValue, "YYYY-MM-DD").format("YYYY/MM/DD");
                case "isoWeek": return moment(inputValue, "GGGG-[W]WW").startOf("isoWeek").format("YYYY/MM/DD");
                case "month": return moment(inputValue, "YYYY-MM").startOf("month").format("YYYY/MM/DD");
                default: return moment(inputValue).format("YYYY/MM/DD");
            }
        },
        formatInputForType(dateType, dateSelected) {
            switch (dateType) {
                case "day": return moment(dateSelected, "YYYY/MM/DD").format("YYYY-MM-DD");
                case "isoWeek": return moment(dateSelected, "YYYY/MM/DD").format("GGGG-[W]WW");
                case "month": return moment(dateSelected, "YYYY/MM/DD").format("YYYY-MM");
                default: return moment(dateSelected, "YYYY/MM/DD").format("YYYY-MM-DD");
            }
        },
        formatDateLabel(dateType, dateSelected) {
            if (!dateType || !dateSelected) return "—";
            if (dateType === "month") return moment(dateSelected, "YYYY/MM/DD").locale("es").format("MMMM YYYY");
            if (dateType === "isoWeek") {
                const date = moment(dateSelected, "YYYY/MM/DD");
                return `Semana ${date.isoWeek()} — ${date.isoWeekYear()}`;
            }
            return moment(dateSelected, "YYYY/MM/DD").format("DD/MM/YYYY");
        },
        formatNumber(number, maximumFractionDigits = 2) {
            return Intl.NumberFormat("es-ES", { minimumFractionDigits: 0, maximumFractionDigits }).format(this.parseNumber(number));
        },
        formatCurrency(number) {
            return `${this.formatNumber(number, 2)}€`;
        },
        formatHeatmapValue(value) {
            const number = this.parseNumber(value);

            if (number <= 0) return "";
            if (number >= 10) return this.formatNumber(number, 0);
            if (number >= 1) return this.formatNumber(number, 1);

            return this.formatNumber(number, 2);
        },
        getHeatmapCellStyle(value) {
            const number = this.parseNumber(value);
            const max = this.heatmapMaxConsumption || 0;

            if (!number || max <= 0) {
                return {
                    backgroundColor: "#f8fafc",
                    color: "#64748b"
                };
            }

            const intensity = Math.min(1, Math.sqrt(number / max));
            const hue = 58 - (58 * intensity);
            const lightness = 72 - (18 * intensity);

            return {
                backgroundColor: `hsl(${hue}, 100%, ${lightness}%)`,
                color: "#111827"
            };
        },
        getHeatmapPeriodLabel(rowKey, hour) {
            if (!this.contract) return "";

            const consumptionDate = moment(rowKey, "YYYY/MM/DD").hour(hour);
            const isWeekend = consumptionDate.day() === 6 || consumptionDate.day() === 0;
            const isHoliday = this.holidayDates.includes(consumptionDate.format("YYYY/MM/DD"));
            const billingHour = hour + 1;

            if (this.contract.codeFare === "2T") {
                const fee = this.dataFees[0];

                if (isWeekend || isHoliday) return "P3";
                if (fee.intervals.low.includes(billingHour)) return "P3";
                if (fee.intervals.mid.includes(billingHour)) return "P2";

                return "P1";
            }

            const fee = this.dataFees[1];
            const monthConfig = fee.months[consumptionDate.month()];

            if (isWeekend || isHoliday) return "P6";
            if (fee.intervals.low.includes(billingHour)) return "P6";
            if (fee.intervals.mid.includes(billingHour)) return monthConfig.mid;

            return monthConfig.high;
        },
        parseNumber(value) {
            if (typeof value === "number") return value;
            if (typeof value === "string") return value === "" ? 0 : Number(value.replace(",", ".")) || 0;
            return 0;
        },
        normalizeCups(cups) {
            return String(cups ?? "").replace(/[^a-zA-Z0-9]/g, "").toUpperCase();
        },
        normalizeFeeName(fee) {
            const value = String(fee ?? "").toUpperCase().replace("TARIFA", "").trim();
            if (value.includes("2.0") || value === "2T") return "2.0TD";
            if (value.includes("3.0") || value === "3T") return "3.0TD";
            if (value.includes("6.1") || value === "6T") return "6.1TD";
            return value || "";
        },
        getDaysForSelectedPeriod(dateType, dateSelected) {
            if (dateType === "day") return 1;
            if (dateType === "isoWeek") return 7;
            if (dateType === "month") return moment(dateSelected, "YYYY/MM/DD").daysInMonth();
            return 30;
        },

        selectGeneralDateType(dateType) {
            if (this.generalDateType === dateType) return;
            this.generalDateType = dateType;
            this.generalDateInput = this.formatInputForType(this.generalDateType, this.generalDateSelected);
        },
        updateGeneralDate() {
            this.generalDateSelected = this.parseInputDate(this.generalDateType, this.generalDateInput);
            this.generalDateInput = this.formatInputForType(this.generalDateType, this.generalDateSelected);
        },
        selectDateType(dateType) {
            if (this.dateType === dateType) return;
            this.dateType = dateType;
            this.dateInput = this.formatInputForType(this.dateType, this.dateSelected);
            this.includePriceComparisonInReport = false;
            if (this.supplySelected) this.getConsumptionData();
        },
        updateDate() {
            this.dateSelected = this.parseInputDate(this.dateType, this.dateInput);
            this.dateInput = this.formatInputForType(this.dateType, this.dateSelected);
            this.includePriceComparisonInReport = false;
            if (this.supplySelected) this.getConsumptionData();
        },

        isSupplySelected(supply) {
            return this.selectedSupplies.some(item => item.cups === supply.cups);
        },
        toggleSupply(supply) {
            const index = this.selectedSupplies.findIndex(item => item.cups === supply.cups);
            if (index >= 0) this.selectedSupplies.splice(index, 1);
            else this.selectedSupplies.push(supply);
        },
        toggleAllSupplies(event) {
            this.selectedSupplies = event.target.checked ? [...this.supplies] : [];
        },
        getSupplyLoadStatus(supply) {
            return this.loadedSuppliesData[supply.cups]?.status ?? "pending";
        },
        getSupplyLoadLabel(supply) {
            switch (this.getSupplyLoadStatus(supply)) {
                case "loading": return "Cargando";
                case "loaded": return "Cargado";
                case "no-data": return "Sin datos";
                case "error": return "Error";
                default: return "Pendiente";
            }
        },
        getSupplyLoadedDateLabel(supply) {
            const item = this.loadedSuppliesData[supply.cups];
            if (!item) return "—";
            return item.dateLabel ?? this.formatDateLabel(item.dateType, item.dateSelected);
        },
        getSelectedSupplyStatus() {
            if (!this.supplySelected) return null;
            return this.loadedSuppliesData[this.supplySelected.cups]?.status ?? null;
        },

        async fetchMarketers() {
            try {
                const res = await axios.get("/api/marketers");
                this.marketers = res.data.marketers ?? [];
                if (this.marketers[0]?.createdBy !== "65cb57489c2c285441086a43") {
                    await this.fetchZocoMarketers();
                }
            } catch (err) {
                console.error("Error cargando comercializadoras", err);
            }
        },
        async fetchZocoMarketers() {
            try {
                const res = await axios.get("/api/marketers", { params: { assignContractTo: "65cb57489c2c285441086a43" } });
                const zocoMarketers = (res.data.marketers ?? []).map(marketer => ({
                    ...marketer,
                    name: `${marketer.name} (ZOCO)`,
                    isZocoMarketer: true
                }));
                this.marketers = [...this.marketers, ...zocoMarketers];
            } catch (err) {
                console.error("Error cargando comercializadoras ZOCO", err);
            }
        },
        async fetchCurrentContractByCups(cups) {
            const normalized = this.normalizeCups(cups);
            if (!normalized) return null;

            const res = await axios.get("/api/tools/datadisContracts", {
                params: { cups: normalized }
            });

            return res.data?.order ?? null;
        },

        extractPricesFromOrder(order) {
            const prices = order?.pricesProduct?.prices ?? order?.prices ?? null;
            return {
                power: prices?.power ?? {},
                energy: prices?.consume ?? prices?.energy ?? {}
            };
        },
        extractHiredPower(order, datadisContract) {
            const fromOrder = order?.consumptionData?.hiredPotency ?? order?.consumptionData?.power ?? order?.hiredPotency;
            if (Array.isArray(fromOrder)) return this.toSixPeriods(fromOrder);
            if (typeof fromOrder === "object" && fromOrder !== null) return this.toSixPeriods(fromOrder);
            const numeric = this.parseNumber(fromOrder);

            if (numeric > 0) return [numeric, numeric, 0, 0, 0, 0];

            const candidates = [
                datadisContract?.power1,
                datadisContract?.power2,
                datadisContract?.potenciaP1,
                datadisContract?.potenciaP2,
                datadisContract?.contractedPower
            ].filter(value => this.parseNumber(value) > 0);

            if (candidates.length) {
                const p1 = this.parseNumber(candidates[0]);
                const p2 = this.parseNumber(candidates[1] ?? candidates[0]);
                return [p1, p2, 0, 0, 0, 0];
            }

            return [0, 0, 0, 0, 0, 0];
        },
        toSixPeriods(value) {
            if (Array.isArray(value)) {
                return Array.from({ length: 6 }, (_, i) => this.parseNumber(value[i] ?? 0));
            }

            if (typeof value === "object" && value !== null) {
                return ["P1", "P2", "P3", "P4", "P5", "P6"].map((period, index) => {
                    return this.parseNumber(value[period] ?? value[period.toLowerCase()] ?? value[index] ?? value[String(index + 1)] ?? 0);
                });
            }

            return Array.from({ length: 6 }, () => 0);
        },
        priceObjectToArray(objectOrArray) {
            if (Array.isArray(objectOrArray)) return this.toSixPeriods(objectOrArray);
            const obj = objectOrArray ?? {};
            return ["P1", "P2", "P3", "P4", "P5", "P6"].map(key => this.parseNumber(obj[key] ?? 0));
        },
        calcPriceTotals({ powerPrices, energyPrices, hiredPower, periodConsumption, days }) {
            const power = { total: 0 };
            const energy = { total: 0 };
            const powerArr = this.priceObjectToArray(powerPrices);
            const energyArr = this.priceObjectToArray(energyPrices);
            const hiredPowerArr = this.toSixPeriods(hiredPower);

            for (let i = 0; i < 6; i++) {
                const key = `P${i + 1}`;
                power[key] = Number((hiredPowerArr[i] * powerArr[i] * days).toFixed(2));
                energy[key] = Number((this.parseNumber(periodConsumption?.[key] ?? 0) * energyArr[i]).toFixed(2));
                power.total += power[key];
                energy.total += energy[key];
            }

            power.total = Number(power.total.toFixed(2));
            energy.total = Number(energy.total.toFixed(2));

            return {
                power,
                energy,
                total: Number((power.total + energy.total).toFixed(2))
            };
        },
        buildDatadisOffers({ fee, hiredPower, periodConsumption, days, currentTotal }) {
            const offers = [];

            this.sortedMarketers.forEach(marketer => {
                const feeMarketer = marketer.fees?.electricity?.find(item => this.normalizeFeeName(item.name).includes(this.normalizeFeeName(fee)));
                if (!feeMarketer) return;

                (marketer.products?.electricity ?? []).forEach(product => {
                    const feeProduct = (product.fees ?? []).find(item => item.id?.$oid === feeMarketer.id?.$oid && !item.archived);
                    if (!feeProduct?.prices) return;

                    const powerPrices = feeProduct.prices.power ?? {};
                    const energyPrices = feeProduct.prices.consume ?? feeProduct.prices.energy ?? {};
                    const hasPower = this.priceObjectToArray(powerPrices).some(value => value > 0);
                    const hasEnergy = this.priceObjectToArray(energyPrices).some(value => value > 0);
                    if (!hasPower || !hasEnergy) return;

                    const subTotal = this.calcPriceTotals({
                        powerPrices,
                        energyPrices,
                        hiredPower,
                        periodConsumption,
                        days
                    });

                    const total = subTotal.total;
                    const save = Number((currentTotal - total).toFixed(2));

                    offers.push({
                        marketer: marketer.name,
                        marketerId: marketer._id,
                        fee: feeMarketer.name,
                        product: product.name,
                        priceType: feeProduct.priceType ?? "fixed",
                        prices: {
                            power: this.priceObjectToArray(powerPrices),
                            energy: this.priceObjectToArray(energyPrices)
                        },
                        subTotal,
                        total,
                        save,
                        savePercent: currentTotal === 0 ? 0 : Number(((save / currentTotal) * 100).toFixed(2))
                    });
                });
            });

            return offers.sort((a, b) => a.total - b.total).slice(0, 5);
        },
        buildPriceComparison({ supply, datadisContract, reportData, currentOrder }) {
            const prices = this.extractPricesFromOrder(currentOrder);
            const powerPrices = prices.power;
            const energyPrices = prices.energy;
            const hasCurrentPrices = this.priceObjectToArray(powerPrices).some(v => v > 0) && this.priceObjectToArray(energyPrices).some(v => v > 0);

            if (!currentOrder || !hasCurrentPrices) return null;

            const fee = this.normalizeFeeName(currentOrder.fee || datadisContract?.codeFare);
            const days = this.getDaysForSelectedPeriod(reportData.dateType, reportData.dateSelected);
            const hiredPower = this.extractHiredPower(currentOrder, datadisContract);
            const periodConsumption = reportData.periodConsumption ?? { P1: 0, P2: 0, P3: 0, P4: 0, P5: 0, P6: 0 };

            const currentSubTotal = this.calcPriceTotals({
                powerPrices,
                energyPrices,
                hiredPower,
                periodConsumption,
                days
            });

            const topOffers = this.buildDatadisOffers({
                fee,
                hiredPower,
                periodConsumption,
                days,
                currentTotal: currentSubTotal.total
            });

            return {
                cups: this.normalizeCups(supply?.cups),
                fee,
                days,
                dateType: reportData.dateType,
                dateSelected: reportData.dateSelected,
                dateLabel: reportData.dateLabel,
                periodConsumption,
                hiredPower,
                current: {
                    marketer: currentOrder.marketer ?? "",
                    product: currentOrder.product ?? "",
                    orderId: currentOrder._id ?? null,
                    prices: {
                        power: this.priceObjectToArray(powerPrices),
                        energy: this.priceObjectToArray(energyPrices)
                    },
                    subTotal: currentSubTotal,
                    total: currentSubTotal.total
                },
                bestOffer: topOffers[0] ?? null,
                topOffers
            };
        },

        async getAccounts() {
            const res = await axios.post(`/api/accounts/getDatadisAccounts/${this.basicData.userLogged._id}`, {
                userList: JSON.stringify(this.basicData.userList),
                cups: JSON.stringify(this.basicData.enterprise?.monitoredCups)
            });

            this.accounts = (res.data.accounts ?? [])
                .sort((a, b) => a.name.localeCompare(b.name))
                .map(item => ({ ...item, name: item.name.length > 30 ? item.name.slice(0, 27) + "..." : item.name }));

            this.cif = this.accounts[0]?.CIF ?? "";
        },

        async getToken() {
            this.isTokenLoaded = false;
            await axios.get("/api/tools/obtainDatadisToken").then((res) => {
                this.token = res.data;
                this.isTokenLoaded = true;

                //Si trae cups carga
                if (this.$route.query.CUPS && this.supplies.length === 0)
                    this.preloadCupsSelected()
            });
        },
        async getSupplies() {

            this.supplySelected = null;
            this.consumptions = null;
            this.selectedSupplies = [];
            this.loadedSuppliesData = {};
            this.priceComparisonByCups = {};

            let cif = this.cif.toLowerCase();
            const regexNifCif = /^(?:(\d{8})([A-Z])|[XYZ]\d{7,8}[A-Z]|([ABCDEFGHJKLMNPQRSUVW])(\d{7})([0-9A-J]))$/;
            if (cif === "b56037518") cif = "";
            else if (!regexNifCif.test(this.cif)) {
                Swal.fire({ icon: "error", title: "NIF o CIF inválido", text: "El NIF o CIF no es válido" });
                return;
            }

            this.initDefaultDates();
            this.loadingSupplies = true;
            await axios.get(`https://datadis.es/api-private/api/get-supplies?authorizedNif=${cif}`, {
                headers: { "Authorization": `Bearer ${this.token}` }
            }).then(res => {
                this.supplies = res.data;

                //Si hay algún cups lo selecciono
                if (this.$route.query.CUPS)
                    this.selectSupply(this.supplies.find(item => item.cups.substring(0, 20) === this.$route.query.CUPS.substring(0, 20)));


            }).catch(res => {
                console.log(res.error);
            }).finally(() => {
                if (!this.$route.query.CUPS)
                    this.showSupplies = true;
                this.loadingSupplies = false;
            });
        },
        async fetchConsumptionDataForSupply(supply, dateType, dateSelected) {
            let cif = this.cif.toLowerCase();
            if (cif === "b56037518") cif = "";

            const formData = new FormData();
            formData.append("token", this.token);
            formData.append("cif", cif);
            formData.append("supply", JSON.stringify(supply));
            formData.append("date", dateSelected);
            formData.append("dateType", dateType);

            const res = await axios.post("/api/tools/getDatadisConsumptionData", formData);
            return {
                consumptions: res.data.consumption ?? [],
                contract: res.data.contract?.[0] ?? null
            };
        },
        calculateSupplyReportData(consumptions, contract, dateType, dateSelected, supply = null, currentOrder = null) {
            const previousState = {
                dateType: this.dateType,
                dateSelected: this.dateSelected,
                dateInput: this.dateInput,
                consumptions: this.clone(this.consumptions),
                contract: this.clone(this.contract),
                stackedChartSeries: this.clone(this.stackedChartSeries),
                donutSeries: this.clone(this.donutSeries),
                summaryBarsSeries: this.clone(this.summaryBarsSeries),
                heatmapSeries: this.clone(this.heatmapSeries),
                totalConsumption: this.totalConsumption,
                consumptionPerInterval: this.consumptionPerInterval,
                periodConsumption: this.clone(this.periodConsumption),
            };

            this.dateType = dateType;
            this.dateSelected = dateSelected;
            this.dateInput = this.formatInputForType(dateType, dateSelected);
            this.consumptions = consumptions;
            this.contract = contract;
            this.calcData();

            const reportData = {
                supply,
                consumptions: this.clone(this.consumptions),
                contract: this.clone(this.contract),
                stackedChartSeries: this.clone(this.stackedChartSeries),
                donutSeries: this.clone(this.donutSeries),
                summaryBarsSeries: this.clone(this.summaryBarsSeries),
                heatmapSeries: this.clone(this.heatmapSeries),
                totalConsumption: this.totalConsumption,
                consumptionPerInterval: this.consumptionPerInterval,
                periodConsumption: this.clone(this.periodConsumption),
                dateType,
                dateSelected,
                dateLabel: this.formatDateLabel(dateType, dateSelected)
            };

            if (supply && currentOrder) {
                reportData.priceComparison = this.buildPriceComparison({
                    supply,
                    datadisContract: contract,
                    reportData,
                    currentOrder
                });
            }

            this.dateType = previousState.dateType;
            this.dateSelected = previousState.dateSelected;
            this.dateInput = previousState.dateInput;
            this.consumptions = previousState.consumptions;
            this.contract = previousState.contract;
            this.stackedChartSeries = previousState.stackedChartSeries;
            this.donutSeries = previousState.donutSeries;
            this.summaryBarsSeries = previousState.summaryBarsSeries;
            this.heatmapSeries = previousState.heatmapSeries;
            this.totalConsumption = previousState.totalConsumption;
            this.consumptionPerInterval = previousState.consumptionPerInterval;
            this.periodConsumption = previousState.periodConsumption;

            return reportData;
        },
        async loadSelectedSuppliesData() {
            if (this.selectedSupplies.length === 0) return;

            this.initDefaultDates();
            this.bulkLoadingConsumptionData = true;
            this.bulkLoadIndex = 0;
            this.bulkLoadTotal = this.selectedSupplies.length;

            const bulkDateType = this.generalDateType;
            const bulkDateSelected = this.generalDateSelected;
            const bulkDateLabel = this.formatDateLabel(bulkDateType, bulkDateSelected);

            for (const supply of this.selectedSupplies) {
                this.bulkLoadIndex++;
                this.loadedSuppliesData = {
                    ...this.loadedSuppliesData,
                    [supply.cups]: { status: "loading", supply, dateType: bulkDateType, dateSelected: bulkDateSelected, dateLabel: bulkDateLabel }
                };

                try {
                    const [{ consumptions, contract }, currentOrder] = await Promise.all([
                        this.fetchConsumptionDataForSupply(supply, bulkDateType, bulkDateSelected),
                        this.fetchCurrentContractByCups(supply.cups).catch(() => null)
                    ]);

                    if (!Array.isArray(consumptions) || consumptions.length === 0 || !contract) {
                        this.loadedSuppliesData = {
                            ...this.loadedSuppliesData,
                            [supply.cups]: {
                                status: "no-data",
                                supply,
                                contract,
                                consumptions: [],
                                dateType: bulkDateType,
                                dateSelected: bulkDateSelected,
                                dateLabel: bulkDateLabel,
                                totalConsumption: null,
                                consumptionPerInterval: null,
                                donutSeries: [],
                                stackedChartSeries: [],
                                summaryBarsSeries: [],
                                heatmapSeries: [],
                                periodConsumption: { P1: 0, P2: 0, P3: 0, P4: 0, P5: 0, P6: 0 },
                                priceComparison: null
                            }
                        };
                        continue;
                    }

                    const reportData = this.calculateSupplyReportData(consumptions, contract, bulkDateType, bulkDateSelected, supply, currentOrder);
                    if (reportData.priceComparison) {
                        this.priceComparisonByCups = { ...this.priceComparisonByCups, [supply.cups]: reportData.priceComparison };
                    }

                    const hasUsefulData = reportData.stackedChartSeries?.length > 0 && Number(reportData.totalConsumption || 0) > 0;
                    this.loadedSuppliesData = {
                        ...this.loadedSuppliesData,
                        [supply.cups]: {
                            status: hasUsefulData ? "loaded" : "no-data",
                            supply,
                            ...reportData,
                            dateType: bulkDateType,
                            dateSelected: bulkDateSelected,
                            dateLabel: bulkDateLabel
                        }
                    };
                } catch (e) {
                    console.error(`Error cargando suministro ${supply.cups}:`, e);
                    this.loadedSuppliesData = {
                        ...this.loadedSuppliesData,
                        [supply.cups]: { status: "error", supply, errorMessage: e?.message ?? "Error cargando datos", dateType: bulkDateType, dateSelected: bulkDateSelected, dateLabel: bulkDateLabel }
                    };
                }
            }

            this.bulkLoadingConsumptionData = false;
        },
        selectSupply(supply) {
            this.supplySelected = supply;
            this.showSupplies = false;
            this.includePriceComparisonInReport = false;

            const loadedData = this.loadedSuppliesData[supply.cups];
            if (loadedData) {
                this.dateType = loadedData.dateType ?? this.generalDateType;
                this.dateSelected = loadedData.dateSelected ?? this.generalDateSelected;
                this.dateInput = this.formatInputForType(this.dateType, this.dateSelected);
                this.contract = loadedData.contract ?? null;
                this.consumptions = loadedData.consumptions ?? null;
                this.totalConsumption = loadedData.totalConsumption ?? null;
                this.consumptionPerInterval = loadedData.consumptionPerInterval ?? null;
                this.donutSeries = loadedData.donutSeries ?? [];
                this.stackedChartSeries = loadedData.stackedChartSeries ?? [];
                this.summaryBarsSeries = loadedData.summaryBarsSeries ?? [];
                this.heatmapSeries = loadedData.heatmapSeries ?? [];
                this.periodConsumption = loadedData.periodConsumption ?? { P1: 0, P2: 0, P3: 0, P4: 0, P5: 0, P6: 0 };
                if (loadedData.priceComparison) {
                    this.priceComparisonByCups = { ...this.priceComparisonByCups, [supply.cups]: loadedData.priceComparison };
                }
                this.currentContractStatus = loadedData.priceComparison ? "found" : "not-found";
                return;
            }

            this.dateType = this.generalDateType;
            this.dateSelected = this.generalDateSelected;
            this.dateInput = this.formatInputForType(this.dateType, this.dateSelected);
            this.getConsumptionData();
        },
        deselectSupply() {
            this.supplySelected = null;
            this.showSupplies = true;
            this.consumptions = null;
            this.includePriceComparisonInReport = false;
        },
        async getConsumptionData() {
            if (!this.supplySelected) return;

            this.loadingConsumptionData = true;
            this.loadingContractPrices = true;
            this.currentContractStatus = null;

            const currentSupply = this.supplySelected;
            const currentDateType = this.dateType;
            const currentDateSelected = this.dateSelected;
            const currentDateLabel = this.formatDateLabel(currentDateType, currentDateSelected);

            this.loadedSuppliesData = {
                ...this.loadedSuppliesData,
                [currentSupply.cups]: {
                    ...(this.loadedSuppliesData[currentSupply.cups] ?? {}),
                    status: "loading",
                    supply: currentSupply,
                    dateType: currentDateType,
                    dateSelected: currentDateSelected,
                    dateLabel: currentDateLabel
                }
            };

            try {
                const [{ consumptions, contract }, currentOrder] = await Promise.all([
                    this.fetchConsumptionDataForSupply(currentSupply, currentDateType, currentDateSelected),
                    this.fetchCurrentContractByCups(currentSupply.cups).catch(() => null)
                ]);

                this.currentContractOrder = currentOrder;
                this.contract = contract;
                this.consumptions = consumptions;

                if (!Array.isArray(consumptions) || consumptions.length === 0 || !contract) {
                    this.totalConsumption = null;
                    this.consumptionPerInterval = null;
                    this.donutSeries = [];
                    this.stackedChartSeries = [];
                    this.summaryBarsSeries = [];
                    this.heatmapSeries = [];
                    this.periodConsumption = { P1: 0, P2: 0, P3: 0, P4: 0, P5: 0, P6: 0 };
                    this.currentContractStatus = currentOrder ? "found" : "not-found";
                    this.includePriceComparisonInReport = false;

                    this.loadedSuppliesData = {
                        ...this.loadedSuppliesData,
                        [currentSupply.cups]: {
                            status: "no-data",
                            supply: currentSupply,
                            contract,
                            consumptions: [],
                            dateType: currentDateType,
                            dateSelected: currentDateSelected,
                            dateLabel: currentDateLabel,
                            priceComparison: null
                        }
                    };
                    return;
                }

                this.calcData();

                const reportData = {
                    dateType: currentDateType,
                    dateSelected: currentDateSelected,
                    dateLabel: currentDateLabel,
                    periodConsumption: this.clone(this.periodConsumption)
                };

                const priceComparison = this.buildPriceComparison({
                    supply: currentSupply,
                    datadisContract: contract,
                    reportData,
                    currentOrder
                });

                if (!priceComparison) this.includePriceComparisonInReport = false;

                this.currentContractStatus = priceComparison ? "found" : "not-found";
                this.priceComparisonByCups = { ...this.priceComparisonByCups, [currentSupply.cups]: priceComparison };

                const status = this.stackedChartSeries.length > 0 && Number(this.totalConsumption || 0) > 0 ? "loaded" : "no-data";
                this.loadedSuppliesData = {
                    ...this.loadedSuppliesData,
                    [currentSupply.cups]: {
                        status,
                        supply: currentSupply,
                        contract: this.clone(this.contract),
                        consumptions: this.clone(this.consumptions),
                        totalConsumption: this.totalConsumption,
                        consumptionPerInterval: this.consumptionPerInterval,
                        donutSeries: this.clone(this.donutSeries),
                        stackedChartSeries: this.clone(this.stackedChartSeries),
                        summaryBarsSeries: this.clone(this.summaryBarsSeries),
                        heatmapSeries: this.clone(this.heatmapSeries),
                        periodConsumption: this.clone(this.periodConsumption),
                        priceComparison,
                        dateType: currentDateType,
                        dateSelected: currentDateSelected,
                        dateLabel: currentDateLabel
                    }
                };
            } catch (e) {
                console.error("Error cargando consumo:", e);
                this.includePriceComparisonInReport = false;
                this.loadedSuppliesData = {
                    ...this.loadedSuppliesData,
                    [currentSupply.cups]: {
                        status: "error",
                        supply: currentSupply,
                        errorMessage: e?.message ?? "Error cargando datos",
                        dateType: currentDateType,
                        dateSelected: currentDateSelected,
                        dateLabel: currentDateLabel
                    }
                };
            } finally {
                this.loadingConsumptionData = false;
                this.loadingContractPrices = false;
            }
        },
        calcData() {
            this.stackedChartSeries = [];
            this.donutSeries = [{ value: 0, category: "Valle" }, { value: 0, category: "Llano" }, { value: 0, category: "Punta" }];
            this.summaryBarsSeries = [];
            this.heatmapSeries = [];
            this.periodConsumption = { P1: 0, P2: 0, P3: 0, P4: 0, P5: 0, P6: 0 };
            this.totalConsumption = null;
            this.consumptionPerInterval = null;

            if (!this.consumptions || !this.contract) return;

            let startDate, endDate, dateFormat, summaryFormat, dateChangeMethod, intervalChangeMethod;
            switch (this.dateType) {
                case "day":
                    startDate = moment(this.dateSelected).subtract(2, "days").startOf("day");
                    endDate = moment(this.dateSelected).add(2, "days").endOf("day");
                    dateFormat = "HH";
                    dateChangeMethod = "hour";
                    summaryFormat = "DD/MM";
                    intervalChangeMethod = "date";
                    break;
                case "isoWeek":
                    startDate = moment(this.dateSelected).subtract(2, "weeks").startOf("isoWeek");
                    endDate = moment(this.dateSelected).add(2, "weeks").endOf("isoWeek");
                    dateFormat = "DD/MM";
                    dateChangeMethod = "date";
                    summaryFormat = "[S]WW";
                    intervalChangeMethod = "isoWeek";
                    break;
                case "month":
                    startDate = moment(this.dateSelected).subtract(2, "months").startOf("month");
                    endDate = moment(this.dateSelected).add(2, "months").endOf("month");
                    dateFormat = "D";
                    dateChangeMethod = "date";
                    summaryFormat = "MMM";
                    intervalChangeMethod = "month";
                    break;
                default:
                    return;
            }

            let fee = null;
            if (this.contract.codeFare === "2T") {
                fee = this.dataFees[0];
            } else {
                fee = this.dataFees[1];
                this.donutSeries = [
                    { value: 0, category: "P6" },
                    { value: 0, category: fee.months[moment(this.dateSelected).month()].mid },
                    { value: 0, category: fee.months[moment(this.dateSelected).month()].high }
                ];
            }

            let date, intervalDate, stackedData, summaryData, isWeekend, isHoliday;
            const donutData = { low: 0, mid: 0, high: 0 };

            const getPeriodKey = (consumptionDate, interval) => {
                if (this.contract.codeFare === "2T") {
                    if (interval === "low") return "P3";
                    if (interval === "mid") return "P2";
                    return "P1";
                }
                if (interval === "low") return "P6";
                const monthConfig = fee.months[consumptionDate.month()];
                if (interval === "mid") return monthConfig.mid;
                return monthConfig.high;
            };

            const addToPeriod = (consumptionDate, interval, value) => {
                const periodKey = getPeriodKey(consumptionDate, interval);
                this.periodConsumption[periodKey] += value;
            };

            for (const consumption of this.consumptions) {
                const consumptionDate = moment(`${consumption.date} ${consumption.time}`, "YYYY/MM/DD HH:mm").subtract(1, "hours");
                const consumptionKWh = Number(consumption.consumptionKWh || 0);

                if (consumptionDate.isSame(moment(this.dateSelected), this.dateType)) {
                    if (typeof date === "undefined") {
                        summaryData = { date: consumptionDate.format(summaryFormat), low: 0, mid: 0, high: 0, columnSettings: {} };
                    }
                    if (consumptionDate[dateChangeMethod]() !== date) {
                        if (typeof date !== "undefined") this.stackedChartSeries.push(stackedData);
                        date = consumptionDate[dateChangeMethod]();
                        isWeekend = consumptionDate.day() === 6 || consumptionDate.day() === 0;
                        isHoliday = this.holidayDates.includes(consumptionDate.format("YYYY/MM/DD"));
                        stackedData = { date: consumptionDate.format(dateFormat), low: 0, mid: 0, high: 0 };
                    }

                    if (isWeekend || isHoliday) {
                        stackedData.low += consumptionKWh;
                        donutData.low += consumptionKWh;
                        summaryData.low += consumptionKWh;
                        addToPeriod(consumptionDate, "low", consumptionKWh);
                    } else {
                        for (const interval in fee.intervals) {
                            if (fee.intervals[interval].includes(parseInt(consumption.time))) {
                                stackedData[interval] += consumptionKWh;
                                donutData[interval] += consumptionKWh;
                                summaryData[interval] += consumptionKWh;
                                addToPeriod(consumptionDate, interval, consumptionKWh);
                            }
                        }
                    }
                } else if (consumptionDate.isBetween(startDate, endDate, null, [])) {
                    if (consumptionDate[intervalChangeMethod]() !== intervalDate) {
                        if (intervalDate !== undefined) this.summaryBarsSeries.push(summaryData);
                        intervalDate = consumptionDate[intervalChangeMethod]();
                        summaryData = { date: consumptionDate.format(summaryFormat), low: 0, mid: 0, high: 0, columnSettings: { fill: "#ccc", stroke: "#ccc" } };
                    }
                    summaryData.low += consumptionKWh;
                }
            }

            if (stackedData) this.stackedChartSeries.push(stackedData);
            if (summaryData) this.summaryBarsSeries.push(summaryData);
            Object.values(donutData).forEach((value, i) => { this.donutSeries[i].value = value; });

            this.totalConsumption = Object.values(donutData).reduce((total, interval) => total + interval, 0);
            this.consumptionPerInterval = this.stackedChartSeries.length > 0 ? this.totalConsumption / this.stackedChartSeries.length : null;

            let dataLength, dateTypeToAdd;
            switch (this.dateType) {
                case "day": dataLength = 24; dateTypeToAdd = "hours"; break;
                case "isoWeek": dataLength = 7; dateTypeToAdd = "days"; break;
                case "month": dataLength = moment(this.dateSelected).daysInMonth(); dateTypeToAdd = "days"; break;
            }

            if (this.stackedChartSeries.length === 0) return;

            let lastDate = moment(this.stackedChartSeries[this.stackedChartSeries.length - 1].date, dateFormat);
            while (this.stackedChartSeries.length < dataLength) {
                lastDate = lastDate.add(1, dateTypeToAdd);
                this.stackedChartSeries.push({ date: lastDate.format(dateFormat), low: 0, mid: 0, high: 0 });
            }

            this.buildHeatmapSeries();
        },
        buildHeatmapSeries() {
            this.heatmapSeries = [];
            if (!this.consumptions || !this.dateSelected || !this.dateType) return;

            let start, end, rowFormat;
            switch (this.dateType) {
                case "day":
                    start = moment(this.dateSelected).startOf("day");
                    end = moment(this.dateSelected).endOf("day");
                    rowFormat = "DD/MM";
                    break;
                case "isoWeek":
                    start = moment(this.dateSelected).startOf("isoWeek");
                    end = moment(this.dateSelected).endOf("isoWeek");
                    rowFormat = "dd DD";
                    break;
                case "month":
                    start = moment(this.dateSelected).startOf("month");
                    end = moment(this.dateSelected).endOf("month");
                    rowFormat = "DD MMM";
                    break;
            }

            const rowsMap = {};
            let cursor = start.clone();
            while (cursor.isSameOrBefore(end, "day")) {
                const rowKey = cursor.format("YYYY/MM/DD");
                rowsMap[rowKey] = { rowKey, rowLabel: cursor.locale("es").format(rowFormat), values: Array(24).fill(0) };
                cursor.add(1, "day");
            }

            for (const consumption of this.consumptions) {
                const consumptionDate = moment(`${consumption.date} ${consumption.time}`, "YYYY/MM/DD HH:mm").subtract(1, "hours");
                if (!consumptionDate.isBetween(start, end, null, "[]")) continue;
                const rowKey = consumptionDate.format("YYYY/MM/DD");
                const hour = consumptionDate.hour();
                if (rowsMap[rowKey]) rowsMap[rowKey].values[hour] += Number(consumption.consumptionKWh || 0);
            }

            this.heatmapSeries = Object.values(rowsMap);
        },
        async generateReportBlobFromData(reportData) {
            const res = await axios.post("/api/tools/generateDatadisReport", {
                supply: reportData.supply,
                contract: reportData.contract,
                dateType: reportData.dateType,
                dateSelected: reportData.dateSelected,
                dateLabel: reportData.dateLabel ?? this.formatDateLabel(reportData.dateType, reportData.dateSelected),
                totalConsumption: reportData.totalConsumption,
                consumptionPerInterval: reportData.consumptionPerInterval,
                donutSeries: reportData.donutSeries,
                stackedChartSeries: reportData.stackedChartSeries,
                summaryBarsSeries: reportData.summaryBarsSeries,
                heatmapSeries: reportData.heatmapSeries,
                periodConsumption: reportData.periodConsumption,
                priceComparison: reportData.priceComparison ?? null,
                basicData: this.basicData
            }, { responseType: "blob" });

            return res.data;
        },
        async downloadReportFromData(reportData) {
            const blob = await this.generateReportBlobFromData(reportData);
            const url = URL.createObjectURL(new Blob([blob], { type: "application/pdf" }));
            const a = document.createElement("a");
            a.href = url;
            a.download = `consumo_${reportData.supply.cups}_${moment(reportData.dateSelected, "YYYY/MM/DD").format("YYYY-MM-DD")}.pdf`;
            a.click();
            URL.revokeObjectURL(url);
        },
        buildCurrentReportPayload() {
            return {
                supply: this.supplySelected,
                contract: this.contract,
                dateType: this.dateType,
                dateSelected: this.dateSelected,
                dateLabel: this.formatDateLabel(this.dateType, this.dateSelected),
                totalConsumption: this.totalConsumption,
                consumptionPerInterval: this.consumptionPerInterval,
                donutSeries: this.donutSeries,
                stackedChartSeries: this.stackedChartSeries,
                summaryBarsSeries: this.summaryBarsSeries,
                heatmapSeries: this.heatmapSeries,
                periodConsumption: this.periodConsumption,
                priceComparison: this.includePriceComparisonInReport ? this.currentPriceComparison : null
            };
        },
        buildBulkReportPayload(report) {
            return {
                supply: report.supply,
                contract: report.contract,
                dateType: report.dateType,
                dateSelected: report.dateSelected,
                dateLabel: report.dateLabel ?? this.formatDateLabel(report.dateType, report.dateSelected),
                totalConsumption: report.totalConsumption,
                consumptionPerInterval: report.consumptionPerInterval,
                donutSeries: report.donutSeries,
                stackedChartSeries: report.stackedChartSeries,
                summaryBarsSeries: report.summaryBarsSeries,
                heatmapSeries: report.heatmapSeries,
                periodConsumption: report.periodConsumption,
                priceComparison: this.includeBulkPriceComparisonInReport ? (report.priceComparison ?? null) : null
            };
        },
        async getReportEmailTarget(cups) {
            const res = await axios.get("/api/tools/datadisReportEmailTarget", {
                params: {
                    cups: this.normalizeCups(cups)
                }
            });

            return res.data;
        },
        getSelectedAccount() {
            return (this.accounts ?? []).find(account => account.CIF === this.cif) ?? null;
        },
        getAccountEmail(account) {
            return account?.email
                ?? account?.mail
                ?? account?.contactEmail
                ?? account?.billingEmail
                ?? account?.accountEmail
                ?? account?.user?.email
                ?? "";
        },
        async getReportEmailTargetWithFallback(cups) {
            let target = null;

            try {
                target = await this.getReportEmailTarget(cups);
            } catch (e) {
                console.error("No se pudo obtener email objetivo por CUPS:", e);
                target = null;
            }

            const selectedAccount = this.getSelectedAccount();
            const fallbackEmail = this.getAccountEmail(selectedAccount);

            return {
                ...(target ?? {}),
                account: target?.account ?? selectedAccount,
                email: target?.email ?? fallbackEmail
            };
        },
        escapeHtml(value) {
            return String(value ?? "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        },
        buildReportEmailMessage(reportData, target) {
            const cups = this.escapeHtml(this.normalizeCups(reportData.supply?.cups));
            const accountName = this.escapeHtml(target?.account?.name ?? "cliente");
            const periodLabel = this.escapeHtml(reportData.dateLabel ?? this.formatDateLabel(reportData.dateType, reportData.dateSelected));
            const enterpriseName = this.escapeHtml(this.basicData?.enterprise?.name ?? "nuestro equipo");

            return `
                <p>Hola,</p>
                <p>Adjuntamos el informe de consumo energético correspondiente al suministro <strong>${cups}</strong>.</p>
                <p><strong>Cuenta:</strong> ${accountName}</p>
                <p><strong>Periodo:</strong> ${periodLabel}</p>
                <p>Quedamos a tu disposición para cualquier consulta.</p>
                <p>Un saludo,</p>
                <p>El equipo de <strong>${enterpriseName}</strong></p>
            `;
        },
        buildBulkReportEmailMessage(reports) {
            const enterpriseName = this.escapeHtml(this.basicData?.enterprise?.name ?? "nuestro equipo");

            const reportsList = reports.map(report => {
                const cups = this.escapeHtml(this.normalizeCups(report.supply?.cups));
                const period = this.escapeHtml(report.dateLabel ?? this.formatDateLabel(report.dateType, report.dateSelected));

                return `<li><strong>${cups}</strong> — ${period}</li>`;
            }).join("");

            return `
                <p>Hola,</p>
                <p>Adjuntamos los informes de consumo energético correspondientes a los suministros seleccionados.</p>
                <ul>${reportsList}</ul>
                <p>Quedamos a tu disposición para cualquier consulta.</p>
                <p>Un saludo,</p>
                <p>El equipo de <strong>${enterpriseName}</strong></p>
            `;
        },
        buildReportFileName(reportData) {
            const cups = this.normalizeCups(reportData.supply?.cups);
            const date = moment(reportData.dateSelected, "YYYY/MM/DD").format("YYYY-MM-DD");

            return `informe_consumo_${cups}_${date}.pdf`;
        },
        blobToBase64(blob) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();

                reader.onloadend = () => resolve(reader.result);
                reader.onerror = reject;

                reader.readAsDataURL(blob);
            });
        },


        async saveReportAttachmentForMassiveEmail(reportData) {
            const pdfBlob = await this.generateReportBlobFromData(reportData);
            const fileName = this.buildReportFileName(reportData);
            const pdfBase64 = await this.blobToBase64(pdfBlob);

            const res = await axios.post("/api/tools/prepareMassiveEmailDoc", {
                pdfBase64,
                title: fileName
            });

            const doc = res.data.doc;

            return {
                fileName: doc.value,
                title: doc.title,
                defaultTitle: doc.defaultTitle,
                value: doc.value,
                icon: doc.icon || "fa-file-pdf",
                errors: doc.errors || {}
            };
        },
        openMassiveEmailToolWithDraft(emailDraft) {
            const draft = {
                ...emailDraft,
                from: "datadis",
                createdAt: Date.now()
            };

            const key = "datadisMassiveEmailDraft";
            const serializedDraft = JSON.stringify(draft);

            sessionStorage.setItem(key, serializedDraft);
            localStorage.setItem(key, serializedDraft);

            console.log("DATADIS - origin:", window.location.origin);
            console.log("DATADIS - draft guardado sessionStorage:", sessionStorage.getItem(key));
            console.log("DATADIS - draft guardado localStorage:", localStorage.getItem(key));

            window.location.assign("/tools?section=massiveEmail&withDraft=true&from=datadis");
        },
        buildSingleReportMassiveEmailDraft(reportData, target, savedFile, recipientEmail) {
            const cups = this.normalizeCups(reportData.supply?.cups);

            return {
                subject: `Informe de consumo energético - ${cups}`,
                message: this.buildReportEmailMessage(reportData, target),
                docs: [
                    {
                        title: savedFile.title || "Informe de consumo energético",
                        defaultTitle: savedFile.defaultTitle || savedFile.fileName,
                        value: savedFile.value || savedFile.fileName,
                        icon: savedFile.icon || "fa-file-pdf",
                        errors: savedFile.errors || {}
                    }
                ],
                recipients: [
                    {
                        email: recipientEmail,
                        type: "account"
                    }
                ]
            };
        },
        buildBulkReportMassiveEmailDraft(reports, savedFiles, recipientEmail) {
            return {
                subject: `Informes de consumo energético - ${reports.length} suministros`,
                message: this.buildBulkReportEmailMessage(reports),
                docs: savedFiles.map(savedFile => ({
                    title: savedFile.title || "Informe de consumo energético",
                    defaultTitle: savedFile.defaultTitle || savedFile.fileName,
                    value: savedFile.value || savedFile.fileName,
                    icon: savedFile.icon || "fa-file-pdf",
                    errors: savedFile.errors || {}
                })),
                recipients: [
                    {
                        email: recipientEmail,
                        type: "account"
                    }
                ]
            };
        },
        async openCurrentReportInMassiveEmail() {
            if (!this.supplySelected) return;

            if (!this.consumptions || this.stackedChartSeries.length <= 1) {
                Swal.fire({
                    icon: "info",
                    title: "Sin informe",
                    text: "Primero tienes que cargar datos para este suministro."
                });
                return;
            }

            this.sendingReportEmail = true;

            try {
                const reportData = this.buildCurrentReportPayload();
                const target = await this.getReportEmailTargetWithFallback(reportData.supply.cups);

                const confirm = await Swal.fire({
                    icon: "question",
                    title: "Preparar correo masivo",
                    html: `
                        <p>Se preparará el correo con el informe del suministro:</p>
                        <p><strong>${this.escapeHtml(this.normalizeCups(reportData.supply.cups))}</strong></p>
                        <p>Puedes confirmar o modificar el correo destinatario:</p>
                    `,
                    input: "email",
                    inputValue: target?.email ?? "",
                    inputPlaceholder: "Correo destinatario",
                    showCancelButton: true,
                    confirmButtonText: "Preparar correo",
                    cancelButtonText: "Cancelar",
                    inputValidator: value => {
                        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                        if (!value) return "Introduce un correo.";
                        if (!regex.test(value)) return "El correo no es válido.";

                        return null;
                    }
                });

                if (!confirm.isConfirmed) return;

                const savedDoc = await this.saveReportAttachmentForMassiveEmail(reportData);

                const draft = this.buildSingleReportMassiveEmailDraft(
                    reportData,
                    target,
                    savedDoc,
                    confirm.value
                );

                this.openMassiveEmailToolWithDraft(draft);
            } catch (e) {
                console.error("Error preparando correo masivo:", e);

                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: e?.response?.data?.message ?? "No se pudo preparar el correo masivo."
                });
            } finally {
                this.sendingReportEmail = false;
            }
        },
        async openSelectedReportsInMassiveEmail() {
            const reports = this.selectedLoadedReports;
            if (reports.length === 0) {
                Swal.fire({ icon: "info", title: "Sin informes", text: "No hay suministros seleccionados con datos cargados." });
                return;
            }

            const selectedAccount = this.getSelectedAccount();
            const fallbackEmail = this.getAccountEmail(selectedAccount);

            const confirm = await Swal.fire({
                icon: "question",
                title: "Preparar correo masivo",
                html: `
                    <p>Se preparará un correo con <strong>${reports.length}</strong> informe(s).</p>
                    <p>Puedes confirmar o modificar el correo destinatario:</p>
                `,
                input: "email",
                inputValue: fallbackEmail ?? "",
                inputPlaceholder: "Correo destinatario",
                showCancelButton: true,
                confirmButtonText: "Preparar correo",
                cancelButtonText: "Cancelar",
                inputValidator: value => {
                    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                    if (!value) return "Introduce un correo.";
                    if (!regex.test(value)) return "El correo no es válido.";

                    return null;
                }
            });

            if (!confirm.isConfirmed) return;

            this.sendingBulkReportEmails = true;
            this.bulkEmailIndex = 0;
            this.bulkEmailTotal = reports.length;

            try {
                const reportPayloads = reports.map(report => this.buildBulkReportPayload(report));
                const savedFiles = [];

                for (const reportData of reportPayloads) {
                    this.bulkEmailIndex++;
                    savedFiles.push(await this.saveReportAttachmentForMassiveEmail(reportData));
                }

                const draft = this.buildBulkReportMassiveEmailDraft(reportPayloads, savedFiles, confirm.value);
                this.openMassiveEmailToolWithDraft(draft);
            } catch (e) {
                console.error("Error preparando correo masivo:", e);

                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: e?.response?.data?.message ?? "No se pudo preparar el correo masivo."
                });
            } finally {
                this.sendingBulkReportEmails = false;
            }
        },
        async generateReport() {
            if (!this.supplySelected) return;
            this.generatingPdf = true;
            try {
                await this.downloadReportFromData(this.buildCurrentReportPayload());
            } catch (e) {
                console.error("Error generando informe:", e);
                Swal.fire({ icon: "error", title: "Error", text: "No se pudo generar el informe." });
            } finally {
                this.generatingPdf = false;
            }
        },
        async generateSelectedReports() {
            const reports = this.selectedLoadedReports;
            if (reports.length === 0) {
                Swal.fire({ icon: "info", title: "Sin informes", text: "No hay suministros seleccionados con datos cargados." });
                return;
            }

            this.generatingBulkReports = true;
            this.bulkReportIndex = reports.length;
            this.bulkReportTotal = reports.length;

            try {
                const payload = {
                    bulk: true,
                    basicData: this.basicData,
                    reports: reports.map(report => this.buildBulkReportPayload(report))
                };

                const res = await axios.post("/api/tools/generateDatadisReport", payload, { responseType: "blob" });
                const url = URL.createObjectURL(new Blob([res.data], { type: "application/zip" }));
                const a = document.createElement("a");
                a.href = url;
                a.download = `informes_datadis_${moment().format("YYYYMMDD_HHmm")}.zip`;
                a.click();
                URL.revokeObjectURL(url);
            } catch (e) {
                console.error("Error generando paquete de informes:", e);
                Swal.fire({ icon: "error", title: "Error", text: "No se pudo generar el paquete de informes." });
            } finally {
                this.generatingBulkReports = false;
            }
        },
        changeDateSelected(direction) {
            switch (this.dateType) {
                case "day":
                    this.dateSelected = direction === "prev" ? moment(this.dateSelected).subtract(1, "days").format("YYYY/MM/DD") : moment(this.dateSelected).add(1, "days").format("YYYY/MM/DD");
                    break;
                case "isoWeek":
                    this.dateSelected = direction === "prev" ? moment(this.dateSelected).subtract(1, "weeks").format("YYYY/MM/DD") : moment(this.dateSelected).add(1, "weeks").format("YYYY/MM/DD");
                    break;
                case "month":
                    this.dateSelected = direction === "prev" ? moment(this.dateSelected).subtract(1, "months").format("YYYY/MM/DD") : moment(this.dateSelected).add(1, "months").format("YYYY/MM/DD");
                    break;
            }
            this.dateInput = this.formatInputForType(this.dateType, this.dateSelected);
            this.includePriceComparisonInReport = false;
            this.getConsumptionData();
        },
        async preloadCupsSelected() {

            this.cups = this.$route.query.CUPS;

            //Busco la cuenta que tiene ese cif
            await axios.get('/api/accounts/getCIFByCUPS', { params: { cups: this.cups } })
                .then(res => {
                    this.cif = res.data.cif
                })
                .catch(err => { console.log(err) })

            await this.getSupplies();
        }
    }
}
</script>

<style scoped>
.datadis-supplies-table {
    display: grid;
    grid-template-columns: 40px minmax(190px, 1.1fr) 120px 150px minmax(220px, 1.5fr) 95px 105px 145px 35px;
    align-items: center;
    gap: 12px;
}

.datadis-bulk-toolbar {
    padding: 12px 16px;
    border-radius: 14px;
    background: rgba(0, 0, 0, 0.02);
}

.datadis-compare-option {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
}

.datadis-compare-option input {
    cursor: pointer;
}

.datadis-compare-option.disabled,
.datadis-compare-option input:disabled + span {
    opacity: 0.45;
    cursor: not-allowed;
}

.datadis-status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
    background: #eef2f7;
    color: #4b5563;
}

.datadis-status-badge[data-status="loaded"] { background: #dcfce7; color: #166534; }
.datadis-status-badge[data-status="no-data"] { background: #fef9c3; color: #854d0e; }
.datadis-status-badge[data-status="error"] { background: #fee2e2; color: #991b1b; }
.datadis-status-badge[data-status="loading"] { background: #dbeafe; color: #1d4ed8; }

.datadis-price-card {
    padding: 18px;
}

.datadis-price-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
}

.datadis-offer-table {
    display: grid;
    grid-template-columns: minmax(260px, 1.6fr) 100px 100px 100px 100px;
    gap: 10px;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.datadis-offer-header {
    font-weight: 700;
    opacity: 0.65;
}

.text-end {
    text-align: right;
}

.datadis-heatmap-card {
    padding: 18px;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    justify-content: flex-start;
    gap: 10px;
    overflow: hidden;
}

.datadis-heatmap-title-row {
    width: 100%;
}

.datadis-heatmap-scroll {
    display: block;
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    padding-bottom: 8px;
}

.datadis-heatmap-grid {
    display: grid;
    grid-template-columns: 92px repeat(24, minmax(42px, 1fr));
    gap: 1px;
    width: 100%;
    min-width: 1180px;
    flex: 0 0 auto;
}

.datadis-heatmap-row-label {
    min-height: 42px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 8px;
    font-size: 13px;
    color: #374151;
    white-space: nowrap;
}

.datadis-heatmap-hour {
    min-height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: #374151;
}

.datadis-heatmap-cell {
    min-height: 42px;
    border-radius: 2px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    line-height: 1.05;
    transition: transform 0.12s ease, box-shadow 0.12s ease;
}

.datadis-heatmap-cell:hover {
    transform: scale(1.06);
    z-index: 2;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);
}

.datadis-heatmap-value {
    font-size: 13px;
    font-weight: 700;
}

.datadis-heatmap-period {
    margin-top: 3px;
    font-size: 11px;
    font-weight: 600;
    opacity: 0.65;
}

.datadis-heatmap-legend {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 8px;
    width: 100%;
    min-width: 0;
    font-size: 12px;
    color: #4b5563;
    white-space: nowrap;
}

.datadis-heatmap-gradient {
    width: 100%;
    height: 12px;
    border-radius: 999px;
    background: linear-gradient(90deg, #fff36b, #ffb347, #ff3030);
}

@media (max-width: 810px) {
    .datadis-supplies-table {
        grid-template-columns: 30px minmax(160px, 1fr) 80px 90px minmax(150px, 1fr) 70px 90px 120px 30px;
        overflow-x: auto;
    }

    .datadis-price-grid,
    .datadis-offer-table {
        grid-template-columns: 1fr;
    }

    .text-end {
        text-align: left;
    }

    .datadis-heatmap-card {
        padding: 12px;
    }

    .datadis-heatmap-grid {
        grid-template-columns: 70px repeat(24, 44px);
        min-width: 1130px;
    }

    .datadis-heatmap-row-label {
        font-size: 12px;
    }
}
</style>

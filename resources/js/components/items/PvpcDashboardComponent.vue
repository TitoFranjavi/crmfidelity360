<template>
    <div class="pvpc-dashboard-widget">
        <div class="pvpc-widget-header">
            <div>
                <h3>Precios electricidad</h3>
                <p>Resumen diario PVPC, 3.0TD y 6.1TD</p>
            </div>

            <button type="button" @click="goToDetail">
                Ver más detalle
            </button>
        </div>

        <div class="pvpc-widget-top-row">
            <div class="pvpc-widget-tabs">
                <button
                    type="button"
                    :class="{ active: selectedTariff === 'PVPC' }"
                    @click="changeTariff('PVPC')"
                >
                    PVPC
                </button>

                <button
                    type="button"
                    :class="{ active: selectedTariff === '3.0TD' }"
                    @click="changeTariff('3.0TD')"
                >
                    3.0TD
                </button>

                <button
                    type="button"
                    :class="{ active: selectedTariff === '6.1TD' }"
                    @click="changeTariff('6.1TD')"
                >
                    6.1TD
                </button>
            </div>

            <span class="pvpc-widget-date">
                {{ getTariffLabel() }} · {{ formatDate(data?.date || selectedDate) }}
            </span>
        </div>

        <div v-if="loading" class="pvpc-widget-loading">
            Cargando precios...
        </div>

        <div v-else-if="error" class="pvpc-widget-error">
            {{ error }}
        </div>

        <div v-else-if="data" class="pvpc-widget-content">
            <div class="pvpc-widget-summary">
                <div class="pvpc-summary-card">
                    <span>Precio medio</span>
                    <strong>{{ formatPrice(data.average_c_kwh) }}</strong>
                    <small>c€/kWh</small>
                </div>

                <div class="pvpc-summary-card cheap">
                    <span>Hora más barata</span>
                    <strong>{{ formatHour(data.min?.hour) }}</strong>
                    <small>{{ formatPrice(getPrice(data.min)) }} c€/kWh</small>
                </div>

                <div class="pvpc-summary-card expensive">
                    <span>Hora más cara</span>
                    <strong>{{ formatHour(data.max?.hour) }}</strong>
                    <small>{{ formatPrice(getPrice(data.max)) }} c€/kWh</small>
                </div>
            </div>

            <div class="pvpc-widget-chart-card">
                <div class="pvpc-widget-chart-head">
                    <strong>Precio horario</strong>
                    <span>Valores en c€/kWh</span>
                </div>

                <div class="pvpc-widget-chart">
                    <div
                        v-for="item in data.hours"
                        :key="'mini-' + item.hour"
                        class="pvpc-widget-bar-column"
                        :title="formatHour(item.hour) + ' · ' + formatPrice(getPrice(item)) + ' c€/kWh'"
                    >
                        <div
                            class="pvpc-widget-top-price"
                            :class="{
                                min: isMinHour(item),
                                max: isMaxHour(item)
                            }"
                        >
                            {{ formatPrice(getPrice(item)) }}
                        </div>

                        <div class="pvpc-widget-bar-wrap">
                            <div
                                class="pvpc-widget-bar"
                                :class="{
                                    min: isMinHour(item),
                                    max: isMaxHour(item)
                                }"
                                :style="{ height: getBarHeight(item) + '%' }"
                            ></div>
                        </div>

                        <div class="pvpc-widget-hour">
                            {{ formatShortHour(item.hour) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios'
import moment from 'moment'

export default {
    name: 'PvpcDashboardComponent',

    data() {
        return {
            selectedTariff: 'PVPC',
            selectedDate: moment().format('YYYY-MM-DD'),
            data: null,
            loading: false,
            error: null,
        }
    },

    mounted() {
        this.loadPrices()
    },

    methods: {
        async changeTariff(tariff) {
            if (this.selectedTariff === tariff) {
                return
            }

            this.selectedTariff = tariff
            await this.loadPrices()
        },

        async loadPrices() {
            this.loading = true
            this.error = null

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

                this.data = response.data
            } catch (error) {
                this.data = null
                this.error = 'No hay precios cargados para hoy'
            } finally {
                this.loading = false
            }
        },

        goToDetail() {
            this.$router.push('/pvpc')
        },

        getTariffLabel() {
            if (this.selectedTariff === 'PVPC') {
                return 'PVPC 2.0TD'
            }

            return 'Indexado ' + this.selectedTariff
        },

        getPrice(item) {
            return Number(item?.total_c_kwh ?? item?.price_c_kwh ?? 0)
        },

        getMaxPrice() {
            if (!this.data?.hours?.length) {
                return 1
            }

            const max = Math.max(...this.data.hours.map(item => this.getPrice(item)))

            return max > 0 ? max : 1
        },

        getBarHeight(item) {
            const value = this.getPrice(item)
            const max = this.getMaxPrice()

            return Math.max((value / max) * 100, 12)
        },

        normalizeHour(hour) {
            if (hour === null || hour === undefined) {
                return ''
            }

            const text = String(hour).trim()

            if (text.includes(':')) {
                return text.slice(0, 2).padStart(2, '0')
            }

            return text.padStart(2, '0')
        },

        isMinHour(item) {
            return this.normalizeHour(item?.hour) === this.normalizeHour(this.data?.min?.hour)
        },

        isMaxHour(item) {
            return this.normalizeHour(item?.hour) === this.normalizeHour(this.data?.max?.hour)
        },

        getAverageBetween(from, to) {
            if (!this.data?.hours?.length) {
                return 0
            }

            const items = this.data.hours.filter(item => {
                const hour = Number(this.normalizeHour(item.hour))
                return hour >= from && hour <= to
            })

            if (!items.length) {
                return 0
            }

            const total = items.reduce((sum, item) => sum + this.getPrice(item), 0)

            return total / items.length
        },

        formatPrice(value) {
            return Number(value || 0).toLocaleString('es-ES', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })
        },

        formatHour(hour) {
            if (hour === null || hour === undefined) {
                return '--:--'
            }

            const text = String(hour).trim()

            if (text.includes(':')) {
                return text.slice(0, 5)
            }

            return text.padStart(2, '0') + ':00'
        },

        formatShortHour(hour) {
            return this.normalizeHour(hour)
        },

        formatDate(date) {
            if (!date) {
                return ''
            }

            return moment(date).format('DD/MM/YYYY')
        },
    },
}
</script>

<style scoped>
.pvpc-dashboard-widget {
    width: 100%;
    background: #f5f6f8;
    border-radius: 14px;
    padding: 20px;
    border: 1px solid #e4e8ef;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.pvpc-widget-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 18px;
    margin-bottom: 14px;
}

.pvpc-widget-header h3 {
    margin: 0;
    color: #00316e;
    font-size: 19px;
    font-weight: 900;
}

.pvpc-widget-header p {
    margin: 4px 0 0;
    color: #6b7da1;
    font-size: 13px;
    font-weight: 600;
}

.pvpc-widget-header button {
    border: 0;
    background: #00316e;
    color: #ffffff;
    border-radius: 999px;
    padding: 9px 16px;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    white-space: nowrap;
    box-shadow: 0 10px 22px rgba(0, 49, 110, 0.16);
}

.pvpc-widget-top-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.pvpc-widget-tabs {
    display: inline-flex;
    gap: 6px;
    padding: 4px;
    background: #eaf2fd;
    border-radius: 999px;
}

.pvpc-widget-tabs button {
    border: 0;
    background: transparent;
    color: #00316e;
    border-radius: 999px;
    padding: 8px 14px;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
}

.pvpc-widget-tabs button.active {
    background: #00316e;
    color: #ffffff;
}

.pvpc-widget-date {
    color: #6b7da1;
    font-size: 12px;
    font-weight: 800;
    white-space: nowrap;
}

.pvpc-widget-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 14px;
}

.pvpc-summary-card {
    background: #ffffff;
    border-radius: 13px;
    padding: 13px 14px;
    border: 1px solid #e4eaf3;
}

.pvpc-summary-card span {
    display: block;
    color: #6b7da1;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 5px;
}

.pvpc-summary-card strong {
    display: block;
    color: #00316e;
    font-size: 22px;
    line-height: 1;
    font-weight: 900;
    margin-bottom: 5px;
}

.pvpc-summary-card small {
    color: #6b7da1;
    font-size: 12px;
    font-weight: 700;
}

.pvpc-summary-card.cheap strong {
    color: #0b9f55;
}

.pvpc-summary-card.expensive strong {
    color: #d92d2d;
}

.pvpc-widget-chart-card {
    background: #ffffff;
    border: 1px solid #e3ebf6;
    border-radius: 16px;
    padding: 16px 18px 12px;
    margin-bottom: 14px;
}

.pvpc-widget-chart-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
}

.pvpc-widget-chart-head strong {
    color: #00316e;
    font-size: 14px;
    font-weight: 900;
}

.pvpc-widget-chart-head span {
    color: #607aa6;
    font-size: 12px;
    font-weight: 700;
}

.pvpc-widget-chart {
    height: 250px;
    display: grid;
    grid-template-columns: repeat(24, minmax(0, 1fr));
    gap: 6px;
    align-items: end;
    border-bottom: 2px solid #00316e;
    padding: 8px 4px 10px;
    background: repeating-linear-gradient(
        to top,
        transparent 0,
        transparent 54px,
        rgba(0, 49, 110, 0.055) 55px
    );
    border-radius: 12px 12px 0 0;
}

.pvpc-widget-bar-column {
    height: 100%;
    display: grid;
    grid-template-rows: 28px 1fr 20px;
    align-items: end;
    min-width: 0;
    text-align: center;
}

.pvpc-widget-top-price {
    color: #00316e;
    font-size: 12px;
    font-weight: 900;
    line-height: 1;
    margin-bottom: 7px;
    white-space: nowrap;
    transition: all 0.18s ease;
}

.pvpc-widget-top-price.min {
    color: #1b9e5a;
}

.pvpc-widget-top-price.max {
    color: #e23b2f;
}

.pvpc-widget-bar-wrap {
    height: 100%;
    display: flex;
    align-items: end;
    justify-content: center;
}

.pvpc-widget-bar {
    width: 54%;
    max-width: 18px;
    min-width: 7px;
    min-height: 10px;
    background: linear-gradient(180deg, #1c4b8f 0%, #12376f 100%);
    border-radius: 999px 999px 4px 4px;
    transition: height 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
    box-shadow: 0 5px 12px rgba(0, 49, 110, 0.12);
}

.pvpc-widget-bar.min {
    background: linear-gradient(180deg, #7bd2ff 0%, #48aeea 100%);
    box-shadow: 0 5px 14px rgba(72, 174, 234, 0.28);
}

.pvpc-widget-bar.max {
    background: linear-gradient(180deg, #ff6961 0%, #e23b2f 100%);
    box-shadow: 0 5px 14px rgba(226, 59, 47, 0.28);
}

.pvpc-widget-bar-column:hover .pvpc-widget-bar {
    transform: translateY(-3px);
    box-shadow: 0 9px 18px rgba(0, 49, 110, 0.20);
}

.pvpc-widget-bar-column:hover .pvpc-widget-top-price {
    color: #00316e;
    transform: translateY(-1px);
}

.pvpc-widget-hour {
    margin-top: 7px;
    color: #6b7da1;
    font-size: 12px;
    font-weight: 800;
}

.pvpc-widget-periods {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    margin-top: 12px;
}

.pvpc-widget-periods > div {
    background: #eef3fa;
    border-radius: 12px;
    padding: 10px 12px;
}

.pvpc-widget-periods strong {
    display: block;
    color: #00316e;
    font-size: 16px;
    font-weight: 900;
    margin-bottom: 3px;
}

.pvpc-widget-periods span {
    color: #6b7da1;
    font-size: 11px;
    font-weight: 700;
}

.pvpc-widget-loading,
.pvpc-widget-error {
    padding: 18px;
    border-radius: 16px;
    font-size: 13px;
    font-weight: 800;
}

.pvpc-widget-loading {
    background: #eef5ff;
    color: #00316e;
}

.pvpc-widget-error {
    background: #fff1f1;
    color: #d92d2d;
}

@media (max-width: 900px) {
    .pvpc-dashboard-widget {
        padding: 14px;
        border-radius: 16px;
        margin-top: 16px;
    }

    .pvpc-widget-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
        margin-bottom: 12px;
    }

    .pvpc-widget-header h3 {
        font-size: 17px;
    }

    .pvpc-widget-header p {
        font-size: 12px;
        line-height: 1.3;
    }

    .pvpc-widget-header button {
        width: 100%;
        height: 40px;
        padding: 0 14px;
    }

    .pvpc-widget-top-row {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }

    .pvpc-widget-tabs {
        width: 100%;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        border-radius: 14px;
    }

    .pvpc-widget-tabs button {
        padding: 9px 6px;
        font-size: 12px;
    }

    .pvpc-widget-date {
        text-align: left;
        font-size: 11px;
        white-space: normal;
    }

    .pvpc-widget-summary {
        grid-template-columns: 1fr;
        gap: 7px;
        margin-bottom: 12px;
    }

    .pvpc-summary-card {
        padding: 9px 11px;
        border-radius: 12px;
        display: grid;
        grid-template-columns: 1fr auto;
        grid-template-areas:
            "label value"
            "unit unit";
        align-items: center;
        column-gap: 10px;
    }

    .pvpc-summary-card span {
        grid-area: label;
        font-size: 11px;
        line-height: 1.15;
        min-height: unset;
        margin-bottom: 0;
    }

    .pvpc-summary-card strong {
        grid-area: value;
        font-size: 18px;
        text-align: right;
        margin-bottom: 0;
    }

    .pvpc-summary-card small {
        grid-area: unit;
        font-size: 10px;
        text-align: right;
        margin-top: 2px;
    }

    .pvpc-widget-chart-card {
        padding: 12px 10px 10px;
        border-radius: 14px;
        overflow: hidden;
    }

    .pvpc-widget-chart-head {
        margin-bottom: 10px;
    }

    .pvpc-widget-chart-head strong {
        font-size: 13px;
    }

    .pvpc-widget-chart-head span {
        font-size: 11px;
    }

    .pvpc-widget-chart {
        height: 220px;
        grid-template-columns: repeat(24, 32px);
        gap: 7px;
        overflow-x: auto;
        overflow-y: hidden;
        padding: 10px 8px 10px;
        -webkit-overflow-scrolling: touch;
        touch-action: pan-x;
    }

    .pvpc-widget-chart::-webkit-scrollbar {
        height: 4px;
    }

    .pvpc-widget-chart::-webkit-scrollbar-thumb {
        background: rgba(0, 49, 110, 0.25);
        border-radius: 999px;
    }

    .pvpc-widget-bar-column {
        grid-template-rows: 26px 1fr 18px;
    }

    .pvpc-widget-top-price {
        font-size: 9px;
        margin-bottom: 5px;
        background: #ffffff;
        border: 1px solid #dce7f5;
        border-radius: 999px;
        padding: 3px 4px;
        box-shadow: 0 3px 8px rgba(0, 49, 110, 0.08);
    }

    .pvpc-widget-top-price.min {
        background: #eaf8f1;
        border-color: #bdebd2;
    }

    .pvpc-widget-top-price.max {
        background: #fff0ef;
        border-color: #ffc7c2;
    }

    .pvpc-widget-bar {
        width: 48%;
        max-width: 13px;
        min-width: 7px;
    }

    .pvpc-widget-hour {
        font-size: 9px;
        margin-top: 5px;
    }

    .pvpc-widget-periods {
        grid-template-columns: 1fr;
    }
}
</style>

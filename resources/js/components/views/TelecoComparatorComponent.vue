<template>
    <transition name="slide-up" mode="out-in">
        <div
            class="min-h-100-vh w-100-max d-flex justify-center align-center py-50"
            data-bg="blanco"
            :key="currentView"
        >
            <!-- FORMULARIO -->
            <div v-if="currentView === 'form'" class="dashboard-card w-700-px w-90-max">
                <transition name="step" mode="out-in">
                    <div :key="currentStep" class="d-flex column align-center w-100">

                        <!-- Servicio -->
                        <template v-if="currentStep === 'serviceType'">
                            <p class="text" data-size="20" data-weight="600">
                                ¿Qué servicio desea comparar?
                            </p>

                            <div class="d-flex w-100 justify-center mt-20 wrap-mobile" data-gap="20">
                                <div
                                    class="custom-button d-flex align-center"
                                    data-size="regular"
                                    data-style="twoBlue"
                                    @click="nextStep('Fibra')"
                                >
                                    <i class="far fa-wifi mr-10" data-size="16" data-weight="400"></i>
                                    <p data-size="16" data-weight="400">Fibra</p>
                                </div>

                                <div
                                    class="custom-button d-flex align-center"
                                    data-size="regular"
                                    data-style="twoBlue"
                                    @click="nextStep('Movil')"
                                >
                                    <i class="far fa-mobile-screen mr-10" data-size="16" data-weight="400"></i>
                                    <p data-size="16" data-weight="400">Móvil</p>
                                </div>

                                <div
                                    class="custom-button d-flex align-center"
                                    data-size="regular"
                                    data-style="twoBlue"
                                    @click="nextStep('Fibra+Movil')"
                                >
                                    <i class="far fa-signal mr-10"></i>
                                    <p data-size="16" data-weight="400">Fibra + Móvil</p>
                                </div>
                            </div>
                        </template>

                        <!-- Código postal -->
                        <template v-else-if="currentStep === 'postalCode'">
                            <p class="text text-center" data-size="20" data-weight="600">
                                ¿Dónde necesita el servicio?
                            </p>

                            <p class="text mt-10 opacity-6 text-center" data-size="14">
                                Introduzca su código postal para priorizar la cobertura más adecuada.
                            </p>

                            <div class="separator" />

                            <div class="d-flex column align-center w-100 mt-20" data-gap="15">
                                <div class="postal-card">
                                    <i class="far fa-location-dot" data-size="28" data-color="principal"></i>

                                    <input
                                        v-model="inputData.postalCode"
                                        type="text"
                                        maxlength="5"
                                        inputmode="numeric"
                                        class="postal-input"
                                        placeholder="Código postal"
                                        @input="onPostalInput"
                                    >

                                    <p v-if="detectedCity" class="text" data-size="14" data-weight="500">
                                        {{ detectedCity }}
                                    </p>
                                </div>

                                <div class="d-flex justify-center w-100" data-gap="20">
                                    <div class="custom-button" data-size="small" @click="goBack">
                                        Atrás
                                    </div>

                                    <div
                                        class="custom-button"
                                        data-size="small"
                                        data-style="twoBlue"

                                        @click="continueFromPostal"
                                    >
                                        Continuar
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Líneas -->
                        <template v-else-if="currentStep === 'mobileLines'">
                            <p class="text" data-size="20" data-weight="600">
                                ¿Cuántas líneas móviles necesita?
                            </p>

                            <div class="d-flex w-100 justify-center mt-20 wrap-mobile" data-gap="20">
                                <div
                                    class="custom-button"
                                    data-size="regular"
                                    data-style="twoBlue"
                                    @click="nextStep('1 línea principal')"
                                >
                                    1 línea
                                </div>

                                <div
                                    class="custom-button"
                                    data-size="regular"
                                    data-style="twoBlue"
                                    @click="nextStep('2 líneas')"
                                >
                                    2 líneas
                                </div>

                                <div
                                    class="custom-button"
                                    data-size="regular"
                                    data-style="twoBlue"
                                    @click="nextStep('3 o más')"
                                >
                                    3 o más
                                </div>
                            </div>

                            <div class="d-flex justify-center w-100 mt-20" data-gap="20">
                                <div class="custom-button" data-size="small" @click="goBack">
                                    Atrás
                                </div>
                            </div>
                        </template>

                        <!-- Uso -->
                        <template v-else-if="currentStep === 'internetUsage'">
                            <p class="text" data-size="20" data-weight="600">
                                ¿Para qué usa internet principalmente?
                            </p>

                            <div class="d-flex w-100 justify-center mt-20 wrap-mobile" data-gap="20">
                                <div
                                    class="custom-button tooltip-trigger"
                                    data-size="regular"
                                    data-style="twoBlue"
                                    @click="nextStep('Uso básico (WhatsApp, Redes)')"
                                >
                                    Uso básico
                                    <span class="tooltip-box">
                                        Ideal para mensajería, redes sociales y navegación ligera.
                                    </span>
                                </div>

                                <div
                                    class="custom-button tooltip-trigger"
                                    data-size="regular"
                                    data-style="twoBlue"
                                    @click="nextStep('Equilibrado (Teletrabajo, Netflix)')"
                                >
                                    Equilibrado
                                    <span class="tooltip-box">
                                        Perfecto para teletrabajo, streaming en HD y uso diario.
                                    </span>
                                </div>

                                <div
                                    class="custom-button tooltip-trigger"
                                    data-size="regular"
                                    data-style="twoBlue"
                                    @click="nextStep('Intensivo (Gaming, Descargas)')"
                                >
                                    Intensivo
                                    <span class="tooltip-box">
                                        Recomendado para gaming, 4K, descargas grandes y uso exigente.
                                    </span>
                                </div>
                            </div>

                            <div class="d-flex justify-center w-100 mt-20" data-gap="20">
                                <div class="custom-button" data-size="small" @click="goBack">
                                    Atrás
                                </div>
                            </div>
                        </template>

                        <!-- Permanencia -->
                        <template v-else-if="currentStep === 'permanence'">
                            <p class="text" data-size="20" data-weight="600">
                                ¿Le importa tener permanencia?
                            </p>

                            <div class="d-flex w-100 justify-center mt-20 wrap-mobile" data-gap="20">
                                <div
                                    class="custom-button"
                                    data-size="regular"
                                    data-style="twoBlue"
                                    @click="nextStep('No me importa si hay permanencia')"
                                >
                                    Con permanencia
                                </div>

                                <div
                                    class="custom-button"
                                    data-size="regular"
                                    data-style="twoBlue"
                                    @click="nextStep('Quiero libertad total (Sin permanencia)')"
                                >
                                    Sin permanencia
                                </div>
                            </div>

                            <div class="d-flex justify-center w-100 mt-20" data-gap="20">
                                <div class="custom-button" data-size="small" @click="goBack">
                                    Atrás
                                </div>
                            </div>
                        </template>

                        <!-- Gasto actual -->
                        <template v-else-if="currentStep === 'currentSpend'">
                            <p class="text" data-size="20" data-weight="600">
                                ¿Cuánto paga de media actualmente?
                            </p>

                            <p class="text mt-10 opacity-6 text-center" data-size="14">
                                Solo le mostraremos tarifas que encajen con su perfil y puedan mejorar su situación actual.
                            </p>

                            <div class="separator" />

                            <div class="d-flex column align-center w-100 mt-20">
                                <div class="price-display">
                                    <p class="text" data-size="34" data-weight="600">
                                        {{ formatEUR(inputData.currentSpend) }}
                                    </p>
                                    <p class="text opacity-6" data-size="14">
                                        al mes
                                    </p>
                                </div>

                                <input
                                    v-model.number="inputData.currentSpend"
                                    type="range"
                                    min="10"
                                    max="150"
                                    step="1"
                                    class="slider-custom mt-20"
                                >

                                <div class="d-flex justify-between w-100 mt-10 slider-labels">
                                    <p class="text opacity-6" data-size="12">10€</p>
                                    <p class="text opacity-6" data-size="12">150€</p>
                                </div>
                            </div>

                            <div class="d-flex justify-center w-100 mt-25" data-gap="20">
                                <div class="custom-button" data-size="small" @click="goBack">
                                    Atrás
                                </div>

                                <div
                                    class="custom-button"
                                    data-size="small"
                                    data-style="twoBlue"
                                    @click="startLoading"
                                >
                                    Buscar ofertas
                                </div>
                            </div>
                        </template>

                        <!-- Loading -->
                        <template v-else-if="currentStep === 'loading'">
                            <p class="text text-center" data-size="20" data-weight="600">
                                Analizando opciones...
                            </p>

                            <p class="text text-center mt-10 opacity-6" data-size="14">
                                {{ loadingMessages[currentMessage] }}
                            </p>

                            <div class="loading-wrapper">
                                <div class="loader"></div>
                            </div>

                            <div class="d-flex justify-center w-100 mt-20">
                                <div class="custom-button" data-size="small" @click="resetComparator">
                                    Cancelar
                                </div>
                            </div>
                        </template>

                    </div>
                </transition>
            </div>

            <!-- RESULTADOS -->
            <div v-else-if="currentView === 'results'" class="d-flex column align-center w-100" data-gap="40">
                <div class="w-700-px w-90-max">
                    <button class="custom-button" data-size="small" @click="resetComparator">
                        <i class="fa-solid fa-arrow-left"></i> Volver
                    </button>

                    <div class="d-flex justify-between mt-20 column-mobile" data-gap="10">
                        <p class="text ellipsis noWidth" data-size="17" data-weight="600">
                            Comparativa telefonía
                        </p>
                        <p class="text ellipsis noWidth opacity-7" data-size="14">
                            {{ readableServiceType }}
                        </p>
                    </div>

                    <div class="separator h-2-px mx-auto"></div>

                    <div class="d-flex align-center wrap-mobile" data-gap="15">
                        <div class="summary-chip">
                            <i class="far fa-signal-stream mr-5"></i>{{ readableServiceType }}
                        </div>

                        <div v-if="inputData.serviceType !== 'Fibra'" class="summary-chip">
                            <i class="far fa-mobile-screen mr-5"></i>{{ inputData.mobileLines }}
                        </div>

                        <div class="summary-chip">
                            <i class="far fa-gauge mr-5"></i>{{ inputData.internetUsage }}
                        </div>

                        <div class="summary-chip">
                            <i class="far fa-unlock-keyhole mr-5"></i>{{ inputData.permanence }}
                        </div>

                        <div class="summary-chip">
                            <i class="far fa-location-dot mr-5"></i>{{ detectedCity || 'España' }}
                        </div>

                        <div class="summary-chip summary-chip--highlight">
                            <i class="far fa-euro-sign mr-5"></i>{{ formatEUR(inputData.currentSpend) }}/mes
                        </div>
                    </div>

                </div>



                <div class="w-700-px w-90-max d-flex column" data-gap="20">
                    <div
                        v-if="topResults.length === 0"
                        class="dashboard-card column justify-center align-center w-100 p-20 text-center"
                    >
                        <p class="text" data-size="18" data-weight="600">
                            No hemos encontrado ofertas que mejoren su situación actual
                        </p>

                        <p class="text mt-10" data-size="14">
                            Pruebe a ampliar el presupuesto o permitir permanencia para ver más opciones.
                        </p>

                        <div class="custom-button mt-15" data-size="small" @click="resetComparator">
                            Hacer otra comparativa
                        </div>
                    </div>

                    <div v-else class="d-flex column" data-gap="20">
                        <div
                            v-for="(offer, index) in topResults"
                            :key="offer.id"
                            class="dashboard-card column justify-between w-100 result-card"
                            :class="{ 'result-card--best': index === 0 }"
                        >
                            <div v-if="index === 0" class="best-badge">
                                Mejor opción recomendada
                            </div>

                            <div class="d-flex justify-between align-center column-mobile" data-gap="15">
                                <div class="d-flex column">
                                    <p class="text opacity-6" data-size="12" data-weight="600">
                                        {{ offer.operador }}
                                    </p>

                                    <p class="text mt-5" data-size="19" data-weight="600">
                                        {{ offer.producto }}
                                    </p>
                                </div>

                                <div class="d-flex align-end" data-gap="5">
                                    <p class="text" style="line-height:1" data-size="30" data-weight="600">
                                        {{ formatEUR(offer.price) }}
                                    </p>
                                    <p class="text opacity-6">/ mes</p>
                                </div>
                            </div>

                            <div class="separator" />

                            <div class="d-flex justify-between align-center offer-mobile" data-gap="20">
                                <div class="d-flex column" data-gap="8">
                                    <p class="text" data-size="14">
                                        <span class="opacity-6">Cobertura:</span>
                                        {{ offer.cobertura || '-' }}
                                    </p>

                                    <p class="text" data-size="14">
                                        <span class="opacity-6">Fibra:</span>
                                        {{ offer.fibraMb ? `${offer.fibraMb} Mb` : '-' }}
                                    </p>

                                    <p class="text" data-size="14">
                                        <span class="opacity-6">Datos móviles:</span>
                                        {{ offer.datosGB ? `${offer.datosGB} GB` : '-' }}
                                    </p>

                                    <p class="text" data-size="14">
                                        <span class="opacity-6">Líneas incluidas:</span>
                                        {{ offer.lineasIncluidas || '-' }}
                                    </p>

                                    <p class="text" data-size="14">
                                        <span class="opacity-6">Permanencia:</span>
                                        {{ offer.permanenciaMeses }} meses
                                    </p>
                                </div>

                                <div class="d-flex column justify-center align-center">
                                    <div class="save-box">
                                        <p class="text" data-size="14">Ahorra</p>
                                        <p class="text" data-size="26" data-weight="600" data-color="success">
                                            {{ formatEUR(calcAhorroMes(offer)) }}
                                        </p>
                                        <p class="text opacity-6" data-size="13">al mes</p>
                                    </div>
                                </div>

                                <div class="d-flex column justify-center align-center" data-gap="10">
                                    <div
                                        class="custom-button"
                                        data-size="regular"
                                        data-style="twoBlue"
                                        @click="offerSelected = offer"
                                    >
                                        Contratar
                                    </div>
                                </div>
                            </div>

                            <transition name="expand">
                                <div v-if="expanded[offer.id]" class="expand-wrapper mt-15">
                                    <div class="details-box">
                                        <p class="text" data-size="14">
                                            <span class="opacity-6">Ahorro anual estimado:</span>
                                            {{ formatEUR(calcAhorroAnual(offer)) }}
                                        </p>

                                        <p class="text mt-5" data-size="14">
                                            <span class="opacity-6">Tipo de tarifa:</span>
                                            {{ readableTariffType(offer.tipo) }}
                                        </p>

                                        <p class="text mt-5" data-size="14">
                                            <span class="opacity-6">Enlace:</span>
                                            <a :href="offer.url" target="_blank" rel="noopener noreferrer">
                                                Ver oferta
                                            </a>
                                        </p>
                                    </div>
                                </div>
                            </transition>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RESULTADOS -->
            <div v-else-if="currentView === 'results'" class="d-flex column align-center w-100" data-gap="40">
                ...
            </div>

            <!-- 🔥 CONFIRMACIÓN (MISMO ESTILO QUE LUZ/GAS) -->
            <div v-else-if="currentView === 'confirmation'" class="d-flex column align-center w-700-px w-90-max" data-gap="10">
                <i class="fas fa-check-circle" data-size="50" data-color="success"></i>

                <p class="text" data-size="30" data-weight="600">
                    Solicitud enviada correctamente
                </p>

                <p class="text mt-10 text-center" data-size="18">
                    Hemos recibido su solicitud. En breve uno de nuestros gestores se pondrá en contacto con usted para finalizar la contratación.
                </p>

                <div
                    class="custom-button mt-20"
                    data-size="big"
                    @click="resetComparator"
                >
                    Hacer otra comparativa
                </div>
            </div>
        </div>
    </transition>

    <!-- MODAL CONTRATAR -->
<div v-if="offerSelected" class="modal" @click="offerSelected = null">
    <div
        class="w-500-px w-90-max d-flex column align-center round p-20 justify-center"
        data-gap="10"
        data-round="20"
        data-bg="blanco"
        data-border-color="principal"
        @click.stop
    >
        <p class="text" data-size="20" data-weight="600">
            Solicitar contratación
        </p>

        <p class="text w-90-max" data-size="12">
            Déjenos sus datos y un gestor se pondrá en contacto con usted.
        </p>

        <form class="d-flex column form w-100 w-90-max">
            <div class="form-group no-margin">
                <label>Nombre</label>
                <div class="input-group">
                    <input v-model="clientData.name" type="text" required>
                </div>
            </div>

            <div class="form-group no-margin">
                <label>Email</label>
                <div class="input-group">
                    <input v-model="clientData.email" type="email" required>
                </div>
            </div>

            <div class="form-group no-margin">
                <label>Teléfono</label>
                <div class="input-group">
                    <input v-model="clientData.phone" type="tel" required>
                </div>
            </div>

            <button type="submit" class="custom-button mt-20" data-size="medium" :disabled="isSendingOrder" :class="{ 'disabled-button': isSendingOrder }"
                @click.prevent="createOrder">

                <span v-if="!isSendingOrder">Enviar datos</span>

                <span v-else class="loading-dots text opacity-5">
                    Cargando<span>.</span><span>.</span><span>.</span>
                </span>

            </button>
        </form>
    </div>
</div>
<FloatingContactButtons />
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from "vue";
import axios from "axios";
import FloatingContactButtons from "../items/FloatingContactButtons.vue";


const currentView = ref("form");
const currentStep = ref("serviceType");

const offerSelected = ref(null)

const isSendingOrder = ref(false)

const clientData = reactive({
    name: '',
    email: '',
    phone: ''
})

const errors = reactive({})

const inputData = reactive({
    serviceType: "",
    postalCode: "",
    preferredCoverage: null,
    mobileLines: "",
    internetUsage: "",
    permanence: "",
    currentSpend: 60,
});

const expanded = reactive({});
const detectedCity = ref("");
const currentMessage = ref(0);
let loadingTimer = null;
let loadingMessageTimer = null;

const loadingMessages = [
    "Buscando tarifas que encajen con su consumo...",
    "Priorizando opciones con mejor relación calidad-precio...",
    "Calculando ahorro mensual y anual...",
    "Preparando las mejores recomendaciones para usted..."
];

const coverageMap = {
    1: "Movistar", 2: "Movistar", 3: "Movistar", 4: "Movistar", 5: "Movistar",
    6: "Movistar", 7: "Movistar", 8: "Orange", 9: "Movistar", 10: "Movistar",
    11: "Orange", 12: "Orange", 13: "Movistar", 14: "Movistar", 15: "Movistar",
    16: "Movistar", 17: "Orange", 18: "Orange", 19: "Movistar", 20: "Vodafone",
    21: "Movistar", 22: "Movistar", 23: "Movistar", 24: "Movistar", 25: "Movistar",
    26: "Movistar", 27: "Movistar", 28: "Movistar", 29: "Orange", 30: "Orange",
    31: "Movistar", 32: "Movistar", 33: "Movistar", 34: "Movistar", 35: "Movistar",
    36: "Movistar", 37: "Movistar", 38: "Movistar", 39: "Movistar", 40: "Movistar",
    41: "Movistar", 42: "Movistar", 43: "Orange", 44: "Movistar", 45: "Movistar",
    46: "Vodafone", 47: "Movistar", 48: "Vodafone", 49: "Movistar", 50: "Movistar",
    51: "Movistar", 52: "Movistar"
};

const cpCityMap = {
    "28": "Madrid",
    "08": "Barcelona",
    "41": "Sevilla",
    "46": "Valencia",
    "29": "Málaga",
    "48": "Bilbao",
    "50": "Zaragoza",
    "14": "Córdoba",
    "30": "Murcia",
    "18": "Granada",
    "15": "A Coruña",
    "36": "Pontevedra",
    "38": "Tenerife",
    "35": "Las Palmas"
};

// ─── Carga desde API ────────────────────────────────────────────────────────

const TARIFFS = ref([]);

/**
 * Normaliza el nombre del tipo de tarifa procedente de fee.name en MongoDB
 * a los valores internos que usa el comparador: "Fibra", "Movil", "Fibra+Movil".
 */
function normalizeTipo(feeName) {
    const n = (feeName || "").toLowerCase().replace(/\s+/g, "");
    if (n.includes("fibra") && (n.includes("movil") || n.includes("móvil"))) return "Fibra+Movil";
    if (n.includes("fibra")) return "Fibra";
    if (n.includes("movil") || n.includes("móvil")) return "Movil";
    return feeName || "";
}

/**
 * Convierte el array de marketers recibido de /api/marketers
 * en una lista plana de tarifas para el comparador.
 */
function transformMarketers(marketers) {
    const result = [];

    for (const marketer of marketers) {
        const telephonyProducts = marketer?.products?.telephony ?? [];

        for (const product of telephonyProducts) {
            const fees = product?.fees ?? [];

            for (const fee of fees) {
                const comp = fee?.comparador ?? {};

                result.push({
                    id: fee._id?.$oid || fee._id || fee.id?.$oid || fee.id || "",
                    tipo: normalizeTipo(fee.name),
                    operador: marketer.name || "",
                    producto: product.name || "",
                    price: Number(comp.price || 0),
                    fibraMb: Number(comp.fibraMb || 0),
                    datosGB: Number(comp.datosGB || 0),
                    lineasIncluidas: comp.lineasIncluidas ?? null,
                    permanenciaMeses: Number(comp.permanenciaMeses || 0),
                    cobertura: comp.cobertura || marketer.coverage || "",
                    url: comp.url || "",
                });
            }
        }
    }

    return result;
}

onMounted(async () => {
    try {

        const params = {};

        if(false && this.referralUser){
                params.assignContractTo = this.referralUser
            }
        const { data } = await axios.get("/api/marketers", { params });
        // La API devuelve { marketers: [...] }, no un array directo
        const marketers = Array.isArray(data) ? data : (data.marketers ?? []);
        TARIFFS.value = transformMarketers(marketers);
    } catch (error) {
        console.error("Error al cargar los marketers desde la API:", error);
        TARIFFS.value = [];
    }
});

// ─── Lógica del comparador ──────────────────────────────────────────────────

const readableServiceType = computed(() => {
    if (inputData.serviceType === "Fibra+Movil") return "Fibra + Móvil";
    if (inputData.serviceType === "Movil") return "Solo Móvil";
    if (inputData.serviceType === "Fibra") return "Solo Fibra";
    return "-";
});

function nextStep(value) {
    if (currentStep.value === "serviceType") {
        inputData.serviceType = value;
        currentStep.value = "postalCode";
        return;
    }

    if (currentStep.value === "mobileLines") {
        inputData.mobileLines = value;
        currentStep.value = "internetUsage";
        return;
    }

    if (currentStep.value === "internetUsage") {
        inputData.internetUsage = value;
        currentStep.value = "permanence";
        return;
    }

    if (currentStep.value === "permanence") {
        inputData.permanence = value;
        currentStep.value = "currentSpend";
    }
}

function goBack() {
    if (currentStep.value === "postalCode") {
        currentStep.value = "serviceType";
        return;
    }

    if (currentStep.value === "mobileLines") {
        currentStep.value = "postalCode";
        return;
    }

    if (currentStep.value === "internetUsage") {
        currentStep.value = inputData.serviceType === "Fibra" ? "postalCode" : "mobileLines";
        return;
    }

    if (currentStep.value === "permanence") {
        currentStep.value = "internetUsage";
        return;
    }

    if (currentStep.value === "currentSpend") {
        currentStep.value = "permanence";
    }
}

function onPostalInput() {
    inputData.postalCode = inputData.postalCode.replace(/\D/g, "").slice(0, 5);

    if (inputData.postalCode.length >= 2) {
        const province = inputData.postalCode.substring(0, 2);
        detectedCity.value = cpCityMap[province] || "España";
    } else {
        detectedCity.value = "";
    }
}

function continueFromPostal() {
    if (inputData.postalCode.length !== 5) return;

    const province = parseInt(inputData.postalCode.substring(0, 2), 10);
    inputData.preferredCoverage = coverageMap[province] || null;

    currentStep.value = inputData.serviceType === "Fibra" ? "internetUsage" : "mobileLines";
}

function startLoading() {
    currentStep.value = "loading";
    currentMessage.value = 0;

    clearTimers();

    loadingMessageTimer = setInterval(() => {
        currentMessage.value = (currentMessage.value + 1) % loadingMessages.length;
    }, 900);

    loadingTimer = setTimeout(() => {
        clearTimers();
        currentView.value = "results";
    }, 3200);
}

function clearTimers() {
    if (loadingTimer) {
        clearTimeout(loadingTimer);
        loadingTimer = null;
    }

    if (loadingMessageTimer) {
        clearInterval(loadingMessageTimer);
        loadingMessageTimer = null;
    }
}

const filteredTariffs = computed(() => {
    // Comparamos en minúsculas para cubrir cualquier variante residual
    const tipo = (inputData.serviceType || "").toLowerCase();
    const uso = inputData.internetUsage || "";
    const gasto = Number(inputData.currentSpend || 0);

    let arr = [...TARIFFS.value].filter((t) => (t.tipo || "").toLowerCase() === tipo);

    arr = arr.filter((t) => t.price > 0 && t.price <= gasto * 1.25);

    if ((inputData.permanence || "").includes("Sin permanencia")) {
        arr = arr.filter((t) => Number(t.permanenciaMeses || 0) === 0);
    }

    if (tipo !== "fibra" && inputData.mobileLines) {
        const required =
            inputData.mobileLines.includes("2") ? 2 :
            inputData.mobileLines.includes("3") ? 3 : 1;

        arr = arr.filter((t) => {
            return t.lineasIncluidas == null || t.lineasIncluidas >= required;
        });
    }

    if (tipo === "fibra" || tipo === "fibra+movil") {
        arr = arr.filter((t) => {
            const mb = Number(t.fibraMb || 0);
            if (mb === 0) return true;
            if (uso.includes("básico") || uso.includes("Básico")) return mb >= 100;
            if (uso.includes("Equilibrado")) return mb >= 300;
            if (uso.includes("Intensivo")) return mb >= 600;
            return true;
        });
    }

    if (tipo === "movil" || tipo === "fibra+movil") {
        arr = arr.filter((t) => {
            const gb = Number(t.datosGB || 0);
            if (gb === 0) return true;
            if (uso.includes("básico") || uso.includes("Básico")) return gb >= 5;
            if (uso.includes("Equilibrado")) return gb >= 20;
            if (uso.includes("Intensivo")) return gb >= 80;
            return true;
        });
    }

    // Fallback: si no hay resultados, mostrar todas las del tipo sin filtro de precio
    if (arr.length === 0) {
        arr = TARIFFS.value.filter((t) => (t.tipo || "").toLowerCase() === tipo);

        if ((inputData.permanence || "").includes("Sin permanencia")) {
            arr = arr.filter((t) => Number(t.permanenciaMeses || 0) === 0);
        }
    }

    return arr;
});

function scoreTariff(t) {
    let score = 0;

    const tipo = inputData.serviceType || "";
    const uso = inputData.internetUsage || "";
    const price = Number(t.price || 0);
    const gasto = Number(inputData.currentSpend || 0);
    const gb = Number(t.datosGB || 0);
    const speed = Number(t.fibraMb || 0);
    const permanencia = Number(t.permanenciaMeses || 0);

    const ahorro = gasto - price;

    if (gasto > 0) {
        const ahorroRatio = ahorro / gasto;
        score += ahorroRatio > 0 ? ahorroRatio * 45 : ahorroRatio * 25;
    }

    if (price <= gasto * 0.5) score += 8;
    else if (price <= gasto * 0.75) score += 5;

    if (inputData.preferredCoverage) {
        if (t.cobertura === inputData.preferredCoverage) score += 18;
        else score -= 8;
    }

    if (tipo === "Movil" || tipo === "Fibra+Movil") {
        if (uso.includes("Básico")) {
            if (gb === 0) score -= 5;
            else if (gb < 10) score -= 25;
            else if (gb <= 40) score += 24;
            else if (gb <= 100) score += 16;
            else score += 8;
        }

        if (uso.includes("Equilibrado")) {
            if (gb === 0) score -= 5;
            else if (gb < 30) score -= 18;
            else if (gb <= 100) score += 28;
            else if (gb <= 200) score += 22;
            else score += 14;
        }

        if (uso.includes("Intensivo")) {
            if (gb === 0) score -= 5;
            else if (gb < 80) score -= 30;
            else if (gb <= 200) score += 32;
            else score += 28;
        }
    }

    if (tipo === "Fibra" || tipo === "Fibra+Movil") {
        if (uso.includes("Básico")) {
            if (speed === 0) score -= 5;
            else if (speed < 100) score -= 15;
            else if (speed <= 300) score += 20;
            else if (speed <= 600) score += 17;
            else score += 12;
        }

        if (uso.includes("Equilibrado")) {
            if (speed === 0) score -= 5;
            else if (speed < 300) score -= 18;
            else if (speed <= 600) score += 24;
            else if (speed <= 1000) score += 20;
            else score += 14;
        }

        if (uso.includes("Intensivo")) {
            if (speed === 0) score -= 5;
            else if (speed < 600) score -= 25;
            else if (speed <= 1000) score += 28;
            else score += 24;
        }
    }

    if ((inputData.permanence || "").includes("Sin permanencia")) {
        if (permanencia === 0) score += 15;
        else score -= 20;
    } else if (permanencia === 0) {
        score += 5;
    }

    if (tipo !== "Fibra" && inputData.mobileLines) {
        const required =
            inputData.mobileLines.includes("2") ? 2 :
            inputData.mobileLines.includes("3") ? 3 : 1;

        if (t.lineasIncluidas != null) {
            if (t.lineasIncluidas === required) score += 10;
            else if (t.lineasIncluidas > required) score += 4;
        }
    }

    if (tipo === "Movil" && gb >= 20 && gb <= 150) score += 6;
    if (tipo === "Fibra" && speed >= 300 && speed <= 1000) score += 6;

    if (tipo === "Fibra+Movil") {
        if (speed >= 300 && speed <= 1000) score += 4;
        if (gb >= 20 && gb <= 150) score += 4;
    }

    return score;
}

async function createOrder() {
    if (isSendingOrder.value) return

    if (!clientData.name) {
        errors.name = 'El nombre es obligatorio'
        return
    }

    if (!offerSelected.value) {
        alert('No hay oferta seleccionada')
        return
    }

    isSendingOrder.value = true

    console.log(isSendingOrder.value)

    clearTimers()

    const formData = new FormData()

    formData.append('client', JSON.stringify(clientData))

    const offerPayload = {
        marketer: offerSelected.value.operador,
        product: offerSelected.value.producto,
        fee: offerSelected.value.price
    }

    formData.append('offerSelected', JSON.stringify(offerPayload))
    formData.append('type', 'telephony')
    formData.append('serviceType', inputData.serviceType)

    try {
        await axios.post('/api/openComparator/registerOrder', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })

        offerSelected.value = null
        currentView.value = 'confirmation'

    } catch (error) {
        console.error(error)
        alert('Error al enviar la solicitud')
    } finally {
        isSendingOrder.value = false
    }
}

const topResults = computed(() => {
    const sorted = [...filteredTariffs.value]
        .sort((a, b) => scoreTariff(b) - scoreTariff(a));

    const result = [];
    const companyCount = {};

    for (const item of sorted) {
        const operador = item.operador || "unknown";

        if (!companyCount[operador]) {
            companyCount[operador] = 0;
        }

        // máximo 2 por compañía
        if (companyCount[operador] >= 2) continue;

        result.push(item);
        companyCount[operador]++;

        // máximo total 6 resultados
        if (result.length >= 6) break;
    }

    return result;
});

function formatEUR(value) {
    return `${Number(value || 0).toFixed(2)}€`;
}

function calcAhorroMes(tariff) {
    const ahorro = Number(inputData.currentSpend || 0) - Number(tariff.price || 0);
    return Math.max(0, Number(ahorro.toFixed(2)));
}

function calcAhorroAnual(tariff) {
    return Number((calcAhorroMes(tariff) * 12).toFixed(2));
}

function toggleDetails(id) {
    expanded[id] = !expanded[id];
}

function readableTariffType(tipo) {
    if (tipo === "Fibra+Movil") return "Fibra + Móvil";
    if (tipo === "Movil") return "Solo móvil";
    if (tipo === "Fibra") return "Solo fibra";
    return tipo || "-";
}

function interesarme(tarifa) {
    const urls = {
        O2: "https://o2online.es/fibra-movil-tv/tarifas-dispositivos/",
        PEPEPHONE: "https://www.pepephone.com/",
        DIGI: "https://www.digimobil.es/",
        MASMOVIL: "https://www.masmovil.es/",
        SIMYO: "https://www.simyo.es/",
        LOWI: "https://www.lowi.es/",
        JAZZTEL: "https://www.jazztel.com/ofertas",
        FINETWORK: "https://www.finetwork.com/"
    };

    const url = tarifa.url || urls[tarifa.operador] || "https://www.google.com";
    window.open(url, "_blank");
}

function resetComparator() {
    clearTimers();

    currentView.value = "form";
    currentStep.value = "serviceType";

    inputData.serviceType = "";
    inputData.postalCode = "";
    inputData.preferredCoverage = null;
    inputData.mobileLines = "";
    inputData.internetUsage = "";
    inputData.permanence = "";
    inputData.currentSpend = 60;

    detectedCity.value = "";
    currentMessage.value = 0;

    Object.keys(expanded).forEach((key) => delete expanded[key]);
}

onBeforeUnmount(() => {
    clearTimers();
});
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.4s ease;
}

.slide-up-enter-from {
    transform: translateY(40px);
    opacity: 0;
}

.slide-up-enter-to {
    transform: translateY(0);
    opacity: 1;
}

.slide-up-leave-from {
    transform: translateY(0);
    opacity: 1;
}

.slide-up-leave-to {
    transform: translateY(-40px);
    opacity: 0;
}

.step-enter-active,
.step-leave-active {
    transition: all 0.35s ease;
}

.step-enter-from {
    opacity: 0;
    transform: translateX(30px);
}

.step-leave-to {
    opacity: 0;
    transform: translateX(-30px);
}

.step-enter-to,
.step-leave-from {
    opacity: 1;
    transform: translateX(0);
}

.expand-enter-active,
.expand-leave-active {
    transition: max-height 0.4s ease, opacity 0.25s ease, transform 0.25s ease;
}

.expand-enter-from,
.expand-leave-to {
    max-height: 0;
    opacity: 0;
    transform: translateY(-5px);
}

.expand-enter-to,
.expand-leave-from {
    max-height: 600px;
    opacity: 1;
    transform: translateY(0);
}

.expand-wrapper {
    overflow: hidden;
}

.wrap-mobile {
    flex-wrap: wrap;
}

.noWidth {
    max-width: 100%;
}

.postal-card {
    width: 100%;
    max-width: 360px;
    border: 1px solid rgba(29, 78, 216, 0.15);
    border-radius: 20px;
    padding: 24px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14px;
    background: rgba(255,255,255,0.75);
}

.postal-input {
    width: 100%;
    border: 1px solid #dbeafe;
    border-radius: 14px;
    padding: 14px 16px;
    text-align: center;
    font-size: 18px;
    font-weight: 600;
    outline: none;
    transition: all 0.2s ease;
}

.postal-input:focus {
    border-color: #1d4ed8;
    box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.08);
}

.slider-custom {
    width: 100%;
    max-width: 420px;
    appearance: none;
    height: 8px;
    border-radius: 999px;
    background: #dbeafe;
    outline: none;
}

.slider-custom::-webkit-slider-thumb {
    appearance: none;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #1d4ed8;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(29, 78, 216, 0.25);
}

.slider-custom::-moz-range-thumb {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #1d4ed8;
    border: none;
    cursor: pointer;
}

.slider-labels {
    max-width: 420px;
}

.price-display {
    text-align: center;
}

.summary-chip {
    padding: 8px 14px;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 13px;
    font-weight: 500;
    border: 1px solid rgba(29, 78, 216, 0.15);
}

.summary-chip--highlight {
    background: #1d4ed8;
    color: white;
}

.result-card {
    position: relative;
}

.result-card--best {
    border: 1px solid rgba(34, 197, 94, 0.35);
    box-shadow: 0 10px 30px rgba(34, 197, 94, 0.08);
}

.best-badge {
    position: absolute;
    top: -12px;
    right: 20px;
    background: #22c55e;
    color: white;
    border-radius: 999px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 600;
}

.save-box {
    min-width: 120px;
    text-align: center;
}

.details-box {
    border: 1px solid #dbeafe;
    border-radius: 16px;
    padding: 16px;
    background: #f8fbff;
}

.tooltip-trigger {
    position: relative;
}

.tooltip-box {
    position: absolute;
    bottom: 115%;
    left: 50%;
    transform: translateX(-50%);
    width: 220px;
    background: white;
    color: #334155;
    border-radius: 14px;
    padding: 12px 14px;
    font-size: 13px;
    font-weight: 400;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
    opacity: 0;
    pointer-events: none;
    transition: all 0.25s ease;
    z-index: 20;
}

.tooltip-trigger:hover .tooltip-box {
    opacity: 1;
    transform: translateX(-50%) translateY(-4px);
}

.loading-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 25px 0 10px 0;
}

.loader {
    width: 54px;
    height: 54px;
    border: 5px solid #dbeafe;
    border-top-color: #1d4ed8;
    border-radius: 50%;
    animation: spin 0.85s linear infinite;
}

.disabled-button {
    pointer-events: none;
    opacity: 0.45;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 768px) {
    .wrap-mobile {
        flex-wrap: wrap;
    }

    .column-mobile {
        flex-direction: column !important;
    }

    .offer-mobile {
        flex-direction: column;
        align-items: flex-start !important;
    }

    .dashboard-card {
        padding: 20px;
    }

    .best-badge {
        position: static;
        margin-bottom: 10px;
        align-self: flex-start;
    }
}
</style>

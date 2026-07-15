<template>
    <div class="tc">
        <div class="separator"></div>

        <!-- ══════════════════════ FORMULARIO ══════════════════════ -->
        <div v-if="currentView === 'form'" class="d-flex justify-center mt-20 pb-20">
            <form
                v-if="currentStep !== 'loading'"
                class="dashboard-card column justify-between form tc-card w-700-px w-90-max"
                @submit.prevent="startLoading"
            >
                <!-- Hero -->
                <div class="tc-hero text-center">
                    <div class="tc-hero__icon mx-auto">
                        <i class="far fa-phone-volume"></i>
                    </div>
                    <h2 class="text mt-15 mb-8">Comparador de telefonía</h2>
                    <p class="text opacity-6 mx-auto tc-hero__desc" data-size="14">
                        Indique su perfil y le mostraremos las mejores ofertas de fibra, móvil y paquetes combinados disponibles en su zona.
                    </p>
                </div>

                <div class="separator h-2-px w-100 mx-auto my-20"></div>

                <div class="tc-form-grid">

                    <!-- PASO 1 · Tipo de servicio -->
                    <div class="form-group no-margin tc-field tc-field--full">
                        <label class="tc-label">
                            <span class="tc-label__step">1</span>
                            ¿Qué servicio necesita?
                        </label>
                        <div class="tc-service-grid">
                            <button
                                v-for="svc in serviceTypes"
                                :key="svc.value"
                                type="button"
                                :class="['tc-service-btn', { 'tc-service-btn--active': inputData.serviceType === svc.value }]"
                                @click="selectServiceType(svc.value)"
                            >
                                <i :class="svc.icon"></i>
                                <span>{{ svc.label }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- PASO 2 · Código postal -->
                    <div class="form-group no-margin tc-field">
                        <label class="tc-label">
                            <span class="tc-label__step">2</span>
                            Código postal
                        </label>
                        <div class="input-group tc-input">
                            <i class="far fa-location-dot opacity-6 mr-5"></i>
                            <input
                                v-model="inputData.postalCode"
                                type="text"
                                maxlength="5"
                                inputmode="numeric"
                                placeholder="Ej. 41001"
                                @input="onPostalInput"
                            >
                        </div>
                        <p v-if="detectedCity" class="tc-input-hint mt-5">
                            <i class="far fa-circle-check mr-4"></i> Cobertura estimada en <strong>{{ detectedCity }}</strong>
                        </p>
                    </div>

                    <!-- PASO 3 · Gasto actual -->
                    <div class="form-group no-margin tc-field">
                        <label class="tc-label">
                            <span class="tc-label__step">3</span>
                            ¿Cuánto paga actualmente?
                        </label>
                        <div class="input-group tc-input">
                            <i class="far fa-euro-sign opacity-6 mr-5"></i>
                            <input
                                v-model.number="inputData.currentSpend"
                                type="number"
                                min="1"
                                step="0.01"
                                placeholder="Ej. 59.90"
                            >
                            <span class="tc-input-suffix">/mes</span>
                        </div>
                    </div>

                    <!-- PASO 4 · Requisitos mínimos -->
                    <transition name="tc-expand">
                        <div v-if="inputData.serviceType" class="form-group no-margin tc-field tc-field--full">
                            <label class="tc-label">
                                <span class="tc-label__step">4</span>
                                ¿Qué mínimos necesita?
                                <span class="tc-label__hint">Solo se mostrarán ofertas que los cumplan</span>
                            </label>

                            <div class="tc-resources__grid">
                                <!-- Fibra -->
                                <div v-if="inputData.serviceType === 'Fibra' || inputData.serviceType === 'Fibra+Movil'" class="form-group no-margin tc-field">
                                    <label>Velocidad de fibra mínima</label>
                                    <div class="input-group tc-input">
                                        <i class="far fa-wifi opacity-6 mr-5"></i>
                                        <input v-model.number="inputData.fiberMb" type="number" min="50" step="50" placeholder="Ej. 600">
                                        <span class="tc-input-suffix">Mb</span>
                                    </div>
                                </div>

                                <!-- Datos móviles -->
                                <div v-if="inputData.serviceType === 'Movil' || inputData.serviceType === 'Fibra+Movil'" class="form-group no-margin tc-field">
                                    <label>Datos móviles mínimos</label>
                                    <div class="input-group tc-input">
                                        <i class="far fa-chart-network opacity-6 mr-5"></i>
                                        <input v-model.number="inputData.dataGB" type="number" min="1" step="1" placeholder="Ej. 50">
                                        <span class="tc-input-suffix">GB</span>
                                    </div>
                                </div>

                                <!-- Líneas -->
                                <div v-if="inputData.serviceType === 'Movil' || inputData.serviceType === 'Fibra+Movil'" class="form-group no-margin tc-field">
                                    <label>Número de líneas móviles</label>
                                    <div class="input-group tc-input">
                                        <i class="far fa-mobile-screen opacity-6 mr-5"></i>
                                        <input v-model.number="inputData.mobileLines" type="number" min="1" max="10" placeholder="Ej. 2">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>

                    <!-- PASO 5 · Permanencia -->
                    <div class="form-group no-margin tc-field tc-field--full">
                        <label class="tc-label">
                            <span class="tc-label__step">5</span>
                            Permanencia
                        </label>
                        <div
                            class="tc-checkbox-line"
                            @click="inputData.allowPermanence = !inputData.allowPermanence"
                        >
                            <div class="tc-checkbox" :class="{ 'tc-checkbox--checked': inputData.allowPermanence }">
                                <i v-if="inputData.allowPermanence" class="fas fa-check"></i>
                            </div>
                            <div>
                                <p class="text" data-size="14" data-weight="600">Me da igual si tiene permanencia</p>
                                <p class="text opacity-6" data-size="12">
                                    Si no lo marca, solo verá ofertas sin compromiso de permanencia.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- CTA -->
                <div class="tc-cta-area mt-25">
                    <p v-if="!canCompare" class="tc-cta-hint mb-12">
                        <i class="far fa-circle-info mr-5"></i>
                        {{ formHint }}
                    </p>
                    <button
                        type="submit"
                        :class="['custom-button mx-auto', { 'disabled-button': !canCompare }]"
                        data-size="big"
                        :disabled="!canCompare"
                    >
                        Comparar ofertas <i class="far fa-chart-line-up ml-5"></i>
                    </button>
                </div>
            </form>

            <!-- Loading -->
            <div v-else class="dashboard-card column align-center justify-center tc-card w-700-px w-90-max text-center">
                <div class="tc-loader-wrap">
                    <div class="tc-loader"></div>
                </div>
                <p class="text mt-20" data-size="22" data-weight="600">Buscando ofertas de telefonía</p>
                <p class="text opacity-6 mt-8" data-size="14">{{ loadingMessages[currentMessage] }}</p>
                <div class="tc-loading-dots mt-15">
                    <span v-for="i in 4" :key="i" :class="{ active: currentMessage === i - 1 }"></span>
                </div>
            </div>
        </div>

        <!-- ══════════════════════ RESULTADOS ══════════════════════ -->
        <div v-else-if="currentView === 'results'" class="d-flex mt-20 pb-20" :class="{ 'column align-center': true }">

            <div class="d-flex w-1180-px w-90-max mx-auto tc-results-layout" style="gap: 20px; align-items: flex-start; flex-wrap: wrap;">

                <!-- Panel izquierdo: resumen + filtros activos -->
                <div class="dashboard-card column tc-summary mb-auto" style="flex: 0 0 280px; min-width: 240px;">
                    <button class="custom-button mb-12" data-size="small" @click="resetComparator">
                        <i class="fa-solid fa-arrow-left mr-5"></i> Nueva búsqueda
                    </button>

                    <p class="text" data-size="18" data-weight="600">Ofertas recomendadas</p>
                    <p class="text opacity-6 mt-4" data-size="13">
                        {{ readableServiceType }} · {{ detectedCity || 'España' }}
                        <span v-if="topResults.length > 0" class="tc-count-badge ml-8">{{ topResults.length }} oferta{{ topResults.length !== 1 ? 's' : '' }}</span>
                    </p>

                    <div v-if="inputData.currentSpend" class="mt-12">
                        <p class="text opacity-5" data-size="11">Pago actual</p>
                        <p class="text" data-size="24" data-weight="700">
                            {{ formatEUR(inputData.currentSpend) }}<span class="text opacity-5" data-size="13">/mes</span>
                        </p>
                        <p v-if="topResults.length > 0 && calcAhorroMes(topResults[0]) > 0" class="tc-best-saving mt-4">
                            Ahorra hasta <strong>{{ formatEUR(calcAhorroMes(topResults[0])) }}/mes</strong>
                        </p>
                    </div>

                    <div class="separator my-12"></div>

                    <!-- Chips de resumen de búsqueda -->
                    <p class="text opacity-6 mb-8" data-size="11" data-weight="600">FILTROS APLICADOS</p>
                    <div class="d-flex column" style="gap: 6px;">
                        <div class="tc-chip tc-chip--blue">
                            <i class="far fa-signal-stream mr-5"></i>{{ readableServiceType }}
                        </div>
                        <div v-if="inputData.serviceType !== 'Movil'" class="tc-chip">
                            <i class="far fa-wifi mr-5"></i>{{ inputData.fiberMb }} Mb fibra mín.
                        </div>
                        <div v-if="inputData.serviceType !== 'Fibra'" class="tc-chip">
                            <i class="far fa-chart-network mr-5"></i>{{ inputData.dataGB }} GB datos mín.
                        </div>
                        <div v-if="inputData.serviceType !== 'Fibra'" class="tc-chip">
                            <i class="far fa-mobile-screen mr-5"></i>{{ inputData.mobileLines }} línea{{ inputData.mobileLines > 1 ? 's' : '' }} mín.
                        </div>
                        <div v-if="inputData.unlimitedCalls && inputData.serviceType !== 'Fibra'" class="tc-chip tc-chip--green">
                            <i class="far fa-phone mr-5"></i>Llamadas ilim.
                        </div>
                        <div class="tc-chip" :class="inputData.allowPermanence ? '' : 'tc-chip--amber'">
                            <i :class="['mr-5 far', inputData.allowPermanence ? 'fa-lock-open' : 'fa-lock']"></i>
                            {{ permanenceText() }}
                        </div>
                    </div>

                    <!-- Ordenación -->
                    <div class="separator my-12"></div>
                    <p class="text opacity-6 mb-8" data-size="11" data-weight="600">ORDENAR POR</p>
                    <div class="input-group tc-input tc-sort-input">
                        <i class="far fa-arrow-down-wide-short opacity-6 mr-5"></i>
                        <select v-model="offerSort">
                            <option value="recommended">Recomendadas</option>
                            <option value="price">Menor precio</option>
                            <option value="saving">Mayor ahorro</option>
                        </select>
                    </div>
                </div>

                <!-- Panel derecho: lista de ofertas (estilo comparador de luz) -->
                <div class="tc-results-panel" style="flex: 1; min-width: 0;">

                    <!-- Buscador -->
                    <div class="d-flex justify-between align-center mb-12" style="gap: 10px; flex-wrap: wrap;">
                        <h3 class="text" data-size="17" data-weight="600">
                            Resultados
                            <span v-if="topResults.length > 0" class="tc-count-badge ml-8">{{ topResults.length }}</span>
                        </h3>
                        <div class="input-group tc-input tc-search-input">
                            <i class="opacity-6 fa-regular fa-magnifying-glass mr-5"></i>
                            <input type="text" placeholder="Buscar por operador o tarifa..." v-model="offerSearch">
                        </div>
                    </div>

                    <!-- Sin resultados -->
                    <div v-if="topResults.length === 0" class="dashboard-card column justify-center align-center w-100 p-20 text-center">
                        <i class="far fa-face-thinking" data-size="36" style="color: #94a3b8;"></i>
                        <p class="text mt-15" data-size="18" data-weight="600">Sin resultados con estos filtros</p>
                        <p class="text mt-8 opacity-6" data-size="14">
                            Prueba a permitir permanencia, ampliar el gasto actual o revisar el código postal.
                        </p>
                        <div class="custom-button mt-15" data-size="small" @click="resetComparator">Hacer otra búsqueda</div>
                    </div>

                    <!-- ── Cabecera de columnas (ancho fijo en la derecha, igual que comparador de luz) ── -->
                    <div v-else class="tc-offer-header d-flex justify-between align-center px-16 mb-8">
                        <p class="text opacity-6" data-size="12">Oferta</p>
                        <div class="tc-offer-right-block">
                            <p class="text opacity-6 tc-col-price text-end" data-size="12">Precio/mes</p>
                            <p v-if="inputData.currentSpend" class="text opacity-6 tc-col-saving text-end" data-size="12">Ahorro/mes</p>
                            <div class="tc-col-actions"></div>
                        </div>
                    </div>

                    <!-- ── Lista de ofertas ── -->
                    <div class="d-flex column" style="gap: 10px;">
                        <div
                            v-for="(offer, index) in topResults"
                            :key="offer.id || index"
                            :class="['dashboard-card column p-16 mb-0 w-100 tc-offer-card', { 'tc-offer--best': index === 0 }]"
                        >
                            <!-- Fila principal: estructura idéntica al comparador de luz -->
                            <!-- Izquierda: flex libre con overflow hidden | Derecha: ancho fijo -->
                            <div
                                class="tc-offer-main pointer"
                                @click="toggleDetails(offer.id || index)"
                            >
                                <!-- ── IZQUIERDA: logo + info (crece, pero nunca empuja la derecha) ── -->
                                <div class="tc-offer-left">
                                    <!-- Logo -->
                                    <div class="tc-logo" style="flex-shrink: 0;">
                                        <img
                                            v-if="offer.logo"
                                            :src="`/assets/marketers_logo/${offer.logo}`"
                                            alt=""
                                            class="contain-img"
                                        >
                                        <span v-else>{{ marketerInitials(offer.marketer) }}</span>
                                    </div>

                                    <!-- Nombre + subtítulo + chips: overflow hidden para no invadir derecha -->
                                    <div class="tc-offer-info">
                                        <!-- Línea 1: nombre + badges -->
                                        <div class="tc-offer-title-row d-flex align-center">
                                            <p class="tc-offer-title text" data-size="15" data-weight="600">{{ offer.product }}</p>
                                            <span v-if="index === 0" class="tc-badge tc-badge--green">
                                                <i class="fas fa-star mr-3"></i>Mejor opción
                                            </span>
                                            <span v-if="Number(offer.permanenciaMeses) === 0" class="tc-badge tc-badge--amber">
                                                Sin permanencia
                                            </span>
                                        </div>
                                        <!-- Línea 2: operador + tipo -->
                                        <p class="tc-offer-subtitle text opacity-6 mt-2" data-size="12">
                                            {{ offer.marketer }} · {{ readableTariffType(offer.fee) }}
                                        </p>
                                        <!-- Línea 3: chips de características -->
                                        <div class="tc-feat-chips mt-6">
                                            <span v-if="offer.fibraMb" class="tc-feat-chip">
                                                <i class="far fa-wifi mr-3"></i>
                                                {{ offer.fibraMb >= 1000 ? `${offer.fibraMb / 1000} Gb` : `${offer.fibraMb} Mb` }} fibra
                                            </span>
                                            <span v-if="offer.datosGB" class="tc-feat-chip">
                                                <i class="far fa-signal mr-3"></i>{{ offer.datosGB }} GB
                                            </span>
                                            <span v-if="offer.lineasIncluidas" class="tc-feat-chip">
                                                <i class="far fa-mobile-screen mr-3"></i>
                                                {{ offer.lineasIncluidas }} línea{{ offer.lineasIncluidas > 1 ? 's' : '' }}
                                            </span>
                                            <span v-if="offer.coverage" class="tc-feat-chip">
                                                <i class="far fa-map-pin mr-3"></i>{{ offer.coverage }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- ── DERECHA: bloque de ancho fijo, igual que w-410-px del comparador de luz ── -->
                                <div class="tc-offer-right-block">
                                    <!-- Precio -->
                                    <div class="tc-col-price text-end">
                                        <p class="text opacity-6" data-size="11">Precio</p>
                                        <p class="text" data-size="18" data-weight="600">
                                            {{ formatEUR(offer.price) }}<span class="text opacity-5" data-size="12">/mes</span>
                                        </p>
                                    </div>
                                    <!-- Ahorro -->
                                    <div v-if="inputData.currentSpend" class="tc-col-saving text-end">
                                        <p class="text opacity-6" data-size="11">Ahorro</p>
                                        <p class="text" data-size="18" data-weight="600" :data-color="calcAhorroMes(offer) > 0 ? 'success' : 'rojo'">
                                            {{ formatEUR(calcAhorroMes(offer)) }}<span class="text opacity-5" data-size="12">/mes</span>
                                        </p>
                                    </div>
                                    <!-- Acciones -->
                                    <div class="tc-col-actions d-flex align-center justify-end" style="gap: 8px;">
                                        <button
                                            class="custom-button"
                                            data-size="small"
                                            data-bg="amarillo"
                                            @click.stop="createOpportunity(offer)"
                                        >
                                            <i class="far fa-user-plus mr-5"></i>Oportunidad
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Detalle expandible (copia del estilo de la sección de precios del comparador de luz) ── -->
                            <transition name="tc-expand">
                                <div v-if="expanded[offer.id || index]" class="tc-offer__detail" @click.stop>

                                    <!-- Grid de detalle igual que comparator-table de luz -->
                                    <div class="tc-detail-grid">
                                        <div v-if="offer.fibraMb" class="tc-detail-item">
                                            <p class="tc-detail-item__label">
                                                <i class="far fa-wifi mr-4"></i>Velocidad fibra
                                            </p>
                                            <p class="tc-detail-item__value">
                                                {{ offer.fibraMb >= 1000 ? `${offer.fibraMb / 1000} Gb` : `${offer.fibraMb} Mb` }}
                                            </p>
                                        </div>
                                        <div v-if="offer.datosGB" class="tc-detail-item">
                                            <p class="tc-detail-item__label"><i class="far fa-signal mr-4"></i>Datos móviles</p>
                                            <p class="tc-detail-item__value">{{ offer.datosGB }} GB</p>
                                        </div>
                                        <div v-if="offer.lineasIncluidas != null" class="tc-detail-item">
                                            <p class="tc-detail-item__label"><i class="far fa-mobile-screen mr-4"></i>Líneas incluidas</p>
                                            <p class="tc-detail-item__value">{{ offer.lineasIncluidas }}</p>
                                        </div>
                                        
                                        <div class="tc-detail-item">
                                            <p class="tc-detail-item__label"><i class="far fa-lock mr-4"></i>Permanencia</p>
                                            <p class="tc-detail-item__value">
                                                {{ Number(offer.permanenciaMeses) === 0 ? 'Sin permanencia' : `${offer.permanenciaMeses} meses` }}
                                            </p>
                                        </div>
                                        <div v-if="offer.coverage" class="tc-detail-item">
                                            <p class="tc-detail-item__label"><i class="far fa-map-pin mr-4"></i>Red preferente</p>
                                            <p class="tc-detail-item__value">{{ offer.coverage }}</p>
                                        </div>
                                        <div v-if="inputData.currentSpend" class="tc-detail-item tc-detail-item--highlight">
                                            <p class="tc-detail-item__label"><i class="far fa-piggy-bank mr-4"></i>Ahorro anual</p>
                                            <p class="tc-detail-item__value tc-detail-item__value--green">
                                                {{ formatEUR(calcAhorroAnual(offer)) }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- CTAs al pie del detalle -->
                                    <div class="tc-offer__detail-cta">
                                        <a
                                            v-if="offer.url"
                                            :href="offer.url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="custom-button"
                                            data-size="small"
                                            data-bg="azul"
                                            data-mode="translucent"
                                        >
                                            <i class="far fa-arrow-up-right-from-square mr-5"></i>Ver oferta
                                        </a>
                                        <button
                                            class="custom-button"
                                            data-size="regular"
                                            data-bg="amarillo"
                                            @click="createOpportunity(offer)"
                                        >
                                            <i class="far fa-user-plus mr-5"></i>Crear oportunidad
                                        </button>
                                
                                    </div>
                                </div>
                            </transition>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════ CONFIRMACIÓN ══════════════════════ -->
        <div v-else-if="currentView === 'confirmation'" class="d-flex column align-center w-700-px w-90-max mx-auto text-center" data-gap="10">
            <i class="fas fa-check-circle" data-size="50" data-color="success"></i>
            <p class="text mt-10" data-size="28" data-weight="600">Solicitud enviada</p>
            <p class="text mt-8" data-size="16">
                Hemos recibido su solicitud. Un gestor se pondrá en contacto para finalizar la contratación.
            </p>
            <div class="custom-button mt-20" data-size="big" @click="resetComparator">Hacer otra comparativa</div>
        </div>

        <!-- ══════════════════════ MODAL CONTRATACIÓN ══════════════════════ -->
        <div v-if="offerSelected" class="modal" @click="offerSelected = null">
            <div class="tc-modal" @click.stop>
                <div class="tc-modal__header">
                    <div class="tc-modal__offer-preview">
                        <div class="tc-logo tc-logo--sm">
                            <img
                                v-if="offerSelected.logo"
                                :src="`/assets/marketers_logo/${offerSelected.logo}`"
                                alt=""
                                class="contain-img"
                            >
                            <span v-else>{{ marketerInitials(offerSelected.marketer) }}</span>
                        </div>
                        <div>
                            <p class="text" data-size="16" data-weight="600">{{ offerSelected.product }}</p>
                            <p class="text opacity-6" data-size="12">
                                {{ offerSelected.marketer }} · {{ formatEUR(offerSelected.price) }}/mes
                            </p>
                        </div>
                    </div>
                    <button class="tc-modal__close" type="button" @click="offerSelected = null">
                        <i class="far fa-times"></i>
                    </button>
                </div>

                <div class="separator my-15"></div>

                <p class="text" data-size="14" data-weight="600">Datos de contacto</p>
                <p class="text opacity-6 mt-4 mb-15" data-size="12">
                    Un gestor le llamará para finalizar la contratación sin compromiso.
                </p>

                <form class="d-flex column form w-100">
                    <div class="form-group no-margin">
                        <label>Nombre</label>
                        <div class="input-group">
                            <input v-model="clientData.name" type="text" placeholder="Nombre completo" required>
                        </div>
                    </div>
                    <div class="form-group no-margin">
                        <label>Correo electrónico</label>
                        <div class="input-group">
                            <input v-model="clientData.email" type="email" placeholder="correo@ejemplo.com" required>
                        </div>
                    </div>
                    <div class="form-group no-margin">
                        <label>Teléfono de contacto</label>
                        <div class="input-group">
                            <input v-model="clientData.phone" type="tel" placeholder="6XX XXX XXX" required>
                        </div>
                    </div>
                    <button
                        type="submit"
                        class="custom-button mt-20"
                        data-size="medium"
                        @click.prevent="createOrder"
                    >
                        <i class="far fa-paper-plane mr-5"></i> Enviar solicitud
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, getCurrentInstance, onBeforeUnmount, onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";

// ─── Estado de navegación ───────────────────────────────────────────────────

const router = useRouter();
const instance = getCurrentInstance();
const currentView = ref("form");
const currentStep = ref("form");
const offerSearch = ref("");
const offerSort = ref("recommended");
const offerSelected = ref(null);

// ─── Datos del cliente ──────────────────────────────────────────────────────

const clientData = reactive({
    name: "",
    email: "",
    phone: "",
});

const errors = reactive({});

const opportunityData = reactive({
    name: "",
    agentName: "",
    agentPhone: "",
    agentEmail: "",
    CIF: "",
    currentMarketer: "",
    billingInfo: {
        community: "",
        province: "",
        locality: "",
        address: "",
        postal: "",
    },
    studyDate: new Date().toISOString().slice(0, 10),
    order: {
        productType: "ct",
        marketerLogo: "",
        marketer: "",
        fee: "",
        product: "",
        CUPS: "",
        direc: "",
        zip: "",
        town: "",
        province: "",
        serviceType: "",
        fiberMb: "",
        dataGB: "",
        mobileLines: "",
        calls: "",
        permanence: "",
        coverage: "",
    },
});

// ─── Datos del formulario ───────────────────────────────────────────────────

const inputData = reactive({
    serviceType: "",
    postalCode: "",
    preferredCoverage: null,
    mobileLines: "",
    fiberMb: "",
    dataGB: "",
    unlimitedCalls: false,
    allowPermanence: false,
    currentSpend: "",
});

// ─── Estado de expansión de tarjetas ───────────────────────────────────────

const expanded = reactive({});

// ─── Estado de carga ────────────────────────────────────────────────────────

const detectedCity = ref("");
const currentMessage = ref(0);
let loadingTimer = null;
let loadingMessageTimer = null;

const loadingMessages = [
    "Buscando tarifas que encajen con su consumo...",
    "Priorizando opciones con mejor relación calidad-precio...",
    "Calculando ahorro mensual y anual...",
    "Preparando las mejores recomendaciones para usted...",
];

// ─── Constantes del formulario ──────────────────────────────────────────────

const serviceTypes = [
    { value: "Fibra",       label: "Solo fibra",    icon: "far fa-wifi" },
    { value: "Movil",       label: "Solo móvil",    icon: "far fa-mobile-screen" },
    { value: "Fibra+Movil", label: "Fibra + Móvil", icon: "far fa-signal" },
];

// ─── Mapas de cobertura y provincias ───────────────────────────────────────

const coverageMap = {
    1: "Movistar",  2: "Movistar",  3: "Movistar",  4: "Movistar",  5: "Movistar",
    6: "Movistar",  7: "Movistar",  8: "Orange",    9: "Movistar",  10: "Movistar",
    11: "Orange",   12: "Orange",   13: "Movistar", 14: "Movistar", 15: "Movistar",
    16: "Movistar", 17: "Orange",   18: "Orange",   19: "Movistar", 20: "Vodafone",
    21: "Movistar", 22: "Movistar", 23: "Movistar", 24: "Movistar", 25: "Movistar",
    26: "Movistar", 27: "Movistar", 28: "Movistar", 29: "Orange",   30: "Orange",
    31: "Movistar", 32: "Movistar", 33: "Movistar", 34: "Movistar", 35: "Movistar",
    36: "Movistar", 37: "Movistar", 38: "Movistar", 39: "Movistar", 40: "Movistar",
    41: "Movistar", 42: "Movistar", 43: "Orange",   44: "Movistar", 45: "Movistar",
    46: "Vodafone", 47: "Movistar", 48: "Vodafone", 49: "Movistar", 50: "Movistar",
    51: "Movistar", 52: "Movistar",
};

const cpCityMap = {
    "28": "Madrid",    "08": "Barcelona", "41": "Sevilla",
    "46": "Valencia",  "29": "Málaga",    "48": "Bilbao",
    "50": "Zaragoza",  "14": "Córdoba",   "30": "Murcia",
    "18": "Granada",   "15": "A Coruña",  "36": "Pontevedra",
    "38": "Tenerife",  "35": "Las Palmas",
};

// ─── Carga de tarifas desde API ─────────────────────────────────────────────

const TARIFFS = ref([]);

/**
 * Normaliza el nombre del tipo de tarifa procedente de fee.name en MongoDB
 * a los valores internos: "Fibra", "Movil", "Fibra+Movil".
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
                    fee: normalizeTipo(fee.name),
                    marketer: marketer.name || "",
                    product: product.name || "",
                    price: Number(comp.price || 0),
                    //fee: Number(comp.price || 0),
                    fibraMb: Number(comp.fibraMb || 0),
                    datosGB: Number(comp.datosGB || 0),
                    lineasIncluidas: comp.lineasIncluidas ?? null,
                    //llamadasIlimitadas: Boolean(comp.llamadasIlimitadas),
                    permanenciaMeses: Number(comp.permanenciaMeses || 0),
                    coverage: marketer.coverage || "",
                    logo: marketer.logo || "",
                    url: comp.url || "",
                });
            }
        }
    }

    return result;
}

onMounted(async () => {
    try {
        const { data } = await axios.get("/api/marketers", {});
        const marketers = Array.isArray(data) ? data : (data.marketers ?? []);
        TARIFFS.value = transformMarketers(marketers);
    } catch (error) {
        console.error("Error al cargar los marketers desde la API:", error);
        TARIFFS.value = [];
    }
});

// ─── Computed ───────────────────────────────────────────────────────────────

const readableServiceType = computed(() => {
    if (inputData.serviceType === "Fibra+Movil") return "Fibra + Móvil";
    if (inputData.serviceType === "Movil") return "Solo Móvil";
    if (inputData.serviceType === "Fibra") return "Solo Fibra";
    return "-";
});

const canCompare = computed(() => {
    const hasService = Boolean(inputData.serviceType);
    const hasPostalCode = /^\d{5}$/.test(inputData.postalCode || "");
    const hasSpend = Number(inputData.currentSpend || 0) > 0;
    const needsFiber = inputData.serviceType === "Fibra" || inputData.serviceType === "Fibra+Movil";
    const needsMobile = inputData.serviceType === "Movil" || inputData.serviceType === "Fibra+Movil";
    const hasFiber = !needsFiber || Number(inputData.fiberMb || 0) > 0;
    const hasMobile = !needsMobile || (Number(inputData.mobileLines || 0) > 0 && Number(inputData.dataGB || 0) > 0);
    return hasService && hasPostalCode && hasSpend && hasFiber && hasMobile;
});

const formHint = computed(() => {
    if (!inputData.serviceType) return "Seleccione el tipo de servicio.";
    if (!/^\d{5}$/.test(inputData.postalCode || "")) return "Introduzca un código postal de 5 dígitos.";
    if (!Number(inputData.currentSpend || 0)) return "Indique su gasto mensual actual.";
    const needsFiber = inputData.serviceType === "Fibra" || inputData.serviceType === "Fibra+Movil";
    const needsMobile = inputData.serviceType === "Movil" || inputData.serviceType === "Fibra+Movil";
    if (needsFiber && !Number(inputData.fiberMb || 0)) return "Indique la velocidad de fibra mínima.";
    if (needsMobile && !Number(inputData.dataGB || 0)) return "Indique los GB de datos móviles mínimos.";
    if (needsMobile && !Number(inputData.mobileLines || 0)) return "Indique el número de líneas móviles.";
    return "";
});

/**
 * Filtrado principal de tarifas.
 * Los filtros de fibra, datos y líneas se aplican SIEMPRE (no solo como score)
 * para que reflejen exactamente los mínimos que el usuario ha pedido.
 */
const filteredTariffs = computed(() => {
    const fee = (inputData.serviceType || "").toLowerCase();
    const gasto = Number(inputData.currentSpend || 0);
    const requiredFiber = Number(inputData.fiberMb || 0);
    const requiredGb = Number(inputData.dataGB || 0);
    const requiredLines = Number(inputData.mobileLines || 0);

    // 1. Filtrar por tipo de servicio
    let arr = [...TARIFFS.value].filter((t) => (t.fee || "").toLowerCase() === fee);

    // 2. Precio razonable (máx 125% del gasto actual)
    arr = arr.filter((t) => t.price > 0 && t.price <= gasto * 1.25);

    // 3. Permanencia
    if (!inputData.allowPermanence) {
        arr = arr.filter((t) => Number(t.permanenciaMeses || 0) === 0);
    }

    // 4. Llamadas ilimitadas (si el usuario las requiere)
    /*if (inputData.unlimitedCalls && (fee === "movil" || fee === "fibra+movil")) {
        arr = arr.filter((t) => t.llamadasIlimitadas);
    }*/

    // 5. ── FILTRO DE FIBRA: solo ofertas con >= Mb mínimos requeridos ──
    if (fee === "fibra" || fee === "fibra+movil") {
        if (requiredFiber > 0) {
            arr = arr.filter((t) => {
                const mb = Number(t.fibraMb || 0);
                // mb === 0 significa que no hay dato: lo permitimos (cobertura general)
                return mb === 0 || mb >= requiredFiber;
            });
        }
    }

    // 6. ── FILTRO DE DATOS MÓVILES: solo ofertas con >= GB mínimos requeridos ──
    if (fee === "movil" || fee === "fibra+movil") {
        if (requiredGb > 0) {
            arr = arr.filter((t) => {
                const gb = Number(t.datosGB || 0);
                return gb === 0 || gb >= requiredGb;
            });
        }

        // 7. ── FILTRO DE LÍNEAS: solo ofertas con >= líneas requeridas ──
        if (requiredLines > 0) {
            arr = arr.filter((t) => {
                // Si lineasIncluidas es null/undefined la oferta no especifica líneas: la dejamos pasar
                if (t.lineasIncluidas == null) return true;
                return Number(t.lineasIncluidas) >= requiredLines;
            });
        }
    }

    // Fallback: si no hay resultados relajamos el filtro de precio
    if (arr.length === 0) {
        arr = TARIFFS.value.filter((t) => (t.fee || "").toLowerCase() === fee);
        if (!inputData.allowPermanence) {
            arr = arr.filter((t) => Number(t.permanenciaMeses || 0) === 0);
        }
        // Mantenemos igualmente los filtros de fibra/datos/líneas en el fallback
        if (fee === "fibra" || fee === "fibra+movil") {
            if (requiredFiber > 0) arr = arr.filter((t) => Number(t.fibraMb || 0) === 0 || Number(t.fibraMb || 0) >= requiredFiber);
        }
        if (fee === "movil" || fee === "fibra+movil") {
            if (requiredGb > 0) arr = arr.filter((t) => Number(t.datosGB || 0) === 0 || Number(t.datosGB || 0) >= requiredGb);
            if (requiredLines > 0) arr = arr.filter((t) => t.lineasIncluidas == null || Number(t.lineasIncluidas) >= requiredLines);
        }
    }

    return arr;
});

const topResults = computed(() => {
    let sorted = [...filteredTariffs.value]
        .map((item) => ({ ...item, _score: scoreTariff(item) }));

    // Búsqueda de texto
    const search = (offerSearch.value || "").toLowerCase().trim();
    if (search) {
        sorted = sorted.filter((item) =>
            [item.marketer, item.product, item.coverage, readableTariffType(item.fee)]
                .join(" ")
                .toLowerCase()
                .includes(search)
        );
    }

    // Ordenación
    if (offerSort.value === "price") {
        sorted.sort((a, b) => Number(a.price || 0) - Number(b.price || 0));
    } else if (offerSort.value === "saving") {
        sorted.sort((a, b) => calcAhorroMes(b) - calcAhorroMes(a));
    } else {
        sorted.sort((a, b) => b._score - a._score);
    }

    // Máximo 2 ofertas por operador, máximo 8 en total
    const result = [];
    const companyCount = {};

    for (const item of sorted) {
        const marketer = item.marketer || "unknown";
        if (!companyCount[marketer]) companyCount[marketer] = 0;
        if (companyCount[marketer] >= 2) continue;
        result.push(item);
        companyCount[marketer]++;
        if (result.length >= 8) break;
    }

    return result;
});

// ─── Lógica de scoring ──────────────────────────────────────────────────────

function scoreTariff(t) {
    let score = 0;

    const fee = inputData.serviceType || "";
    const price = Number(t.price || 0);
    const gasto = Number(inputData.currentSpend || 0);
    const gb = Number(t.datosGB || 0);
    const speed = Number(t.fibraMb || 0);
    const permanencia = Number(t.permanenciaMeses || 0);
    const requiredFiber = Number(inputData.fiberMb || 0);
    const requiredGb = Number(inputData.dataGB || 0);
    const requiredLines = Number(inputData.mobileLines || 0);
    const ahorro = gasto - price;

    // Ahorro respecto al gasto actual
    if (gasto > 0) {
        const ahorroRatio = ahorro / gasto;
        score += ahorroRatio > 0 ? ahorroRatio * 45 : ahorroRatio * 25;
    }

    if (price <= gasto * 0.5) score += 8;
    else if (price <= gasto * 0.75) score += 5;

    // Cobertura preferida
    if (inputData.preferredCoverage) {
        if (t.coverage === inputData.preferredCoverage) score += 18;
        else score -= 8;
    }

    // Calidad de la fibra: cuanto más se acerque al mínimo pedido (sin pasarse demasiado), mejor
    if (fee === "Fibra" || fee === "Fibra+Movil") {
        if (speed === 0) score -= 4;
        else if (speed === requiredFiber) score += 24;
        else if (speed <= requiredFiber * 1.5) score += 20;
        else score += 12;
    }

    // Calidad de datos y líneas
    if (fee === "Movil" || fee === "Fibra+Movil") {
        if (gb === 0) score -= 4;
        else if (gb === requiredGb) score += 24;
        else if (gb <= requiredGb * 1.8) score += 20;
        else score += 12;

        if (t.lineasIncluidas != null) {
            const includedLines = Number(t.lineasIncluidas || 0);
            if (includedLines === requiredLines) score += 12;
            else if (includedLines > requiredLines) score += 5;
        }

        //if (inputData.unlimitedCalls && t.llamadasIlimitadas) score += 10;
    }

    // Permanencia
    if (!inputData.allowPermanence) {
        if (permanencia === 0) score += 15;
    } else if (permanencia === 0) {
        score += 5;
    }

    return score;
}

// ─── Handlers del formulario ────────────────────────────────────────────────

function selectServiceType(type) {
    inputData.serviceType = type;

    if (type === "Fibra") {
        inputData.mobileLines = "";
        inputData.dataGB = "";
        inputData.unlimitedCalls = false;
    }

    if (type === "Movil") {
        inputData.fiberMb = "";
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

function startLoading() {
    onPostalInput();
    if (!canCompare.value) return;

    const province = parseInt(inputData.postalCode.substring(0, 2), 10);
    inputData.preferredCoverage = coverageMap[province] || null;

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
    if (loadingTimer) { clearTimeout(loadingTimer); loadingTimer = null; }
    if (loadingMessageTimer) { clearInterval(loadingMessageTimer); loadingMessageTimer = null; }
}

// ─── Contratación ───────────────────────────────────────────────────────────

function setTemporalCookie(name, value, hours = 1) {
    const cookies = instance?.proxy?.$cookies;

    if (cookies?.set) {
        cookies.set(name, value, `${hours}h`);
        return;
    }

    const expires = new Date(Date.now() + hours * 60 * 60 * 1000).toUTCString();
    document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/`;
}

 function createOpportunity(offer) {
    const confirmPromise =
         Swal.fire({
            icon: "warning",
            title: "¿Estás seguro que quieres crear una oportunidad?",
            confirmButtonText: "Sí",
            showConfirmButton: true,
            cancelButtonText: "No",
            showCancelButton: true,
        })

    confirmPromise.then((res) => {
        if (!res.isConfirmed) return;

        const marketer = offer.marketer;
        const fee = offer.fee;
        const product = offer.product;

        opportunityData.name = opportunityData.name || `${marketer} - ${product}`;
        opportunityData.currentMarketer = opportunityData.currentMarketer || "Telefonía";
        opportunityData.billingInfo = {
            ...opportunityData.billingInfo,
            postal: inputData.postalCode || opportunityData.billingInfo.postal,
            locality: detectedCity.value || opportunityData.billingInfo.locality,
        };

        opportunityData.order = {
            ...opportunityData.order,
            productType: "ct",
            marketer,
            marketerLogo: offer.logo ? `/assets/marketers_logo/${offer.logo}` : "",
            fee,
            product,
            CUPS: "",
            zip: inputData.postalCode || "",
            town: detectedCity.value || "",
            serviceType: offer.fee || inputData.serviceType,
            fiberMb: offer.fibraMb || "",
            dataGB: offer.datosGB || "",
            mobileLines: offer.lineasIncluidas ?? "",
            //calls: offer.llamadasIlimitadas ? "Ilimitadas" : "No incluidas",
            permanence: Number(offer.permanenciaMeses || 0),
            coverage: offer.coverage || "",
        };

        const temporalCreateOppCookie = JSON.stringify(opportunityData);
        setTemporalCookie("temporalCreateOppCookie", temporalCreateOppCookie, 1);

        const routeData = router.resolve({ name: "oportunitiesRegister" });
        window.open(routeData.href, "_blank");
    });
}

// ─── Helpers de presentación ────────────────────────────────────────────────

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

function readableTariffType(fee) {
    if (fee === "Fibra+Movil") return "Fibra + Móvil";
    if (fee === "Movil") return "Solo móvil";
    if (fee === "Fibra") return "Solo fibra";
    return fee || "-";
}

function marketerInitials(name) {
    return (name || "?")
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join("")
        .toUpperCase();
}

function permanenceText() {
    return inputData.allowPermanence ? "Me da igual la permanencia" : "Solo sin permanencia";
}

// ─── Reset ──────────────────────────────────────────────────────────────────

function resetComparator() {
    clearTimers();

    currentView.value = "form";
    currentStep.value = "form";
    offerSearch.value = "";
    offerSort.value = "recommended";

    inputData.serviceType = "";
    inputData.postalCode = "";
    inputData.preferredCoverage = null;
    inputData.mobileLines = "";
    inputData.fiberMb = "";
    inputData.dataGB = "";
    inputData.unlimitedCalls = false;
    inputData.allowPermanence = false;
    inputData.currentSpend = "";

    detectedCity.value = "";
    currentMessage.value = 0;

    Object.keys(expanded).forEach((key) => delete expanded[key]);
}

onBeforeUnmount(() => {
    clearTimers();
});
</script>


<style scoped>

/* ─── Contenedor raíz ─────────────────────────────────────────────────────── */

.tc {
    width: 100%;
}

/* ─── Tarjeta del formulario ──────────────────────────────────────────────── */

.tc-card {
    padding: 28px;
}

/* ─── Hero ────────────────────────────────────────────────────────────────── */

.tc-hero__icon {
    width: 60px;
    height: 60px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 24px;
}

.tc-hero__desc {
    max-width: 500px;
}

/* ─── Grid del formulario ─────────────────────────────────────────────────── */

.tc-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.tc-field--full {
    grid-column: 1 / -1;
}

/* ─── Labels con paso numerado ────────────────────────────────────────────── */

.tc-label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
}

.tc-label__step {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #1d4ed8;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    flex-shrink: 0;
}

.tc-label__hint {
    font-size: 11px;
    font-weight: 400;
    color: #94a3b8;
    margin-left: 2px;
}

/* ─── Inputs ──────────────────────────────────────────────────────────────── */

.tc-input input,
.tc-input select {
    background: transparent;
    border: none;
    outline: none;
    flex: 1;
    min-width: 0;
}

.tc-input-suffix {
    font-size: 13px;
    color: #94a3b8;
    flex-shrink: 0;
}

.tc-input-hint {
    font-size: 12px;
    color: #15803d;
}

/* ─── Grid de tipos de servicio ───────────────────────────────────────────── */

.tc-service-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}

.tc-service-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 16px 10px;
    border-radius: 12px;
    border: 1.5px solid #e2e8f0;
    background: #fff;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    color: #475569;
    transition: all 0.18s ease;
}

.tc-service-btn i {
    font-size: 20px;
}

.tc-service-btn:hover {
    border-color: #93c5fd;
    background: #eff6ff;
    color: #1d4ed8;
}

.tc-service-btn--active {
    border-color: #1d4ed8;
    background: #eff6ff;
    color: #1d4ed8;
    font-weight: 600;
}

/* ─── Grid de recursos (fibra, datos, líneas) ─────────────────────────────── */

.tc-resources__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

/* ─── Checkbox personalizado ──────────────────────────────────────────────── */

.tc-checkbox-line {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    transition: border-color 0.15s ease, background 0.15s ease;
}

.tc-checkbox-line:hover {
    border-color: #93c5fd;
    background: #f8faff;
}

.tc-checkbox {
    width: 18px;
    height: 18px;
    border-radius: 5px;
    border: 1.5px solid #cbd5e1;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 10px;
    color: #fff;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.tc-checkbox--checked {
    background: #1d4ed8;
    border-color: #1d4ed8;
}

/* ─── CTA del formulario ──────────────────────────────────────────────────── */

.tc-cta-area {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.tc-cta-hint {
    font-size: 13px;
    color: #94a3b8;
    text-align: center;
}

/* ─── Layout general de resultados ───────────────────────────────────────── */

.tc-results-layout {
    width: min(96vw, 1360px);
    max-width: 1360px;
}

.tc-results-panel {
    flex: 1 1 0;
    min-width: 760px !important;
}

.tc-offer-card {
    width: 100%;
    border-radius: 18px;
    overflow: visible;
}

/* ─── Resumen (panel lateral izquierdo en resultados) ─────────────────────── */

.tc-summary {
    padding: 20px;
}

/* ─── Chips de resumen ────────────────────────────────────────────────────── */

.tc-chip {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.tc-chip--blue {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;
}

.tc-chip--green {
    background: #f0fdf4;
    color: #15803d;
    border-color: #bbf7d0;
}

.tc-chip--amber {
    background: #fffbeb;
    color: #b45309;
    border-color: #fde68a;
}

/* ─── Badge de conteo ─────────────────────────────────────────────────────── */

.tc-count-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    background: #eff6ff;
    color: #1d4ed8;
}

/* ─── Cabecera de ahorro ──────────────────────────────────────────────────── */

.tc-best-saving {
    font-size: 13px;
    color: #15803d;
}

/* ─── Fila de oferta: izquierda flexible + derecha ancha y fija ─────────── */
/* La zona derecha usa el espacio sobrante real de la card para que precio,
   ahorro y botón no se amontonen sobre el nombre ni entre ellos. */

.tc-offer-header,
.tc-offer-main {
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) minmax(650px, 720px);
    column-gap: 28px;
    align-items: center;
    width: 100%;
}

.tc-offer-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    overflow: hidden;
}

.tc-offer-info {
    min-width: 0;
    width: 100%;
    overflow: hidden;
}

.tc-offer-title-row {
    gap: 6px;
    flex-wrap: wrap;
}

.tc-offer-title {
    min-width: 0;
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.tc-offer-subtitle {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.tc-offer-right-block {
    width: 100%;
    display: grid !important;
    grid-template-columns: 140px 150px minmax(330px, 1fr);
    align-items: center;
    justify-content: stretch;
    column-gap: 22px;
    min-width: 0;
}

/* ─── Columnas internas del bloque derecho ───────────────────────────────── */

.tc-col-price {
    width: auto;
    text-align: right;
    padding-right: 0;
    white-space: nowrap;
}

.tc-col-saving {
    width: auto;
    text-align: right;
    padding-right: 0;
    white-space: nowrap;
}

.tc-col-actions {
    width: auto;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding-left: 0;
    white-space: nowrap;
}

/* ─── Tarjeta de oferta destacada ─────────────────────────────────────────── */

.tc-offer--best {
    border: 1.5px solid #bfdbfe !important;
    background: #fafcff;
}

/* ─── Logo del operador ───────────────────────────────────────────────────── */

.tc-logo {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
    color: #1d4ed8;
    overflow: hidden;
    flex-shrink: 0;
}

.tc-logo--sm {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    font-size: 11px;
}

.contain-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 4px;
}

/* ─── Badges de oferta ────────────────────────────────────────────────────── */

.tc-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.tc-badge--green {
    background: #dcfce7;
    color: #15803d;
}

.tc-badge--amber {
    background: #fef3c7;
    color: #92400e;
}

/* ─── Chips de características de la oferta ───────────────────────────────── */

.tc-feat-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.tc-feat-chip {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 500;
    background: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

.tc-feat-chip--special {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;
}


.tc-search-input {
    min-width: 260px;
    max-width: 360px;
    flex: 1 1 260px;
}

/* ─── Chevron ─────────────────────────────────────────────────────────────── */

.tc-chevron {
    font-size: 14px;
    color: #94a3b8;
    transition: transform 0.22s ease;
    cursor: pointer;
}

.tc-chevron--open {
    transform: rotate(180deg);
}

/* ─── Detalle expandible ──────────────────────────────────────────────────── */

.tc-offer__detail {
    border-top: 1px solid #f1f5f9;
    padding: 16px 18px;
    background: #fafbfc;
    margin-top: 14px;
    border-radius: 0 0 10px 10px;
}

.tc-detail-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    padding: 14px;
    border: 1px solid #dbeafe;
    border-radius: 14px;
    background: #f8fbff;
}

.tc-detail-item__label {
    font-size: 11px;
    color: #94a3b8;
    margin-bottom: 4px;
}

.tc-detail-item__value {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
}

.tc-detail-item__value--green {
    color: #15803d;
}

.tc-detail-item--highlight {
    background: #f0fdf4;
    border: 1px solid rgba(21, 128, 61, 0.12);
    border-radius: 10px;
    padding: 8px 10px;
}

.tc-offer__detail-cta {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 14px;
    flex-wrap: wrap;
}

/* ─── Modal de contratación ───────────────────────────────────────────────── */

.tc-modal {
    width: 100%;
    max-width: 460px;
    background: #fff;
    border-radius: 20px;
    border: 1.5px solid rgba(29, 78, 216, 0.14);
    padding: 24px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.14);
}

.tc-modal__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.tc-modal__offer-preview {
    display: flex;
    align-items: center;
    gap: 12px;
}

.tc-modal__close {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 1px solid #e2e8f0;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #64748b;
    font-size: 14px;
    transition: background 0.15s ease;
    flex-shrink: 0;
}

.tc-modal__close:hover {
    background: #f1f5f9;
}

/* ─── Loader ──────────────────────────────────────────────────────────────── */

.tc-loader-wrap {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #eff6ff;
    display: flex;
    align-items: center;
    justify-content: center;
}

.tc-loader {
    width: 32px;
    height: 32px;
    border: 3px solid #bfdbfe;
    border-top-color: #1d4ed8;
    border-radius: 50%;
    animation: tc-spin 0.9s linear infinite;
}

@keyframes tc-spin {
    to { transform: rotate(360deg); }
}

.tc-loading-dots {
    display: flex;
    gap: 6px;
}

.tc-loading-dots span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #cbd5e1;
    transition: background 0.3s ease;
}

.tc-loading-dots span.active {
    background: #1d4ed8;
}

/* ─── Animación de expansión ──────────────────────────────────────────────── */

.tc-expand-enter-active,
.tc-expand-leave-active {
    transition: max-height 0.35s ease, opacity 0.25s ease, transform 0.25s ease;
    overflow: hidden;
}

.tc-expand-enter-from,
.tc-expand-leave-to {
    max-height: 0;
    opacity: 0;
    transform: translateY(-4px);
}

.tc-expand-enter-to,
.tc-expand-leave-from {
    max-height: 600px;
    opacity: 1;
    transform: translateY(0);
}


.tc-input {
    min-height: 44px;
    display: flex;
    align-items: center;
    width: 100%;
    padding: 0 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    background: #fff;
    transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
}

.tc-sort-input {
    width: 100%;
}
/* ─── Utilidades ──────────────────────────────────────────────────────────── */

.flex-wrap { flex-wrap: wrap; }
.flex-shrink-0 { flex-shrink: 0; }
.pointer { cursor: pointer; }

/* ─── Responsive ──────────────────────────────────────────────────────────── */

@media (max-width: 1180px) {
    .tc-results-panel {
        min-width: 100% !important;
    }

    .tc-summary {
        flex: 1 1 100% !important;
        width: 100%;
    }
}

@media (max-width: 900px) {
    .tc-offer-header {
        grid-template-columns: minmax(0, 1fr) minmax(470px, 520px);
        column-gap: 14px;
    }

    .tc-offer-main {
        grid-template-columns: minmax(0, 1fr) minmax(470px, 520px);
        column-gap: 14px;
    }

    .tc-offer-right-block {
        grid-template-columns: 110px 120px minmax(230px, 1fr);
        column-gap: 10px;
    }
}

@media (max-width: 768px) {
    .tc-card {
        padding: 18px;
    }

    .tc-form-grid {
        grid-template-columns: 1fr;
    }

    .tc-service-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .tc-resources__grid {
        grid-template-columns: 1fr;
    }

    .tc-detail-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .tc-offer-header,
    .tc-offer-main {
        grid-template-columns: 1fr;
        align-items: stretch;
        row-gap: 10px;
    }

    .tc-offer-right-block {
        grid-template-columns: 1fr 1fr auto;
        column-gap: 12px;
        width: 100%;
    }

    .tc-col-price,
    .tc-col-saving {
        text-align: left;
    }
}

@media (max-width: 480px) {
    .tc-service-grid {
        grid-template-columns: 1fr;
    }

    .tc-detail-grid {
        grid-template-columns: 1fr;
    }

    .tc-offer-header {
        display: none !important;
    }

    .tc-offer-main {
        align-items: flex-start !important;
        flex-direction: column;
    }

    .tc-offer-right-block {
        grid-template-columns: 1fr auto;
        width: 100%;
        justify-content: space-between;
    }

    .tc-col-saving {
        display: none;
    }

    .tc-col-price {
        text-align: left;
    }
}
</style>
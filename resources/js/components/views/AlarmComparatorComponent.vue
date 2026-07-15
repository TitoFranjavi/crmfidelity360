<template>
    <transition name="slide-up" mode="out-in">
        <div class="min-h-100-vh w-100-max d-flex justify-center align-center py-50" data-bg="blanco" :key="currentView">

            <!-- FORMULARIO -->
            <div v-if="currentView === 'form'" class="dashboard-card w-700-px w-90-max">
                <transition name="step" mode="out-in">
                    <div :key="currentStep" class="d-flex column align-center w-100">

                        <!-- QUÉ QUIERE PROTEGER -->
                        <template v-if="currentStep === 'protectType'">
                            <p class="text" data-size="20" data-weight="600">
                                ¿Qué quiere proteger?
                            </p>
                            <p class="text mt-10 opacity-6 text-center" data-size="14">
                                Seleccione el tipo de inmueble para adaptar mejor la solución.
                            </p>
                            <div class="d-flex w-100 justify-center mt-20 wrap-mobile" data-gap="20">
                                <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="nextStep('Hogar')">
                                    <i class="far fa-house mr-10" data-size="16"></i>
                                    <p data-size="16" data-weight="400">Hogar</p>
                                </div>
                                <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="nextStep('Negocio')">
                                    <i class="far fa-briefcase mr-10" data-size="16"></i>
                                    <p data-size="16" data-weight="400">Negocio</p>
                                </div>
                            </div>
                        </template>

                        <!-- TIPO HOGAR -->
                        <template v-else-if="currentStep === 'homeType'">
                            <p class="text" data-size="20" data-weight="600">
                                ¿Qué tipo de vivienda es?
                            </p>
                            <p class="text mt-10 opacity-6 text-center" data-size="14">
                                Esto nos ayuda a adaptar mejor la instalación.
                            </p>
                            <div class="d-flex w-100 justify-center mt-20 wrap-mobile" data-gap="20">
                                <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="nextStep('Piso')">
                                    <i class="far fa-building mr-10" data-size="16"></i>
                                    <p data-size="16" data-weight="400">Piso</p>
                                </div>
                                <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="nextStep('Casa')">
                                    <i class="far fa-house-chimney mr-10" data-size="16"></i>
                                    <p data-size="16" data-weight="400">Casa</p>
                                </div>
                                <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="nextStep('Ático/Bajo')">
                                    <i class="far fa-layer-group mr-10" data-size="16"></i>
                                    <p data-size="16" data-weight="400">Ático / Bajo</p>
                                </div>
                            </div>
                            <div class="d-flex justify-center w-100 mt-20">
                                <div class="custom-button" data-size="small" @click="goBack">
                                    <i class="fa-solid fa-arrow-left mr-5"></i> Atrás
                                </div>
                            </div>
                        </template>

                        <!-- TIPO NEGOCIO -->
                        <template v-else-if="currentStep === 'businessType'">
                            <p class="text" data-size="20" data-weight="600">
                                ¿Qué tipo de negocio es?
                            </p>
                            <p class="text mt-10 opacity-6 text-center" data-size="14">
                                El tipo de local influye en la solución de seguridad recomendada.
                            </p>
                            <div class="d-flex w-100 justify-center mt-20 wrap-mobile" data-gap="20">
                                <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="nextStep('Oficina')">
                                    <i class="far fa-desktop mr-10" data-size="16"></i>
                                    <p data-size="16" data-weight="400">Oficina</p>
                                </div>
                                <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="nextStep('Comercio')">
                                    <i class="far fa-store mr-10" data-size="16"></i>
                                    <p data-size="16" data-weight="400">Comercio</p>
                                </div>
                                <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="nextStep('Nave')">
                                    <i class="far fa-warehouse mr-10" data-size="16"></i>
                                    <p data-size="16" data-weight="400">Nave</p>
                                </div>
                            </div>
                            <div class="d-flex justify-center w-100 mt-20">
                                <div class="custom-button" data-size="small" @click="goBack">
                                    <i class="fa-solid fa-arrow-left mr-5"></i> Atrás
                                </div>
                            </div>
                        </template>

                        <!-- TIENE ALARMA -->
                        <template v-else-if="currentStep === 'hasAlarm'">
                            <p class="text" data-size="20" data-weight="600">
                                ¿Tiene ya alarma?
                            </p>
                            <p class="text mt-10 opacity-6 text-center" data-size="14">
                                Así podremos saber si necesita una instalación nueva o una sustitución.
                            </p>
                            <div class="d-flex w-100 justify-center mt-20 wrap-mobile" data-gap="20">
                                <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="nextStep(true)">
                                    <i class="far fa-check mr-10" data-size="16"></i>
                                    <p data-size="16" data-weight="400">Sí</p>
                                </div>
                                <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="nextStep(false)">
                                    <i class="far fa-xmark mr-10" data-size="16"></i>
                                    <p data-size="16" data-weight="400">No</p>
                                </div>
                            </div>
                            <div class="d-flex justify-center w-100 mt-20">
                                <div class="custom-button" data-size="small" @click="goBack">
                                    <i class="fa-solid fa-arrow-left mr-5"></i> Atrás
                                </div>
                            </div>
                        </template>

                        <!-- MASCOTA -->
                        <template v-else-if="currentStep === 'hasPet'">
                            <p class="text" data-size="20" data-weight="600">
                                ¿Tiene mascota?
                            </p>
                            <p class="text mt-10 opacity-6 text-center" data-size="14">
                                Algunas alarmas necesitan configuración especial si hay animales en casa.
                            </p>
                            <div class="d-flex w-100 justify-center mt-20 wrap-mobile" data-gap="20">
                                <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="nextStep(true)">
                                    <i class="far fa-paw mr-10" data-size="16"></i>
                                    <p data-size="16" data-weight="400">Sí</p>
                                </div>
                                <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="nextStep(false)">
                                    <i class="far fa-xmark mr-10" data-size="16"></i>
                                    <p data-size="16" data-weight="400">No</p>
                                </div>
                            </div>
                            <div class="d-flex justify-center w-100 mt-20">
                                <div class="custom-button" data-size="small" @click="goBack">
                                    <i class="fa-solid fa-arrow-left mr-5"></i> Atrás
                                </div>
                            </div>
                        </template>

                        <!-- SUMINISTRO ELÉCTRICO -->
                        <template v-else-if="currentStep === 'electricity'">
                            <p class="text" data-size="20" data-weight="600">
                                ¿Tiene suministro eléctrico?
                            </p>
                            <p class="text mt-10 opacity-6 text-center" data-size="14">
                                Necesitamos saberlo para valorar la instalación correctamente.
                            </p>
                            <div class="d-flex w-100 justify-center mt-20 wrap-mobile" data-gap="20">
                                <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="nextStep(true)">
                                    <i class="far fa-plug mr-10" data-size="16"></i>
                                    <p data-size="16" data-weight="400">Sí</p>
                                </div>
                                <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="nextStep(false)">
                                    <i class="far fa-xmark mr-10" data-size="16"></i>
                                    <p data-size="16" data-weight="400">No</p>
                                </div>
                            </div>
                            <div class="d-flex justify-center w-100 mt-20">
                                <div class="custom-button" data-size="small" @click="goBack">
                                    <i class="fa-solid fa-arrow-left mr-5"></i> Atrás
                                </div>
                            </div>
                        </template>

                        <!-- URGENCIA -->
                        <template v-else-if="currentStep === 'urgency'">
                            <p class="text" data-size="20" data-weight="600">
                                ¿Urgencia de instalación?
                            </p>
                            <p class="text mt-10 opacity-6 text-center" data-size="14">
                                Indíquenos cuándo quiere instalar la alarma.
                            </p>
                            <div class="d-flex w-100 justify-center mt-20 wrap-mobile" data-gap="20">
                                <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="nextStep('Inmediata')">
                                    <i class="far fa-bolt mr-10" data-size="16"></i>
                                    <p data-size="16" data-weight="400">Inmediata</p>
                                </div>
                                <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="nextStep('Próximos días')">
                                    <i class="far fa-calendar-days mr-10" data-size="16"></i>
                                    <p data-size="16" data-weight="400">Próximos días</p>
                                </div>
                                <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="nextStep('Solo información')">
                                    <i class="far fa-circle-info mr-10" data-size="16"></i>
                                    <p data-size="16" data-weight="400">Solo información</p>
                                </div>
                            </div>
                            <div class="d-flex justify-center w-100 mt-20">
                                <div class="custom-button" data-size="small" @click="goBack">
                                    <i class="fa-solid fa-arrow-left mr-5"></i> Atrás
                                </div>
                            </div>
                        </template>

                        <!-- FORMULARIO FINAL -->
                        <template v-else-if="currentStep === 'lead'">
                            <p class="text" data-size="20" data-weight="600">
                                Complete sus datos
                            </p>
                            <p class="text mt-10 opacity-6 text-center" data-size="14">
                                Un asesor contactará con usted para ayudarle con la mejor solución.
                            </p>

                            <div class="separator" />

                            <div class="d-flex column form w-100 mt-20" data-gap="20" style="padding: 0 20px;">
                                <div class="form-group no-margin">
                                    <label>Nombre</label>
                                    <div class="input-group">
                                        <input v-model="inputData.name" type="text" placeholder="Ej. María García">
                                    </div>
                                </div>
                                <div class="form-group no-margin">
                                    <label>Teléfono</label>
                                    <div class="input-group">
                                        <input v-model="inputData.phone" type="tel" placeholder="Ej. 612 345 678">
                                    </div>
                                </div>
                                <div class="form-group no-margin">
                                    <label>Email</label>
                                    <div class="input-group">
                                        <input v-model="inputData.email" type="email" placeholder="Ej. maria@email.com">
                                    </div>
                                </div>
                                <div class="form-group no-margin">
                                    <label>Observaciones <span class="opacity-6">(opcional)</span></label>
                                    <div class="input-group">
                                        <textarea v-model="inputData.notes" rows="3" placeholder="Cualquier detalle adicional..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex w-100 justify-center mt-25 wrap-mobile" data-gap="20">
                                <div class="custom-button" data-size="small" @click="goBack">
                                    <i class="fa-solid fa-arrow-left mr-5"></i> Atrás
                                </div>
                                <div
                                    class="custom-button"
                                    data-size="regular"
                                    data-style="blue"
                                    :class="{ 'disabled-button': isSendingLead }"
                                    @click="submit"
                                >
                                    <span v-if="!isSendingLead">Solicitar asesoramiento</span>
                                    <span v-else class="loading-dots text opacity-5">
                                        Enviando<span>.</span><span>.</span><span>.</span>
                                    </span>
                                </div>
                            </div>
                        </template>

                        <!-- LOADING -->
                        <template v-else-if="currentStep === 'loading'">
                            <p class="text" data-size="20" data-weight="600">
                                Preparando su solicitud...
                            </p>
                            <p class="text mt-10 opacity-6 text-center" data-size="14">
                                {{ loadingMessages[currentMessage] }}
                            </p>
                            <div class="d-flex justify-center mt-30">
                                <div class="loader"></div>
                            </div>
                        </template>

                    </div>
                </transition>
            </div>

            <!-- CONFIRMACIÓN -->
            <div v-else-if="currentView === 'confirmation'" class="d-flex column align-center w-700-px w-90-max" data-gap="10">
                <i class="fas fa-check-circle" data-size="50" data-color="success"></i>
                <p class="text" data-size="30" data-weight="600">
                    Solicitud enviada correctamente
                </p>
                <p class="text mt-10 text-center" data-size="18">
                    Hemos recibido su solicitud. En breve uno de nuestros asesores se pondrá en contacto con usted.
                </p>
                <div class="custom-button mt-20" data-size="big" @click="resetComparator">
                    Hacer otra consulta
                </div>

                <!-- OTRAS ACCIONES -->
                <div class="w-100 mt-40">
                    <div class="separator mb-30" />
                    <p class="text text-center mb-20" data-size="18" data-weight="600">
                        ¿Qué más podemos hacer por usted?
                    </p>
                    <div class="otras-acciones-grid">
                        <a href="/comparatortelefonia" class="otras-acciones-card">
                            <div class="otras-acciones-icon">
                                <i class="far fa-mobile-screen-button" data-size="26"></i>
                            </div>
                            <p class="text" data-size="15" data-weight="600">Comparador de telefonía</p>
                            <p class="text opacity-6" data-size="13">Encuentra la mejor tarifa de móvil o fibra</p>
                        </a>
                        <a href="/comparator" class="otras-acciones-card">
                            <div class="otras-acciones-icon">
                                <i class="far fa-lightbulb" data-size="26"></i>
                            </div>
                            <p class="text" data-size="15" data-weight="600">Comparador de energía</p>
                            <p class="text opacity-6" data-size="13">Compara tarifas de luz y gas y ahorra</p>
                        </a>
                        <a href="/autoconsumo" class="otras-acciones-card">
                            <div class="otras-acciones-icon">
                                <i class="far fa-solar-panel" data-size="26"></i>
                            </div>
                            <p class="text" data-size="15" data-weight="600">Presupuesto autoconsumo</p>
                            <p class="text opacity-6" data-size="13">Solicita presupuesto para placas solares</p>
                        </a>
                        <a href="/reclamaciones" class="otras-acciones-card">
                            <div class="otras-acciones-icon">
                                <i class="far fa-file-circle-exclamation" data-size="26"></i>
                            </div>
                            <p class="text" data-size="15" data-weight="600">Reclamaciones</p>
                            <p class="text opacity-6" data-size="13">Gestiona una reclamación con tu compañía</p>
                        </a>
                    </div>
                </div>
            </div>
            <FloatingContactButtons />
        </div>
    </transition>
</template>

<script setup>
import { onBeforeUnmount, reactive, ref } from "vue";
import FloatingContactButtons from "../items/FloatingContactButtons.vue";

const referralUser = ref(new URLSearchParams(window.location.search).get("ref"));
const isSendingLead = ref(false);
const errors = reactive({});

const currentView = ref("form");
const currentStep = ref("protectType");

const inputData = reactive({
    protectType: "",
    propertyType: "",
    hasAlarm: null,
    hasPet: null,
    electricity: null,
    urgency: "",
    name: "",
    phone: "",
    email: "",
    notes: ""
});

const history = ref([]);
const currentMessage = ref(0);
let loadingTimer = null;
let loadingMessageTimer = null;

const loadingMessages = [
    "Analizando sus necesidades de seguridad...",
    "Preparando una recomendación personalizada...",
    "Recogiendo los datos para un asesor...",
    "Ya casi está listo..."
];

function pushHistory(step) {
    history.value.push(step);
}

function nextStep(value) {
    if (currentStep.value === "protectType") {
        inputData.protectType = value;
        pushHistory("protectType");
        currentStep.value = value === "Hogar" ? "homeType" : "businessType";
        return;
    }
    if (currentStep.value === "homeType") {
        inputData.propertyType = value;
        pushHistory("homeType");
        currentStep.value = "hasAlarm";
        return;
    }
    if (currentStep.value === "businessType") {
        inputData.propertyType = value;
        pushHistory("businessType");
        currentStep.value = "hasAlarm";
        return;
    }
    if (currentStep.value === "hasAlarm") {
        inputData.hasAlarm = value;
        pushHistory("hasAlarm");
        currentStep.value = inputData.protectType === "Hogar" ? "hasPet" : "electricity";
        return;
    }
    if (currentStep.value === "hasPet") {
        inputData.hasPet = value;
        pushHistory("hasPet");
        currentStep.value = "electricity";
        return;
    }
    if (currentStep.value === "electricity") {
        inputData.electricity = value;
        pushHistory("electricity");
        currentStep.value = "urgency";
        return;
    }
    if (currentStep.value === "urgency") {
        inputData.urgency = value;
        pushHistory("urgency");
        startLoading();
    }
}

function goBack() {
    const previous = history.value.pop();
    if (previous) currentStep.value = previous;
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
        currentStep.value = "lead";
    }, 2600);
}

function clearTimers() {
    if (loadingTimer) { clearTimeout(loadingTimer); loadingTimer = null; }
    if (loadingMessageTimer) { clearInterval(loadingMessageTimer); loadingMessageTimer = null; }
}

function resetComparator() {
    clearTimers();
    currentView.value = "form";
    currentStep.value = "protectType";
    history.value = [];
    Object.assign(inputData, {
        protectType: "", propertyType: "", hasAlarm: null,
        hasPet: null, electricity: null, urgency: "",
        name: "", phone: "", email: "", notes: ""
    });
}

async function submit() {
    if (isSendingLead.value) return;

    errors.name = "";
    errors.email = "";
    errors.phone = "";

    if (inputData.email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!emailRegex.test(inputData.email)) {
            errors.email = "Introduzca un email válido";
            return;
        }
    }

    if (inputData.phone) {
        const cleanedPhone = inputData.phone.replace(/\s+/g, "");

        if (cleanedPhone.length < 9) {
            errors.phone = "Introduzca un teléfono válido";
            return;
        }
    }

    isSendingLead.value = true;

    const formData = new FormData();

    formData.append("data", JSON.stringify({
        protectType: inputData.protectType,
        propertyType: inputData.propertyType,
        hasAlarm: inputData.hasAlarm,
        hasPet: inputData.hasPet,
        electricity: inputData.electricity,
        urgency: inputData.urgency,
        name: inputData.name,
        phone: inputData.phone,
        email: inputData.email,
        notes: inputData.notes,
    }));

    if (referralUser.value) {
        formData.append("ref", referralUser.value);
    }

    try {
        await axios.post(
            "/api/openComparator/registerAlarmOpportunity",
            formData,
            { headers: { "Content-Type": "multipart/form-data" } }
        );

        currentView.value = "confirmation";

    } catch (error) {
        console.error("Error backend:", error.response?.data);

        const backendMessage = error.response?.data?.message;

        if (typeof Swal !== "undefined") {
            Swal.fire({
                icon: "error",
                title: "Error al enviar la solicitud",
                text: backendMessage || "Ha ocurrido un error inesperado. Por favor inténtelo de nuevo.",
                confirmButtonText: "Aceptar"
            });
        } else {
            alert(backendMessage || "Ha ocurrido un error inesperado.");
        }

    } finally {
        isSendingLead.value = false;
    }
}
onBeforeUnmount(() => clearTimers());
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active { transition: all 0.4s ease; }
.slide-up-enter-from { transform: translateY(40px); opacity: 0; }
.slide-up-enter-to { transform: translateY(0); opacity: 1; }
.slide-up-leave-from { transform: translateY(0); opacity: 1; }
.slide-up-leave-to { transform: translateY(-40px); opacity: 0; }

.step-enter-active,
.step-leave-active { transition: all 0.35s ease; }
.step-enter-from { opacity: 0; transform: translateX(30px); }
.step-leave-to { opacity: 0; transform: translateX(-30px); }
.step-enter-to,
.step-leave-from { opacity: 1; transform: translateX(0); }

.wrap-mobile { flex-wrap: wrap; }

/* ========================= */
/* OTRAS ACCIONES            */
/* ========================= */

.otras-acciones-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.otras-acciones-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 8px;
    padding: 24px 16px;
    border-radius: 14px;
    border: 1px solid #D6E4FF;
    background: #FFFFFF;
    text-decoration: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease;
    cursor: pointer;
}

.otras-acciones-card:hover {
    border-color: #3B82F6;
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.12);
    transform: translateY(-2px);
}

.otras-acciones-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #EFF6FF;
    color: #1D4ED8;
    margin-bottom: 4px;
    font-size: 22px;
}

/* ========================= */
/* RESPONSIVE MÓVIL          */
/* ========================= */

@media (max-width: 768px) {
    .dashboard-card {
        padding: 20px;
    }
    .input-group input,
    .input-group textarea {
        padding: 12px;
        font-size: 16px;
    }
    .custom-button {
        min-height: 48px;
    }
    .otras-acciones-grid {
        grid-template-columns: 1fr;
    }
    .wrap-mobile {
        flex-wrap: wrap;
    }
}
</style>
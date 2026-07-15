<template>
    <section class="autoconsumo-page">
        <div class="autoconsumo-header">
            <div class="autoconsumo-badge">
            
                <span>Autoconsumo solar</span>
            </div>

            <h1 class="autoconsumo-title">
                Solicite su estudio de autoconsumo
            </h1>

            <p class="autoconsumo-description">
                Rellene el formulario y un gestor se pondrá en contacto con usted para
                preparar una propuesta adaptada a su vivienda o negocio.
            </p>
        </div>

        <div
            class="w-500-px w-90-max d-flex column align-center round p-20 justify-center request-card"
            data-gap="10"
            data-round="20"
            data-bg="blanco"
            data-border-color="principal"
        >
            <p class="text request-title" data-size="20" data-weight="600">
                Solicitar presupuesto
            </p>

            <p class="text w-90-max request-subtitle" data-size="12">
                Déjenos sus datos y un gestor se pondrá en contacto con usted para
                prepararle una propuesta de autoconsumo.
            </p>

            <form class="d-flex column form w-100 w-90-max" @submit.prevent="submitRequest">
                <div class="form-group no-margin">
                    <label>Nombre</label>
                    <div class="input-group">
                        <input
                            data-size="10"
                            v-model="formData.name"
                            type="text"
                            placeholder=""
                        >
                        <span v-if="errors.name" class="error">{{ errors.name }}</span>
                    </div>
                </div>

                <div class="form-group no-margin">
                    <label>Email</label>
                    <div class="input-group">
                        <input
                            data-size="10"
                            v-model="formData.email"
                            type="email"
                            placeholder=""
                        >
                        <span v-if="errors.email" class="error">{{ errors.email }}</span>
                    </div>
                </div>

                <div class="form-group no-margin">
                    <label>Teléfono</label>
                    <div class="input-group">
                        <input
                            data-size="10"
                            v-model="formData.phone"
                            type="tel"
                            placeholder=""
                        >
                        <span v-if="errors.phone" class="error">{{ errors.phone }}</span>
                    </div>
                </div>

                <div class="form-group no-margin">
                    <label>Factura <span class="optional-text">(opcional)</span></label>
                    <div class="input-group">
                        <label class="file-input-custom">
                            <input
                                type="file"
                                @change="handleFileUpload"
                                accept=".pdf,.jpg,.jpeg,.png"
                            >
                            <span v-if="!formData.invoice">Adjuntar factura</span>
                            <span v-else>{{ formData.invoice.name }}</span>
                        </label>
                        <span v-if="errors.invoice" class="error">{{ errors.invoice }}</span>
                    </div>
                </div>

                <div class="checkbox-row" @click="acceptedTerms = !acceptedTerms">
                    <input type="checkbox" v-model="acceptedTerms" @click.stop />
                    <span>
                        Acepto las
                        <a href="/terminos" target="_blank">condiciones de uso</a>.
                    </span>
                </div>

                <button
                    type="submit"
                    class="custom-button mt-20 submit-button"
                    data-size="medium"
                    :disabled="isSendingRequest || !acceptedTerms"
                    :class="{ 'disabled-button': isSendingRequest || !acceptedTerms }"
                >
                    <span v-if="!isSendingRequest">Enviar datos</span>

                    <span v-else class="loading-dots text opacity-5">
                        Cargando<span>.</span><span>.</span><span>.</span>
                    </span>
                </button>
            </form>

            <transition name="fade">
                <div v-if="successMessage" class="success-box">
                    <div class="success-icon">✓</div>
                    <div>
                        <p class="success-title">Solicitud enviada correctamente</p>
                        <p class="success-text">
                            Hemos recibido su petición. Un gestor se pondrá en contacto con usted.
                        </p>
                    </div>
                </div>
            </transition>

            <transition name="fade">
                <div v-if="errors.general" class="error-box">
                    {{ errors.general }}
                </div>
            </transition>

            <p class="opacity-9 w-90-max request-footer" data-size="10">
                Si dispone de factura, puede adjuntarla para que el estudio de autoconsumo
                sea más preciso, aunque no es obligatorio.
            </p>
        </div>
        <FloatingContactButtons />
    </section>
</template>

<script>

import FloatingContactButtons from '../items/FloatingContactButtons.vue';

export default {
    name: 'AutoconsumoRequestPage',
    components: {
        FloatingContactButtons,
    },
    data() {
    return {
        formData: {
            name: '',
            email: '',
            phone: '',
            invoice: null,
        },
        referralUser: new URLSearchParams(window.location.search).get('ref'),
        acceptedTerms: false,
        isSendingRequest: false,
        successMessage: '',
        errors: {},
    };
},  
    methods: {
        handleFileUpload(event) {
            const file = event.target.files[0] || null;
            this.formData.invoice = file;
            this.errors.invoice = '';
            this.errors.general = '';
        },

        validateForm() {
    this.errors = {};

    if (this.formData.email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!emailRegex.test(this.formData.email)) {
            this.errors.email = 'Introduzca un email válido';
        }
    }

    if (this.formData.phone) {
        const cleanedPhone = this.formData.phone.replace(/\s+/g, '');

        if (cleanedPhone.length < 9) {
            this.errors.phone = 'Introduzca un teléfono válido';
        }
    }

    if (this.formData.invoice) {
        const allowedTypes = [
            'application/pdf',
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp'
        ];

        if (!allowedTypes.includes(this.formData.invoice.type)) {
            this.errors.invoice = 'Solo se permiten PDF, JPG, PNG o WEBP';
        }
    }

    return Object.keys(this.errors).length === 0;
},      

async submitRequest() {
    if (!this.acceptedTerms) return;
    if (!this.validateForm()) return;
    if (this.isSendingRequest) return;

    this.isSendingRequest = true;
    this.successMessage = '';
    this.errors.general = '';

    const payload = new FormData();

    payload.append('data', JSON.stringify({
        name: this.formData.name,
        email: this.formData.email,
        phone: this.formData.phone,
    }));

    if (this.formData.invoice) {
        payload.append('invoice', this.formData.invoice);
    }

    if (this.referralUser) {
        payload.append('ref', this.referralUser);
    }

    try {
        const response = await axios.post(
            '/api/openComparator/registerAutoconsumoOpportunity',
            payload,
            {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            }
        );

        console.log('Oportunidad creada:', response.data);

        this.successMessage = 'Solicitud enviada correctamente';

        this.formData = {
            name: '',
            email: '',
            phone: '',
            invoice: null,
        };

        this.acceptedTerms = false;
        this.errors = {};

        setTimeout(() => {
            this.successMessage = '';
        }, 6000);

    } catch (error) {
        console.error('Error backend:', error.response?.data || error);

        const backendMessage = error.response?.data?.message;

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error al enviar la solicitud',
                text: backendMessage || 'Ha ocurrido un error inesperado. Por favor inténtelo de nuevo.',
                confirmButtonText: 'Aceptar'
            });
        } else {
            this.errors.general = backendMessage || 'Ha ocurrido un error inesperado. Por favor inténtelo de nuevo.';
        }

    } finally {
        this.isSendingRequest = false;
    }
},
    },
};
</script>

<style scoped>
.autoconsumo-page {
    min-height: 100vh;
    background: linear-gradient(180deg, #F7FAFF 0%, #EEF4FD 100%);
    padding: 40px 20px 60px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 26px;
}

.autoconsumo-header {
    text-align: center;
    max-width: 720px;
}

.autoconsumo-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 14px;
    border-radius: 999px;
    background: #EAF2FF;
    border: 1px solid #D6E4FF;
    color: #1D4ED8;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 14px;
}

.autoconsumo-title {
    margin: 0 0 10px;
    font-size: 32px;
    font-weight: 800;
    color: #0B3F91;
    line-height: 1.15;
}

.autoconsumo-description {
    margin: 0;
    font-size: 15px;
    color: #5D7295;
    line-height: 1.65;
}

.request-card {
    background: #FFFFFF;
    border: 1.5px solid #0B4EA2;
    border-radius: 24px;
    box-shadow: 0 14px 36px rgba(29, 78, 216, 0.08);
    padding: 28px 24px;
}

.request-title {
    color: #083F8C;
    font-size: 24px;
    font-weight: 700;
    text-align: center;
    margin: 0;
}

.request-subtitle {
    text-align: left;
    color: #173E84;
    line-height: 1.55;
    font-size: 13px;
    margin-bottom: 10px;
}

.form-group {
    margin-bottom: 14px;
    width: 100%;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #7C91B8;
    font-size: 13px;
    font-weight: 500;
}

.optional-text {
    color: #9BA8BF;
    font-weight: 400;
}

.input-group {
    width: 100%;
}

.input-group input[type="text"],
.input-group input[type="email"],
.input-group input[type="tel"] {
    width: 100%;
    height: 44px;
    border: 1px solid #D7DFEB;
    border-radius: 14px;
    background: #FFFFFF;
    padding: 0 14px;
    font-size: 14px;
    color: #1E3A5F;
    outline: none;
    box-sizing: border-box;
    transition: all 0.2s ease;
}

.input-group input[type="text"]:focus,
.input-group input[type="email"]:focus,
.input-group input[type="tel"]:focus {
    border-color: #A9C4F3;
    box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.08);
}

.file-input-custom {
    width: 100%;
    min-height: 44px;
    border: 1px solid #D7DFEB;
    border-radius: 14px;
    background: #FFFFFF;
    padding: 11px 14px;
    font-size: 13px;
    color: #6A7FA2;
    display: flex;
    align-items: center;
    cursor: pointer;
    box-sizing: border-box;
}

.file-input-custom input {
    display: none;
}

.checkbox-row {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-top: 10px;
    color: #083F8C;
    font-size: 13px;
    line-height: 1.5;
    cursor: pointer;
    user-select: none;
}

.checkbox-row input[type="checkbox"] {
    margin-top: 2px;
    cursor: pointer;
}

.checkbox-row a {
    color: #083F8C;
    text-decoration: none;
    font-weight: 500;
}

.checkbox-row a:hover {
    text-decoration: underline;
}

.submit-button {
    width: 100%;
    min-height: 48px;
    border-radius: 14px;
    background: #B3B3B3;
    border: none;
    color: #FFFFFF;
    font-weight: 700;
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.10);
}

.submit-button:not(:disabled) {
    background: linear-gradient(180deg, #FCD34D 0%, #FBBF24 100%);
    color: #173E84;
    box-shadow: 0 10px 22px rgba(251, 191, 36, 0.28);
}

.request-footer {
    text-align: left;
    color: #173E84;
    line-height: 1.6;
    margin-top: 10px;
    font-size: 12px;
}

.error {
    display: block;
    margin-top: 6px;
    color: #DC2626;
    font-size: 12px;
}

.loading-dots span {
    animation: blink 1.2s infinite;
}

.loading-dots span:nth-child(2) {
    animation-delay: 0.2s;
}

.success-box {
    width: 90%;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-top: 18px;
    padding: 14px;
    border-radius: 16px;
    background: #ECFDF5;
    border: 1px solid #A7F3D0;
    color: #065F46;
    box-sizing: border-box;
}

.success-icon {
    width: 28px;
    height: 28px;
    min-width: 28px;
    border-radius: 50%;
    background: #10B981;
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    font-weight: 800;
}

.success-title {
    margin: 0 0 3px;
    font-size: 13px;
    font-weight: 700;
    color: #065F46;
}

.success-text {
    margin: 0;
    font-size: 12px;
    line-height: 1.45;
    color: #047857;
}

.error-box {
    width: 90%;
    margin-top: 14px;
    padding: 12px 14px;
    border-radius: 14px;
    background: #FEF2F2;
    border: 1px solid #FECACA;
    color: #B91C1C;
    font-size: 13px;
    box-sizing: border-box;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease, transform 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}

.fade-enter-to,
.fade-leave-from {
    opacity: 1;
    transform: translateY(0);
}

.loading-dots span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes blink {
    0%, 20% { opacity: 0.25; }
    50% { opacity: 1; }
    100% { opacity: 0.25; }
}

@media (max-width: 768px) {
    .autoconsumo-page {
        padding: 24px 14px 40px;
        gap: 18px;
    }

    .autoconsumo-title {
        font-size: 26px;
    }

    .autoconsumo-description {
        font-size: 14px;
    }

    .request-card {
        padding: 22px 16px;
        border-radius: 20px;
    }

    .request-title {
        font-size: 20px;
    }

    .input-group input[type="text"],
    .input-group input[type="email"],
    .input-group input[type="tel"] {
        height: 46px;
        font-size: 16px;
    }

    .submit-button {
        min-height: 50px;
        font-size: 16px;
    }
}
</style>
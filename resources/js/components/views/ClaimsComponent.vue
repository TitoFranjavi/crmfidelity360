<template>
    <section class="claim-page">
        <div v-if="currentView === 'form'" class="d-flex column align-center w-100 claim-form-view" data-gap="26">
            <div class="claim-header">
                <div class="claim-badge">
                    <span>Reclamaciones</span>
                </div>

                <h1 class="claim-title">
                    Envíenos su reclamación
                </h1>

                <p class="claim-description">
                    Cuéntenos qué ha ocurrido y un gestor revisará su caso para ayudarle
                    lo antes posible.
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
                    Formulario de reclamación
                </p>

                <p class="text w-90-max request-subtitle" data-size="12">
                    Déjenos sus datos y explique brevemente el motivo de la reclamación.
                    Revisaremos su caso y nos pondremos en contacto con usted.
                </p>

                <form class="d-flex column form w-100 w-90-max" @submit.prevent="submitClaim">
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
                        <label>Tipo de reclamación</label>

                        <div class="option-grid">
                            <button
                                type="button"
                                class="option-card"
                                :class="{ active: formData.claimType === 'billing' }"
                                @click="formData.claimType = 'billing'"
                            >
                                Factura
                            </button>

                            <button
                                type="button"
                                class="option-card"
                                :class="{ active: formData.claimType === 'contract' }"
                                @click="formData.claimType = 'contract'"
                            >
                                Contrato
                            </button>

                            <button
                                type="button"
                                class="option-card"
                                :class="{ active: formData.claimType === 'supply' }"
                                @click="formData.claimType = 'supply'"
                            >
                                Suministro
                            </button>

                            <button
                                type="button"
                                class="option-card"
                                :class="{ active: formData.claimType === 'other' }"
                                @click="formData.claimType = 'other'"
                            >
                                Otro
                            </button>
                        </div>
                    </div>

                    <div class="form-group no-margin">
                        <label>Explique su reclamación</label>
                        <div class="input-group">
                            <textarea
                                v-model="formData.message"
                                rows="5"
                                placeholder="Indique aquí qué ha ocurrido, fechas, importes o cualquier dato que nos ayude a revisar su caso."
                            ></textarea>
                            <span v-if="errors.message" class="error">{{ errors.message }}</span>
                        </div>
                    </div>

                    <div class="checkbox-row" @click="acceptedTerms = !acceptedTerms">
                        <input type="checkbox" v-model="acceptedTerms" @click.stop />
                        <span>
                            Acepto las
                            <a href="/terminos" target="_blank">condiciones de uso</a>.
                        </span>
                    </div>

                    <transition name="fade">
                        <div v-if="errors.general" class="error-box">
                            {{ errors.general }}
                        </div>
                    </transition>

                    <button
                        type="submit"
                        class="custom-button mt-20 submit-button"
                        data-size="medium"
                        :disabled="isSendingClaim || !acceptedTerms"
                        :class="{ 'disabled-button': isSendingClaim || !acceptedTerms }"
                    >
                        <span v-if="!isSendingClaim">Enviar reclamación</span>

                        <span v-else class="loading-dots text opacity-5">
                            Enviando<span>.</span><span>.</span><span>.</span>
                        </span>
                    </button>
                </form>

                <p class="opacity-9 w-90-max request-footer" data-size="10">
                    Cuantos más detalles incluya, más fácil será revisar su caso y darle una respuesta precisa.
                </p>
            </div>
        </div>

        <div
            v-else-if="currentView === 'confirmation'"
            class="d-flex column align-center w-700-px w-90-max confirmation-box"
            data-gap="10"
        >
            <div class="confirmation-card">
                <div class="confirmation-icon">
                    <i class="fas fa-check"></i>
                </div>

                <p class="text confirmation-title" data-size="30" data-weight="600">
                    Reclamación enviada correctamente
                </p>

                <p class="text confirmation-text mt-10" data-size="18">
                    Hemos recibido su reclamación. En breve uno de nuestros gestores
                    revisará su caso y se pondrá en contacto con usted.
                </p>

                <div
                    class="custom-button mt-20 confirmation-button"
                    data-size="big"
                    @click="resetForm"
                >
                    Enviar otra reclamación
                </div>
            </div>
        </div>
        <FloatingContactButtons />
    </section>
</template>

<script>
import FloatingContactButtons from "../items/FloatingContactButtons.vue";
export default {
    name: 'ClaimsRequestPage',
    components: {
        FloatingContactButtons,
    },
    data() {
        return {
            currentView: 'form',
            formData: {
                name: '',
                email: '',
                phone: '',
                claimType: '',
                message: '',
            },
            referralUser: new URLSearchParams(window.location.search).get('ref'),
            acceptedTerms: false,
            isSendingClaim: false,
            errors: {},
        };
    },
    methods: {
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

            if (this.formData.message && this.formData.message.length < 10) {
                this.errors.message = 'Explique un poco más la reclamación';
            }

            return Object.keys(this.errors).length === 0;
        },

        async submitClaim() {
            if (!this.acceptedTerms) return;
            if (!this.validateForm()) return;
            if (this.isSendingClaim) return;

            this.isSendingClaim = true;
            this.errors.general = '';

            const payload = new FormData();

            payload.append('data', JSON.stringify({
                name: this.formData.name,
                email: this.formData.email,
                phone: this.formData.phone,
                claimType: this.formData.claimType,
                message: this.formData.message,
            }));

            if (this.referralUser) {
                payload.append('ref', this.referralUser);
            }

            try {
                const response = await axios.post(
                    '/api/openComparator/registerClaimOpportunity',
                    payload,
                    {
                        headers: {
                            'Content-Type': 'multipart/form-data',
                        },
                    }
                );

                console.log('Reclamación creada:', response.data);

                this.formData = {
                    name: '',
                    email: '',
                    phone: '',
                    claimType: '',
                    message: '',
                };

                this.acceptedTerms = false;
                this.errors = {};
                this.currentView = 'confirmation';

            } catch (error) {
                console.error('Error backend:', error.response?.data || error);

                const backendMessage = error.response?.data?.message;

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al enviar la reclamación',
                        text: backendMessage || 'Ha ocurrido un error inesperado. Por favor inténtelo de nuevo.',
                        confirmButtonText: 'Aceptar'
                    });
                } else {
                    this.errors.general = backendMessage || 'Ha ocurrido un error inesperado. Por favor inténtelo de nuevo.';
                }

            } finally {
                this.isSendingClaim = false;
            }
        },

        resetForm() {
            this.formData = {
                name: '',
                email: '',
                phone: '',
                claimType: '',
                message: '',
            };

            this.acceptedTerms = false;
            this.isSendingClaim = false;
            this.errors = {};
            this.currentView = 'form';
        },
    },
};
</script>

<style scoped>
.claim-page {
    min-height: 100vh;
    background: linear-gradient(180deg, #F7FAFF 0%, #EEF4FD 100%);
    padding: 40px 20px 60px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 26px;
}

.claim-form-view {
    max-width: 100%;
}

.claim-header {
    text-align: center;
    max-width: 720px;
}

.claim-badge {
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

.claim-title {
    margin: 0 0 10px;
    font-size: 32px;
    font-weight: 800;
    color: #0B3F91;
    line-height: 1.15;
}

.claim-description {
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

.input-group {
    width: 100%;
}

.input-group input[type="text"],
.input-group input[type="email"],
.input-group input[type="tel"],
.input-group textarea {
    width: 100%;
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

.input-group input[type="text"],
.input-group input[type="email"],
.input-group input[type="tel"] {
    height: 44px;
}

.input-group textarea {
    min-height: 120px;
    padding-top: 12px;
    padding-bottom: 12px;
    resize: vertical;
    line-height: 1.5;
    font-family: inherit;
}

.input-group input:focus,
.input-group textarea:focus {
    border-color: #A9C4F3;
    box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.08);
}

.option-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 9px;
}

.option-card {
    min-height: 42px;
    border: 1px solid #D7DFEB;
    border-radius: 14px;
    background: #FFFFFF;
    color: #173E84;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    padding: 10px 12px;
    transition: all 0.2s ease;
}

.option-card:hover {
    border-color: #A9C4F3;
    background: #F8FBFF;
}

.option-card.active {
    border-color: #1D4ED8;
    background: #EAF2FF;
    color: #083F8C;
    box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.08);
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
    background: linear-gradient(180deg, #60A5FA 0%, #2563EB 100%);
    color: #FFFFFF;
    box-shadow: 0 10px 22px rgba(37, 99, 235, 0.24);
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

.error-box {
    width: 100%;
    margin-top: 14px;
    padding: 12px 14px;
    border-radius: 14px;
    background: #FEF2F2;
    border: 1px solid #FECACA;
    color: #B91C1C;
    font-size: 13px;
    box-sizing: border-box;
}

.loading-dots span {
    animation: blink 1.2s infinite;
}

.loading-dots span:nth-child(2) {
    animation-delay: 0.2s;
}

.loading-dots span:nth-child(3) {
    animation-delay: 0.4s;
}

.confirmation-box {
    min-height: 460px;
    justify-content: center;
    text-align: center;
}

.confirmation-card {
    width: 100%;
    max-width: 640px;
    background: #FFFFFF;
    border: 1.5px solid #0B4EA2;
    border-radius: 24px;
    box-shadow: 0 14px 36px rgba(29, 78, 216, 0.08);
    padding: 42px 32px;
    box-sizing: border-box;
}

.confirmation-icon {
    width: 68px;
    height: 68px;
    margin: 0 auto 18px;
    border-radius: 50%;
    background: #ECFDF5;
    border: 1px solid #A7F3D0;
    color: #059669;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
}

.confirmation-title {
    color: #083F8C;
    text-align: center;
    margin: 0;
    line-height: 1.2;
}

.confirmation-text {
    color: #5D7295;
    text-align: center;
    line-height: 1.6;
    max-width: 520px;
    margin-left: auto;
    margin-right: auto;
}

.confirmation-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48px;
    padding: 0 24px;
    border-radius: 14px;
    background: linear-gradient(180deg, #60A5FA 0%, #2563EB 100%);
    color: #FFFFFF;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 10px 22px rgba(37, 99, 235, 0.24);
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

@keyframes blink {
    0%, 20% {
        opacity: 0.25;
    }

    50% {
        opacity: 1;
    }

    100% {
        opacity: 0.25;
    }
}

@media (max-width: 768px) {
    .claim-page {
        padding: 24px 14px 40px;
        gap: 18px;
    }

    .claim-title {
        font-size: 26px;
    }

    .claim-description {
        font-size: 14px;
    }

    .request-card {
        padding: 22px 16px;
        border-radius: 20px;
    }

    .request-title {
        font-size: 20px;
    }

    .option-grid {
        grid-template-columns: 1fr;
    }

    .input-group input[type="text"],
    .input-group input[type="email"],
    .input-group input[type="tel"] {
        height: 46px;
        font-size: 16px;
    }

    .input-group textarea {
        font-size: 16px;
    }

    .submit-button {
        min-height: 50px;
        font-size: 16px;
    }

    .confirmation-card {
        padding: 32px 20px;
        border-radius: 20px;
    }

    .confirmation-title {
        font-size: 24px;
    }

    .confirmation-text {
        font-size: 15px;
    }

    .confirmation-button {
        width: 100%;
    }
}
</style>
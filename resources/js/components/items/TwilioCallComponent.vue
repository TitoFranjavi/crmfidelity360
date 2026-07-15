<template>
    <div v-if="visible" class="iphone-widget">

        <button class="iphone-close" @click="close">✕</button>

        <div class="iphone-top">
            <p class="iphone-status-text">{{ statusText }}</p>
            <p class="iphone-name">{{ name || phone }}</p>
            <p class="iphone-timer" v-if="isActive">{{ timerDisplay }}</p>
            <p class="iphone-rec" v-if="isActive">⏺ Grabando</p>
        </div>

        <!-- Pantalla idle: solo botón llamar centrado -->
        <div v-if="!isActive && !isCalling" class="iphone-idle">
            <div class="iphone-btn-wrap">
                <button class="iphone-btn iphone-btn-green" @click="startCall">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="white">
                        <path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/>
                    </svg>
                </button>
                <span class="iphone-btn-label">Llamar</span>
            </div>
        </div>

        <!-- Grid de botones estilo iPhone en llamada -->
        <div v-if="isActive || isCalling" class="iphone-grid">

            <div class="iphone-btn-wrap">
                <button class="iphone-btn iphone-btn-gray" :class="{ active: muted }" @click="toggleMute">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="white">
                        <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm-1-9c0-.55.45-1 1-1s1 .45 1 1v6c0 .55-.45 1-1 1s-1-.45-1-1V5zm6 6c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                    </svg>
                </button>
                <span class="iphone-btn-label">{{ muted ? 'Activar' : 'Silenciar' }}</span>
            </div>

            <div class="iphone-btn-wrap">
                <button class="iphone-btn iphone-btn-red" @click="hangUp">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="white" style="transform: rotate(135deg)">
                        <path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/>
                    </svg>
                </button>
                <span class="iphone-btn-label">Finalizar</span>
            </div>

            <div class="iphone-btn-wrap">
                <button class="iphone-btn iphone-btn-gray">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="white">
                        <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/>
                    </svg>
                </button>
                <span class="iphone-btn-label">Altavoz</span>
            </div>

        </div>

        <p class="iphone-msg">{{ message }}</p>

    </div>
</template>

<script>
import { Device } from '@twilio/voice-sdk'

export default {
    name: 'TwilioCallComponent',
    props: ['phone', 'name', 'id', 'isOrder', 'basicData'],
    emits: ['close'],
    data() {
        return {
            visible:   true,
            isCalling: false,
            isActive:  false,
            muted:     false,
            message:   'Iniciando...',
            seconds:   0,
            interval:  null
            // _device y _activeCall NO van aquí para evitar el Proxy de Vue 3
        }
    },

    computed: {
        timerDisplay() {
            const m = String(Math.floor(this.seconds / 60)).padStart(2, '0')
            const s = String(this.seconds % 60).padStart(2, '0')
            return `${m}:${s}`
        },
        statusText() {
            if (this.isCalling) return 'Llamando a móvil...'
            if (this.isActive)  return 'En llamada'
            return 'Listo para llamar'
        }
    },

    async mounted() {
        // Inicializar fuera del sistema reactivo de Vue
        this._device     = null
        this._activeCall = null

        try {
            const res  = await fetch('/api/twilio/voice-token')
            const data = await res.json()
            console.log('Token recibido:', data.token ? 'OK' : 'NO')

            this._device = new Device(data.token, { logLevel: 1 })

            this._device.on('error', (error) => {
                console.error('Device error:', error)
                this.message = 'Error del dispositivo: ' + error.message
            })

            await this._device.register()
            console.log('Device registrado correctamente')
            this.message = ''
        } catch (e) {
            console.error('Error en mounted:', e)
            this.message = 'Error al inicializar: ' + e.message
        }
    },

    beforeUnmount() {
        if (this._device) this._device.unregister()
        clearInterval(this.interval)
    },

    methods: {
        async canStartCall() {
            try {
                const response = await axios.get('/api/twilio/availableCallMinutes', {
                    params: {
                        enterpriseId: this.basicData?.subdomainEnterprise?._id,
                        orderId: this.id
                    }
                });

                if (response.data.available) return true;

                Swal.fire({
                    icon: 'error',
                    title: 'Sin minutos disponibles',
                    text: 'No dispone de minutos de llamadas disponibles. Compre un pack de minutos para poder realizar llamadas.',
                    confirmButtonText: 'Vale'
                });

                return false;

            } catch (error) {
                console.log(error);

                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo comprobar el saldo',
                    text: 'Inténtelo de nuevo en unos segundos.',
                    confirmButtonText: 'Vale'
                });

                return false;
            }
        },
        async startCall() {

            const canStart = await this.canStartCall();
            if (!canStart) return;

            if (!this._device) {
                this.message = 'Dispositivo no inicializado'
                return
            }
            this.isCalling = true
            this.message   = ''
            try {
                console.log('Intentando llamar a:', this.phone)
                this._activeCall = await this._device.connect({
                    params: { To: this.phone, id: this.id, isOrder: this.isOrder, enterpriseId: this.basicData.subdomainEnterprise?._id }
                })
                console.log('Llamada iniciada')

                this._activeCall.on('accept', () => {
                    console.log('Llamada aceptada')
                    this.isActive  = true
                    this.isCalling = false
                    this.interval  = setInterval(() => this.seconds++, 1000)
                })
                this._activeCall.on('disconnect', () => {
                    console.log('Llamada desconectada')
                    this.resetState()
                })
                this._activeCall.on('error', (error) => {
                    console.error('Error en la llamada:', error)
                    this.message   = 'Error: ' + error.message
                    this.isCalling = false
                })
            } catch (e) {
                console.error('Error al iniciar llamada:', e)
                this.message   = 'Error al llamar: ' + e.message
                this.isCalling = false
            }
        },
        hangUp() {
            if (this._activeCall) this._activeCall.disconnect()
        },
        toggleMute() {
            if (!this._activeCall) return
            this.muted = !this.muted
            this._activeCall.mute(this.muted)
        },
        resetState() {
            this.isActive  = false
            this.isCalling = false
            this.muted     = false
            this.message   = 'Llamada finalizada'
            clearInterval(this.interval)
            this.seconds   = 0
            this._activeCall = null
        },
        close() {
            if (this._activeCall) this._activeCall.disconnect()
            if (this._device) this._device.unregister()
            this.$emit('close')
        }
    }
}
</script>

<style scoped>
    .iphone-widget.iphone-widget {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 99999;
        width: 300px;
        background: #1a1208;
        border-radius: 44px;
        padding: 28px 20px 36px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        border: 1px solid rgba(255,255,255,0.06);
    }

    /* Reset global interno */
    .iphone-widget * {
        box-sizing: border-box;
    }

    .iphone-widget p {
        margin: 0;
    }

    /* BOTONES COMPLETAMENTE LIMPIOS */
    .iphone-widget .iphone-btn {
        all: unset;
        width: 72px;
        height: 72px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: opacity 0.15s, transform 0.1s;
    }

    .iphone-widget .iphone-btn:active {
        transform: scale(0.93);
        opacity: 0.8;
    }

    .iphone-widget .iphone-btn-green { background: #30d158; }
    .iphone-widget .iphone-btn-red   { background: #ff3b30; }
    .iphone-widget .iphone-btn-gray  { background: rgba(255,255,255,0.15); }
    .iphone-widget .iphone-btn-gray.active { background: rgba(255,255,255,0.4); }

    .iphone-close {
        all: unset;
        position: absolute;
        top: 16px;
        right: 20px;
        background: rgba(255,255,255,0.1);
        color: rgba(255,255,255,0.6);
        border-radius: 50%;
        width: 26px;
        height: 26px;
        cursor: pointer;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .iphone-top {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        margin-top: 8px;
        margin-bottom: 32px;
    }

    .iphone-status-text {
        font-size: 15px;
        color: rgba(255,255,255,0.55);
    }

    .iphone-name {
        font-size: 28px;
        font-weight: 600;
        color: #fff;
    }

    .iphone-timer {
        font-size: 15px;
        color: rgba(255,255,255,0.55);
        margin-top: 4px;
    }

    .iphone-rec {
        font-size: 12px;
        color: #ff453a;
    }

    .iphone-idle {
        display: flex;
        justify-content: center;
        margin: 16px 0 32px;
    }

    .iphone-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px 12px;
        width: 100%;
        padding: 0 8px;
        margin-bottom: 8px;
    }

    .iphone-btn-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }

    .iphone-btn-label {
        font-size: 12px;
        color: rgba(255,255,255,0.65);
        text-align: center;
    }

    .iphone-msg {
        font-size: 12px;
        color: rgba(255,255,255,0.25);
        margin-top: 4px;
        min-height: 16px;
    }
</style>

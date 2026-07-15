<template>
    <div class="chat-widget">

        <!-- Burbuja flotante -->
        <button
            class="chat-bubble"
            @click="toggleChat"
            :class="{ 'chat-bubble--open': isOpen }"
            title="Asistente Zoco Energía"
        >
            <i v-if="!isOpen" class="fas fa-comment-dots"></i>
            <i v-else class="fas fa-times"></i>
            <span v-if="!isOpen" class="chat-bubble__label">Asistente</span>
        </button>

        <!-- Ventana de chat -->
        <transition name="chat-slide">
            <div v-if="isOpen" class="chat-window">

                <!-- Header -->
                <div class="chat-header">
                    <div class="chat-header__info">
                        <div class="chat-header__avatar">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div>
                            <p class="chat-header__name">Asistente Zoco</p>
                            <p class="chat-header__status">
                                <span class="chat-header__dot"></span>
                                Disponible
                            </p>
                        </div>
                    </div>
                    <button class="chat-header__close" @click="toggleChat">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Mensajes -->
                <div class="chat-messages" ref="messagesContainer">

                    <!-- Mensaje de bienvenida -->
                    <div v-if="messages.length === 0" class="chat-welcome">
                        <i class="fas fa-bolt chat-welcome__icon"></i>
                        <p class="chat-welcome__title">¡Hola! Soy tu asistente</p>
                        <p class="chat-welcome__text">Pregúntame sobre clientes, contratos, oportunidades o cualquier duda sobre el CRM de Zoco Energía.</p>
                    </div>

                    <!-- Historial de mensajes -->
                    <div
                        v-for="(msg, idx) in messages"
                        :key="idx"
                        class="chat-message"
                        :class="msg.role === 'user' ? 'chat-message--user' : 'chat-message--assistant'"
                    >
                        <div v-if="msg.role === 'assistant'" class="chat-message__avatar">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="chat-message__bubble">
                            <p class="chat-message__text" v-html="formatMessage(msg.content)"></p>
                        </div>
                    </div>

                    <!-- Indicador "Escribiendo..." -->
                    <div v-if="isTyping" class="chat-message chat-message--assistant chat-message--typing">
                        <div class="chat-message__avatar">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="chat-message__bubble">
                            <span class="typing-dot"></span>
                            <span class="typing-dot"></span>
                            <span class="typing-dot"></span>
                        </div>
                    </div>
                </div>

                <!-- Input -->
                <div class="chat-input-area">
                    <textarea
                        v-model="inputText"
                        ref="inputRef"
                        class="chat-input"
                        placeholder="Escribe tu pregunta..."
                        rows="1"
                        @keydown.enter.exact.prevent="sendMessage"
                        @keydown.shift.enter="null"
                        @input="autoResize"
                        :disabled="isTyping"
                    ></textarea>
                    <button
                        class="chat-send-btn"
                        @click="sendMessage"
                        :disabled="isTyping || !inputText.trim()"
                    >
                        <i v-if="isTyping" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-paper-plane"></i>
                    </button>
                </div>

            </div>
        </transition>
    </div>
</template>

<script>
export default {
    name: 'ChatWidget',
    props: ['basicData'],

    data() {
        return {
            isOpen: false,
            isTyping: false,
            inputText: '',
            messages: [],
        };
    },

    methods: {
        toggleChat() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.$nextTick(() => {
                    this.$refs.inputRef?.focus();
                    this.scrollToBottom();
                });
            }
        },

        async sendMessage() {
            const text = this.inputText.trim();
            if (!text || this.isTyping) return;

            this.messages.push({ role: 'user', content: text });
            this.inputText = '';
            this.isTyping = true;

            this.$nextTick(() => {
                this.scrollToBottom();
                if (this.$refs.inputRef) {
                    this.$refs.inputRef.style.height = 'auto';
                }
            });

            try {
                const res = await axios.post('/api/chat', {
                    messages: this.messages,
                });

                if (res.data.ok) {
                    this.messages.push({ role: 'assistant', content: res.data.message });
                } else {
                    this.messages.push({
                        role: 'assistant',
                        content: 'Lo siento, ha ocurrido un error. Por favor inténtalo de nuevo.',
                    });
                }
            } catch (err) {
                this.messages.push({
                    role: 'assistant',
                    content: 'No he podido conectar con el servidor. Verifica tu conexión e inténtalo de nuevo.',
                });
            } finally {
                this.isTyping = false;
                this.$nextTick(() => this.scrollToBottom());
            }
        },

        scrollToBottom() {
            const container = this.$refs.messagesContainer;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },

        autoResize(e) {
            const el = e.target;
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 120) + 'px';
        },

        formatMessage(text) {
            return text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/\n/g, '<br>');
        },
    },
};
</script>

<style scoped>
/* ── Widget contenedor ── */
.chat-widget {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 12px;
}

/* ── Burbuja flotante ── */
.chat-bubble {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #8B1010;
    color: #fff;
    border: none;
    border-radius: 50px;
    padding: 14px 20px;
    font-size: 18px;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(139, 16, 16, 0.4);
    transition: all 0.2s ease;
}
.chat-bubble:hover {
    background: #a01212;
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(139, 16, 16, 0.5);
}
.chat-bubble--open {
    border-radius: 50%;
    padding: 14px;
}
.chat-bubble__label {
    font-size: 14px;
    font-weight: 600;
}

/* ── Ventana de chat ── */
.chat-window {
    width: 360px;
    height: 520px;
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 8px 40px rgba(0, 0, 0, 0.15);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid #f0f0f0;
}

/* ── Header ── */
.chat-header {
    background: #8B1010;
    color: #fff;
    padding: 16px 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}
.chat-header__info {
    display: flex;
    align-items: center;
    gap: 12px;
}
.chat-header__avatar {
    width: 38px;
    height: 38px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}
.chat-header__name {
    font-weight: 700;
    font-size: 14px;
    margin: 0;
}
.chat-header__status {
    font-size: 11px;
    opacity: 0.85;
    display: flex;
    align-items: center;
    gap: 5px;
    margin: 2px 0 0;
}
.chat-header__dot {
    width: 6px;
    height: 6px;
    background: #4ade80;
    border-radius: 50%;
    display: inline-block;
}
.chat-header__close {
    background: none;
    border: none;
    color: #fff;
    font-size: 16px;
    cursor: pointer;
    opacity: 0.8;
    padding: 4px;
    transition: opacity 0.2s;
}
.chat-header__close:hover { opacity: 1; }

/* ── Mensajes ── */
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: #f9f9f9;
}
.chat-messages::-webkit-scrollbar { width: 4px; }
.chat-messages::-webkit-scrollbar-track { background: transparent; }
.chat-messages::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }

/* ── Bienvenida ── */
.chat-welcome {
    text-align: center;
    padding: 24px 16px;
    color: #666;
}
.chat-welcome__icon {
    font-size: 32px;
    color: #8B1010;
    margin-bottom: 12px;
    display: block;
}
.chat-welcome__title {
    font-weight: 700;
    font-size: 15px;
    color: #333;
    margin: 0 0 8px;
}
.chat-welcome__text {
    font-size: 13px;
    line-height: 1.5;
    margin: 0;
}

/* ── Burbuja de mensaje ── */
.chat-message {
    display: flex;
    align-items: flex-end;
    gap: 8px;
}
.chat-message--user {
    flex-direction: row-reverse;
}
.chat-message__avatar {
    width: 28px;
    height: 28px;
    background: #8B1010;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    flex-shrink: 0;
}
.chat-message__bubble {
    max-width: 75%;
    padding: 10px 14px;
    border-radius: 16px;
    font-size: 13px;
    line-height: 1.5;
}
.chat-message--user .chat-message__bubble {
    background: #8B1010;
    color: #fff;
    border-bottom-right-radius: 4px;
}
.chat-message--assistant .chat-message__bubble {
    background: #fff;
    color: #333;
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}
.chat-message__text { margin: 0; word-break: break-word; }

/* ── Indicador de escritura ── */
.chat-message--typing .chat-message__bubble {
    display: flex;
    gap: 4px;
    align-items: center;
    padding: 12px 16px;
}
.typing-dot {
    width: 7px;
    height: 7px;
    background: #aaa;
    border-radius: 50%;
    animation: typing-bounce 1.2s infinite;
}
.typing-dot:nth-child(2) { animation-delay: 0.2s; }
.typing-dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes typing-bounce {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-6px); }
}

/* ── Input ── */
.chat-input-area {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    padding: 12px 14px;
    background: #fff;
    border-top: 1px solid #f0f0f0;
    flex-shrink: 0;
}
.chat-input {
    flex: 1;
    border: 1px solid #e5e5e5;
    border-radius: 12px;
    padding: 10px 14px;
    font-size: 13px;
    resize: none;
    outline: none;
    font-family: inherit;
    line-height: 1.5;
    max-height: 120px;
    transition: border-color 0.2s;
    background: #f9f9f9;
}
.chat-input:focus {
    border-color: #8B1010;
    background: #fff;
}
.chat-input:disabled { opacity: 0.6; cursor: not-allowed; }
.chat-send-btn {
    width: 38px;
    height: 38px;
    background: #8B1010;
    color: #fff;
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    cursor: pointer;
    flex-shrink: 0;
    transition: all 0.2s;
}
.chat-send-btn:hover:not(:disabled) {
    background: #a01212;
    transform: scale(1.05);
}
.chat-send-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* ── Animación de entrada ── */
.chat-slide-enter-active,
.chat-slide-leave-active {
    transition: all 0.25s ease;
}
.chat-slide-enter-from,
.chat-slide-leave-to {
    opacity: 0;
    transform: translateY(16px) scale(0.97);
}
</style>

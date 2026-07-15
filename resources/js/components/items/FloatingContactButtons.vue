<template>
    <div
        v-if="contact.phone || contact.whatsapp"
        class="floating-contact"
    >
        <a
            v-if="contact.phone"
            class="floating-contact-button floating-contact-button-phone"
            :href="phoneHref"
            aria-label="Llamar por teléfono"
        >
            <span class="floating-contact-icon">☎</span>
            <span>¿Te ayudamos?</span>
        </a>

        <a
            v-if="contact.whatsapp"
            class="floating-contact-button floating-contact-button-whatsapp"
            :href="whatsappHref"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="Escribir por WhatsApp"
        >
            <i class="fa-brands fa-whatsapp"></i>
            <span>WhatsApp</span>
        </a>
    </div>
</template>

<script>
export default {
    name: 'FloatingContactButtons',

    data() {
        return {
            contact: {
                phone: null,
                whatsapp: null,
            },

            defaultContact: {
                phone: '653062438',
                whatsapp: '653062438',
            },
        };
    },

    computed: {
        phoneHref() {
            return `tel:${this.normalizePhone(this.contact.phone)}`;
        },

        whatsappHref() {
            const number = this.normalizePhone(this.contact.whatsapp).replace('+', '');

            return `https://wa.me/${number}`;
        },
    },

    mounted() {
        this.loadFloatingContact();
    },

    methods: {
        async loadFloatingContact() {
            const ref = this.getRefFromUrl();

            if (ref) {
                localStorage.setItem('open_comparator_ref', ref);
            }

            const savedRef = ref || localStorage.getItem('open_comparator_ref');

            const isWitroPage = window.location.pathname === '/cargadordecoche';

            console.log(savedRef, isWitroPage)

            if (savedRef === null && isWitroPage) {
                this.contact = {
                    phone:    '641279582',
                    whatsapp: '641279582',
                };
                return;
            }

            try {
                const query = savedRef ? `?ref=${encodeURIComponent(savedRef)}` : '';

                const response = await fetch(`/api/openComparator/floatingcontact${query}`, {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                    },
                });

                const result = await response.json();

                if (result.success && result.data) {
                    this.contact = {
                        phone: result.data.phone || this.defaultContact.phone,
                        whatsapp: result.data.whatsapp || result.data.phone || this.defaultContact.whatsapp,
                    };

                    return;
                }

                this.contact = { ...this.defaultContact };
            } catch (error) {
                console.error('Error cargando contacto flotante:', error);

                this.contact = { ...this.defaultContact };
            }


            console.log('REF DETECTADO EN FLOATING:', ref);
        },

        getRefFromUrl() {
            const params = new URLSearchParams(window.location.search);

            return params.get('ref');
        },

        normalizePhone(phone) {
            if (!phone) {
                return '';
            }

            const cleaned = String(phone)
                .replace(/\s+/g, '')
                .replace(/[()-]/g, '');

            if (cleaned.startsWith('+')) {
                return cleaned;
            }

            if (cleaned.startsWith('00')) {
                return `+${cleaned.substring(2)}`;
            }

            return `+34${cleaned}`;
        },
    },
};
</script>

<style scoped>
.floating-contact {
    position: fixed;
    right: 24px;
    bottom: 24px;
    z-index: 999999;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 10px;
    pointer-events: auto;
}

.floating-contact-button {
    min-height: 52px;
    padding: 0 22px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: #FFFFFF;
    text-decoration: none;
    font-size: 15px;
    font-weight: 700;
    box-shadow: 0 10px 26px rgba(0, 0, 0, 0.16);
    transition: all 0.2s ease;
    white-space: nowrap;
}

.floating-contact-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 30px rgba(0, 0, 0, 0.20);
}

.floating-contact-button-phone {
    background: linear-gradient(135deg, #FF8A65 0%, #FF9F7A 100%);
}

.floating-contact-button-whatsapp {
    background: linear-gradient(135deg, #25D366 0%, #1EAE56 100%);
}

.floating-contact-icon {
    font-size: 18px;
    line-height: 1;
}

@media (max-width: 768px) {
    .floating-contact {
        right: 14px;
        bottom: 14px;
    }

    .floating-contact-button {
        width: 52px;
        height: 52px;
        min-height: 52px;
        padding: 0;
        border-radius: 50%;
    }

    .floating-contact-button span:not(.floating-contact-icon) {
        display: none;
    }

    .floating-contact-icon {
        font-size: 22px;
    }
}
</style>
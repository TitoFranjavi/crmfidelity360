<template>
    <div class="d-flex column justify-center w-100 align-center p-30 min-h-100-vh relPos" data-bg="blanco">
        <img :src="'/assets/enterprises/zocoenergia/logos/mini-dark.png'" class="absPos right mt-20 ml-20 w-100-px pointer" style="top:0" @click="actionLink('/')">

        <!--Seleccionar plan-->
        <div v-if="section === 0">
            <!-- Encabezado -->
            <header class="text-center">
                <h2 class="mt-20" data-size="30">Elige el plan que encaja contigo</h2>
            </header>

            <!--Toggle mensual/anual-->
            <div class="toggle-button my-40">
                <span :class="{ active: !isAnnual }">Mensual</span>

                <button type="button" class="toggle-switch" :class="{ annual: isAnnual }" @click="isAnnual = !isAnnual" aria-label="Cambiar facturación">
                    <span class="toggle-ball"></span>
                </button>

                <span :class="{ active: isAnnual }">Anual</span>
            </div>


            <!-- Grid de planes -->
            <div class="d-flex justify-center" data-gap="60" v-if="basicData && basicData.zocoPlans">
                <!--Planes-->
                <div v-for="plan in basicData.zocoPlans" class="subscription-card pointer" :class="[priceSelected === plan.name ? 'selected' : '']" @click="selectPrice(plan.name)">

                    <h1 class="text-center">{{ plan.name }}</h1>

                    <div class="price text-center">
                        <transition name="price" mode="out-in">
                            <div :key="isAnnual + plan.name" class="price-inner">
                                <span class="amount">€{{ plan[isAnnual ? 'annualPrice': 'price'] }}</span>
                                <span class="period">/{{ isAnnual ? 'año' : 'mes' }}</span>
                            </div>
                        </transition>
                    </div>

                    <div class="features">
                        <!--Incluye-->
                        <div v-for="included in plan.included"><i class="fa-regular fa-check"></i> {{ included.title }}</div>

                        <!--No incluye-->
                        <div v-for="notIncluded in plan.notIncluded" class="opacity-5">- {{ notIncluded.title }}</div>
                    </div>
                </div>
            </div>

            <p class="mt-25 text-center opacity-6">Precios sin IVA. Sujeto a condiciones comerciales.</p>

            <div v-if="priceSelected" class="mx-auto text-center custom-button mt-20 w-400-px" data-size="regular" data-style="gradient" @click="stripeCheckout"><!--@click="section = 1"-->
                Contratar
            </div>
        </div>

        <!--Confirmar seleccion-->
        <div v-if="section === 1" class="d-flex justify-center w-100 align-center min-h-100-vh" data-bg="blanco">

            <div class="d-grid w-80" data-column="2" data-gap="50">

                <!--Plan seleccionado-->
                <div class="d-flex column">
                    <p class="text mb-5" data-size="35" data-weight="700">Confirma tu selección</p>
                    <p class="mb-50" data-size="11" data-weight="300">Revise los detalles de su suscripción antes de completar la activación.</p>

                    <div v-if="priceSelectedInfo" class="subscription-card pointer selected" @click="selectPrice(priceSelectedInfo.name)">

                        <h1 class="text-center">{{ priceSelectedInfo.name }}</h1>

                        <div class="price text-center">
                            <transition name="price" mode="out-in">
                                <div :key="isAnnual + priceSelectedInfo.name" class="price-inner">
                                    <span class="amount">€{{ priceSelectedInfo[isAnnual ? 'annualPrice': 'price'] }}</span>
                                    <span class="period">/{{ isAnnual ? 'año' : 'mes' }}</span>
                                </div>
                            </transition>
                        </div>

                        <div class="features">
                            <!--Incluye-->
                            <div v-for="included in priceSelectedInfo.included"><i class="fa-regular fa-check"></i> {{ included.title }}</div>

                            <!--No incluye-->
                            <div v-for="notIncluded in priceSelectedInfo.notIncluded" class="opacity-5">- {{ notIncluded.title }}</div>
                        </div>
                    </div>
                </div>

                <!---->
                <div>
                    <div class="p-24 round" data-bg="gris-principal" data-round="15">
                        <p data-size="28" data-weight="600">Datos de pago</p>

                        <div class="d-flex justify-center align-center p-20 mt-50 round" data-bg="blanco" data-round="15" data-gap="25">

                            <i data-size="25" data-color="azul"  class="far fa-building-columns my-auto"></i>

                            <div class="w-100">
                                <div data-bg="blanco" data-round="15">
                                    <p data-size="11" data-weight="400">NÚMERO DE CUENTA BANCARIA</p>
                                </div>
                                <div class="form" v-if="!ibanSaved || isEditing || iban">
                                    <div class="form-group mt-0">
                                        <div class="input-group d-flex align-center" data-size="15">
                                            <input v-model="iban" @input="formatIBAN" maxlength="29" data-size="15" placeholder="ES00 0000 0000 0000 0000 0000" type="text">
                                        </div>
                                        <!--<span v-if="orderToModify.errors.asercordDecommision" class="error">{{orderToModify.errors.asercordDecommision }}</span>-->
                                    </div>
                                </div>

                                <div v-else>
                                    <p data-spacing="3" data-weight="700">{{ maskedIBAN }}</p>
                                </div>
                            </div>

                            <!--Cambiar-->
                            <div v-if="ibanSaved && !isEditing && !iban">
                                <p class="pointer" data-color="azul" @click="isEditing = true">Cambiar</p>
                            </div>

                            <!--Cancelar-->
                            <div v-if="ibanSaved && (isEditing || iban)">
                                <p class="pointer" data-color="rojo" @click="iban = null; isEditing = false">Cancelar</p>
                            </div>

                        </div>


                        <div class="mt-20 p-20 round" data-bg="azul-claro" data-round="15">
                            <i class="fas fa-badge-check"></i> <span data-weight="600">Grado bancario de encriptación de 256-bit.</span> Tus datos están siendo manejados con una precisión profesional.
                        </div>

                        <button @click="checkout" v-if="!isLoading" class="custom-button w-100 mt-50" data-size="regular" data-style="gradient">Confirma tu suscripción</button>
                        <button v-else class="custom-button w-100 mt-50" data-size="regular" data-style="gradient"><span class="loading-dots text" data-color="blanco">Cargando<span>.</span><span>.</span><span>.</span></span></button>

                        <p class="mt-20 text-center opacity-5" data-size="10" data-weight="300">
                            Al confirmar la suscripción, el cargo de <strong>{{ priceSelectedInfo[isAnnual ? 'annualPrice' : 'price'] }}€</strong> será realizado en su cuenta. La suscripción se renovará automáticamente salvo cancelación.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "PricingComponent",
    props: ["basicData"],
    data(){
        return {
            section: 0,
            freeSubdomains: ['65cb57489c2c285441086a43', '67a4b5f728300393f408ff32'],
            priceSelected: 'Pro',
            isAnnual: false,
            iban: '',
            ibanSaved: null,
            isEditing: false,
            isLoading: false
        }
    },
    watch: {
        'basicData.userSubdomain': {
            handler(val) {
                if (val?._id && !this.ibanSaved) {
                    this.checkHasIBAN();
                }
            },
            immediate: true
        }
    },
    methods:{
        selectPrice(price){
            this.priceSelected = price;
        },
        async checkout(){

            if (this.isLoading) return;

            // VALIDACIÓN IBAN
            if ((!this.iban && !this.ibanSaved) || (this.iban && !this.validateIBAN(this.iban))) {
                Swal.fire({
                    icon: "error",
                    title: "IBAN inválido",
                    text: "Por favor, introduzca un IBAN correcto.",
                    confirmButtonText: "Entendido"
                });
                return;
            }

            if(this.basicData.userLogged.label === 'Usuario subdominio' && !this.basicData.userLogged.subscription){

                this.isLoading = true;

                try {
                    await axios.post('/api/enterprise/getSubscription', {
                        plan: this.priceSelectedInfo,
                        isAnnual: this.isAnnual,
                        iban: !this.iban
                            ? this.ibanSaved.replace(/\s+/g, '')
                            : this.iban.replace(/\s+/g, ''),
                        userSubdomain: this.basicData.userSubdomain
                    });

                    Swal.fire({
                        icon: 'success',
                        text: '¡La suscripción ha sido completada!',
                        timer: 2500,
                        timerProgressBar: true
                    }).then(() => {
                        this.$router.push('/profile');
                    });

                } catch (err) {
                    console.log(err);
                } finally {
                    this.isLoading = false;
                }

            } else {
                Swal.fire({
                    icon: "info",
                    title: "Ya tienes una suscripción con Zoco Energía.",
                    confirmButtonText: "Vale"
                })
            }
        },
        async stripeCheckout(){
            if (this.isLoading) return;

            if(this.basicData.userLogged.label === 'Usuario subdominio' && !this.basicData.userLogged.subscription){

                this.isLoading = true;

                try {

                    await axios.post('/api/stripe/checkout', {
                        plan: this.priceSelectedInfo.id,
                        isAnnual: this.isAnnual,
                        enterprise: this.basicData.subdomainEnterprise,
                    })
                        .then((res) => {
                            window.location.href = res.data.url;
                        })

                } catch (err) {
                    console.log(err);
                } finally {
                    this.isLoading = false;
                }

            } else {
                Swal.fire({
                    icon: "info",
                    title: "Ya tienes una suscripción con Zoco Energía.",
                    confirmButtonText: "Vale"
                })
            }
        },
        validateIBAN() {
            if (!this.iban) return false;

            let iban = this.iban.replace(/\s+/g, '').toUpperCase();

            // estructura básica
            if (!/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/.test(iban)) {
                return false;
            }

            // opcional: validar longitud España
            if (iban.startsWith('ES') && iban.length !== 24) {
                return false;
            }

            // mover los 4 primeros al final
            const rearranged = iban.slice(4) + iban.slice(0, 4);

            // convertir letras a números
            const numericIBAN = rearranged
                .split('')
                .map(char => isNaN(char) ? char.charCodeAt(0) - 55 : char)
                .join('');

            // módulo 97
            let remainder = numericIBAN;
            while (remainder.length > 2) {
                remainder = (parseInt(remainder.slice(0, 9), 10) % 97) + remainder.slice(9);
            }

            return parseInt(remainder, 10) % 97 === 1;
        },
        formatIBAN() {
            // quitar espacios y poner mayúsculas
            let value = this.iban.replace(/\s+/g, '').toUpperCase();

            // solo letras y números
            value = value.replace(/[^A-Z0-9]/g, '');

            // limitar longitud (IBAN máx 24 en España, 34 internacional)
            value = value.substring(0, 34);

            // separar cada 4 caracteres
            value = value.match(/.{1,4}/g)?.join(' ') || '';

            this.iban = value;
        },
        async checkHasIBAN() {
            await axios.get('/api/enterprise/checkHasIBAN', { params: { userSubdomain: this.basicData.userSubdomain._id } })
                .then((res) => {
                    this.ibanSaved = res.data.iban;
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        actionLink(route){
            this.$router.push(route);
        },
    },
    computed: {
        priceSelectedInfo() {
            if (!this.priceSelected || !this.basicData.zocoPlans) return null;

            return this.basicData.zocoPlans.find(plan => plan.name === this.priceSelected);
        },
        maskedIBAN() {
            if (!this.ibanSaved) return '';

            const iban = this.ibanSaved.replace(/\s+/g, '');

            // últimos 4 dígitos
            const last4 = iban.slice(-4);

            return '**** **** **** ' + last4;
        }
    }
}
</script>

<style></style>

<template>
    <div class="login">

        <!--Header-->
        <div class="d-flex align-center p-30 image-header relPos" :data-bg="basicData.enterprise.color">
            <div class="img-div">
                <img class="general-icon" data-size="big" data-style="square" data-weight="500" :src="'/assets/enterprises/' + basicData.enterprise.asset_folder + '/logos/logo-light.png'" alt="Imagen CRM">
            </div>

            <div class="bottom-info">

                <a class="opacity-7 my-5" :href="'https://' + basicData.enterprise.secondaryWeb" target="_blank" data-color="blanco">{{ basicData.enterprise.secondaryWeb }}</a>

                <a class="opacity-7 my-5" data-color="blanco" :href="'mailto:' + basicData.enterprise.email">{{ basicData.enterprise.email }}</a>
            </div>
        </div>

        <!--Contenido-->
        <div class="content">

            <!--Imagen CRM-->
            <div class="logo-top">
                <img :src="'/assets/enterprises/' + basicData.enterprise.asset_folder + '/logos/mini-dark.png'" alt="Logo CRM">

                <div class="my-auto ml-10 text" data-size="22" data-weight="600">{{ basicData.enterprise.name }}</div>
            </div>

            <!--Titulo-->
            <div class="px-30 py-60">

                <p class="text" data-size="25" data-weight="600">Bienvenido</p>

                <p class="text" data-size="15">Registrate mediante el código que te han proporcionado</p>
            </div>


            <!--Formulario checkear código-->
            <form v-if="!isCodeValidated" v-on:submit.prevent="checkCode" class="form px-30">

                <!--Input código-->
                <div v-bind:class="{ wrong: errors.key}" class="form-group">
                    <div class="input-code my-30 dFlex flex-column" v-bind:class="{ wrong: errors.key}">
                        <div class="d-flex justify-between" v-for="i in 3">
                            <input
                                class="d-flex w-90"
                                :id="'key'+ i"
                                :data-index="i - 1"
                                type="text"
                                v-on:focus="delete errors.key"
                                v-on:paste="pasteCodeEvent"
                                v-on:keydown="keyPressInput"
                            >
                            <p class="text my-auto ml-10" v-if="i < 3">-</p>
                        </div>
                    </div>

                    <span v-if="errors.key" class="error">{{ errors.key }}</span>
                </div>


                <!--Info abajo-->
                <div class="infoDown mt-50">

                    <!--Recuperar contraseña-->
                    <div class="remember d-flex" data-align="center">
                        <router-link to="/portal" class="ml-auto" data-style="underline" data-color="principal" data-size="13">Volver a iniciar sesión</router-link>
                    </div>

                    <!--Botón iniciar sesión/registrar-->
                    <button class="my-20 custom-button w-100" data-size="big" type="submit">
                        Comprobar código
                    </button>
                </div>
            </form>

            <!--Formulario registrar usuario-->
            <form v-if="isCodeValidated" v-on:submit.prevent="createUser" class="form px-30" autocomplete="off">

                <!--Nombre-->
                <div v-bind:class="{ wrong: errors.firstName}" class="form-group">
                    <div class="input-group" data-style="line">
                        <input
                            v-on:focus="delete errors['firstName']"
                            data-size="12"
                            v-model="user.firstName"
                            type="text"
                            name="firstName"
                            autocomplete="given-name"
                            placeholder="Nombre"
                        >
                    </div>
                    <span v-if="errors.firstName" class="error">{{ errors.firstName }}</span>
                </div>

                <!--Apellidos-->
                <div v-bind:class="{ wrong: errors.lastName}" class="form-group">
                    <div class="input-group" data-style="line">
                        <input
                            v-on:focus="delete errors['lastName']"
                            data-size="12"
                            v-model="user.lastName"
                            type="text"
                            name="lastName"
                            autocomplete="family-name"
                            placeholder="Apellidos"
                        >
                    </div>
                    <span v-if="errors.lastName" class="error">{{ errors.lastName }}</span>
                </div>

                <!--Email-->
                <div v-bind:class="{ wrong: errors.email}" class="form-group">
                    <div class="input-group" data-style="line">
                        <input
                            v-on:focus="delete errors['email']"
                            data-size="12"
                            v-model="user.email"
                            type="email"
                            name="email"
                            autocomplete="email"
                            placeholder="Correo"
                        >
                    </div>
                    <span v-if="errors.email" class="error">{{ errors.email }}</span>
                </div>

                <!--Telefono-->
                <div v-bind:class="{ wrong: errors.phone}" class="form-group">
                    <div class="input-group" data-style="line">
                        <input
                            v-on:focus="delete errors['phone']"
                            data-size="12"
                            v-model="user.phone"
                            type="text"
                            name="phone"
                            autocomplete="tel"
                            placeholder="Telefono"
                        >
                    </div>
                    <span v-if="errors.phone" class="error">{{ errors.phone }}</span>
                </div>

                <!--Dni-->
                <div v-bind:class="{ wrong: errors.dni}" class="form-group">
                    <div class="input-group" data-style="line">
                        <input
                            v-on:focus="delete errors['dni']"
                            data-size="12"
                            v-model="user.dni"
                            type="text"
                            name="dni"
                            autocomplete="off"
                            placeholder="DNI/CIF"
                        >
                    </div>
                    <span v-if="errors.dni" class="error">{{ errors.dni }}</span>
                </div>

                <!--Género-->
                <div v-bind:class="{ wrong: errors.gender}" class="form-group">
                    <div class="input-group" data-style="line">
                        <select
                            v-model="user.gender"
                            v-on:focus="delete errors.gender"
                            name="gender"
                            autocomplete="off"
                        >
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                            <option value="O">Otro</option>
                        </select>
                    </div>
                    <span v-if="errors.gender" class="error">{{ errors.gender }}</span>
                </div>

                <!--Dirección-->
                <div v-bind:class="{ wrong: errors.address}" class="form-group">
                    <div class="input-group" data-style="line">
                        <input
                            v-on:focus="delete errors['address']"
                            data-size="12"
                            v-model="user.address"
                            type="text"
                            name="address"
                            autocomplete="street-address"
                            placeholder="Dirección"
                        >
                    </div>
                    <span v-if="errors.address" class="error">{{ errors.address }}</span>
                </div>

                <!--Código postal-->
                <div v-bind:class="{ wrong: errors.postal}" class="form-group">
                    <div class="input-group" data-style="line">
                        <input
                            v-on:focus="delete errors['postal']"
                            data-size="12"
                            v-model="user.postal"
                            type="text"
                            name="postal"
                            autocomplete="postal-code"
                            placeholder="Código postal"
                        >
                    </div>
                    <span v-if="errors.postal" class="error">{{ errors.postal }}</span>
                </div>

                <!--Provincia-->
                <div v-bind:class="{ wrong: errors.province}" class="form-group">
                    <div class="input-group" data-style="line">
                        <input
                            v-on:focus="delete errors['province']"
                            data-size="12"
                            v-model="user.province"
                            type="text"
                            name="province"
                            autocomplete="address-level1"
                            placeholder="Provincia"
                        >
                    </div>
                    <span v-if="errors.province" class="error">{{ errors.province }}</span>
                </div>

                <!--Población-->
                <div v-bind:class="{ wrong: errors.locality}" class="form-group">
                    <div class="input-group" data-style="line">
                        <input
                            v-on:focus="delete errors['locality']"
                            data-size="12"
                            v-model="user.locality"
                            type="text"
                            name="locality"
                            autocomplete="off"
                            placeholder="Población"
                        >
                    </div>
                    <span v-if="errors.locality" class="error">{{ errors.locality }}</span>
                </div>

                <!--Contraseña-->
                <div v-bind:class="{ wrong: errors.password}" class="form-group">
                    <div class="input-group" data-style="line">
                        <input
                            v-on:focus="delete errors['password']"
                            data-size="12"
                            v-model="user.password"
                            :type="seePassword ? 'text' : 'password'"
                            name="new-user-password"
                            autocomplete="new-password"
                            placeholder="Contraseña"
                        >

                        <!--Boton ver contraseña-->
                        <div v-on:click="seePassword = !seePassword">
                            <i class="text fas pointer" :class="seePassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </div>
                    </div>
                    <span v-if="errors.password" class="error">{{ errors.password }}</span>
                </div>

                <!--Repito contraseña-->
                <div v-bind:class="{ wrong: errors.repeatPassword}" class="form-group">
                    <div class="input-group" data-style="line">
                        <input
                            v-on:focus="delete errors['repeatPassword']"
                            data-size="12"
                            v-model="user.repeatPassword"
                            :type="seePassword ? 'text' : 'password'"
                            name="new-user-repeat-password"
                            autocomplete="new-password"
                            placeholder="Repite la contraseña"
                        >
                    </div>
                    <span v-if="errors.repeatPassword" class="error">{{ errors.repeatPassword }}</span>
                </div>

                <!--Aceptación tratamiento de datos-->
                <div class="d-flex my-5">
                    <div class="custom-checkbox my-auto mr-10" v-on:click="dataTreatment = !dataTreatment">
                        <div v-bind:class="{ selected: dataTreatment }"></div>
                    </div>

                    <p class="my-auto mr-15" data-color="principal">
                        Doy mi consentimiento para el tratamiento de mis datos personales
                    </p>
                </div>

                <!--Info abajo-->
                <div class="infoDown mt-50">

                    <!--Recuperar contraseña-->
                    <div class="remember d-flex" data-align="center">
                        <router-link
                            to="/portal/recover"
                            class="ml-auto"
                            data-style="underline"
                            data-color="principal"
                            data-size="13"
                        >
                            Volver a iniciar sesión
                        </router-link>
                    </div>

                    <!--Botón iniciar sesión/registrar-->
                    <button class="my-20 custom-button w-100" data-size="big" type="submit">
                        Crear usuario
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>

<script>
export default {
    name: "PortalRegisterComponent",
    props:['basicData'],
    data(){
        return{
            isCodeValidated: false,
            key:'',
            serial: '',
            user:{
                firstName: '',
                lastName: '',
                gender: 'M',
                profileImage: 'default.jpg',
                email: '',
                dni: '',
                phone: '',
                address: '',
                postal: '',
                province: '',
                locality: '',
                password: '',
                repeatPassword: ''
            },
            seePassword: false,
            errors:{
                key:''
            },
            dataTreatment: false
        }
    },
    methods:{
        checkCode(){
            axios.get(`/api/serial/checkKey/${this.key}`)
                .then((res) => {
                    this.isCodeValidated = true;

                    this.serial = res.data.serial;
                })
                .catch((err) => {
                    this.errors.key = err.response.data.message;
                })
        },
        async createUser(){

            //Validaciones
            let hasErrors = false;

                if (!this.dataTreatment) {
                    await Swal.fire({
                        icon: 'error',
                        text: 'Para proceder con la creación del usuario primero debes aceptar el consentimiento de datos',
                        timerProgressBar: true,
                        timer: 2500
                    });

                    return;
                }

                //Nombre
                if (this.user.firstName === ''){
                    this.errors.firstName = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                //Apellidos
                if (this.user.lastName === ''){
                    this.errors.lastName = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }


                //Email
                if (this.user.email === ''){
                    this.errors.email = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                let regexEmail = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;

                if ( this.user.email && !regexEmail.test(this.user.email)){
                    this.errors.email = this.getErrorMessage('malformedEmail');
                    hasErrors = true;
                }


                //Telefono
                if (this.user.phone === ''){
                    this.errors.phone = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                if (this.user.phone.length > 9){
                    this.errors.phone = this.getErrorMessage('malformedPhone');
                    hasErrors = true;
                }

                //Dni
                if (this.user.dni === ''){
                    this.errors.dni = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                /*let regexDNI = /^\d{8}[A-Z]$/;

                if ( this.user.dni && !regexDNI.test(this.user.dni)){
                    this.errors.dni = 'El DNI esta mal formado';
                    hasErrors = true;
                }*/


                //Dirección
                if (this.user.address === ''){
                    this.errors.address = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                //Código postal
                if (this.user.postal === ''){
                    this.errors.postal = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                if (this.user.postal && isNaN(this.user.postal)){
                    this.errors.postal = 'El campo tiene que ser de tipo númerico';
                    hasErrors = true;
                }

                //Contraseña
                if (this.user.password === ''){
                    this.errors.password = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                //Repetir contraseña
                if (this.user.repeatPassword === ''){
                    this.errors.repeatPassword = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                if (this.user.password !== this.user.repeatPassword){
                    this.errors.repeatPassword = 'Las contraseñas no coinciden';
                    hasErrors = true;
                }


            if(!hasErrors){

                let data = new FormData();

                data.append('user', JSON.stringify(this.user));
                data.append('serial', JSON.stringify(this.serial));

                await axios.post(`/api/user/withKey`, data)
                    .then((res) => {

                        //Inicio sesión con el usuario
                        axios.post(`/api/auth`, {emailOrPhone: this.user.phone, password: this.user.password})
                            .then((res) => {
                                this.$router.push('/')
                            })
                            .catch((err) => {
                                console.log(err)
                            })

                    })
                    .catch((err) => {
                        this.errors = err.response.data.errors;
                    })

            }
        },
        // Helper: leer el valor real de los inputs y reconstruir this.key
        syncKey() {
            this.key = [0, 1, 2].map(i => $(`[data-index="${i}"]`).val()).join('');
        },
        pasteCodeEvent(e) {
            e.preventDefault();
            let text = e.clipboardData.getData('text').replaceAll(/[\n\s]/g, '').toUpperCase();

            // Aceptar con o sin guiones
            let regex = /^([A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}|[A-Z0-9]{12})$/;
            if (!regex.test(text)) return;

            // Quitar guiones y dividir en grupos de 4
            text = text.replaceAll('-', '');
            const parts = [text.slice(0, 4), text.slice(4, 8), text.slice(8, 12)];

            parts.forEach((part, index) => {
                $(`[data-index="${index}"]`).val(part);
            });

            $(`[data-index="2"]`).focus();
            this.syncKey();
        },
        keyPressInput(e) {
            const currentInput = $(e.target);
            const index = parseInt(currentInput.data('index'));
            const keyCode = e.keyCode || e.which;

            // Paste via teclado — dejar que el evento paste lo maneje
            if ((e.metaKey || e.ctrlKey) && e.key === 'v') {
                return; // no preventDefault, el evento paste se dispara solo
            }

            // Borrar
            if (keyCode === 8) {
                e.preventDefault();
                const val = currentInput.val();
                if (val.length > 0) {
                    currentInput.val(val.slice(0, -1));
                } else if (index > 0) {
                    const prevInput = $(`[data-index="${index - 1}"]`);
                    prevInput.val(prevInput.val().slice(0, -1)).focus();
                }
                this.syncKey();
                return;
            }

            // Solo permitir alfanuméricos
            const isNumber = keyCode >= 48 && keyCode <= 57;
            const isUpperLetter = keyCode >= 65 && keyCode <= 90;
            const isLowerLetter = keyCode >= 97 && keyCode <= 122;

            if (!isNumber && !isUpperLetter && !isLowerLetter) {
                e.preventDefault();
                return;
            }

            e.preventDefault();
            const char = e.key.toUpperCase(); // normalizar a mayúsculas
            const currentVal = currentInput.val();

            if (currentVal.length < 4) {
                currentInput.val(currentVal + char);
            }

            // Si el input está lleno, pasar al siguiente
            if (currentInput.val().length >= 4 && index < 2) {
                $(`[data-index="${index + 1}"]`).focus();
            }

            this.syncKey();
        },
        getErrorMessage(type){

            let message = '';

            switch (type){
                case 'isEmpty':
                    message = 'Este campo no puede estar vacio'
                    break;

                case 'malformedEmail':
                    message = 'El email esta mal formado';
                    break;

                case 'malformedPhone':
                    message = 'El número de telefono esta mal formado';
                    break;

                default:
                    message = 'Hay errores en el formulario'
                    break;
            }

            return message;
        },
    },
    computed:{

    }
}
</script>

<style scoped>

</style>

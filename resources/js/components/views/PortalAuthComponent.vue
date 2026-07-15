<template>
    <div class="login" v-if="basicData.enterprise">

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

                <p class="text" data-size="25" data-weight="600">Bienvenido de vuelta</p>

                <p class="text" data-size="15">Entra y disfruta de todas nuestras herramientas</p>
            </div>


            <!--Formulario-->
            <form v-on:submit.prevent="login" class="form px-30">

                <!--Email o telefono-->
                <div v-bind:class="{ wrong: errors.emailOrPhone}" class="form-group">
                    <div class="input-group" data-style="line">
                        <input v-on:focus="delete errors['emailOrPhone']" data-size="12" v-model="credentials.emailOrPhone" type="text" name="emailOrPhone" placeholder="Email o teléfono">
                    </div>
                    <span v-if="errors.emailOrPhone" class="error">{{ errors.emailOrPhone }}</span>
                </div>

                <!--Password-->
                <div v-bind:class="{ wrong: errors.password}" class="form-group">
                    <div class="input-group" data-style="line">
                        <input v-model="credentials.password" data-size="15" v-on:focus="delete errors['password']" type="password"
                               name="password"
                               placeholder="Contraseña">
                        <p v-on:click="toggleInput"
                           class="toggle-input">
                            <span class="message-icon"><i class="far fa-eye"></i></span>
                        </p>
                    </div>
                    <span v-if="errors.password" class="error">{{ errors.password }}</span>
                </div>


                <!--Botón recordar-->
                <div class="remember d-flex justify-between">
                    <div class="d-flex my-auto">
                        <input type="checkbox" v-model="credentials.remember" class="mr-10">

                        <div class="text" data-size="11" data-weight="600">Recuerdame durante 30 días</div>
                    </div>

                    <!--<div class="down" data-align="center">
                        <router-link to="/portal/recover" data-style="underline" data-size="11">Olvidé mis datos</router-link>
                    </div>-->
                </div>



                <!--Info abajo-->
                <div class="infoDown mt-50">

                    <!--Recuperar contraseña-->
                    <div class="remember d-flex justify-between" data-align="center">
                        <router-link to="/portal/register" data-style="underline" data-color="principal" data-size="13">Registro</router-link>

                        <router-link to="/portal/recover" data-style="underline" data-color="principal" data-size="13">Olvidé mis datos</router-link>
                    </div>

                    <!--Botón iniciar sesión/registrar-->
                    <button class="my-20 custom-button w-100" data-size="big" :data-bg="basicData.enterprise.color" data-color="blanco" type="submit">
                        Iniciar sesión
                    </button>

                    <!--Demo-->
                    <!--<div data-align="center">
                        <router-link to="/portal/recover">
                            <p class="text" data-size="13">¿Quieres una demo? <span data-weight="600">Solicítala desde aquí</span></p>
                        </router-link>
                    </div>-->
                </div>

                <p class="text-center" v-show="basicData && basicData.enterprise"> <a
        :href="'/politica-privacidad'"
        target="_blank"
        class="text"
        data-size="12"
        data-style="underline"
        data-color="principal"
    >
        Política de privacidad
    </a></p>
            </form>

        </div>

    </div>
</template>

<script>
export default {
    name: "PortalAuthComponent",
    props:['basicData'],
    data(){
        return{
            credentials: {
                emailOrPhone:'',
                password: '',
                remember: false
            },
            errors: {}
        }
    },
    methods:{
        login(){

            this.errors = {};

            //Email o telefono
            if (this.credentials.emailOrPhone === ''){
                this.errors.emailOrPhone = this.getErrorMessage('isEmpty');
            }

            let regexEmail = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;

            if (isNaN(this.credentials.emailOrPhone) && !regexEmail.test(this.credentials.emailOrPhone)){
                this.errors.emailOrPhone = this.getErrorMessage('malformedEmail');

            }else if(!isNaN(this.credentials.emailOrPhone) && this.credentials.emailOrPhone.length !== 9){
                console.log('es un número de telefono y esta malformado')
                this.errors.emailOrPhone = this.getErrorMessage('malformedPhone');
            }

            //Contraseña
            if (this.credentials.password === ''){
                this.errors.password = this.getErrorMessage('isEmpty');
            }


            //Si no hay errores
            if (Object.keys(this.errors).length === 0){

                axios.post('/api/auth', {...this.credentials, enterprise: this.basicData?.enterprise?._id})
                    .then((res) => {
                        this.$router.push('/')
                    })
                    .catch((err) => {

                        const swalConfig = {
                            icon: 'error',
                            title: err.response.data.info,
                        };

                        if (this.basicData?.enterprise?.subdomainUser === '65cb57489c2c285441086a43') {
                            swalConfig.html = err.response.data.message;
                        }

                        Swal.fire(swalConfig);
                    })

            }





        },
        toggleInput({target}) {
            let that = $(target.closest('.toggle-input'));
            let input = that.closest('.input-group').find('input');
            let variables = {
                type: 'text',
                message: 'Ocultar',
                icon: 'fa-eye-slash'
            };

            if (input.attr('type') === variables.type) {
                variables.type = 'password';
                variables.message = 'Mostrar';
                variables.icon = 'fa-eye';
            }

            input.attr('type', variables.type);
            that.find('.message-icon i').remove('i');
            that.find('.message-icon').append(`<i class="far ${variables.icon}"></i>`);
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
        }
    }
}
</script>
<style scoped>
@media (max-width: 768px) {


    .image-header .general-icon {
        width: 100px !important;
        max-width: 80vw;
        height: auto !important;
        object-fit: contain;
    }


}
</style>

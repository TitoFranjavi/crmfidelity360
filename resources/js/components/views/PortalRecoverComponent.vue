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

                <p class="text" data-size="25" data-weight="600">Recupera tu contraseña</p>

                <p class="text" data-size="15">Introduce tu correo o tu teléfono y te enviaremos un código con el que recuperarla</p>
            </div>

            <!--Formulario enviar codigo-->
            <form v-on:submit.prevent="sendCode" class="form px-30">

                <!--Email o telefono-->
                <div v-bind:class="{ wrong: emailOrPhoneError}" class="form-group">
                    <div class="input-group" data-style="line">
                        <input v-on:focus="emailOrPhoneError = ''" v-model="emailOrPhone" type="text" name="emailOrPhone"
                               placeholder="Email o teléfono">
                    </div>
                    <span v-if="emailOrPhoneError" class="error">{{ emailOrPhoneError }}</span>
                </div>


                <!--Info abajo-->
                <div class="mt-50">

                    <!--Botón iniciar sesión/registrar-->
                    <button class="my-20 custom-button w-100" data-size="big" type="submit">
                        Enviar código
                    </button>

                    <!--Demo-->
                    <div data-align="center">
                        <router-link to="/portal" data-style="underline">
                            <p class="text" data-size="13">Volver a iniciar sesión</p>
                        </router-link>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
export default {
    name: "PortalRecoverComponent",
    props:['basicData'],
    data(){
        return{
            emailOrPhone: '',
            emailOrPhoneError: ''
        }
    },
    methods:{
        sendCode(){

            //Validaciones
            if(this.emailOrPhone === ''){
                this.emailOrPhoneError = 'Este campo no puede estar vacio';
            }


            //Si no hay errores
            if(this.emailOrPhoneError === ''){
                axios.post('/api/auth/sendRecoverCode', { emailOrPhone: this.emailOrPhone, enterprise: this.basicData.enterprise })
                    .then((res) => {
                        this.$router.push('/portal/recover/update?credentials=' + this.emailOrPhone);
                    })
                    .catch((err) => {

                        if (err.response){
                            let noExistsUser = err.response.data.message;

                            if (noExistsUser){
                                this.emailOrPhoneError = noExistsUser;
                            }
                        }
                    })
            }


        }
    }
}
</script>

<style scoped>

</style>

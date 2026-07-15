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

                <p class="text" data-size="25" data-weight="600">Introduce tu token y tus credenciales</p>

                <p v-if="!isCorrectAccess" class="text" data-size="15">¡Corre, tienes 5 minutos desde que se manda el mensaje para introducir tu token!</p>

                <p v-else class="text" data-size="15">Introduce tu nueva contraseña</p>
            </div>


            <form v-if="!isCorrectAccess" v-on:submit.prevent.stop="checkAccess" class="form px-30">

                <!--Token y credenciales-->
                <div class="input-code my-30 dFlex flex-column" v-bind:class="{ wrong: errorsAccess.token}">
                    <input :id="'key'+ i"  v-for="i in 5" :data-index="i - 1" type="text" v-on:focus="delete errorsAccess.token" v-on:paste.prevent="pasteCodeEvent" v-on:keydown.prevent="keyPressInput">
                </div>
                <span v-if="errorsAccess.token" class="error">{{ errorsAccess.token }}</span>


                <!--Email o telefono-->
                <div v-bind:class="{ wrong: errorsAccess.credentials}" class="form-group my-30">
                    <div class="input-group" data-style="line">
                        <input v-on:focus="delete errorsAccess.credentials" v-model="access.credentials" type="text" name="emailOrPhone"
                               placeholder="Email o teléfono">
                    </div>
                    <span v-if="errorsAccess.credentials" class="error">{{ errorsAccess.credentials }}</span>
                </div>


                <!--Info abajo-->
                <div class="mt-50">

                    <!--Botón iniciar comprobar credenciales-->
                    <button class="my-20 custom-button w-100" data-size="big" type="submit">
                        Comprobar credenciales
                    </button>

                    <!--Demo-->
                    <div data-align="center">
                        <router-link to="/portal" data-style="underline">
                            <p class="text" data-size="13">Volver a iniciar sesión</p>
                        </router-link>
                    </div>
                </div>

            </form>




            <!--Formulario cambiar contraseña-->
            <form v-if="isCorrectAccess" v-on:submit.prevent.stop="changeUserPassword" class="form px-30">

                <!--Contraseña-->
                <div v-bind:class="{ wrong: errorsChangePassword.new}" class="form-group">
                    <div class="input-group" data-style="line">
                        <input v-on:focus="delete errorsChangePassword.new" v-model="changePassword.new" type="text" name="newPassword"
                               placeholder="Nueva contraseña">
                    </div>
                    <span v-if="errorsChangePassword.new" class="error">{{ errorsChangePassword.new }}</span>
                </div>


                <!--Repetir contraseña nueva-->
                <div v-bind:class="{ wrong: errorsChangePassword.repeat}" class="form-group">
                    <div class="input-group" data-style="line">
                        <input v-on:focus="delete errorsChangePassword.repeat" v-model="changePassword.repeat" type="text" name="repeatNewPassword"
                               placeholder="Repite la contraseña">
                    </div>
                    <span v-if="errorsChangePassword.repeat" class="error">{{ errorsChangePassword.repeat }}</span>
                </div>


                <!--Info abajo-->
                <div class="mt-50">

                    <!--Botón iniciar sesión/registrar-->
                    <button class="my-20 custom-button w-100" data-size="big" type="submit">
                        Cambiar contraseña
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
    name: "PortalUpdatePasswordComponent",
    props:['basicData'],
    data(){
        return{
            isCorrectAccess: false,
            access:{
                token: '',
                credentials: ''
            },
            errorsAccess:{},
            changePassword: {
                new: '',
                repeat: ''
            },
            errorsChangePassword:{}
        }
    },
    mounted() {

        if (this.$route.query.credentials !== '' && this.$route.query.credentials !== undefined){
            this.access.credentials = this.$route.query.credentials;
        }

        if (this.$route.query.token !== '' && this.$route.query.token !== undefined){
            this.access.token = this.$route.query.token;
            this.pasteCodeFromURL()
        }
    },
    methods:{
        pasteCodeEvent(e) {
            let text = e.clipboardData.getData('text');
            let firstFiveChars = text.slice(0, 5);
            firstFiveChars = [...firstFiveChars];
            firstFiveChars = firstFiveChars.filter(char => !isNaN(char));

            firstFiveChars.forEach((item, index) => {
                $(`[data-index="${index}"]`).val(item).focus();
            })

            this.access.token = text;
        },
        keyPressInput(e) {
            let currentInput = $(e.target);
            let index = currentInput.index();

            if (e.keyCode === 8) {
                // Estoy borrando
                currentInput.val('');
                if (index > 0) $(`[data-index="${index - 1}"]`).focus();
                this.access.token = this.access.token.slice(0,-1);

            }

            if (!isNaN(e.key * 1)) {
                currentInput.val(e.key);
                if (index < 4){
                    $(`[data-index="${index + 1}"]`).focus();


                }
            }
        },
        pasteCodeFromURL(){

            if (this.access.token !== ''){
                let text = this.access.token;

                let firstFiveChars = text.slice(0, 5);
                firstFiveChars = [...firstFiveChars];
                firstFiveChars = firstFiveChars.filter(char => !isNaN(char));

                firstFiveChars.forEach((item, index) => {
                    $(`[data-index="${index}"]`).val(item).focus();
                })
            }
        },
        checkAccess(){

            this.access.token = '';

            for (let i = 1; i <= 5; i++){
                this.access.token = this.access.token + $('#key'+i).val();
            }

            //Validaciones
            let hasErrors = false;

            if (this.access.token === ''){
                hasErrors = true;
                this.errorsAccess.token = 'El token no puede estar vacio';
            }

            if (this.access.credentials === ''){
                hasErrors = true;
                this.errorsAccess.credentials = 'Las credenciales no pueden estar vacias';
            }


            if (!hasErrors){
                axios.post('/api/auth/checkCredentials', this.access)
                    .then((res) => {
                        this.isCorrectAccess = true
                    })
                    .catch((err) => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: err.response.data.message
                        })
                    })
            }

        },
        changeUserPassword(){

            //Validaciones
            if (this.changePassword.new === ''){
                this.errorsChangePassword.new = 'Este campo no puede estar vacio';
            }


            if (this.changePassword.repeat === ''){
                this.errorsChangePassword.repeat = 'Este campo no puede estar vacio';
            }

            if (this.changePassword.repeat !== this.changePassword.new){
                this.errorsChangePassword.repeat = 'La co';
            }


            if (Object.values(this.errorsChangePassword).length === 0){
                axios.put('/api/auth/changePassword', { access: this.access, credentials: this.changePassword })
                    .then((res) => {
                        this.$router.push('/');
                    })
                    .catch((err) => {
                        console.log(err)
                    })
            }

        }
    }
}
</script>

<style scoped>

</style>

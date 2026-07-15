<template>
    <div class="content-white">

        <form v-on:submit.prevent="createEvent" class="form register-pos" v-on:click="isAccFocused = false">

            <!--División de inputs-->
            <div class="top-part">

                <div class="inputs-part">
                    <div class="text" data-size="20" data-weight="700">Detalles del evento</div>

                    <!--Asunto-->
                    <div v-bind:class="{ wrong: errors.subject}" class="form-group">
                        <p class="my-auto"><label>Asunto</label> <span data-color="rojo">*</span></p>
                        <div class="input-group">
                            <input v-on:focus="delete errors['subject']" data-size="12" v-model="event.subject" type="text">
                        </div>
                        <span v-if="errors.subject" class="error">{{ errors.subject }}</span>
                    </div>

                    <!--Descripción-->
                    <div v-bind:class="{ wrong: errors.desc}" class="form-group">
                        <label>Descripción</label>
                        <div class="input-group">
                            <textarea v-on:focus="delete errors['desc']" data-size="12" v-model="event.desc" type="text"></textarea>
                        </div>
                        <span v-if="errors.desc" class="error">{{ errors.desc }}</span>
                    </div>

                    <!--Ubicación-->
                    <div v-if="loadedCharged && isSignedIn" v-bind:class="{ wrong: errors.location}" class="form-group">
                        <label>Ubicación</label>
                        <div class="input-group">
                            <input v-on:focus="delete errors['location']" data-size="12" v-model="event.location" type="text">
                        </div>
                        <span v-if="errors.location" class="error">{{ errors.location }}</span>
                    </div>


                    <!--Cuenta-->
                    <div v-if="loadedCharged && !isSignedIn" v-bind:class="{ wrong: errors.account}" class="form-group" v-on:click.stop="">
                        <label>Cuenta</label>
                        <div class="input-group" v-if="event.account === ''">
                            <input v-on:click="isAccFocused = true" data-size="12" v-model="searchAccountText" type="text">
                            <i class="fa-regular fa-magnifying-glass ml-10 my-auto text"></i>
                        </div>

                        <!--Desplegable con todas las cuentas encontradas-->
                        <div class="select-div mt-10" v-if="event.account === '' && filteredAccounts.length > 0 && isAccFocused">
                            <div class="my-5 d-flex pointer d-flex column" v-for="account in filteredAccounts" v-on:click="selectAccount(account)">
                                <p class="text d-flex my-auto" data-size="12"><i class="fa-solid fa-building my-auto mr-10"></i>{{ account.name }}</p>

                                <div class="separator my-5"></div>
                            </div>
                        </div>


                        <!--Cuenta ya seleccionada-->
                        <div v-if="event.account !== '' && accountSelected" class="d-flex justify-between">

                            <div class="text ml-5 ellipsis" data-size="13">
                                <i class="fa-solid fa-building mr-10"></i> {{ accountSelected.name }}
                            </div>

                            <div class="my-auto pointer" data-color="rojo" v-on:click="event.account = ''">
                                <i class="fa-solid fa-x"></i>
                            </div>

                        </div>

                        <span v-if="errors.account" class="error">{{ errors.account }}</span>
                    </div>

                    <!--Fecha final e inicial-->
                    <div class="half-space">

                        <!--F. inicial-->
                        <div v-bind:class="{ wrong: errors.date.start}" class="form-group">
                            <p class="my-auto"><label>Fecha inicial</label> <span data-color="rojo">*</span></p>
                            <div class="input-group">
                                <input v-on:focus="delete errors['date']['start']" data-size="12" v-model="event.date.start" type="datetime-local">
                            </div>
                            <span v-if="errors.date.start" class="error">{{ errors.date.start }}</span>
                        </div>

                        <!--F. final-->
                        <div v-bind:class="{ wrong: errors.date.end}" class="form-group">
                            <p class="my-auto"><label>Fecha final</label> <span data-color="rojo">*</span></p>
                            <div class="input-group">
                                <input v-on:focus="delete errors['date']['end']" data-size="12" v-model="event.date.end" type="datetime-local">
                            </div>
                            <span v-if="errors.date.end" class="error">{{ errors.date.end }}</span>
                        </div>
                    </div>



                    <!--Recurrencia de evento-->
                    <!--<div v-bind:class="{ wrong: errors.date.recurrency}" class="form-group">
                        <label>Recurrencia del evento</label>
                        <div class="input-group">
                            <select v-on:change="delete errors['date']['recurrency']" v-model="event.date.recurrency">
                                <option v-for="recurrency in reccurrencyTypes" :value="recurrency.code">{{ recurrency.title }}</option>
                            </select>
                        </div>
                        <span v-if="errors.date.recurrency" class="error">{{ errors.date.recurrency }}</span>
                    </div>-->
                </div>

                <!--Separator vertical-->
                <div v-if="loadedCharged && !isSignedIn" class="separator" data-position="vertical"></div>


                <div class="inputs-part">

                    <!--Colores de card-->
                    <div v-if="loadedCharged && !isSignedIn" v-bind:class="{ wrong: errors.color}" class="form-group">
                        <label>Color de tarjeta</label>

                        <!--Listado colores-->
                        <div class="d-flex justify-between mt-10">
                            <div class="color" v-for="color in colors" v-bind:class="{selected: (event.color === color)}" :data-bg="color" v-on:click="selectColor(color)"></div>
                        </div>
                        <span v-if="errors.color" class="error">{{ errors.color }}</span>
                    </div>

                </div>
            </div>

            <!--separador-->
            <div class="separator"></div>

            <!--Botón guardar-->
            <div class="btn-part">
                <button class="custom-button mr-10" data-size="big" data-bg="rojo" v-on:click.prevent="actionLink('/calendar')">Cancelar</button>
                <button class="custom-button" data-size="big">Guardar <i class="fas fa-chevron-right ml-10"></i></button>
            </div>

        </form>
    </div>
</template>

<script>

export default {

    name: "EventRegisterComponent",
    props:['basicData'],
    data(){
        return{
            event:{
                subject: '',
                desc: '',
                date: {
                    start: '',
                    end: '',
                    recurrency: 'none'
                },
                location: '',
                color: 'blue-cake',
                account: ''
            },
            errors:{
                date:{}
            },
            reccurrencyTypes:[
                {
                    code: 'none',
                    title: 'No se repite'
                },
                {
                    code: 'daily',
                    title: 'Diariamente'
                },
                {
                    code: 'weekly',
                    title: 'Semanalmente'
                },
                {
                    code: 'montly',
                    title: 'Mensualmente'
                },
                {
                    code: 'yearly',
                    title: 'Anualmente'
                },
                {
                    code: 'allBusinessDays',
                    title: 'Todos los dias laborales( lunes a viernes )'
                }
            ],
            colors:['blue-cake', 'pink-cake', 'orange-cake', 'yellow-cake', 'green-cake', 'gris-principal'],
            accounts: [],
            searchAccountText: '',
            isAccFocused: false,
            googleData: '',
            loadedCharged: false,
            isSignedIn: false,
        }
    },
    watch:{
        "basicData.userLogged"(){
            this.checkSignedInGoogle();

            //Obtengo las cuentas relacionadas
            this.fetchAllAccounts()
        }
    },
    mounted() {
        if (this.basicData.userLogged && this.accounts.length === 0){
            this.checkSignedInGoogle();
            this.fetchAllAccounts()
        }
    },
    methods:{
        async checkSignedInGoogle(){
            await axios.post('/api/google/checkSignedIn', {user: this.basicData.userLogged})
                .then((res) => {
                    this.isSignedIn = res.data.isSignedIn;
                    this.loadedCharged = true;


                    if (this.isSignedIn)
                        this.refreshToken();

                })
                .catch(err => console.log(err))
        },
        async refreshToken() {

            await axios.get('/api/google/getNewToken', {
                params: { user: this.basicData.userLogged }
            })
                .then((res) => {
                    this.googleData = res.data.data

                    let timeRemaining = this.googleData.expires_in - 60;

                    //Establezco una cuenta atrás de un minuto menos del tiempo que va a expirar el token
                    setTimeout(() => this.refreshToken(), timeRemaining * 1000);


                })
                .catch(err => console.log(err))
        },
        createEvent(){

            let hasErrors= false;

            //Validaciones

                //Asunto
                if(this.event.subject === ''){
                    this.errors.subject = this.getErrorMessage('isEmpty');
                    hasErrors= true;
                }

                //Fecha inicial
                if(this.event.date.start === ''){
                    this.errors.date.start = this.getErrorMessage('isEmpty');
                    hasErrors= true;
                }

                //Fecha final
                if(this.event.date.end === ''){
                    this.errors.date.end = this.getErrorMessage('isEmpty');
                    hasErrors= true;
                }

                //Compruebo que sea posterior que la inicial
                if(this.event.date.end < this.event.date.start){
                    this.errors.date.end = 'La fecha final tiene que ser igual o posterior a la inicial';
                    hasErrors= true;
                }



                if (!hasErrors){

                    if (this.loadedCharged && !this.isSignedIn) {

                        axios.post(`/api/calendar`, { event: this.event })
                            .then((res) => {

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Evento creado',
                                    text: '¡El evento ha sido creado correctamente!',
                                    timer: 1500,
                                    timerProgressBar: true
                                }).then((res) => {
                                    this.$router.push('/calendar')
                                })
                            })
                            .catch((err) => {
                                console.log(err)
                            })

                    }else {

                        axios.post(`/api/google/createEvent`, { event: this.event, token: this.googleData.access_token })
                            .then((res) => {

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Evento creado',
                                    text: '¡El evento ha sido creado correctamente en Google Calendar!',
                                    timer: 1500,
                                    timerProgressBar: true
                                }).then((res) => {
                                    this.$router.push('/calendar')
                                })
                            })
                            .catch((err) => {
                                console.log(err)
                            })

                    }
                }

        },
        selectColor(color){
            this.event.color = color;
        },
        async fetchAllAccounts(){
            await axios.post(`/api/contacts/getAccountsRelated/${this.basicData.userLogged._id}`, { userList: this.basicData.userList })
                .then((res) => {
                    this.accounts = res.data.accounts;
                })
                .catch((err) => {
                    console.log(err)
                });
        },
        selectAccount(account){
            this.event.account = account._id

            this.searchAccountText = '';

            this.errors.account = '';
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
        actionLink(route){
            this.$router.push(route)
        }
    },
    computed:{
        accountSelected(){
            if (this.event.account){
                return this.accounts.find((account) => {
                    return account._id === this.event.account
                })
            }
        },
        filteredAccounts(){

            let accounts = []

            if (this.searchAccountText === '') {
                accounts = this.accounts;
            }else{
                this.accounts.filter((account) => {

                    let AccountFiltered = account.name.replace(' ', '').toLowerCase().normalize('NFC');

                    if (AccountFiltered.includes(this.searchAccountText.replace(' ', '').toLowerCase().normalize('NFC')))
                        accounts.push(account);
                })
            }

            accounts.sort((a, b) => a.name.localeCompare(b.name));

            return accounts;
        },
        isReadOnly(){
            if (!this.basicData.userLogged)
                return true
            else (this.basicData.userLogged)
                return this.basicData.userLogged.permissions.includes('READONLY')
        }
    }
}
</script>

<style scoped>
    .pick{
        background-color: red;
        height: 100px;
        width: 100px;
    }
</style>

<template>
    <div class="parent" v-bind:class="{ 'auth' : $route['meta']['section'] === 'login', 'app' : $route['meta']['section'] === 'app' }" @click="hideAll">
        <!--Dentro de aqui divido entre el login y la app-->

        <!--Login-->
        <div v-if="$route['meta']['section'] === 'login'">

        </div>

        <!--App-->
            <!--barra de navegación-->
            <nav-info-component v-if="$route['meta']['section'] === 'app'" :basicData="data" :isMobileNavBarOpen="isMobileNavBarOpen" @toggleMobileNavbar="isMobileNavBarOpen = !isMobileNavBarOpen"></nav-info-component>

            <!--Encabezado-->
            <div class="header" v-if="data.userLogged && $route['meta']['section'] === 'app'">

                <!--Bienvenida-->
                <div class="text ellipsis desktop-item" data-size="20" data-weight="700">
                    ¡Hola, {{ data.userLogged.firstName }}!
                </div>

                <!--Fecha y hora-->
                <div class="dateTime" data-align="center">
                    <p class="text opacity-5" data-size="10">{{ date }}</p>
                    <p class="text" data-size="18" data-weight="600">{{ time }}</p>
                </div>


                <!--Mensajes e imagen de perfil-->
                <div class="d-flex desktop-item justify-end ml-20 relPos" data-align="end">

                    <!--Buzón-->
                    <!--<i class="far fa-mailbox text mr-15 pointer" data-size="25" v-on:click="actionLink('/messages')"></i>-->

                    <!--Accesos directo-->
                    <i v-if="data.enterprise.url === 'assessoria30.zocoenergia.com'" class="far fa-meter mr-20 my-auto pointer" data-color="principal" data-size="25" v-on:click="dumpRedirect('/tools?section=search-cups')"></i>


                    <!--Barra de búsqueda animada-->
                    <div class="searchBarGlobalBox mr-25" v-bind:class="{ 'active' : isInputActive, 'hasResults': globalSearchText.trim().length > 0 }" @click.stop>

                        <div class="d-flex">
                            <div class="select-opt nowrap my-auto pointer">

                                <!--seleccionado-->
                                <p class="nowrap text" @click="isSeeingOpts = !isSeeingOpts">{{ globalSearchOptions.data[globalSearchOptions.selected].title }} <i class="fas fa-chevron-down"></i></p>

                                <!--desplegable opciones-->
                                <div class="opts" v-bind:class="{ 'active' : isSeeingOpts }">
                                    <div v-for="opt in globalSearchOptions.data" class="text nowrap d-flex">
                                        <div class="custom-checkbox mr-10 my-auto" v-on:click="changeSelectFilter(opt.code)" :class="{ 'selected' : globalSearchOptions.selected === opt.code }"></div>

                                        <p class="my-auto">{{ opt.title }}</p>
                                    </div>
                                </div>

                            </div>
                            <input type="text" v-model="globalSearchText" placeholder="  " @input="debouncedFetchAllGlobal" @focus="isInputActive = true; isSeeingOpts = false"> <!--@blur="onInputBlur"-->
                            <button type="reset" v-on:click="globalSearchText = ''" @click="hideAll">  </button>
                        </div>

                        <!--resultados-->
                        <div class="results" v-bind:class="{ 'active' : globalSearchText.trim().length > 2 }">

                            <div class="separator mx-5"></div>

                            <!--Si ya hay resultados sale-->
                            <div v-if="!isSearchingResults">

                                <!--Si es filtros 'Todos'-->
                                <div v-if="globalSearchOptions.selected === 0 && globalSearchResults.length > 0">

                                    <!--Si no hay ningún dato-->
                                    <div v-if="globalSearchResults[0].length === 0 && globalSearchResults[1].length === 0 && globalSearchResults[2].length === 0 && globalSearchResults[3].length === 0" class="opacity-5 text-center my-10" data-size="10">¡No hay resultados con estos criterios!</div>


                                    <!--si hay datos de algún tipo-->
                                    <div v-else>

                                        <!--Cada categoría-->
                                        <template v-for="(category, categoryInd) in globalSearchResults">


                                            <template v-if="category.length > 0">

                                                <!--Título categoría-->
                                                <p class="text opacity-5 mx-10 mt-10" data-size="12">{{ globalSearchOptions.data[categoryInd + 1].title }}</p>

                                                <div class="h-200-px-max mb-10" style="overflow-y: auto">

                                                    <!--contenido-->
                                                    <div class="my-10 d-flex pointer" v-for="data in category" @dblclick="selectSearchBarItem(categoryInd + 1,data)">

                                                        <div class="w-70 d-flex">
                                                            <!--icono-->
                                                            <div class="my-auto mx-10" data-size="22">
                                                                <i class="far text" v-bind:class="(categoryInd + 1) === 1 ? 'fa-file-lines' : ((categoryInd + 1) === 2 ? 'fa-buildings' : ((categoryInd + 1) === 3 ? 'fa-user' : 'fa-file-circle-question'))"></i>
                                                            </div>


                                                            <!--Info-->
                                                            <div class="ellipsis mr-10">
                                                                <!---->
                                                                <p class="text ellipsis" data-size="13">{{ (categoryInd + 1) === 3 ? (data.name.first + ' ' + data.name.second + ' ' + data.surname.first + ' ' + data.surname.second) : data.name }}</p>

                                                                <p class="opacity-5" data-size="8">{{ (categoryInd + 1) === 1 ? data.CUPS : data.email }}</p>
                                                            </div>
                                                        </div>


                                                        <!--+ info-->
                                                        <div class="w-30 d-flex">

                                                            <!--Si es contrato-->
                                                            <div v-if="(categoryInd + 1) === 1" class="d-flex column ellipsis">
                                                                <div class="custom-button text-center w-90-px mx-auto my-auto" data-size="small" :data-mode="!isHex(getStatus(data).color) ? 'translucent' : null" :data-bg="!isHex(getStatus(data).color) ? getStatus(data).color : null"
                                                                     :style="isHex(getStatus(data).color) ? {
                                                                        backgroundColor: hexToRgba(getStatus(data).color, 0.1),
                                                                        border: `1px solid ${getStatus(data).color}`
                                                                     }: {}" data-color="principal"><p class="w-70-px-max ellipsis">{{ getStatus(data)?.title }}</p></div>
                                                            </div>

                                                            <!--Si es otro-->
                                                            <div v-else class="d-flex column ellipsis">
                                                                <div class="text mx-auto my-auto">{{ (categoryInd + 1) === 3 ? data.phone : data.CIF }}</div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!--si no hay datos-->
                                                    <div v-if="category.length === 0" class="opacity-5 text-center" data-size="10">¡No hay resultados con estos criterios!</div>

                                                </div>

                                                <div class="separator" v-if="categoryInd < globalSearchResults.length - 1"></div>

                                            </template>

                                        </template>

                                    </div>


                                </div>

                                <!--Filtro particular-->
                                <div v-else>

                                    <p class="text opacity-5 mx-10" data-size="12">{{ globalSearchOptions.data[globalSearchOptions.selected].title }}</p>

                                    <div class="my-10 d-flex pointer" v-for="data in globalSearchResults" @dblclick="selectSearchBarItem(globalSearchOptions.selected,data)">

                                        <div class="w-70 d-flex">
                                            <!--icono-->
                                            <div class="my-auto mx-10" data-size="22">
                                                <i class="far text" v-bind:class="globalSearchOptions.selected === 1 ? 'fa-file-lines' : (globalSearchOptions.selected === 2 ? 'fa-buildings' : (globalSearchOptions.selected === 3 ? 'fa-user' : 'fa-file-circle-question'))"></i>
                                            </div>


                                            <!--Info-->
                                            <div class="ellipsis mr-10">
                                                <!---->
                                                <p class="text ellipsis" data-size="13">{{ globalSearchOptions.selected === 3 ? (data.name.first + ' ' + data.name.second + ' ' + data.surname.first + ' ' + data.surname.second) : data.name }}</p>

                                                <p class="opacity-5" data-size="8">{{ globalSearchOptions.selected === 1 ? data.CUPS : data.email }}</p>
                                            </div>
                                        </div>



                                        <!--+ info-->
                                        <div class="w-30 d-flex">

                                            <!--Si es contrato-->
                                            <div v-if="globalSearchOptions.selected === 1" class="d-flex column ellipsis">
                                                <div class="custom-button text-center w-90-px mx-auto my-auto" data-size="small" :data-bg="statuses.find(status => status.code === data.latestStatus.code)?.color" data-color="principal" data-mode="translucent">{{ data.latestStatusTitle }}</div>
                                            </div>

                                            <!--Si es otro-->
                                            <div v-else class="d-flex column ellipsis">
                                                <div class="text mx-auto my-auto">{{ globalSearchOptions.selected === 3 ? data.phone : data.CIF }}</div>
                                            </div>
                                        </div>


                                    </div>

                                    <!--Si no hay datos-->
                                    <div v-if="globalSearchResults.length === 0" class="opacity-5 text-center my-10" data-size="10">¡No hay resultados con estos criterios!</div>

                                </div>
                            </div>

                            <!--Si no sale cargando-->
                            <div class="d-flex" v-else>
                                <i class="fa-solid fa-spinner-third fa-spin mx-auto my-15 text"></i>
                            </div>

                        </div>
                    </div>



                    <div class="relPos" v-on:click="actionLink('/profile')">
                        <!--Gorro de navidad-->
<!--                        <img class="snow-hat pointer"  src="/assets/more/gorro_navidad_crm.png">-->

                        <img class="profile-image pointer" :src="'/assets/profile_images/' + data.userLogged.profileImage" alt="Imagen de perfil de usuario">
                    </div>
                </div>

                <!--Barra de navegación desplegable en movil-->
                <div class="mobile-item mobile-navbar text" v-on:click="isMobileNavBarOpen = !isMobileNavBarOpen">
                    <i class="fa-solid fa-bars"></i>
                </div>
            </div>

        <div class="content-principal" v-bind:class="{ 'auth-page' : $route['meta']['section'] === 'login', 'app-page' : $route['meta']['section'] === 'app' }">
            <div class="box shadow">
                <div class="boxBody relPos">
                    <router-view v-slot="{ Component }">
                        <transition
                            name="fade"
                            mode="out-in">
                            <component :is="Component" :basicData="data"></component>
                        </transition>
                    </router-view>
                </div>
            </div>
        </div>

            <Teleport to="body">
                <twilio-call-component
                    v-if="callState && callState.active"
                    :phone="callState.phone"
                    :name="callState.name"
                    :id="callState.id"
                    :isOrder="callState.isOrder"
                    :basicData="data"
                    @close="endCall()"
                />

                <transition name="slide-right">
                    <div v-if="newVersionAvailable" class="new-version-banner text">
                        <i class="fas fa-cloud-exclamation fa-bounce" data-size="19" />
                        <p class="select-none">Hay una nueva versión disponible</p>
                        <div class="custom-button" data-size="medium" data-style="twoBlue" @click="reload">Recargar</div>
                        <i class="fas fa-close pointer" data-size="19" @click="newVersionAvailable = false" />
                    </div>
                </transition>
            </Teleport>
    </div>
</template>

<script>
import { useCallState, endCall } from "../composables/useCall";
import {useVersionCheck} from "@/composables/useVersionCheck";

export default {
    name: "Vue",
    data(){
        return{
            globalSearchText: '',
            globalSearchOptions: {
                selected: 0,
                data:[
                    {
                        title: 'Todos',
                        code: 0
                    },
                    {
                        title: 'Contratos',
                        code: 1
                    },
                    {
                        title: 'Cuentas',
                        code: 2
                    },
                    {
                        title: 'Contactos',
                        code: 3
                    },
                    {
                        title: 'Oportunidades',
                        code: 4
                    }
                ]
            },
            globalSearchResults:[],
            isSearchingResults: false,
            isSeeingOpts: false,
            data:{
                userLogged: '',
                userList: '',
                userListTop: '',
                enterprise: ''
            },
            date: '',
            time: '',
            months:['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
            isMobileNavBarOpen: false,
            isInputActive: false,
            callState: useCallState(),
        }
    },
    setup() {
        return useVersionCheck()
    },
    beforeCreate() {
        localStorage.clear();
    },
    created(){
        this.debouncedFetchAllGlobal = this.debounce(this.fetchAllGlobalResults, 500);
    },
    mounted(){
        document.addEventListener("click", this.handleClickOutside);
        if (this.$route['meta']['section'] === 'app' || this.$route['meta']['section'] === 'billing') this.getSessionData();

        this.getEnterpriseData()

        this.timer = setInterval(this.getTime, 1000);
    },
    watch:{
        $route(){
            if (this.$route['meta']['section'] === 'app' || this.$route['meta']['section'] === 'billing') this.getSessionData()
        }
    },
    methods:{
        async getSessionData(){
            await axios.get('/api/session/getData')
                .then((res) => {

                    //Usuario logeado
                    this.data.userLogged = res.data.sessionData['userLogged'];
                    localStorage.setItem("userLogged", JSON.stringify(this.data.userLogged));

                    //console.log(JSON.parse(localStorage.getItem("userLogged")))

                    //Usuarios por debajo
                    this.data.userList = res.data.sessionData['userList']

                    //Jerarquía por arriba
                    this.data.userListTop = res.data.sessionData['userListTop']

                    //Usuario subdominio
                    this.data.userSubdomain = this.data.userListTop.find(user => user.label === 'Usuario subdominio');

                    //Lista completa usuarios
                    this.data.userListComplete = res.data.sessionData['userListComplete']

                    //Lista usuarios de usuario subdominio
                    this.data.subdomainUserList = res.data.sessionData['subdomainUserList']

                    //Planes de zoco
                    this.data.zocoPlans = res.data.sessionData['zocoPlans']

                    //Plan seleccionado
                    this.data.subscription = res.data.sessionData['subscription']

                    //Empresa del subdominio
                    this.data.subdomainEnterprise = res.data.sessionData['subdomainEnterprise']


                    //Creo la cookie para almacenar los filtros
                    let filters = {
                        orders:{
                            agents: [],
                            statuses: [],
                            activationDates:{
                                start: '',
                                end: ''
                            },
                            dates:{
                                start: '',
                                end: ''
                            },
                            lowDates:{
                                start: '',
                                end: ''
                            },
                            marketers: [],
                            productTypes: [],
                            fees: [],
                            products: [],
                            searchText: '',
                            searchField: 'all',
                            currentPage: 1,
                            perPage: 50,
                            sortBy: 3
                        },
                        accounts: {
                            agents: [],
                            dates:{
                                start: '',
                                end: ''
                            },
                            perPage: 50,
                            sortBy: 0
                        },
                        contacts:{
                            accounts: [],
                            perPage: 50,
                            sortBy: 0
                        },
                        opportunities:{
                            sortBy: 0
                        }
                    }

                    //Comentar para que no anden borrandose
                    //this.$cookies.remove('filters');

                    if (!this.$cookies.isKey('filters'))
                        this.$cookies.set('filters', JSON.parse(JSON.stringify(filters)))

                    if(!sessionStorage.getItem('filters')){
                        sessionStorage.setItem('filters', JSON.stringify(filters))
                    }

                })
                .catch((err) => {
                    console.log(err)
                })
        },
        getEnterpriseData(){

            //console.log('path --> ',this.$route.path)

            axios.get('/api/session/getEnterpriseData', {
                params:{ url: window.location.hostname }
            })
                .then((res) => {

                    //Info empresa
                    this.data.enterprise = res.data.enterprise;
                    //console.log(this.data.enterprise)

                    //Establezco el favicon
                    this.setFavicon(this.data.enterprise)

                })
                .catch((err) => {
                    console.log(err)
                })
        },
        setFavicon(enterprise) {
            const faviconURL = `/assets/enterprises/${enterprise.asset_folder}/favicon/mstile-150x150.png`;
            const link = document.querySelector("link[rel~='icon']");

            if (link) {
                link.href = faviconURL;

            } else {
                const newLink = document.createElement('link');
                newLink.rel = 'icon';
                newLink.type = 'image/x-icon';
                newLink.href = faviconURL;
                document.head.appendChild(newLink);
            }
        },
        getTime(){
            let currentDateTime  =  new Date();

            let date = currentDateTime.getDate();
            let month = currentDateTime.getUTCMonth();
            let hour = currentDateTime.getHours();
            let minutes = currentDateTime.getMinutes();

            if (hour < 10)
                hour = '0' + hour;

            if (minutes < 10)
                minutes = '0' + minutes;

            this.date = date + ' de ' + this.months[month];
            this.time = hour + ':' + minutes;
        },
        debounce(func, delay) {
            let timeoutId;
            return function (...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    func.apply(this, args);
                }, delay);
            };
        },
        fetchAllGlobalResults(){

            this.isSearchingResults = true;

            this.globalSearchResults = []

            if (this.globalSearchText.trim().length >= 3){

                axios.post('/api/global/search', {
                    text: this.globalSearchText,
                    type: this.globalSearchOptions.data[this.globalSearchOptions.selected].code,
                    userList: this.data.userList,
                })
                    .then((res) => {
                        this.globalSearchResults = res.data.results;

                        this.isSearchingResults = false;
                    })
                    .catch((err) => {
                        console.log(err)
                    })
            }
        },
        hideAll(){
            this.isSeeingOpts = false;

            // El input pierde foco, pero revisamos si hay texto
            this.isInputActive = this.globalSearchText.trim().length > 0;
        },
        changeSelectFilter(code){

            //Borro los datos antiguos
            this.globalSearchResults = [];

            //Selecciono el nuevo filtro
            this.globalSearchOptions.selected = code

            //Escondo el selector
            this.isSeeingOpts = false;

            //Busco de nuevo
            this.fetchAllGlobalResults()
        },
        async actionLink(route){

            if(this.$route.path.split('/')[1] === route.split('/')[1]){
                await this.$router.replace('/temp-route');
                this.$router.push(route)
            }else{
                this.$router.push(route)
            }

        },
        async dumpRedirect(route){
            await this.$router.replace('/dump-route')
            await this.$router.replace(route)
        },
        selectSearchBarItem(categoryInd, data){
            this.actionLink(categoryInd === 1 ? ('/contracts?_id=' + (!!data._id.$oid ? data._id.$oid : data._id)) : (categoryInd === 2 ? ('/accounts/' + data._id) : (categoryInd === 3 ? ('/contacts/' + data._id) : ('/opportunities/' + data._id))))
            this.globalSearchText = "";
            this.hideAll();
        },
        getStatus(order){
            if (!this.data || !this.data.userSubdomain) return ''

            let recentStatus = order.statuses.reduce((latest, current) => {
                return new Date(current.date) > new Date(latest.date) ? current : latest;
            });


            return this.data.userSubdomain.statuses.find((status) => {
                return status.code === recentStatus.code
            })
        },
        isHex(color) {
            return /^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(color);
        },
        hexToRgba(hex, alpha = 1) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        },
        endCall
    },
    beforeDestroy(){
        clearInterval(this.timer);
    }
}
</script>

<style scoped>

</style>

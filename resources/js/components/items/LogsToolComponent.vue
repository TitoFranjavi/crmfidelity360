<template>
    <div @click="recipientDisplaySelected = null">

        <!--Filtros-->
        <div class="d-flex justify-between">

            <!--Filtros particulares-->
            <div class="w-60 relPos" data-gap="5">

                <div class="d-flex align-center f-wrap py-20" data-gap="10">


                    <!--Agentes seleccionados-->
                    <div class="resume-card small" v-on:click.stop="toggleSelectedDisplay('agents')">

                        <!--icono-->
                        <div class="icon">
                            <i class="far fa-users"></i>
                        </div>


                        <div :class="['info']">

                            <p class="number">{{ filters.users.length }}<span class="total">/{{ basicData.userList.length + 1 }}</span></p>

                            <p class="text">
                                agentes
                            </p>

                        </div>

                    </div>

                    <!--Categorias seleccionados-->
                    <div class="resume-card small" v-on:click.stop="toggleSelectedDisplay('categories')" >

                        <!--icono-->
                        <div class="icon">
                            <i class="far fa-list"></i>
                        </div>


                        <div :class="['info']">

                            <p class="number">{{ this.filters.categories.length }}<span class="total">/{{ this.categories.length }}</span></p>

                            <p class="text">
                                categorias
                            </p>

                        </div>

                    </div>

                    <!--Fechas seleccionados-->
                    <div class="resume-card small" v-on:click.stop="toggleSelectedDisplay('dates')" >

                        <!--icono-->
                        <div class="icon">
                            <i class="far fa-calendar"></i>
                        </div>


                        <div class="ml-10">
                            <p class="text">{{ dateFilterTitle() }}</p>
                        </div>

                    </div>


                    <!--Seleccionador destinatarios-->
                    <transition name="fade-vertical">

                        <div v-if="recipientDisplaySelected" v-on:click.stop="" class="recipients-box small form">
                            <p class="text" data-size="13">{{ recipientDisplaySelected === 'agents' ? 'Selecciona entre tus agentes' : (recipientDisplaySelected === 'categories' ? 'Selecciona las categorias' : 'Selecciona las fechas') }}</p>

                            <!--Buscador-->
                            <div class="d-flex" v-if="recipientDisplaySelected !== 'dates'">
                                <!--Buscador-->
                                <div class="form-group">
                                    <div class="input-group">
                                        <input type="text" class="w-260-px" :placeholder="recipientDisplaySelected === 'agents' ? 'Busca por nombre, correo, teléfono, DNI' : 'Busca por nombre'" v-model="searchFilterText">
                                    </div>
                                </div>

                                <!--Selecc. todos -->
                                <div class="d-flex ml-10" v-if="recipientDisplaySelected !== 'emails'">
                                    <!--Check-->
                                    <div class="custom-checkbox my-auto mr-10"  v-on:click="toggleSelectAll">
                                        <div v-bind:class="{ selected: recipientDisplaySelected === 'agents' ? allAgentsSelected :  allCategorySelected }"></div>
                                    </div>
                                    <p class="my-auto">Seleccionar todos</p>
                                </div>
                            </div>


                            <!--Listado-->
                            <div>

                                <!--Agentes-->
                                <div class="user-list" v-if="recipientDisplaySelected === 'agents'">
                                    <div v-if="filteredList.length === 0" class="opacity-5">¡No hay ningún agente!</div>

                                    <div v-for="user in filteredList" class="user" v-on:click="toggleSelect(user._id, 'users')">

                                        <div class="d-flex">

                                            <div class="my-auto w-10">
                                                <img :src="'/assets/profile_images/' + user.profileImage" alt="Imagen usuario">
                                            </div>

                                            <!--label, nombre, correo-->
                                            <div class="content d-flex mx-10 w-80">

                                                <div class="w-70">
                                                    <p class="text w-250-px-max ellipsis" :data-color="filters.users.includes(user._id) ? 'azul' : ''">{{ user.firstName }} {{ user.lastName }}</p>

                                                    <p class="text opacity-3 ellipsis" data-size="8">{{ user.email }}</p>
                                                </div>

                                                <div class="w-30">
                                                    <p class="text opacity-3 ellipsis" data-size="8"><i class="fa-solid fa-phone mr-5"></i>{{ user.phone }}</p>

                                                    <p class="text opacity-3 ellipsis" data-size="8"><i class="fa-solid fa-id-card mr-5"></i>{{ user.dni }}</p>
                                                </div>
                                            </div>

                                            <!--botón seleccionar responsable-->
                                            <div class="text pointer mx-auto my-auto d-flex justify-center align-center w-10">
                                                <i class="far fa-arrow-pointer" :data-color="filters.users.includes(user._id) ? 'azul' : ''"></i>
                                            </div>
                                        </div>

                                        <!--Separador-->
                                        <div class="separator mt-5 mb-0"></div>

                                    </div>
                                </div>

                                <!--Categorias-->
                                <div class="user-list" v-else-if="recipientDisplaySelected === 'categories'">
                                    <div v-if="filteredList.length === 0" class="opacity-5">¡No hay ninguna categoria!</div>

                                    <div v-for="category in filteredList" class="user" v-on:click="toggleSelect(category.code, 'categories')">

                                        <div class="d-flex">

                                            <!--label, nombre, correo-->
                                            <div class="content d-flex mx-10 w-100">
                                                <div class="w-70">
                                                    <p class="text w-250-px-max ellipsis" :data-color="filters.categories.includes(category.code) ? 'azul' : ''">{{ category.title }}</p>
                                                </div>
                                            </div>

                                            <!--botón seleccionar responsable-->
                                            <div class="text pointer mx-auto my-auto d-flex justify-center align-center w-10">
                                                <i class="far fa-arrow-pointer" :data-color="filters.categories.includes(category.code) ? 'azul' : ''"></i>
                                            </div>
                                        </div>

                                        <!--Separador-->
                                        <div class="separator mt-5 mb-0"></div>

                                    </div>
                                </div>

                                <!--Fechas-->
                                <div class="user-list" v-else-if="recipientDisplaySelected === 'dates'">
                                    <div class="select-content center form">

                                        <div class="form-group d-flex">
                                            <p class="w-20 my-auto text">Inicial</p>

                                            <div class="input-group ml-10 w-70">
                                                <input data-size="12" v-model="filters.dates.start" @change="fetchLogs" type="datetime-local">
                                            </div>

                                            <div class="my-auto mx-10 text pointer"
                                                 v-on:click.stop="filters.dates.start = null; fetchLogs()">
                                                <i class="fas fa-x"></i>
                                            </div>

                                        </div>

                                        <div class="form-group d-flex">
                                            <p class="w-20 my-auto text">Final</p>

                                            <div class="input-group ml-10 w-70">
                                                <input data-size="12" v-model="filters.dates.end" @change="fetchLogs" type="datetime-local">
                                            </div>

                                            <div class="my-auto mx-10 text pointer"
                                                 v-on:click.stop="filters.dates.end = null; fetchLogs()">
                                                <i class="fas fa-x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </transition>
                </div>

            </div>

            <div class="py-20 mt-auto d-flex" data-gap="20">

                <!--Cambio páginas-->
                <div class="d-grid" data-column="2">

                    <div class="d-flex justify-center my-auto" data-color="principal">
                        <div class="left pointer" v-bind:class="{ 'opacity-5': currentPage === 1 }"
                             v-on:click="changePage(-1)"><i class="fa-solid fa-chevron-left"></i></div>


                        <!--Info página-->
                        <div class="cont mx-10" data-size="13" data-weight="600">{{ currentPage }} DE {{ totalPages }}
                        </div>


                        <div class="right pointer" v-bind:class="{ 'opacity-5': currentPage === totalPages }"
                             v-on:click="changePage(1)"><i class="fa-solid fa-chevron-right"></i></div>
                    </div>

                    <div class="my-auto ml-auto d-flex">
                        <div class="select-content my-auto">
                            <!--Usuario  a liquidar-->
                            <div class="form my-auto">
                                <div class="form-group">
                                    <div class="input-group">
                                        <select v-model="perPage" v-on:change="changePageSize">
                                            <option v-for="perPage in [50, 100, 200]">{{ perPage }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--Buscador-->
                <div>
                    <div class="d-flex">
                        <div class="search-bar w-100">

                            <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10"></i>

                            <input type="text" data-size="14" placeholder="Buscar un registro..." v-model="searchText">
                        </div>

                        <i data-color="principal" class="pointer fas fa-trash my-10 ml-10" v-on:click=""></i>
                    </div>
                </div>
            </div>

        </div>

        <!--Logs-->
        <div class="px-10 round" data-round="20" data-bg="gris">
            <log-card-component v-if="this.basicData.userList" v-for="(log, logInd) in filteredLogs" :id="'log-card-' + log._id" :log="log" :logInd="logInd" :logs="logs" :basicData="basicData" :marketers="marketers" :highlightLogId="highlightLogId"></log-card-component>
        </div>

        <!--Paginación-->
        <div class="d-grid" data-column="3">

            <div></div>

            <div class="d-flex justify-center mt-20" data-color="principal">
                <div class="left pointer" v-bind:class="{ 'opacity-5': currentPage === 1 }"
                     v-on:click="changePage(-1)"><i class="fa-solid fa-chevron-left"></i></div>


                <!--Info página-->
                <div class="cont mx-10" data-size="13" data-weight="600">{{ currentPage }} DE {{ totalPages }}
                </div>


                <div class="right pointer" v-bind:class="{ 'opacity-5': currentPage === totalPages }"
                     v-on:click="changePage(1)"><i class="fa-solid fa-chevron-right"></i></div>
            </div>

            <div class="my-auto ml-auto d-flex">

                <p class="text my-auto mr-15">Por página: </p>

                <div class="select-content my-auto">
                    <!--Usuario  a liquidar-->
                    <div class="form my-auto">
                        <div class="form-group">
                            <div class="input-group">
                                <select v-model="perPage" v-on:change="changePageSize">
                                    <option v-for="perPage in [50, 100, 200]">{{ perPage }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "ToolsComponent",
        props:['basicData', 'logId'],
        data(){
            return {
                searchText: '',
                searchFilterText: '',
                filters:{
                    users: [],
                    categories: [
                        'contracts',
                        'accounts',
                        'opportunities',
                        'comparatives',
                        'invoice_checker',
                        'ev_charger_budget',
                    ],
                    dates: {
                        start: null,
                        end: null
                    }
                },
                categories: [
                    {
                        code: 'contracts',
                        title: 'Contratos'
                    },
                    {
                        code: 'accounts',
                        title: 'Cuentas'
                    },
                    {
                        code: 'opportunities',
                        title: 'Oportunidades'
                    },
                    {
                        code: 'comparatives',
                        title: 'Comparativas'
                    },
                    {
                        code: 'invoice_checker',
                        title: 'Comprobaciones'
                    },
                    { 
                    code: 'ev_charger_budget', 
                    title: 'Presupuestos cargador' 
                    },
                ],
                logs: null,
                marketers: [],
                recipientDisplaySelected: null,
                currentPage: null,
                totalPages: null,
                perPage: 50,
                highlightLogId: this.logId || null
            }
        },
        created(){
            this.fetchMarketers();
        },
        mounted() {
            this.fetchLogs(true)
        },
        watch: {
            'basicData.userList': {
                handler(newValue) {

                    if (newValue && this.filters.users.length === 0) {

                        this.filters.users = [
                            ...newValue.map(user => user._id),
                            this.basicData.userLogged._id
                        ];
                    }
                },
                immediate: false // No hace falta ejecutarlo al crear
            }
        },
        methods:{
            async fetchLogs(isFirstTime = false){

                await axios.get('/api/logs', {
                    params:{
                        filters: this.filters,
                        isFirstTime,
                        page: this.currentPage,
                        perPage: this.perPage,
                        logId: this.logId
                    }
                })
                    .then((res) => {
                        this.logs = res.data.logs.data

                        //Si venimos con un log concreto y no entra en los filtros/página,
                        //lo anteponemos para que sea visible y se pueda resaltar.
                        if (this.logId && res.data.requestedLog) {
                            const requested = res.data.requestedLog;
                            if (!this.logs.some(log => log._id === requested._id))
                                this.logs.unshift(requested);
                        }

                        if(!this.totalPages)
                            this.totalPages = res.data.logs.last_page;

                        if (!this.currentPage)
                            this.currentPage = res.data.logs.current_page;
                    })
                    .catch((err) => {
                        console.log(err)
                    })
                    .finally((res) => {
                        if (isFirstTime && this.filters.users.length === 0)
                            this.filters.users = [
                                ...this.basicData.userList.map(user => user._id),
                                this.basicData.userLogged._id
                            ];

                        //Salto al log resaltado solo en la primera carga
                        if (isFirstTime && this.highlightLogId)
                            this.scrollToHighlightedLog();
                    })
            },
            scrollToHighlightedLog(){
                this.$nextTick(() => {
                    setTimeout(() => {
                        const el = document.getElementById('log-card-' + this.highlightLogId);
                        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 150);
                });
            },
            async fetchMarketers() {
                await axios.get('/api/marketers')
                    .then((res) => {
                        this.marketers = res.data.marketers;
                        //TODO: Mejorar esto
                        if(this.marketers[0].createdBy !== '65cb57489c2c285441086a43'){
                            this.fetchZocoMarketers();
                        }
                    })
                    .catch((err) => {
                        console.log(err)
                    })
            },
            async fetchZocoMarketers() {
                await axios.get('/api/marketers', {
                    params: { assignContractTo: '65cb57489c2c285441086a43' }
                })
                    .then((res) => {
                        const zocoMarketers = res.data.marketers.map(marketer => ({...marketer, name: `${marketer.name} (ZOCO)`, isZocoMarketer: true}));
                        this.marketers = [...this.marketers, ...zocoMarketers];
                    })
                    .catch((err) => {
                        console.log(err)
                    })
            },
            toggleSelectedDisplay(type){
                if (type === this.recipientDisplaySelected)
                    this.recipientDisplaySelected = null
                else{
                    this.recipientDisplaySelected = type;
                }

                this.searchFilterText = ''
            },
            toggleSelect(value, type){
                //Busco si está, si lo está lo elimino, sino lo añado
                let values = this.filters[type];

                if (values.includes(value))
                    this.filters[type] = values.filter(v => v !== value);
                else
                    this.filters[type].push(value);


                //Cargo los logs con los nuevos filtros
                this.fetchLogs();
            },
            toggleSelectAll(){
                switch (this.recipientDisplaySelected){
                    case 'agents':

                        //Si están todos elimino de email recipients todos los que esten en basicData userlist
                        if (this.allAgentsSelected)
                            this.filters.users = [];
                        else
                            this.filters.users = [...this.basicData.userList.map((user) => user._id), this.basicData.userLogged._id];

                        break;

                    case 'categories':

                        //Si están todos elimino de email recipients todos los que esten en basicData userlist
                        if (this.allCategorySelected){
                            this.filters.categories = [];
                        }else{
                            this.filters.categories = this.categories.map(category => category.code);
                        }

                        break;
                }

                //Cargo los logs con los nuevos filtros
                this.fetchLogs();
            },
            dateFilterTitle() {
                const start = this.filters.dates.start ? this.formatDateEs(this.filters.dates.start) : null;
                const end   = this.filters.dates.end   ? this.formatDateEs(this.filters.dates.end)   : null;

                if (start && end)
                    return `${start} - ${end}`;
                else if (start)
                    return `Desde ${start}`;
                else if (end)
                    return `Hasta ${end}`;
                else
                    return 'Todos los registros';
            },
            formatDateEs(date) {
                if (!date) return null;

                // Si la fecha llega como objeto inesperado, intentar extraerla
                if (typeof date === 'object' && !(date instanceof Date)) {
                    if (date.start) date = date.start;
                    if (date.end) date = date.end;
                }

                const parsed = new Date(date);
                if (isNaN(parsed)) return null;

                return parsed.toLocaleString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            },
            changePage(direction) {
                // Si no tenemos datos de páginas aún → no hacer nada
                if (!this.totalPages || !this.currentPage) return;

                let newPage = this.currentPage + direction;

                // Controlar que no nos salgamos de los límites
                if (newPage < 1) newPage = 1;
                if (newPage > this.totalPages) newPage = this.totalPages;

                // Si la página no cambia → no recargar
                if (newPage === this.currentPage) return;

                this.currentPage = newPage;

                // Cargar los logs de la nueva página
                this.fetchLogs();
            },
            changePageSize() {
                // Cuando cambia el tamaño de página, reiniciamos a la página 1
                this.currentPage = 1;

                // Forzamos que el backend nos devuelva un nuevo total de páginas
                this.totalPages = null;

                // Cargar logs con el nuevo tamaño
                this.fetchLogs();
            }

        },
        computed:{
            filteredList() {
                let list = [];
                let searchTerm = this.searchFilterText.toLowerCase().trim();

                let options = {};
                let fuseAgents, fuseCategories = [];

                switch (this.recipientDisplaySelected) {
                    case 'agents':

                        let allAgents = [
                            ...this.basicData.userList,
                            this.basicData.userLogged
                        ];

                        if (searchTerm === ''){
                            list = allAgents;
                        }else{
                            // Opciones para filtrado con Fuse
                            let options = {
                                includeScore: true,       // Incluir puntajes de coincidencia
                                threshold: 0.2,           // Umbral de coincidencia (más estricto)
                                keys: ['fullName', 'firstName', 'lastName', 'email', 'phone', 'dni'], // Campos a buscar
                                tokenize: true,           // Permite que las palabras se tokenicen
                                matchAllTokens: false,    // No requiere que todos los tokens coincidan
                                ignoreLocation: true,     // Ignorar la ubicación de los términos en el campo
                            };

                            let agentsFullName = allAgents.map(agent => ({
                                ...agent,
                                fullName: `${agent.firstName} ${agent.lastName}`.toLowerCase() // Concatenar nombre y apellido
                            }));

                            fuseAgents = new Fuse(agentsFullName, options);

                            list = fuseAgents.search(searchTerm).map(result => result.item);
                        }

                        break;

                    case 'categories':

                        if (searchTerm === ''){
                            list = this.categories;
                        }else{
                            // Opciones para filtrado con Fuse
                            options = {
                                includeScore: true,        // Incluir puntajes de coincidencia
                                threshold: 0.3,            // Umbral de coincidencia (0 es exacto, 1 es más permisivo)
                                keys: ['title'], // Campos a buscar
                                tokenize: true,
                                matchAllTokens: true
                            };

                            fuseCategories = new Fuse(this.categories, options); // Usar fuseAccounts para las cuentas
                            list = fuseCategories.search(searchTerm).map(result => result.item); // Buscar en cuentas
                        }


                        break;
                }

                return list;
            },
            allAgentsSelected(){
                if (!this.basicData || !this.basicData.userList) return false;

                return this.filters.users.length === (this.basicData.userList.length + 1);
            },
            allCategorySelected(){
                return this.filters.categories.length === this.categories.length;
            },
            filteredLogs() {
                let searchTerm = this.searchText.toLowerCase().trim();

                if (searchTerm === '') return this.logs;

                let options = {
                    includeScore: true,
                    threshold: 0.3,
                    ignoreLocation: true,
                    keys: [
                        'type',
                        'metadata.CUPS',
                        'metadata.name',
                        'metadata.input.pdf.titular',
                        'metadata.output.total.0.marketer',
                        'metadata.output.total.0.product',
                        'metadata.output.total.0.priceType',
                        'metadata.output.total.0.fee',
                        'metadata.output.total.0.prices.total'
                    ]
                };

                let fuse = new Fuse(this.logs, options);

                return fuse.search(searchTerm).map(r => r.item);
            }
        }
    }
</script>

<style scoped>

</style>

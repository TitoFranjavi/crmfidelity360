<template>
    <div class="content-white" v-on:click="hideCustomSelects">
        <!--header movil-->
        <div class="mobile-item dashboard-mobile">

            <!--Header: título + fecha-->
            <div class="dash-header">
                <div class="text" data-size="24" data-weight="700">{{ $route.meta.title }}</div>

                <div class="custom-select no-hover dash-date-select"
                     v-on:click.stop="seeFilters('dates')"
                     v-bind:class="{ seeing: isSeeingFiltersPc.dates }">
                    <div data-color="azul" data-size="13">
                        {{ getPrettyDatesFilters }}<i class="far fa-calendar ml-10"></i>
                    </div>

                    <div class="select-content left form">
                        <div class="form-group d-flex">
                            <p class="w-20 my-auto text">Inicial</p>
                            <div class="input-group ml-10 w-70">
                                <input data-size="12" v-model="dates.start"
                                       v-on:change="setDate('dates.start')" onkeydown="return false;" type="date" />
                            </div>
                        </div>
                        <div class="form-group d-flex">
                            <p class="w-20 my-auto text">Final</p>
                            <div class="input-group ml-10 w-70">
                                <input data-size="12" v-model="dates.end"
                                       v-on:change="setDate('dates.end')" onkeydown="return false;" type="date" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!--Selector de usuario-->
            <div class="dash-user-select"
                 v-if="basicData && basicData.userList">
                <div class="custom-select no-hover"
                     v-on:click.stop="seeFilters('user')"
                     v-bind:class="{ seeing: isSeeingFiltersPc.user }">
                    <div class="d-flex align-center">
                        <div class="profile-image mr-10">
                            <img class="pointer h-35-px w-35-px"
                                 :src="'/assets/profile_images/' + userSelected.profileImage"
                                 alt="Imagen de perfil" />
                        </div>
                        <p class="opacity-5 my-auto" data-size="14"
                           v-if="userSelected._id === '65fd4c2f05efc4aa4a050dc2'">
                            {{ basicData.userLogged.firstName }} {{ basicData.userLogged.lastName }}
                        </p>
                        <p class="opacity-5 my-auto" data-size="14" v-else>
                            {{ userSelected.firstName }} {{ userSelected.lastName }}
                        </p>
                    </div>

                    <div class="select-content form">
                        <div v-for="(user, userInd) in filteredUserList"
                             v-on:click="selectUser(user)">
                            <div class="d-flex align-center">
                                <div class="profile-image mr-10">
                                    <img class="pointer h-20-px w-20-px"
                                         :src="'/assets/profile_images/' + user.profileImage"
                                         alt="Imagen de perfil" />
                                </div>
                                <p class="text my-5">{{ user.firstName }} {{ user.lastName }}</p>
                            </div>
                            <p class="separator my-5" v-if="userInd < basicData.userList.length - 1"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!--Cargando-->
            <div class="loading-indicator" v-if="!summaryData">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <p class="text" data-size="14">Cargando datos del escritorio...</p>
            </div>

            <!--Tarjetas resumen-->
            <div class="dash-cards-grid" v-if="summaryData">
                <!--Total consumo-->
                <div class="dashboard-card">
                    <div class="icon"><i class="far fa-lightbulb"></i></div>
                    <div class="info">
                        <p class="title">Total consumo</p>
                        <p class="value">{{ totalConsumption }}</p>
                    </div>
                </div>

                <!--Total contratos-->
                <div class="dashboard-card">
                    <div class="icon"><i class="far fa-file-lines"></i></div>
                    <div class="info">
                        <p class="title">Total contratos</p>
                        <p class="value">{{ summaryData.totalContracts }}</p>
                    </div>
                </div>

                <!--Total comisiones-->
                <div class="dashboard-card">
                    <div class="icon"><i class="far fa-money-bill"></i></div>
                    <div class="info" :class="{ half: canManage('contracts.manageCommissions') }">
                        <div>
                            <p class="title">Com. {{ canManage('contracts.manageCommissions') ? 'agentes' : '' }}</p>
                            <p class="value">
                                {{ Math.round(summaryData.agentCommission ?? 0).toLocaleString("es-ES") }}
                                <span :class="{'points': basicData && basicData.userLogged.commInPoints}">{{ basicData && !basicData.userLogged.commInPoints ? '€' : 'pts.' }}</span>
                            </p>
                        </div>
                        <div v-if="canManage('contracts.manageCommissions')">
                            <p class="title">Com. {{ basicData.enterprise.name }}</p>
                            <p class="value">
                                {{ Math.round(summaryData.subdomainCommission ?? 0).toLocaleString("es-ES") }}
                                <span :class="{'points': basicData && basicData.userLogged.commInPoints}">{{ basicData && !basicData.userLogged.commInPoints ? '€' : 'pts.' }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!--Total agentes-->
                <div class="dashboard-card">
                    <div class="icon"><i class="far fa-users"></i></div>
                    <div class="info">
                        <p class="title">Total agentes</p>
                        <p class="value">{{ summaryData.totalAgents }}</p>
                    </div>
                </div>
            </div>  

            

        </div>

        <!--header pc-->
        <div class="desktop-item">
            <!--Header-->
            <div class="d-flex justify-between align-center">
                <div class="d-flex">
                    <!--Título-->
                    <div class="d-flex mr-20">
                        <div class="text" data-size="30" data-weight="700">
                            {{ $route.meta.title }}
                        </div>
                    </div>

                    <!--Intervalo de fechas-->
                    <div
                        class="custom-select no-hover my-auto px-10 py-5 round"
                        data-round="20"
                        data-bg="gris"
                        v-on:click.stop="seeFilters('dates')"
                        v-bind:class="{ seeing: isSeeingFiltersPc.dates }"
                    >
                        <div data-color="azul" data-size="14">
                            {{ getPrettyDatesFilters
                            }}<i class="far fa-calendar ml-10"></i>
                        </div>

                        <div class="select-content left form">
                            <div class="form-group d-flex">
                                <p class="w-20 my-auto text">Inicial</p>

                                <div class="input-group ml-10 w-70">
                                    <input
                                        data-size="12"
                                        v-model="dates.start"
                                        v-on:change="setDate('dates.start')"
                                        onkeydown="return false;"
                                        type="date"
                                    />
                                </div>

                                <!--<div class="my-auto mx-10 text pointer" v-on:click.stop="deleteFilter('dates.start')">
                                    <i class="fas fa-x"></i>
                                </div>-->
                            </div>

                            <div class="form-group d-flex">
                                <p class="w-20 my-auto text">Final</p>

                                <div class="input-group ml-10 w-70">
                                    <input
                                        data-size="12"
                                        v-model="dates.end"
                                        v-on:change="setDate('dates.end')"
                                        onkeydown="return false;"
                                        type="date"
                                    />
                                </div>

                                <!--<div class="my-auto mx-10 text pointer" v-on:click.stop="deleteFilter('dates.end')">
                                    <i class="fas fa-x"></i>
                                </div>-->
                            </div>
                        </div>
                    </div>
                </div>

                <!--Usuario seleccionado-->
                <div
                    class="custom-select no-hover"
                    v-on:click.stop="seeFilters('user')"
                    v-bind:class="{ seeing: isSeeingFiltersPc.user }"
                    v-if="basicData && basicData.userList"
                >
                    <!---->
                    <div class="d-flex">
                        <p
                            class="opacity-5 my-auto mr-15"
                            v-if="
                                userSelected._id === '65fd4c2f05efc4aa4a050dc2'
                            "
                        >
                            {{ basicData.userLogged.firstName }}
                            {{ basicData.userLogged.lastName }}
                        </p>
                        <p class="opacity-5 my-auto mr-15" v-else>
                            {{ userSelected.firstName }}
                            {{ userSelected.lastName }}
                        </p>

                        <div class="profile-image" data-align="end">
                            <img
                                class="pointer h-50-px w-50-px"
                                :src="
                                    '/assets/profile_images/' +
                                    userSelected.profileImage
                                "
                                alt="Imagen de perfil de usuario"
                            />
                        </div>
                    </div>

                    <div class="select-content form">
                        <div
                            v-for="(user, userInd) in filteredUserList"
                            v-on:click="selectUser(user)"
                        >
                            <div class="d-flex">
                                <div
                                    class="profile-image mr-10"
                                    data-align="end"
                                >
                                    <img
                                        class="pointer h-20-px w-20-px"
                                        :src="
                                            '/assets/profile_images/' +
                                            user.profileImage
                                        "
                                        alt="Imagen de perfil de usuario"
                                    />
                                </div>

                                <p class="text my-5">
                                    {{ user.firstName }} {{ user.lastName }}
                                </p>
                            </div>

                            <p
                                class="separator my-5"
                                v-if="userInd < basicData.userList.length - 1"
                            ></p>
                        </div>
                    </div>
                </div>
            </div>

            <!--Cargando-->
            <div class="loading-indicator" v-if="!summaryData">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <p class="text" data-size="14">Cargando datos del escritorio...</p>
            </div>

            <!---Tarjetas de información general-->
            <div
                class="d-flex justify-between f-wrap mt-20 mb-40"
                data-gap="30"
            >
                <!--Total consumo-->
                <div class="dashboard-card" v-if="summaryData">
                    <!--icono-->
                    <div class="icon">
                        <i class="far fa-lightbulb"></i>
                    </div>

                    <div class="info">
                        <p class="title">Total consumo</p>

                        <p class="value">{{ totalConsumption }}</p>
                    </div>
                </div>

                <!--Total contratos-->
                <div class="dashboard-card">
                    <!--icono-->
                    <div class="icon">
                        <i class="far fa-file-lines"></i>
                    </div>

                    <div class="info" v-if="summaryData">
                        <p class="title">Total contratos</p>

                        <p class="value">{{ summaryData.totalContracts }}</p>
                    </div>

                    <!--gráfica contratos-->
                    <div
                        class="graph"
                        ref="contractGraph"
                        style="width: 120px"
                    ></div>
                </div>

                <!--Total comisión-->
                <div class="dashboard-card" v-if="summaryData && !canManage('users.admiWhiHier')">
                    <!--icono-->
                    <div class="icon">
                        <i class="far fa-money-bill"></i>
                    </div>

                    <div class="info" :class="{ half: canManage('contracts.manageCommissions') }">
                        <div>
                            <p class="title">
                                Total comisiones
                                {{ canManage("contracts.manageCommissions") ? "agentes" : "" }}
                            </p>

                            <p class="value">
                                {{
                                    Math.round(
                                        summaryData.agentCommission ?? 0
                                    ).toLocaleString("es-ES")
                                }}
                                <span :class="{'points': basicData && basicData.userLogged.commInPoints}">{{ basicData && !basicData.userLogged.commInPoints ? '€' : 'pts.' }}</span>
                            </p>
                        </div>

                        <div v-if="canManage('contracts.manageCommissions')">
                            <p class="title">
                                Total comisiones {{ basicData.enterprise.name }}
                            </p>

                            <p class="value">
                                {{
                                    Math.round(
                                        summaryData.subdomainCommission ?? 0
                                    ).toLocaleString("es-ES")
                                }}
                                <span :class="{'points': basicData && basicData.userLogged.commInPoints}">{{ basicData && !basicData.userLogged.commInPoints ? '€' : 'pts.' }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!--Total agentes-->
                <div class="dashboard-card" v-if="summaryData">
                    <!--icono-->
                    <div class="icon">
                        <i class="far fa-users"></i>
                    </div>

                    <div class="info">
                        <p class="title">Total agentes</p>

                        <p class="value">{{ summaryData.totalAgents }}</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Contratos por estado versión móvil -->
        <div class="dash-status-shared" v-if="contractsPerStatusData && contractsPerStatusData.length">
            <div class="mobile-item dash-status-mobile-card">
                <div class="dash-status-mobile-header" @click="isSeeingStatusMobile = !isSeeingStatusMobile">
                    <div class="dash-status-mobile-title">
                        <i class="far fa-chart-bar"></i>
                        <span>Contratos por estado</span>
                    </div>

                    <i
                        class="fa-solid"
                        :class="isSeeingStatusMobile ? 'fa-chevron-up' : 'fa-chevron-down'"
                    ></i>
                </div>

                <div class="dash-status-mobile-list" v-show="isSeeingStatusMobile">
                    <div
                        v-for="(item, index) in contractsPerStatusData"
                        :key="'m' + index"
                        class="dash-status-mobile-item pointer"
                        :style="{
                    '--status-color': getCssColor(item.color),
                    borderLeftColor: getCssColor(item.color)
                }"
                        @click="goToOrdersWithFilter(item.category)"
                    >
                        <div class="dash-status-mobile-main">
                            <div class="dash-status-mobile-info">
                                <p class="dash-status-mobile-name" :style="{ color: getCssColor(item.color) }">
                                    {{ item.title }}
                                </p>

                                <p class="dash-status-mobile-subtitle">
                                    Ver contratos
                                </p>
                            </div>

                            <div class="dash-status-mobile-value">
                                <strong>{{ item.value.toLocaleString('es-ES') }}</strong>
                                <span>contratos</span>
                            </div>
                        </div>

                        <div
                            class="status-commissions mobile"
                            v-if="this.basicData.userSubdomain === '65cb57489c2c285441086a43'"
                        >
                            <div class="status-commission-row">
                                <span>Com. {{ canManage('contracts.manageCommissions') ? 'agentes' : '' }}</span>
                                <strong>{{ formatDashboardMoney(getStatusAgentCommission(item)) }}</strong>
                            </div>

                            <div
                                v-if="canManage('contracts.manageCommissions')"
                                class="status-commission-row"
                            >
                                <span>Com. {{ basicData.enterprise.name }}</span>
                                <strong>{{ formatDashboardMoney(getStatusSubdomainCommission(item)) }}</strong>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="pendingRenewals && pendingRenewals.value > 0 && this.basicData?.userSubdomain?._id !== '6909faa9232c09035a03f3b2'"
                        class="dash-status-mobile-item renewal pointer"
                        style="--status-color: var(--naranja); border-left-color: var(--naranja);"
                        @click="goToOrdersWithFilter(pendingRenewals.orders)"
                    >
                        <div class="dash-status-mobile-main">
                            <div class="dash-status-mobile-info">
                                <p class="dash-status-mobile-name" style="color: var(--naranja);">
                                    {{ pendingRenewals.title }}
                                </p>

                                <p class="dash-status-mobile-subtitle">
                                    Ver renovaciones
                                </p>
                            </div>

                            <div class="dash-status-mobile-value">
                                <strong>{{ pendingRenewals.value.toLocaleString('es-ES') }}</strong>
                                <span>contratos</span>
                            </div>
                        </div>

                        <div
                            class="status-commissions mobile"
                            v-if="this.basicData.userSubdomain === '65cb57489c2c285441086a43'"
                        >
                            <div class="status-commission-row">
                                <span>Com. {{ canManage('contracts.manageCommissions') ? 'agentes' : '' }}</span>
                                <strong>{{ formatDashboardMoney(getStatusAgentCommission(pendingRenewals)) }}</strong>
                            </div>

                            <div
                                v-if="canManage('contracts.manageCommissions')"
                                class="status-commission-row"
                            >
                                <span>Com. {{ basicData.enterprise.name }}</span>
                                <strong>{{ formatDashboardMoney(getStatusSubdomainCommission(pendingRenewals)) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!--Gráficas compartidas (visibles en PC y móvil)-->
        <div class="graphs-container my-40 shared-graphs" data-gap="10">
            <!--Gráfica contratos por comercializadora-->
            <div class="graph-card three">
                <div class="title">Contratos por comercializadora</div>
                <div class="w-100 h-300-px graph" ref="chartContractPerMarketers"></div>
            </div>

            <!--Gráfica contratos por agente-->
            <div class="graph-card three">
                <div class="title">Contratos por agente</div>
                <div class="w-100 h-300-px graph" ref="chartContractPerAgent"></div>
            </div>

            <!--Gráfica consumo por agente-->
            <div class="graph-card three">
                <div class="title">Consumo por agente</div>
                <div class="w-100 h-300-px graph" ref="chartConsumePerUser"></div>
            </div>
        </div>

        <!-- Widget precios electricidad debajo de consumo por agente -->
        <div class="dashboard-pvpc-widget-row">
            <PvpcDashboardComponent />
        </div>

        <!--Contratos por estado (colapsable en móvil)-->
        <div class="dash-status-shared" v-if="contractsPerStatusData && contractsPerStatusData.length">

            <!-- Versión desktop -->
            <div class="desktop-item dashboard-card w-100 mt-20">
                <div class="graph-card three mt-20">
                    <div class="graph-title-centered">
                        <i class="far fa-chart-bar mr-8"></i>
                        <span>Número de contratos por estado</span>
                    </div>
                    <div class="p-10">
                        <div
                            v-if="contractsPerStatusData && contractsPerStatusData.length"
                            class="status-list-row"
                        >
                            <div
                                v-for="(item, index) in contractsPerStatusData"
                                :key="'d' + index"
                                class="status-item clean"
                                :style="{
                            borderColor: getCssColor(item.color),
                            '--status-color': getCssColor(item.color)
                        }"
                                @click="goToOrdersWithFilter(item.category)"
                            >
                                <p class="text status-title" :style="{ color: getCssColor(item.color) }">
                                    {{ item.title }}
                                </p>

                                <p class="text status-value">
                                    {{ item.value.toLocaleString('es-ES') }} contratos
                                </p>

                                <div
                                    v-if="this.basicData.userSubdomain === '65cb57489c2c285441086a43'"
                                    class="status-commissions"
                                >
                                    <div class="status-commission-row">
                                        <span>Com. {{ canManage('contracts.manageCommissions') ? 'agentes' : '' }}</span>
                                        <strong>{{ formatDashboardMoney(getStatusAgentCommission(item)) }}</strong>
                                    </div>

                                    <div
                                        v-if="canManage('contracts.manageCommissions')"
                                        class="status-commission-row"
                                    >
                                        <span>Com. {{ basicData.enterprise.name }}</span>
                                        <strong>{{ formatDashboardMoney(getStatusSubdomainCommission(item)) }}</strong>
                                    </div>
                                </div>
                            </div>
                            <div v-if="pendingRenewals && pendingRenewals.value > 0 && this.basicData?.userSubdomain?._id !== '6909faa9232c09035a03f3b2'"
                                 class="status-item renewal"
                                 style="border-color: var(--naranja); --status-color: var(--naranja);"
                                 @click="goToOrdersWithFilter(pendingRenewals.orders)">
                                <p class="text status-title" style="color: var(--naranja);">{{ pendingRenewals.title }}</p>

                                <p class="text status-value">
                                    {{ pendingRenewals.value.toLocaleString('es-ES') }} contratos
                                </p>

                                <div
                                    v-if="this.basicData.userSubdomain === '65cb57489c2c285441086a43'"
                                    class="status-commissions"
                                >
                                    <div class="status-commission-row">
                                        <span>Com. {{ canManage('contracts.manageCommissions') ? 'agentes' : '' }}</span>
                                        <strong>{{ formatDashboardMoney(getStatusAgentCommission(pendingRenewals)) }}</strong>
                                    </div>

                                    <div
                                        v-if="canManage('contracts.manageCommissions')"
                                        class="status-commission-row"
                                    >
                                        <span>Com. {{ basicData.enterprise.name }}</span>
                                        <strong>{{ formatDashboardMoney(getStatusSubdomainCommission(pendingRenewals)) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="no-data text-center p-20 text-muted">
                            No hay datos en el rango seleccionado.
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <!--Gráficas por fecha compartidas-->
        <div class="graphs-container my-40 shared-graphs" data-gap="10">
            <div class="graph-card two">
                <div class="title">Consumo por fecha</div>
                <div class="w-100 h-300-px graph" ref="chartConsumePerDates"></div>
            </div>
            <div class="graph-card two">
                <div class="title">Contratos por fecha</div>
                <div class="w-100 h-300-px graph" ref="chartContractsPerDates"></div>
            </div>
        </div>
    </div>
</template>

<script>
import * as am5 from "@amcharts/amcharts5";
import * as am5xy from "@amcharts/amcharts5/xy";
import * as am5percent from "@amcharts/amcharts5/percent";
import am5themes_Animated from "@amcharts/amcharts5/themes/Animated";
import PvpcDashboardComponent from "@/components/items/PvpcDashboardComponent.vue";

export default {
    components: {
        PvpcDashboardComponent,
    },

    props: ["basicData"],
    data() {
        return {
            contractsPerStatusData: [],
            pendingRenewals: null,
            isSeeingStatusMobile: false,
            summaryData: null,
            generalData: null,
            smallContractGraph: null,
            contractAndConsumePerDatesGraphData: null,
            contractPerMarketerData: null,
            consumePerDatesData: null,
            consumePerAgentData: null,
            temporalDates: {
                start: "01/10/2024",
                end: "31/10/2024",
            },
            dates: {
                start: "",
                end: "",
            },
            contractGraphGranularity: null,
            /*
             * start: '17/03/2024',
             * end: '30/10/2024'
             *
             */
            userSelected: undefined,
            userSelectedHierarchy: [],
            isSeeingFiltersPc: {
                dates: false,
                user: false,
            },
        };
    },
    async created() {

        if (!this.userSelected && !!this.basicData.userList) {

            if (this.basicData?.userSubdomain?._id !== '6909faa9232c09035a03f3b2')
                this.dates.start = moment().subtract(1, "month").add(1, "day").format("YYYY-MM-DD");
            else {
                let res = await axios.get('/api/dashboard/getFirstDate', {params: { userSubdomain: this.basicData?.userSubdomain?._id }});

                this.dates.start = moment(res.data.firstDate).format("YYYY-MM-DD");
            }

            this.dates.end = moment().endOf("month").format("YYYY-MM-DD");


            //Selecciono el usuario propio de primeras
            if (this.basicData.userLogged._id === "65cb57489c2c285441086a43") {
                let foundedUser = this.basicData.userList.find(
                    (usuario) => usuario._id === "65fd4c2f05efc4aa4a050dc2"
                );
                this.userSelected = foundedUser;

                this.getUserHierarchy(this.userSelected._id, "charge");
            } else {
                if(this.canManage('users.admiWhiHier')){
                    this.userSelected = this.basicData.userSubdomain;

                    this.userSelectedHierarchy = this.basicData.subdomainUserList;
                }else{
                    this.userSelected = this.basicData.userLogged;

                    //Selecciono el listado de usuarios propio de primeras
                    this.userSelectedHierarchy = this.basicData.userList;
                }

                //SACO LOS DATOS NECESARIOS

                //Saco los datos generales para las cards
                this.fetchGeneralData();

                //Saco gráfica pequeña contratos
                this.fetchcontractAndConsumePerDatesGraphData();

                //Saco datos contratos por comercializadora
                this.fetchContractPerMarketerData();

                //Saco datos de consumo por agente
                this.fetchConsumePerAgentGraphData();

                this.fetchContractsPerStatus();
            }
        }
    },
    watch: {
        async "basicData.userList"(newValue) {
            //Si ha cargado
            if (newValue) {

                if (this.basicData?.userSubdomain?._id !== '6909faa9232c09035a03f3b2')
                    this.dates.start = moment().subtract(1, "month").add(1, "day").format("YYYY-MM-DD");
                else {
                    let res = await axios.get('/api/dashboard/getFirstDate', { params: { userSubdomain: this.basicData?.userSubdomain?._id }});

                    this.dates.start = moment(res.data.firstDate).format("YYYY-MM-DD");
                }

                this.dates.end = moment().endOf("month").format("YYYY-MM-DD");

                if (!this.userSelected) {
                    //Selecciono el usuario propio de primeras
                    if (
                        this.basicData.userLogged._id ===
                        "65cb57489c2c285441086a43"
                    ) {
                        let foundedUser = this.basicData.userList.find(
                            (usuario) =>
                                usuario._id === "65fd4c2f05efc4aa4a050dc2"
                        );
                        this.userSelected = foundedUser;

                        this.getUserHierarchy(this.userSelected._id, "charge");
                    } else {
                        if(this.canManage('users.admiWhiHier')){
                            this.userSelected = this.basicData.userSubdomain;

                            this.userSelectedHierarchy = this.basicData.subdomainUserList;
                        }else{
                            this.userSelected = this.basicData.userLogged;

                            //Selecciono el listado de usuarios propio de primeras
                            this.userSelectedHierarchy = newValue;
                        }

                        //SACO LOS DATOS NECESARIOS
                        //Saco los datos generales para las cards
                        this.fetchGeneralData();

                        //Saco gráfica pequeña contratos
                        this.fetchcontractAndConsumePerDatesGraphData();

                        //Saco datos contratos por comercializadora
                        this.fetchContractPerMarketerData();

                        //Saco datos de consumo por agente
                        this.fetchConsumePerAgentGraphData();

                        this.fetchContractsPerStatus();
                    }
                }
            }
        },
    },
    methods: {
        //Saco los datos en funciones separadas y directamente filtrado en la consulta para no tener que sacar todos los contratos para hacerlo en js pq sobrecargaría

        goToOrdersWithFilter(statusCode = null) {
            const isRenewal = Array.isArray(statusCode)

            // Si el usuario seleccionado es el propio logueado o el subdominio (vista "todos"),
            // dejamos agents vacío para que el backend aplique la jerarquía por defecto.
            // Si es un agente específico, pasamos su ID + subordinados para que la lista filtre igual que el dashboard.
            const isDefaultView =
                !this.userSelected ||
                this.userSelected._id === this.basicData?.userLogged?._id ||
                this.userSelected._id === this.basicData?.userSubdomain?._id ||
                this.userSelected._id === '65fd4c2f05efc4aa4a050dc2'

            const agentIds = isDefaultView
                ? []
                : [...new Set([this.userSelected._id, ...this.userSelectedHierarchy.map(u => u._id)])]

            const newFilters = isRenewal
                ? {
                    agents:          agentIds,
                    statuses:        [],
                    creationDates:   { start: null, end: null },
                    activationDates: { start: null, end: null },
                    lowDates:        { start: null, end: null },
                    renewalDates:    { start: this.dates.start, end: this.dates.end },
                    productTypes:    [],
                    fees:            [],
                    marketers:       [],
                    products:        [],
                    subdomains:      null,
                }
                : {
                    agents:          agentIds,
                    statuses:        statusCode ? [statusCode] : [],
                    creationDates:   { start: this.dates.start, end: this.dates.end },
                    activationDates: { start: null, end: null },
                    lowDates:        { start: null, end: null },
                    renewalDates:    { start: null, end: null },
                    productTypes:    [],
                    fees:            [],
                    marketers:       [],
                    products:        [],
                    subdomains:      null,
                }

            sessionStorage.setItem('orderFilters', JSON.stringify(newFilters))
            this.$router.push('/contracts')
        },


        getCssColor(name) {
            if (!name) return '#e0e0e0';
            if (name.startsWith('#') || name.startsWith('rgb')) return name;

            const temp = document.createElement('div');
            // 👇 Ajuste: añadimos el prefijo 'color-'
            temp.style.color = `var(--color-${name})`;
            document.body.appendChild(temp);
            const computedColor = getComputedStyle(temp).color;
            document.body.removeChild(temp);
            return computedColor || '#e0e0e0';
        },

        async fetchGeneralData() {
            //Si es visualizador le meto lo de Subdominio
            let userHierarchyIds =
                this.userSelected._id === "65fd4c2f05efc4aa4a050dc2"
                    ? this.basicData.userList.map((user) => user._id)
                    : this.userSelectedHierarchy.map((user) => user._id);

            await axios
                .post("/api/dashboard/getGeneralData", {
                    dates: {
                        start: moment(this.dates.start).format("DD/MM/YYYY"),
                        end: moment(this.dates.end).format("DD/MM/YYYY"),
                    },
                    userSubdomain: this.basicData.userSubdomain,
                    usersIds: userHierarchyIds,
                    userSelected:
                        this.userSelected._id === "65fd4c2f05efc4aa4a050dc2"
                            ? this.basicData.userLogged
                            : this.userSelected,
                })
                .then((res) => {
                    this.summaryData = {
                        totalContracts: res.data.totalContracts,
                        totalConsumption: res.data.totalConsumption,
                        agentCommission: res.data.agentCommission,
                        subdomainCommission: res.data.subdomainCommission,
                        totalAgents: res.data.totalAgents,
                    };
                })
                .catch((err) => {
                    console.log(err);
                });
        },

        //Contratos por comercializadora
        async fetchContractPerMarketerData() {
            let userHierarchyIds =
                this.userSelected._id === "65fd4c2f05efc4aa4a050dc2"
                    ? this.basicData.userList.map((user) => user._id)
                    : this.userSelectedHierarchy.map((user) => user._id);

            await axios
                .post("/api/dashboard/getContractPerMarketerData", {
                    dates: {
                        start: moment(this.dates.start).format("DD/MM/YYYY"),
                        end: moment(this.dates.end).format("DD/MM/YYYY"),
                    },
                    usersIds: userHierarchyIds,
                    userSelected:
                        this.userSelected._id === "65fd4c2f05efc4aa4a050dc2"
                            ? this.basicData.userLogged
                            : this.userSelected,
                })
                .then((res) => {
                    this.contractPerMarketerData =
                        res.data.contractPerMarketerData;

                    //Gráfica contratos por comercializadora
                    this.loadContractsPerMarketerGraph();
                })
                .catch((err) => {
                    console.log(err);
                });
        },
        loadContractsPerMarketerGraph() {
            // CREO EL ELEMENTO ROOT
            let root = am5.Root.new(this.$refs.chartContractPerMarketers);

            // ESTABLEZCO LOS TEMAS
            root.setThemes([am5themes_Animated.new(root)]);

            const isMobile = window.innerWidth <= 810;

            // Tamaño mínimo para que en móvil no se haga enana
            if (isMobile) {
                this.$refs.chartContractPerMarketers.style.minHeight = "550px";
                this.$refs.chartContractPerMarketers.style.height = "550px";
            }

            // Crear gráfico
            let chart = root.container.children.push(
                am5percent.PieChart.new(root, {
                    layout: root.verticalLayout,
                    paddingTop: isMobile ? 0 : undefined,
                    paddingBottom: isMobile ? 0 : undefined,
                    paddingLeft: isMobile ? 0 : undefined,
                    paddingRight: isMobile ? 0 : undefined,
                })
            );

            // Crear serie
            let series = chart.series.push(
                am5percent.PieSeries.new(root, {
                    valueField: "totalContracts",
                    categoryField: "marketer",
                })
            );

            let data = this.contractPerMarketerData;

            data.forEach((marketer) => {
                switch (marketer.marketer) {
                    case "CYE":
                        marketer.icon = "/assets/marketers_logo_with_bg/CYE-logo.png";
                        marketer.color = am5.color("rgba(170, 192, 75, 1)");
                        break;

                    case "Repsol":
                        marketer.icon = "/assets/marketers_logo_with_bg/repsol-logo.png";
                        marketer.color = am5.color("rgba(154, 45, 45, 1)");
                        break;

                    case "Unieléctrica":
                        marketer.icon = "/assets/marketers_logo_with_bg/Unieléctrica-logo.png";
                        marketer.color = am5.color("rgba(32, 72, 148, 1)");
                        break;

                    case "Naturgy":
                        marketer.icon = "/assets/marketers_logo_with_bg/naturgy-logo.png";
                        marketer.color = am5.color("rgba(221, 121, 46, 1)");
                        break;

                    case "VM":
                        marketer.icon = "/assets/marketers_logo_with_bg/vm-logo.png";
                        marketer.color = am5.color("rgba(126, 95, 82, 1)");
                        break;

                    case "Sin comercializadora":
                        marketer.icon = "/assets/marketers_logo_with_bg/sin-comercializadora-logo.png";
                        marketer.color = am5.color("rgba(245, 191, 65, 1)");
                        break;

                    case "Gana Energía":
                        marketer.icon = "/assets/marketers_logo_with_bg/Gana-energia-logo.png";
                        marketer.color = am5.color("rgba(94, 204, 169, 1)");
                        break;

                    case "Endesa":
                        marketer.icon = "/assets/marketers_logo_with_bg/endesa-logo.png";
                        marketer.color = am5.color("rgba(176, 219, 240, 1)");
                        break;

                    case "IberEléctrica":
                        marketer.icon = "/assets/marketers_logo_with_bg/IberElectrica-logo.png";
                        marketer.color = am5.color("rgba(189, 178, 71, 1)");
                        break;

                    case "Iberdrola":
                        marketer.icon = "/assets/marketers_logo_with_bg/iberdrola-logo.png";
                        marketer.color = am5.color("rgba(101, 134, 54, 1)");
                        break;
                }
            });

            // Ordenar los datos según el valor de totalContracts
            data.sort((a, b) => a.totalContracts - b.totalContracts);

            // Asignar los colores a cada segmento de la gráfica
            series.slices.template.adapters.add("fill", function (fill, target) {
                let marketer = target.dataItem.dataContext;
                return marketer.color || fill;
            });

            // Tooltip al pasar/pulsar
            series.slices.template.setAll({
                tooltipText: "{category}: {value}",
            });

            if (isMobile) {
                // MÓVIL: ocultar labels externos
                series.labels.template.setAll({
                    visible: false,
                    forceHidden: true,
                });

                series.ticks.template.setAll({
                    visible: false,
                    forceHidden: true,
                });

                // MÓVIL: gráfico más grande
                series.setAll({
                    radius: am5.percent(100),
                    innerRadius: 0,
                });
            } else {
                // PC: labels como estaban antes
                series.labels.template.setAll({
                    text: "{category}: {totalContracts}",
                    fontSize: "12px",
                    visible: true,
                    forceHidden: false,
                });

                series.ticks.template.setAll({
                    visible: true,
                    forceHidden: false,
                });
            }

            // Establecer los datos de la serie
            series.data.setAll(data);

            if (isMobile) {
                // Leyenda debajo del gráfico solo en móvil
                let legend = chart.children.push(
                    am5.Legend.new(root, {
                        centerX: am5.percent(50),
                        x: am5.percent(50),
                        marginTop: 5,
                        layout: root.gridLayout,
                    })
                );

                legend.labels.template.setAll({
                    text: "{category}",
                    fontSize: "12px",
                    maxWidth: 130,
                    oversizedBehavior: "truncate",
                });

                legend.valueLabels.template.setAll({
                    text: "{value}",
                    fontSize: "12px",
                });

                // Cuadraditos de color de la leyenda
                legend.markers.template.setAll({
                    width: 12,
                    height: 12,
                });

                legend.markerRectangles.template.setAll({
                    cornerRadiusTL: 2,
                    cornerRadiusTR: 2,
                    cornerRadiusBL: 2,
                    cornerRadiusBR: 2,
                });

                legend.markerRectangles.template.adapters.add("fill", function (fill, target) {
                    const dataItem = target.dataItem;

                    if (
                        dataItem &&
                        dataItem.dataContext &&
                        dataItem.dataContext.dataContext &&
                        dataItem.dataContext.dataContext.color
                    ) {
                        return dataItem.dataContext.dataContext.color;
                    }

                    return fill;
                });

                legend.markerRectangles.template.adapters.add("stroke", function (stroke, target) {
                    const dataItem = target.dataItem;

                    if (
                        dataItem &&
                        dataItem.dataContext &&
                        dataItem.dataContext.dataContext &&
                        dataItem.dataContext.dataContext.color
                    ) {
                        return dataItem.dataContext.dataContext.color;
                    }

                    return stroke;
                });

                legend.data.setAll(series.dataItems);
            }

            // Animaciones
            series.appear();
            chart.appear(1000, 100);

            // Elimino el logo
            root._logo.dispose();

            this.root4 = root;
        },
        /*loadContractsPerMarketerGraph(){

            //CREO EL ELEMENTO ROOT
            let root = am5.Root.new(this.$refs.chartContractPerMarketers);

            //ESTABLEZCO LOS TEMAS
            root.setThemes([
                am5themes_Animated.new(root)
            ]);


            //CREO LA GRÁFICA
            let chart = root.container.children.push(am5xy.XYChart.new(root, {
                panX: false,
                panY: false,
                wheelX: "panX",
                wheelY: "zoomX",
                paddingLeft: 0,
                layout: root.verticalLayout
            }));



            //DATOS TEMPORALES
            let colors = chart.get("colors");


            let data = this.contractPerMarketerData;

            data.forEach((marketer) => {

                marketer.columnSettings = { fill: colors.next() }

                switch(marketer.marketer) {
                    case 'CYE':
                        marketer.icon = "/assets/marketers_logo_with_bg/CYE-logo.png"
                        break;

                    case 'Repsol':
                        marketer.icon = "/assets/marketers_logo_with_bg/repsol-logo.png"
                        break;

                    case 'Unieléctrica':
                        marketer.icon = "/assets/marketers_logo_with_bg/Unieléctrica-logo.png"
                        break;

                    case 'Naturgy':
                        marketer.icon = "/assets/marketers_logo_with_bg/naturgy-logo.png"
                        break;

                    case 'VM':
                        marketer.icon = "/assets/marketers_logo_with_bg/vm-logo.png"
                        break;

                    case 'Sin comercializadora':
                        marketer.icon = "/assets/marketers_logo_with_bg/sin-comercializadora-logo.png"
                        break;

                    case 'Gana Energía':
                        marketer.icon = "/assets/marketers_logo_with_bg/Gana-energia-logo.png"
                        break;

                    case 'Endesa':
                        marketer.icon = "/assets/marketers_logo_with_bg/endesa-logo.png"
                        break;

                    case 'IberEléctrica':
                        marketer.icon = "/assets/marketers_logo_with_bg/IberElectrica-logo.png"
                        break;

                    case 'Iberdrola':
                        marketer.icon = "/assets/marketers_logo_with_bg/iberdrola-logo.png"
                        break;

                }

            })



            //CREO LOS EJES
            let xRenderer = am5xy.AxisRendererX.new(root, {
                minGridDistance: 30,
                minorGridEnabled: true
            })


            //EJE X
            let xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                categoryField: "marketer",
                renderer: xRenderer,
                bullet: function (root, axis, dataItem) {
                    return am5xy.AxisBullet.new(root, {
                        location: 0.5,
                        sprite: am5.Picture.new(root, {
                            width: 35,
                            height: 35,
                            centerY: am5.p50,
                            centerX: am5.p50,
                            y: 25,
                            src: dataItem.dataContext.icon
                        })
                    });
                }
            }));

            xRenderer.grid.template.setAll({
                location: 1
            })

            xRenderer.labels.template.setAll({
                visible: false
            });

            xAxis.data.setAll(data);


            //EJE Y
            let yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                renderer: am5xy.AxisRendererY.new(root, {
                    strokeOpacity: 0.1
                })
            }));


            //SERIES
            let series = chart.series.push(am5xy.ColumnSeries.new(root, {
                xAxis: xAxis,
                yAxis: yAxis,
                valueYField: "totalContracts",
                categoryXField: "marketer"
            }));

            series.columns.template.setAll({
                tooltipText: "{categoryX}: {valueY}",
                tooltipY: 0,
                strokeOpacity: 0,
                templateField: "columnSettings"
            });

            series.data.setAll(data);

            series.appear();
            chart.appear(1000, 100);

            //Elimino el logo
            root._logo.dispose();

            this.root4 = root;
        },*/

        //Gráfica contratos por agente
        loadContractsPerAgentGraph() {
            let root = am5.Root.new(this.$refs.chartContractPerAgent);

            // Configuración de temas
            root.setThemes([am5themes_Animated.new(root)]);

            let data = this.consumePerAgentData;

            // Agregar imágenes a los datos
            data.forEach((agent) => {
                agent.bulletSettings = {
                    src: "https://www.amcharts.com/lib/images/faces/A04.png",
                };

                if (!agent.totalConsumption) agent.totalConsumption = 0;

                agent.color = am5.color(
                    `rgba(${Math.floor(Math.random() * 256)}, ${Math.floor(
                        Math.random() * 256
                    )}, ${Math.floor(Math.random() * 256)}, 1)`
                );
            });

            //Ordeno por consumo
            data.sort((a, b) => a.totalContracts - b.totalContracts);

            // Crear gráfico
            let chart = root.container.children.push(
                am5xy.XYChart.new(root, {
                    panX: false,
                    panY: false,
                    wheelX: "none",
                    wheelY: "none",
                    layout: root.verticalLayout,
                })
            );

            // Configurar eje Y (categorías)
            let yAxis = chart.yAxes.push(
                am5xy.CategoryAxis.new(root, {
                    categoryField: "agent",
                    renderer: am5xy.AxisRendererY.new(root, {
                        minGridDistance: 20, // Ajusta la distancia entre etiquetas
                    }),
                })
            );

            // Centrar etiquetas del eje Y
            yAxis.get("renderer").labels.template.setAll({
                textAlign: "center", // Centra texto horizontalmente
                centerY: am5.p50, // Centra texto verticalmente
                maxWidth: 80, // Limita el ancho máximo de las etiquetas a 100px (ajusta según tus necesidades)
                oversizedBehavior: "truncate",
                fontSize: "12px",
            });

            // Configurar eje X (valores)
            let xAxis = chart.xAxes.push(
                am5xy.ValueAxis.new(root, {
                    min: 0,
                    renderer: am5xy.AxisRendererX.new(root, {
                        minGridDistance: 80,
                    }),
                })
            );

            xAxis.get("renderer").labels.template.setAll({
                fontSize: "12px",
            });

            // Crear series
            let series = chart.series.push(
                am5xy.ColumnSeries.new(root, {
                    name: "Total Consumption",
                    xAxis: xAxis,
                    yAxis: yAxis,
                    valueXField: "totalContracts",
                    categoryYField: "agent",
                })
            );

            // Configurar barras
            series.columns.template.setAll({
                width: am5.percent(90), // Ajusta el ancho de las barras
                centerY: am5.p50, // Centra las barras verticalmente
                fillOpacity: 0.8,
                strokeOpacity: 0,
                cornerRadiusBR: 10,
                cornerRadiusTR: 10,
                cornerRadiusBL: 0,
                cornerRadiusTL: 0,
                maxHeight: 50,
                tooltipText:
                    "[bold]{agent}[/]\nContratos: [bold]{totalContracts}[/]", // Tooltip personalizado
            });

            //Establezco colores de barras
            series.columns.template.adapters.add(
                "fill",
                function (color, target) {
                    let dataContext = target.dataItem.dataContext; // Obtiene el contexto de datos de la barra
                    return dataContext.color || color; // Si el color está en los datos, lo asigna. Si no, usa el valor predeterminado.
                }
            );

            // Agregar datos
            series.data.setAll(data);
            yAxis.data.setAll(data);

            // Habilitar scrollbar vertical interno
            chart.set(
                "scrollbarY",
                am5.Scrollbar.new(root, {
                    orientation: "vertical",
                })
            );

            // Configurar zoom inicial después de que los datos sean validados
            yAxis.events.on("datavalidated", function () {
                // Calcular el número de agentes
                const totalAgents = data.length;

                // Ajustar dinámicamente el zoom en función de la cantidad de agentes
                if (totalAgents <= 10) {
                    // Si hay 10 o menos agentes, mostramos todo sin zoom
                    yAxis.set("start", 0); // Empieza desde el principio (primer agente)
                    yAxis.set("end", 1); // Muestra todos los agentes
                } else if (totalAgents <= 30) {
                    // Si hay entre 11 y 30 agentes, hacemos un zoom de 70% al inicio
                    yAxis.set("start", 0.7); // Empieza desde el principio
                } else {
                    // Si hay más de 30 agentes, hacemos un zoom de 50% al inicio
                    yAxis.set("start", 0.85); // Empieza desde el principio
                }
            });

            // Animaciones iniciales
            series.appear(1000);
            chart.appear(1000, 100);

            // Eliminar logo de amCharts
            root._logo.dispose();

            this.root6 = root;
        },

        //Gráficas contratos por fecha
        async fetchcontractAndConsumePerDatesGraphData() {
            //Saco los ids de la jerarquía
            let userHierarchyIds =
                this.userSelected._id === "65fd4c2f05efc4aa4a050dc2"
                    ? this.basicData.userList.map((user) => user._id)
                    : this.userSelectedHierarchy.map((user) => user._id);

            await axios
                .post("/api/dashboard/contractsAndConsumePerDate", {
                    dates: {
                        start: moment(this.dates.start).format("DD/MM/YYYY"),
                        end: moment(this.dates.end).format("DD/MM/YYYY"),
                    },
                    usersIds: userHierarchyIds,
                    userSelected:
                        this.userSelected._id === "65fd4c2f05efc4aa4a050dc2"
                            ? this.basicData.userLogged
                            : this.userSelected,
                })
                .then((res) => {
                    this.contractAndConsumePerDatesGraphData = JSON.parse(
                        JSON.stringify(res.data.computedData)
                    );

                    this.contractGraphGranularity = res.data.granularity;

                    //Ahora que ya han cargado los datos cargo la gráfica
                    this.loadSmallContractPerDatesGraph();
                    this.loadBigContractPerDatesGraph();
                    this.loadConsumePerDatesGraph();
                })
                .catch((err) => {
                    console.log(err);
                });
        },
        loadSmallContractPerDatesGraph() {
            let root = am5.Root.new(this.$refs.contractGraph);

            // Set themes
            // https://www.amcharts.com/docs/v5/concepts/themes/
            root.setThemes([am5themes_Animated.new(root)]);

            // Create chart
            // https://www.amcharts.com/docs/v5/charts/xy-chart/
            let chart = root.container.children.push(
                am5xy.XYChart.new(root, {
                    panX: false,
                    panY: false,
                    //wheelX: "panX",
                    //wheelY: "zoomX",
                    //pinchZoomX:true,
                    paddingLeft: 0,
                    paddingRight: 0,
                })
            );

            //elimino el botón de zoom
            chart.zoomOutButton.set("forceHidden", true);

            // Create axes
            // https://www.amcharts.com/docs/v5/charts/xy-chart/axes/
            let xAxis = chart.xAxes.push(
                am5xy.CategoryAxis.new(root, {
                    categoryField: "period",
                    renderer: am5xy.AxisRendererX.new(root, {
                        minorGridEnabled: true,
                    }),
                    tooltip: am5.Tooltip.new(root, {}),
                })
            );

            xAxis.data.setAll(this.contractAndConsumePerDatesGraphData);

            //Borro el grid y los labels
            xAxis.get("renderer").labels.template.set("forceHidden", true);
            xAxis.get("renderer").grid.template.set("forceHidden", true);

            //Saco el valor maximo de count para ponerle algo de padding por encima
            let maxCount = Math.max(
                ...this.contractAndConsumePerDatesGraphData.map(
                    (item) => item.count
                )
            );

            let yAxis = chart.yAxes.push(
                am5xy.ValueAxis.new(root, {
                    renderer: am5xy.AxisRendererY.new(root, {
                        pan: "zoom",
                    }),
                    min: -0.5,
                    max: maxCount + 0.5,
                    strictMinMax: true,
                })
            );

            //Borro el grid y los labels
            yAxis.get("renderer").labels.template.set("forceHidden", true);
            yAxis.get("renderer").grid.template.set("forceHidden", true);

            // Add series
            let series = chart.series.push(
                am5xy.SmoothedXLineSeries.new(root, {
                    name: "Nº contratos",
                    xAxis: xAxis,
                    yAxis: yAxis,
                    valueYField: "count",
                    categoryXField: "period",
                    tooltip: am5.Tooltip.new(root, {
                        labelText: "{valueY}",
                    }),
                    stroke: am5.color("#2192FF"),
                })
            );

            series.strokes.template.setAll({
                strokeWidth: 3,
            });

            series.data.setAll(this.contractAndConsumePerDatesGraphData);

            // Make stuff animate on load
            // https://www.amcharts.com/docs/v5/concepts/animations/
            series.appear(1000);
            chart.appear(1000, 100);

            //Elimino el logo
            root._logo.dispose();

            //Asigno la gráfica para dps cambiar los datos
            this.smallContractGraph = chart;

            this.root = root;
        },
        loadBigContractPerDatesGraph() {
            let root = am5.Root.new(this.$refs.chartContractsPerDates);

            // Esstablezco el tema
            // https://www.amcharts.com/docs/v5/concepts/themes/
            root.setThemes([am5themes_Animated.new(root)]);

            // Creo la gráfica
            // https://www.amcharts.com/docs/v5/charts/xy-chart/
            let chart = root.container.children.push(
                am5xy.XYChart.new(root, {
                    panX: true,
                    panY: true,
                    wheelX: "panX",
                    wheelY: "zoomX",
                    pinchZoomX: true,
                    paddingLeft: 0,
                    paddingRight: 0,
                })
            );

            // Añado el cursor
            // https://www.amcharts.com/docs/v5/charts/xy-chart/cursor/
            let cursor = chart.set(
                "cursor",
                am5xy.XYCursor.new(root, {
                    behavior: "zoomX", // Permitir zoom en eje X
                })
            );

            //quito las lineas del cursor
            cursor.lineY.set("visible", false);
            cursor.lineX.set("visible", false);

            // Creo el eje x de tipo 'Categoría'
            // https://www.amcharts.com/docs/v5/charts/xy-chart/axes/
            let xAxis = chart.xAxes.push(
                am5xy.CategoryAxis.new(root, {
                    categoryField: "period",
                    renderer: am5xy.AxisRendererX.new(root, {
                        minorGridEnabled: true,
                        minGridDistance:
                            this.contractGraphGranularity === "week"
                                ? 130
                                : this.contractGraphGranularity === "month"
                                ? 70
                                : this.contractGraphGranularity === "day"
                                ? 100
                                : this.contractGraphGranularity === "hour"
                                ? 70
                                : 50,
                    }),
                    tooltip: am5.Tooltip.new(root, {}),
                })
            );

            //le meto los datos
            xAxis.data.setAll(this.contractAndConsumePerDatesGraphData);

            //Saco el valor maximo de count para ponerle algo de padding por encima
            let maxCount = Math.max(
                ...this.contractAndConsumePerDatesGraphData.map(
                    (item) => item.count
                )
            );

            //Creo el eje y de tipo 'Valores'
            let yAxis = chart.yAxes.push(
                am5xy.ValueAxis.new(root, {
                    renderer: am5xy.AxisRendererY.new(root, {
                        pan: "zoom",
                    }),
                })
            );

            //Creo las series de tipo 'Smooth' para que no tengan picos
            let series = chart.series.push(
                am5xy.SmoothedXLineSeries.new(root, {
                    name: "Nº contratos",
                    xAxis: xAxis,
                    yAxis: yAxis,
                    valueYField: "count",
                    categoryXField: "period",
                    tooltip: am5.Tooltip.new(root, {
                        labelText: "{valueY}",
                    }),
                    stroke: am5.color("#2192FF"),
                })
            );

            series.strokes.template.setAll({
                strokeWidth: 5,
            });

            //Relleno de color
            /*series.fills.template.setAll({
              visible: true,
              fillGradient: am5.LinearGradient.new(root, {
                stops: [
                  { color: am5.color(0x0000ff), opacity: 0.5 },  // Azul
                  { color: am5.color(0x0000ff), opacity: 0 }, // Transparente

                ],
                rotation: 90 // Orientación del degradado
              })
            });*/

            series.data.setAll(this.contractAndConsumePerDatesGraphData);

            // Make stuff animate on load
            // https://www.amcharts.com/docs/v5/concepts/animations/
            series.appear(1000);
            chart.appear(1000, 100);

            //Elimino el logo
            root._logo.dispose();

            //Asigno la gráfica para dps cambiar los datos
            this.smallContractGraph = chart;

            this.root5 = root;
        }, //gráfica grande

        //Consumo por fecha
        loadConsumePerDatesGraph() {
            let root = am5.Root.new(this.$refs.chartConsumePerDates);

            // Esstablezco el tema
            // https://www.amcharts.com/docs/v5/concepts/themes/
            root.setThemes([am5themes_Animated.new(root)]);

            // Creo la gráfica
            // https://www.amcharts.com/docs/v5/charts/xy-chart/
            let chart = root.container.children.push(
                am5xy.XYChart.new(root, {
                    panX: true,
                    panY: true,
                    wheelX: "panX",
                    wheelY: "zoomX",
                    pinchZoomX: true,
                    paddingLeft: 0,
                    paddingRight: 0,
                })
            );

            // Añado el cursor
            // https://www.amcharts.com/docs/v5/charts/xy-chart/cursor/
            let cursor = chart.set(
                "cursor",
                am5xy.XYCursor.new(root, {
                    behavior: "zoomX", // Permitir zoom en eje X
                })
            );

            //quito las lineas del cursor
            cursor.lineY.set("visible", false);
            cursor.lineX.set("visible", false);

            // Creo el eje x de tipo 'Categoría'
            // https://www.amcharts.com/docs/v5/charts/xy-chart/axes/
            let xAxis = chart.xAxes.push(
                am5xy.CategoryAxis.new(root, {
                    categoryField: "period",
                    renderer: am5xy.AxisRendererX.new(root, {
                        minorGridEnabled: true,
                        minGridDistance:
                            this.contractGraphGranularity === "week"
                                ? 130
                                : this.contractGraphGranularity === "month"
                                ? 70
                                : this.contractGraphGranularity === "day"
                                ? 100
                                : this.contractGraphGranularity === "hour"
                                ? 70
                                : 50,
                    }),
                    tooltip: am5.Tooltip.new(root, {}),
                })
            );

            //le meto los datos
            xAxis.data.setAll(this.contractAndConsumePerDatesGraphData);

            //Saco el valor maximo de count para ponerle algo de padding por encima
            let maxCount = Math.max(
                ...this.contractAndConsumePerDatesGraphData.map(
                    (item) => item.consumption
                )
            );

            //Creo el eje y de tipo 'Valores'
            let yAxis = chart.yAxes.push(
                am5xy.ValueAxis.new(root, {
                    renderer: am5xy.AxisRendererY.new(root, {
                        pan: "zoom",
                    }),
                })
            );

            //Creo las series de tipo 'Smooth' para que no tengan picos
            let series = chart.series.push(
                am5xy.SmoothedXLineSeries.new(root, {
                    name: "Nº contratos",
                    xAxis: xAxis,
                    yAxis: yAxis,
                    valueYField: "consumption",
                    categoryXField: "period",
                    tooltip: am5.Tooltip.new(root, {
                        labelText: "{valueY}",
                    }),
                    stroke: am5.color("#2192FF"),
                })
            );

            series.strokes.template.setAll({
                strokeWidth: 5,
            });

            //Relleno de color
            series.fills.template.setAll({
                visible: true,
                fillGradient: am5.LinearGradient.new(root, {
                    stops: [
                        { color: am5.color(0x0000ff), opacity: 0.5 }, // Azul
                        { color: am5.color(0x0000ff), opacity: 0 }, // Transparente
                    ],
                    rotation: 90, // Orientación del degradado
                }),
            });

            series.data.setAll(this.contractAndConsumePerDatesGraphData);

            // Make stuff animate on load
            // https://www.amcharts.com/docs/v5/concepts/animations/
            series.appear(1000);
            chart.appear(1000, 100);

            //Elimino el logo
            root._logo.dispose();

            //Asigno la gráfica para dps cambiar los datos
            this.smallContractGraph = chart;

            this.root7 = root;
        },

async fetchContractsPerStatus() {
  try {
    // 🔹 Definir IDs de jerarquía de usuario
    const userHierarchyIds =
      this.userSelected._id === "65fd4c2f05efc4aa4a050dc2"
        ? this.basicData.userList.map((user) => user._id)
        : this.userSelectedHierarchy.map((user) => user._id);

    // 🔹 Llamar al endpoint del backend
    const res = await axios.post("/api/dashboard/getContractsPerStatus", {
      dates: {
        start: moment(this.dates.start).format("YYYY-MM-DD"),
        end: moment(this.dates.end).format("YYYY-MM-DD"),
      },
      usersIds: userHierarchyIds,
      userSelected:
        this.userSelected._id === "65fd4c2f05efc4aa4a050dc2"
          ? this.basicData.userLogged
          : this.userSelected,
      userSubdomain: this.basicData.userSubdomain,
    });

    // ✅ Asignar datos del gráfico
    this.contractsPerStatusData = res.data.contractsPerStatus || [];
    this.pendingRenewals = res.data.pendingRenewals || {};


    // ================================================
    // 🔹 NUEVO BLOQUE: Guardar filtros obtenidos
    // ================================================
    if (res.data.filtersObtained) {
      const filtersObtained = res.data.filtersObtained;
      const existingFilters = JSON.parse(sessionStorage.getItem("filters")) || {};

      // Mezclar con lo que ya haya en sessionStorage
      existingFilters["orders"] = {
        ...(existingFilters["orders"] || {}),
        ...filtersObtained,
        // Valores base que conviene tener definidos
        dates: { start: "", end: "" },
        activationDates: { start: "", end: "" },
        lowDates: { start: "", end: "" },
        renewalPendingDates: { start: "", end: "" },
        perPage: 50,
        searchText: "",
        withoutCommision: 0,
      };


      sessionStorage.setItem("filters", JSON.stringify(existingFilters));

    }
  } catch (err) {
    console.error("❌ Error en getContractsPerStatus:", err);
  }
},






        //Gráfica consumo por agente
        async fetchConsumePerAgentGraphData() {
            //Saco los ids de la jerarquía
            let userHierarchyIds =
                this.userSelected._id === "65fd4c2f05efc4aa4a050dc2"
                    ? this.basicData.userList.map((user) => user._id)
                    : this.userSelectedHierarchy.map((user) => user._id);

            await axios
                .post("/api/dashboard/getConsumeAndContractsPerAgentData", {
                    dates: {
                        start: moment(this.dates.start).format("DD/MM/YYYY"),
                        end: moment(this.dates.end).format("DD/MM/YYYY"),
                    },
                    usersIds: userHierarchyIds,
                    userSelected:
                        this.userSelected._id === "65fd4c2f05efc4aa4a050dc2"
                            ? this.basicData.userLogged
                            : this.userSelected,
                })
                .then((res) => {
                    this.consumePerAgentData = JSON.parse(
                        JSON.stringify(res.data.consumePerAgentData)
                    );

                    //Cargo la gráfica de consumo por agente
                    this.loadConsumePerAgentGraph();

                    //Cargo la gráfica de contratos por agente
                    this.loadContractsPerAgentGraph();
                })
                .catch((err) => {
                    console.log(err);
                });
        },
        loadConsumePerAgentGraph() {
            let root = am5.Root.new(this.$refs.chartConsumePerUser);

            // Configuración de temas
            root.setThemes([am5themes_Animated.new(root)]);

            let data = this.consumePerAgentData;

            // Agregar imágenes a los datos
            data.forEach((agent) => {
                agent.bulletSettings = {
                    src: "https://www.amcharts.com/lib/images/faces/A04.png",
                };

                if (!agent.totalConsumption) agent.totalConsumption = 0;

                agent.color = am5.color(
                    `rgba(${Math.floor(Math.random() * 256)}, ${Math.floor(
                        Math.random() * 256
                    )}, ${Math.floor(Math.random() * 256)}, 1)`
                );
            });

            //Ordeno por consumo
            data.sort((a, b) => a.totalConsumption - b.totalConsumption);

            // Crear gráfico
            let chart = root.container.children.push(
                am5xy.XYChart.new(root, {
                    panX: false,
                    panY: false,
                    wheelX: "none",
                    wheelY: "none",
                    layout: root.verticalLayout,
                })
            );

            // Configurar eje Y (categorías)
            let yAxis = chart.yAxes.push(
                am5xy.CategoryAxis.new(root, {
                    categoryField: "agent",
                    renderer: am5xy.AxisRendererY.new(root, {
                        minGridDistance: 20, // Ajusta la distancia entre etiquetas
                    }),
                })
            );

            // Centrar etiquetas del eje Y
            yAxis.get("renderer").labels.template.setAll({
                textAlign: "center", // Centra texto horizontalmente
                centerY: am5.p50, // Centra texto verticalmente
                maxWidth: 80, // Limita el ancho máximo de las etiquetas a 100px (ajusta según tus necesidades)
                oversizedBehavior: "truncate",
                fontSize: "12px",
            });

            // Configurar eje X (valores)
            let xAxis = chart.xAxes.push(
                am5xy.ValueAxis.new(root, {
                    min: 0,
                    renderer: am5xy.AxisRendererX.new(root, {
                        minGridDistance: 80,
                    }),
                })
            );

            xAxis.get("renderer").labels.template.setAll({
                fontSize: "12px",
            });

            // Crear series
            let series = chart.series.push(
                am5xy.ColumnSeries.new(root, {
                    name: "Total Consumption",
                    xAxis: xAxis,
                    yAxis: yAxis,
                    valueXField: "totalConsumption",
                    categoryYField: "agent",
                })
            );

            // Configurar barras
            series.columns.template.setAll({
                width: am5.percent(90), // Ajusta el ancho de las barras
                centerY: am5.p50, // Centra las barras verticalmente
                fillOpacity: 0.8,
                strokeOpacity: 0,
                cornerRadiusBR: 10,
                cornerRadiusTR: 10,
                cornerRadiusBL: 0,
                cornerRadiusTL: 0,
                maxHeight: 50,
                tooltipText:
                    "[bold]{agent}[/]\nConsumo: [bold]{totalConsumption}[/]", // Tooltip personalizado
            });

            //Establezco colores de barras
            series.columns.template.adapters.add(
                "fill",
                function (color, target) {
                    let dataContext = target.dataItem.dataContext; // Obtiene el contexto de datos de la barra
                    return dataContext.color || color; // Si el color está en los datos, lo asigna. Si no, usa el valor predeterminado.
                }
            );

            // Agregar datos
            series.data.setAll(data);
            yAxis.data.setAll(data);

            // Habilitar scrollbar vertical interno
            chart.set(
                "scrollbarY",
                am5.Scrollbar.new(root, {
                    orientation: "vertical",
                })
            );

            // Configurar zoom inicial después de que los datos sean validados
            yAxis.events.on("datavalidated", function () {
                // Calcular el número de agentes
                const totalAgents = data.length;

                // Ajustar dinámicamente el zoom en función de la cantidad de agentes
                if (totalAgents <= 10) {
                    // Si hay 10 o menos agentes, mostramos todo sin zoom
                    yAxis.set("start", 0); // Empieza desde el principio (primer agente)
                    yAxis.set("end", 1); // Muestra todos los agentes
                } else if (totalAgents <= 30) {
                    // Si hay entre 11 y 30 agentes, hacemos un zoom de 70% al inicio
                    yAxis.set("start", 0.7); // Empieza desde el principio
                } else {
                    // Si hay más de 30 agentes, hacemos un zoom de 50% al inicio
                    yAxis.set("start", 0.85); // Empieza desde el principio
                }
            });

            // Animaciones iniciales
            series.appear(1000);
            chart.appear(1000, 100);

            // Eliminar logo de amCharts
            root._logo.dispose();

            this.root2 = root;
        },

        //Gráfica contratos por estado
        loadContractsPerStatus() {
            // 🔸 Destruir gráfico anterior si existe (evita duplicados)
            if (this.root3) {
                this.root3.dispose();
            }

            let root = am5.Root.new(this.$refs.chartContractsPerStatus);
            root.setThemes([am5themes_Animated.new(root)]);

            let data = this.contractsPerStatusData || [];

            // Si no hay datos, no continuar
            if (!data.length) {
                console.warn(
                    "No hay datos para mostrar en el gráfico de contratos por estado."
                );
                return;
            }

            // Asigno un color aleatorio por cada estado
            data.forEach((item) => {
                item.color = am5.color(
                    `rgba(${Math.floor(Math.random() * 256)}, ${Math.floor(
                        Math.random() * 256
                    )}, ${Math.floor(Math.random() * 256)}, 1)`
                );
            });

            // Orden ascendente por cantidad
            data.sort((a, b) => a.value - b.value);

            // Crear gráfico XY (barras horizontales)
            let chart = root.container.children.push(
                am5xy.XYChart.new(root, {
                    panX: false,
                    panY: false,
                    wheelX: "none",
                    wheelY: "none",
                    layout: root.verticalLayout,
                })
            );

            // Configurar eje Y (categorías = estados)
            let yAxis = chart.yAxes.push(
                am5xy.CategoryAxis.new(root, {
                    categoryField: "category",
                    renderer: am5xy.AxisRendererY.new(root, {
                        minGridDistance: 20,
                    }),
                })
            );



            // Estilo de etiquetas eje Y
            yAxis.get("renderer").labels.template.setAll({
                textAlign: "center",
                centerY: am5.p50,
                maxWidth: 100,
                oversizedBehavior: "truncate",
                fontSize: "12px",
            });

            // Configurar eje X (valores)
            let xAxis = chart.xAxes.push(
                am5xy.ValueAxis.new(root, {
                    min: 0,
                    renderer: am5xy.AxisRendererX.new(root, {
                        minGridDistance: 80,
                    }),
                })
            );

            xAxis.get("renderer").labels.template.setAll({
                fontSize: "12px",
            });

            // Crear series (barras horizontales)
            let series = chart.series.push(
                am5xy.ColumnSeries.new(root, {
                    name: "Contratos",
                    xAxis: xAxis,
                    yAxis: yAxis,
                    valueXField: "value", // cantidad de contratos
                    categoryYField: "category", // nombre del estado
                })
            );

            // Estilo de las barras
            series.columns.template.setAll({
                width: am5.percent(90),
                centerY: am5.p50,
                fillOpacity: 0.8,
                strokeOpacity: 0,
                cornerRadiusBR: 10,
                cornerRadiusTR: 10,
                cornerRadiusBL: 0,
                cornerRadiusTL: 0,
                maxHeight: 50,
                tooltipText: "[bold]{category}[/]\nContratos: [bold]{value}[/]",
            });

            // Colores dinámicos
            series.columns.template.adapters.add(
                "fill",
                function (color, target) {
                    let dataContext = target.dataItem.dataContext;
                    return dataContext.color || color;
                }
            );

            // Asignar datos
            series.data.setAll(data);
            yAxis.data.setAll(data);

            // Scrollbar vertical
            chart.set(
                "scrollbarY",
                am5.Scrollbar.new(root, {
                    orientation: "vertical",
                })
            );

            // Zoom inicial dinámico según cantidad de estados
            yAxis.events.on("datavalidated", function () {
                const total = data.length;
                if (total <= 10) {
                    yAxis.set("start", 0);
                    yAxis.set("end", 1);
                } else if (total <= 30) {
                    yAxis.set("start", 0.7);
                } else {
                    yAxis.set("start", 0.85);
                }
            });

            // Animaciones
            series.appear(1000);
            chart.appear(1000, 100);

            // Quitar logo de amCharts
            root._logo.dispose();

            // Guardar referencia
            this.root3 = root;
        },

        //Gráfica contratos por comercializadora
        loadContractsPerMarketer() {},
        updateChart(chart, data) {},
        selectUser(user) {
            this.userSelected = user;

            this.getUserHierarchy(this.userSelected._id, "reload");

            this.hideCustomSelects();
        },
        async getUserHierarchy(userId, loadData) {
            await axios
                .get("/api/user/getUserHierarchy/" + userId)
                .then((res) => {
                    this.userSelectedHierarchy = res.data.userHierarchy;

                    if (loadData === "reload") {
                        //Cargo de nuevo los pedidos
                        this.fetchGeneralData();

                        this.fetchContractsPerStatus();


                        if (this.root) {
                            this.root.dispose();
                        }

                        if (this.root2) {
                            this.root2.dispose();
                            this.fetchConsumePerAgentGraphData();
                        }

                        if (this.root4) {
                            this.root4.dispose();
                            this.fetchContractPerMarketerData();
                        }

                        if (this.root6) {
                            this.root6.dispose();
                            //this.fetchContractPerAgentData();
                        }

                        if (this.root5) {
                            this.root5.dispose();
                        }

                        if (this.root7) {
                            this.root7.dispose();
                        }

                        if (this.root || this.root5 || this.root7)
                            this.fetchcontractAndConsumePerDatesGraphData();
                    } else {
                        //SACO LOS DATOS NECESARIOS
                        //Saco los datos generales para las cards
                        this.fetchGeneralData();

                        //Saco gráfica pequeña contratos
                        this.fetchcontractAndConsumePerDatesGraphData();

                        //Saco datos contratos por comercializadora
                        this.fetchContractPerMarketerData();

                        //Saco datos de consumo por agente
                        this.fetchConsumePerAgentGraphData();

                        this.fetchContractsPerStatus();
                    }
                })
                .catch((err) => {
                    console.error(err);
                });
        },
        setDate(date) {
            //Cargo de nuevo los pedidos
            this.fetchGeneralData();

            if (this.root) {
                this.root.dispose();
            }

            if (this.root2) {
                this.root2.dispose();
                this.fetchConsumePerAgentGraphData();
            }

            if (this.root3) {
                this.root3.dispose();
            }
            this.fetchContractsPerStatus();

            if (this.root4) {
                this.root4.dispose();
                this.fetchContractPerMarketerData();
            }

            if (this.root6) {
                this.root6.dispose();
                //this.fetchContractPerAgentData();
            }

            if (this.root5) {
                this.root5.dispose();
            }

            if (this.root7) {
                this.root7.dispose();
            }

            if (this.root || this.root5 || this.root7)
                this.fetchcontractAndConsumePerDatesGraphData();
        },
        seeFilters(type) {
            //Cerrar selects
            this.hideCustomSelects();

            switch (type) {
                case "dates":
                    this.isSeeingFiltersPc.dates = true;
                    break;

                case "user":
                    this.isSeeingFiltersPc.user = true;
                    break;
            }
        },
        hideCustomSelects() {
            //Cierro todos los filtros
            this.isSeeingFiltersPc = {
                dates: false,
            };
        },
        normalizeDashboardNumber(value) {
            if (typeof value === "number") return value;

            if (typeof value === "string") {
                const normalized = value.trim().replace(/\./g, "").replace(",", ".");
                return Number(normalized) || 0;
            }

            return 0;
        },

        getCommissionValueFromStatus(item, keys) {
            if (!item) return 0;

            for (const key of keys) {
                if (item[key] !== undefined && item[key] !== null) {
                    return this.normalizeDashboardNumber(item[key]);
                }
            }

            const nestedSources = [item.commissions, item.commission, item.totals, item.summary];

            for (const source of nestedSources) {
                if (!source || typeof source !== "object") continue;

                for (const key of keys) {
                    if (source[key] !== undefined && source[key] !== null) {
                        return this.normalizeDashboardNumber(source[key]);
                    }
                }
            }

            return 0;
        },

        getStatusAgentCommission(item) {
            return this.getCommissionValueFromStatus(item, [
                "agentCommission",
                "agentsCommission",
                "totalAgentCommission",
                "userCommission",
                "commercialCommission",
                "sellerCommission",
                "agent",
                "agents",
                "user",
                "commercial",
            ]);
        },

        getStatusSubdomainCommission(item) {
            return this.getCommissionValueFromStatus(item, [
                "subdomainCommission",
                "enterpriseCommission",
                "companyCommission",
                "totalSubdomainCommission",
                "subdomain",
                "enterprise",
                "company",
            ]);
        },

        formatDashboardMoney(value) {
            const number = Math.round(this.normalizeDashboardNumber(value));
            const unit = this.basicData && !this.basicData.userLogged.commInPoints ? "€" : "pts.";

            return `${number.toLocaleString("es-ES")} ${unit}`;
        },

        canManage(code) {
            const user = this.basicData?.userLogged;
            const subdomain = this.basicData?.userSubdomain;

            if (!user || !subdomain) return false;

            const label = user.label;
            const labelsPermissions = subdomain.labels_permissions;

            if (!label || !labelsPermissions) return false;
            if (!labelsPermissions[label]) return false;
            if (!code || !code.includes('.')) return false;

            const [module, action] = code.split('.');

            const modulePermissions = labelsPermissions[label][module];

            return Array.isArray(modulePermissions) && modulePermissions.includes(action);
        }
    },
    computed: {
        totalConsumption() {
            if (!this.summaryData || !this.summaryData.totalConsumption)
                return 0;

            let consumption = this.summaryData.totalConsumption ?? 0;
            let magnitude = " kWh";
            if (consumption >= 1000000) {
                consumption /= 1000;
                magnitude = " MWh";
            }
            return Math.round(consumption).toLocaleString("es-ES") + magnitude;
        },
        getPrettyDatesFilters() {
            let startDate = moment(this.dates.start).format("DD/MM/YYYY");
            let endDate = moment(this.dates.end).format("DD/MM/YYYY");

            if (!this.dates.start && !this.dates.end) {
                return "Seleccione fechas";
            } else {
                //si solo se ha seleccionado la fecha inicial
                if (!this.dates.end) {
                    return "Desde  " + startDate;
                } else if (!this.dates.start) {
                    //si solo se ha seleccionado la fecha final
                    return "Hasta  " + endDate;
                } else {
                    return startDate + " - " + endDate;
                }
            }
        },
        filteredUserList() {
            if (!this.basicData || !this.basicData.userList) return [];

            let users = [];

            if(this.canManage('users.admiWhiHier')){
                users = [...this.basicData.subdomainUserList];
            }else{
                users = [...this.basicData.userList];

                users.push(this.basicData.userLogged);
            }

            users.sort((a, b) => {
                // Compara firstName
                const firstNameComparison = a.firstName.localeCompare(
                    b.firstName
                );
                if (firstNameComparison !== 0) return firstNameComparison;

                // Si firstName es igual, compara secondName
                return a.lastName.localeCompare(b.lastName);
            });

            return users;
        },
    },
};
</script>

<style scoped>
/* === Contenedor general === */



/* === Título centrado === */
.graph-title-centered {
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 18px;
  font-weight: 700;
  color: #1e3a8a; /* azul corporativo suave */
  text-align: center;
  margin-bottom: 15px;
  gap: 8px;
}

.graph-title-centered i {
  color: #1e3a8a;
  font-size: 18px;
}

/* === Fila de estados === */
.status-list-row {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 14px;
}

/* === Tarjetas individuales === */
.status-item {
  flex: 1 1 160px;
  min-width: 150px;
  max-width: 220px;
  text-align: center;
  background-color: #fafafa;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  padding: 12px 14px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.status-item:hover {
  transform: translateY(-3px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
}

/* 🟡 Pendientes de renovación (ligero destaque) */
.status-item.renewal {
  background-color: #fff9e6;
  border: 1px solid #ffda6a;
}

/* === Texto interno === */
.status-title {
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 4px;
  color: #222;
}

.status-value {
  font-size: 13px;
  color: #444;
}

.dashboard-pvpc-widget-row {
    width: 100%;
    margin: 18px 0 24px;
}


@media (max-width: 810px) {
    .dashboard-pvpc-widget-row {
        margin: 16px 0 24px;
    }
}

.no-data {
  text-align: center;
  color: #fff9e6;
  font-size: 14px;
  padding: 20px 0;
}

.graph-card.three {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
  width: 100%;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
  transition: height 0.3s ease;
  height: auto; /* 🔸 clave: que se ajuste automáticamente */
}

/* Hace que el padding interno no sea excesivo si hay pocas tarjetas */
.graph-card.three .p-10 {
  padding: 10px 5px 15px 5px;
}

/* Ajuste visual para centrar el contenido cuando hay pocos elementos */
.status-list-row {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 14px;
  align-items: center;
  min-height: 60px; /* Evita que se colapse demasiado */
}

.status-commissions {
  width: 100%;
  margin-top: 10px;
  padding-top: 8px;
  border-top: 1px solid rgba(0, 0, 0, 0.08);
}

.status-commissions.mobile {
  margin-top: 8px;
  padding-top: 7px;
}

.status-commission-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #555;
  margin-top: 4px;
}

.status-commission-row span {
  text-align: left;
}

.status-commission-row strong {
  white-space: nowrap;
  color: #222;
  font-weight: 700;
}




/* ========================= */
/* Contratos por estado móvil */
/* ========================= */

.dash-status-mobile-card {
    width: 100%;
    background: #F5F6F8;
    border-radius: 14px;
    padding: 16px;
    margin: 25px 0 35px 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.dash-status-mobile-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    color: #315f91;
}

.dash-status-mobile-title {
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 16px;
    font-weight: 700;
}

.dash-status-mobile-title i {
    font-size: 16px;
}

.dash-status-mobile-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 14px;
}

.dash-status-mobile-item {
    width: 100%;
    background: #ffffff;
    border-radius: 12px;
    border-left: 5px solid var(--status-color);
    padding: 12px 13px;
    box-shadow: 0 1px 5px rgba(0, 0, 0, 0.05);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.dash-status-mobile-item:active {
    transform: scale(0.98);
}

.dash-status-mobile-item.renewal {
    background: #fff9e6;
}

.dash-status-mobile-main {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}

.dash-status-mobile-info {
    min-width: 0;
}

.dash-status-mobile-name {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
    line-height: 1.25;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dash-status-mobile-subtitle {
    margin: 3px 0 0 0;
    font-size: 12px;
    color: #7a7a7a;
}

.dash-status-mobile-value {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    flex-shrink: 0;
    line-height: 1.1;
}

.dash-status-mobile-value strong {
    font-size: 20px;
    font-weight: 800;
    color: #222;
}

.dash-status-mobile-value span {
    margin-top: 3px;
    font-size: 11px;
    color: #777;
}

@media (max-width: 810px) {

    .status-commissions.mobile {
        margin-top: 10px;
        padding-top: 8px;
        border-top: 1px solid rgba(0, 0, 0, 0.08);
    }

    .status-commissions.mobile .status-commission-row {
        font-size: 11px;
    }
}

</style>

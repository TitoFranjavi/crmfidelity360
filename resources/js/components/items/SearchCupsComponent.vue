<template>
    <section>

        <!-- CUPS -->
        <form class="form" @submit.prevent="checkAPIData">
            <div class="d-flex" data-gap="20">
                <div class="d-flex align-center pointer" data-gap="5" @click="cupsType='electricity'">
                    <i :class="[{'activeType': cupsType === 'electricity'}, 'fa-solid fa-bolt']"></i>
                    <p class="text">Electricidad</p>
                </div>
                <div class="d-flex align-center pointer" data-gap="5" @click="cupsType='gas'">
                    <i :class="[{'activeType': cupsType === 'gas'}, 'fa-solid fa-fire-flame-simple']"></i>
                    <p class="text">Gas</p>
                </div>
                <div class="separator" data-position="vertical" />
                <div class="d-flex column align-center">
                    <div class="form-group d-flex align-center" data-gap="10">
                        <label for="cups input-name">CUPS</label>
                        <div class="input-group">
                            <input ref="cupsInput" type="text"  :name="cupsType=== 'gas' ? 'cups-gas' : 'cups'" placeholder="ES0000XXXXXXXXXXXXAB0F" v-model="cups">
                        </div>
                    </div>
                    <div v-if="cupsType === 'gas'" class="d-flex align-center opacity-6 pointer" data-size="10" data-gap="5" @click="searchCupsDialog = true">
                        <i class="far fa-magnifying-glass pointer text"></i>
                        <p class="text">Buscar CUPS por dirección</p>
                    </div>
                </div>
                <button class="custom-button my-5 mobile-item" data-size="medium"><i class="fa-solid fa-arrow-down"></i></button>
                <button class="custom-button my-5 desktop-item h-40-px" data-size="medium">Buscar</button>
            </div>
        </form>

        <div class="separator"></div>

        <!-- Información -->
        <div v-if="showData === 'electricity'" class="pb-40">

            <div class="d-flex">
                <!-- Potencia -->
                <div class="w-50 form">
                    <p class="text text-center" data-weight="600">Potencia</p>
                    <div class="d-grid form-group" data-column="6">
                        <div class="mx-5" v-for="i in 6">
                            <label>P{{ i }}</label>
                            <div class="input-group">
                                <input data-size="12" v-model="powers[i-1]" disabled>
                            </div>
                        </div>
                    </div>
                    <div class="mt-10 text text-center ml-5"><span class="opacity-6">Potencia máxima: </span>{{Intl.NumberFormat('es', { minimumFractionDigits: 0, maximumFractionDigits: 1 }).format(maxPot)}} kW</div>
                </div>

                <div class="separator mx-10" data-position="vertical"></div>

                <!-- Consumo -->
                <div class="w-50 form">
                    <p class="text text-center" data-weight="600">Consumo</p>
                    <div class="d-grid form-group" data-column="6">
                        <div class="mx-5"  v-for="i in 6">
                            <label>P{{ i }}</label>
                            <div class="input-group">
                                <input data-size="12" v-model="consumptions[i-1]" disabled>
                            </div>
                        </div>
                    </div>
                    <div class="mt-10 text text-center ml-5">
                        <span class="opacity-6">Consumo total: </span>
                        {{ Intl.NumberFormat('es', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(tConsume) }} kWh
                    </div>
                </div>
            </div>

            <div class="separator"></div>
            <div class="mt-10 text text-center ml-5"><span class="opacity-6">Último cambio comercializadora: </span>{{getPrettyDate(supplyData.lastMovement)}}</div>
            <div class="separator"></div>

            <!-- Gráfica de consumo -->
            <div class="mt-40">
                    <h2 class="text text-center">Consumo mensual</h2>
                    <chart-stackedbars-component v-model="consumesGraph" :series="consumesSeries"></chart-stackedbars-component>
            </div>

            <!-- Mapa de calor y consumo por periodos -->
            <div class="mt-40 d-flex justify-between">
                <div class="w-60">
                <h2 class="text text-center">Mapa de calor</h2>
                <chart-heatmap-component :series="heatMapSeries"></chart-heatmap-component>
                </div>
                <div class="w-30">
                    <h2 class="text text-center">Consumo por periodos</h2>
                    <chart-simplebars-component :series="periodsSeries" ></chart-simplebars-component>
                </div>
            </div>

            <div class="mt-40">
                <div class="d-flex justify-around column form">
                    <div class="d-flex column p-10 round" data-round="15" data-size="15"
                         data-weight="600" data-gap="10" data-bg="azul-claro">
                        <div class="d-flex text-center">
                            <div style="width:66%">Consumo (kWh)</div>
                            <div style="width:33%">Potencia demandada (kW)</div>
                        </div>
                        <div class="d-grid data-cups-grid mx-5" data-gap="10">
                            <div class="text text-end">Desde</div>
                            <div class="text text-end">Hasta</div>
                            <div class="d-grid" data-column="6">
                                <template v-for="index in 6">
                                    <p class="text text-end">P{{ index }}</p>
                                </template>
                            </div>
                            <div class="text text-end">Total</div>
                            <div class="d-grid" data-column="6">
                                <template v-for="index in 6">
                                    <p class="text text-end">P{{ index }}</p>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid data-cups-grid mx-20" data-gap="10" v-for="consume in consumes">
                        <div class="text text-end">{{ consume.startDate }}</div>
                        <div class="text text-end">{{ consume.endDate }}</div>
                        <div class="d-grid" data-column="6">
                            <template v-for="period in consume.periods">
                            <div class="text text-end">{{period == "0" ? "-" : Math.round(period)}}</div>
                            </template>
                        </div>
                        <div class="text text-end">{{ Math.round(consume.consumption) }}</div>
                        <div class="d-grid" data-column="6">
                            <template v-for="period in consume.powers">
                            <p class="text text-end">{{period == "0" ? "-" : period}}</p>
                            </template>
                        </div>
                    </div>
                    <div class="d-grid data-cups-grid mt-4 mx-20" data-gap="10">
                        <div></div>
                        <div class="text text-end" data-weight="600" data-size="15">Total</div>
                        <div class="d-grid" data-column="6">
                            <template v-for="index in 6">
                                <div class="text text-end">{{consumptions[index-1]}}</div>
                            </template>
                        </div>
                        <div class="text text-end">{{ tConsume }}</div>
                        <div class="d-grid" data-column="6">
                            <template v-for="index in 6">
                                <div class="text text-end">{{tPowers[index-1]}}</div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div v-if="showData === 'gas'" class="pb-40">
            <div class="d-grid align-center" data-column="2" data-gap="50">
                <!--Datos-->
                <div class="w-full round p-20 h-fit-content" data-bg="gris" data-round="15">
                    <h2>{{supplyData.nombreTitular}} {{supplyData.apellido1Titular}} {{supplyData.apellido2Titular}}</h2>
                    <p class="text mt-20"><i class="fas fa-location-dot"></i> Dirección suministro:</p>
                    <p class="text ml-10" data-size="15">{{ supplyData.tipoViaPS }} <span v-html="supplyData.viaPS"/>
                        {{ supplyData.numFincaPS.replace(/^0+/, "") }}, {{ supplyData.portalPS.replace(/^0+/, "") }}
                        {{ supplyData.pisoPS.replace(/^0+/, "") }}{{supplyData.pisoPS && !isNaN(supplyData.pisoPS) ? "º" : ""}} {{ supplyData.puertaPS.replace(/^0+/, "") }} . {{supplyData.desMunicipioPS}} ({{supplyData.desProvinciaPS}})</p>
                    <p class="text mt-20"><i class="fas fa-address-card"></i> Dirección titular:</p>
                    <p class="text ml-10" data-size="15">{{ supplyData.tipoViaTitular }} <span v-html="supplyData.viaTitular"/>
                        {{ supplyData.numFincaTitular.replace(/^0+/, "") }}, {{ supplyData.portalTitular.replace(/^0+/, "") }}
                        {{ supplyData.pisoTitular.replace(/^0+/, "") }}{{supplyData.pisoTitular && !isNaN(supplyData.pisoTitular) ? "º" : ""}} {{ supplyData.puertaTitular.replace(/^0+/, "") }} . {{supplyData.desMunicipioTitular}} ({{supplyData.desProvinciaTitular}})</p>
                    <p class="text mt-20"><i class="fas fa-pipe-valve"></i> Distribuidora: <span data-size="15">{{supplyData.nombreEmpresaDistribuidora}}</span></p>
                    <div class="d-flex mt-20 justify-between mr-200">
                        <p class="text"><i class="fa-solid fa-file-contract"></i> Tarifa: <span data-size="15">RL{{supplyData.codigoPeajeEnVigor.slice(-1)}}</span></p>
                        <p class="text"><i class="fa-solid fa-handshake-angle"></i> TUR: <span data-size="15">{{supplyData.derechoTUR ? 'Sí' : 'No'}}</span></p>
                        <p v-if="supplyData.cnae" class="text"><i class="fas fa-building"></i> CNAE: <span data-size="15">{{supplyData.cnae}}</span></p>
                    </div>
                    <div class="d-flex mt-20" data-gap="100">
                        <p class="text"><i class="fas fa-calendar-pen"></i> Último movimiento contrato: <span data-size="15">{{getPrettyDate(supplyData.fechaUltimoMovimientoContrato)}}</span></p>
                        <p class="text"><i class="fas fa-calendar-clock"></i>Último cambio comercializadora: <span data-size="15">{{getPrettyDate(supplyData.fechaUltimoCambioComercializador)}}</span></p>
                    </div>
                    <p class="text mt-20"><i class="fas fa-panel-fire"></i> Consumo anual: <span data-size="15">{{Math.round(consumptions[0]).toLocaleString("es-ES")}} kWh</span></p>
                    <!--Tipo de perfil de consumo-->
                    <!--Consumo medio diario-->
                </div>

                <!--Gráfica de consumos-->
                <div class="mt-40">
                    <h2 class="text text-center">Consumo mensual</h2>
                    <chart-stackedbars-component v-model="consumesGraph" :series="consumesSeries" type="gas"></chart-stackedbars-component>
                </div>
            </div>

            <!--Tabla-->
            <div class="mt-40">
                <div class="d-flex justify-around column form">
                    <div class="d-flex column p-10 round" data-round="15" data-size="15"
                         data-weight="600" data-gap="10" data-bg="azul-claro">
                        <div class="d-grid mx-5" data-gap="10" data-column="4">
                            <div class="text text-center">Desde</div>
                            <div class="text text-center">Hasta</div>
                            <div class="text text-center">Consumo (kWh)</div>
                            <div class="text text-center">Consumo medio (kWh/día)</div>
                        </div>
                    </div>
                </div>
                <div class="d-grid mx-20" data-gap="10" data-column="4" v-for="consume in consumes">
                    <div class="text text-center">{{ consume.fechaInicioMesConsumo }}</div>
                    <div class="text text-center">{{ consume.fechaFinMesConsumo }}</div>
                    <div class="text text-center">{{ consume.consumoEnWhP1 }}</div>
                    <div class="text text-center">{{ consume.caudalMedioEnWhdia / 1000 }}</div>
                </div>
            </div>
        </div>

    </section>
    <div v-if="searchCupsDialog" class="floating-box">
        <div class="register-pos w-auto h-auto h-98-max p-30 round" data-round="20" data-border-color="principal">
            <div class="d-flex justify-between">
                <p data-color="principal" data-weight="600" data-size="18" class="mb-10">Buscar CUPS por dirección</p>
                <i data-size="25" class="pointer fas fa-xmark" @click="searchCupsDialog = false"></i>
            </div>
            <form class="d-flex justify-center align-end mt-20 form" data-gap="15" @submit.prevent="searchCupsByAddress">
                <div class="form-group w-100-px">
                    <p class="my-auto"><label>Código postal</label></p>
                    <div class="input-group">
                        <input data-size="10" v-model="searchCups.zipCode" type="text">
                    </div>
                </div>
                <div class="form-group w-200-px">
                    <p class="my-auto"><label>Dirección</label></p>
                    <div class="input-group">
                        <input data-size="10" v-model="searchCups.address" type="text">
                    </div>
                </div>
                <button type="submit" class="custom-button mb-10 h-40" data-size="small">Buscar</button>
            </form>
            <div v-if="!searchCupsLoading && viewSearchCupsList">
                <div v-if="searchCupsList.length > 0">
                    <template v-for="cupsOption of searchCupsList.slice(0,10)">
                        <div class="d-flex align-center py-5" data-gap="10">
                            <i class="far fa-eye text pointer" data-size="11"
                               @click="cups = cupsOption.cups; searchCupsDialog = false"></i>
                            <p class="text w-180-px" data-size="11">{{ cupsOption.cups }}</p>
                            <p class="text" data-size="11">{{ cupsOption.tipoViaPS }} <span v-html="cupsOption.viaPS"/>
                                {{ cupsOption.numFincaPS.replace(/^0+/, "") }}, {{ cupsOption.portalPS.replace(/^0+/, "") }}
                                {{ cupsOption.pisoPS.replace(/^0+/, "") }}{{cupsOption.pisoPS && !isNaN(cupsOption.pisoPS) ? "º" : ""}} {{ cupsOption.puertaPS.replace(/^0+/, "") }}</p>
                        </div>
                        <div class="separator my-5"/>
                    </template>
                </div>
                <div v-else class="text p-10" data-size="16">No se encontraron resultados.</div>
            </div>
            <div v-else-if="searchCupsLoading" class="d-flex align-center justify-center mt-10" data-gap="10">
                <i class="fa-regular fa-spinner-third fa-spin text" data-size="20"></i>
                <div class="text" data-size="20">Buscando</div>
            </div>
        </div>
    </div>
    <!--Pantalla de carga-->
    <div class="loader-box" v-if="loading">
        <div class="loader"></div>
    </div>
</template>

<script>

export default {
    name: 'SearchCupsComponent',
    data() {
        return {
            cups: '',
            loading: false,
            showData: null,
            powers: [],
            consumptions: [],
            consumes: [],
            consumesGraph: null,
            consumesSeries: [],
            periodsSeries: [],
            heatMapSeries: [],
            tConsume: 0,
            tPowers: [],
            orderedPot: [],
            maxPot: 0,
            cupsType: 'electricity',
            searchCupsDialog: false,
            searchCupsLoading: false,
            searchCups: {},
            searchCupsList: [],
            viewSearchCupsList: false,
            supplyData: {}
        }
    },
    mounted() {
        this.$refs.cupsInput.focus()
    },
    methods: {
        async checkAPIData() {
            // Remover 0F en caso de tenerlo
            let cups = this.cups.length === 22 ? this.cups.slice(0,-2) : this.cups;
            let cupsRegex = /^ES\d{16}[a-z]{2}(?:[0-9][a-z])?$/i;

            if (!cupsRegex.test(cups)) {
                await Swal.fire({
                    icon: "warning",
                    title: "CUPS inválido",
                    text: "Por favor introduce un CUPS válido.",
                })
                return;
            }

            this.loading = true;

            if(this.cupsType === 'electricity'){
                axios.get('/api/tools/getAPIConsumption', {
                    params: {CUPS: cups}
                })
                    .then(res => {
                        this.powers = res.data.cupsData.power
                        this.consumptions = res.data.cupsData.consumption
                        this.consumes = res.data.consumptionData
                        this.supplyData.lastMovement = res.data.lastMovement
                        for (let i = 0; i < this.consumptions.length; i++) {
                            this.consumptions[i] = Math.round(this.consumptions[i])
                        }

                        //Mostrar datos
                        this.showData = 'electricity';

                        //Calcular datos
                        this.calcConsumeTotal();
                        this.calcMaxPot();
                        this.calcDemandedPowers();
                        this.calcConsumeGraphData();
                        this.calcPeriodsGraphData();
                        this.calcHeatMapGraphData();


                    }).catch(err => {
                        Swal.fire({
                            icon: "error",
                            title: "Hubo un error",
                            text: "Es posible que el CUPS no tenga datos.",
                        });
                    }).finally(() => {
                        this.loading = false;

                    })
            }else{
                axios.get('/api/sips/getGasConsumption', {
                    params: { CUPS: cups }
                }).then(res => {
                    this.supplyData = res.data.supply;
                    this.consumes = res.data.consumptionData.consumptionIntervals;
                    this.consumptions = res.data.consumptionData.consumption;

                    //Mostrar datos
                    this.showData = 'gas';

                    //Calcular datos
                    this.calcConsumeGraphData();

                }).catch(err => {
                    Swal.fire({
                        icon: "error",
                        title: "Hubo un error",
                        text: "Es posible que el CUPS no tenga datos.",
                    });
                }).finally(() => {
                    this.loading = false;

                })
            }
        },
        calcConsumeGraphData(){
            this.consumesSeries = [];
            if(this.showData === 'electricity'){
                for(let item of this.consumes){
                    //Iteramos sobre los periodos de cada lectura
                    let periodData = {fecha:item.endDate}
                    let index = 1;
                    for(let period of item.periods){
                        let key = `P${index}`;
                        periodData[key] = period;
                        index++;
                    }
                    //Guardamos los datos de la gráfica de consumo
                    periodData["none"] = 0;
                    this.consumesSeries.push(periodData)
                }
            }else{
                for(let item of this.consumes){
                    let periodData = {fecha:item.fechaFinMesConsumo, ['Consumo (kWh)']: item.consumoEnWhP1, none: 0}
                    this.consumesSeries.push(periodData)
                }
            }
        },
        calcPeriodsGraphData(){
            this.periodsSeries = [];
            let index = 1;
            for(let period of this.consumptions){
                let periodData ={
                    period: `P${index}`,
                    value: period
                }
                this.periodsSeries.push(periodData);
                index++;
            }
        },
        calcHeatMapGraphData(){
            this.heatMapSeries = [];
            for(let item of this.consumes){
                // Creo un registro por cada periodo de cada lectura
                let fecha = moment(item.endDate, "DD/MM/YYYY").format("MMM YY");
                let index = 1;
                // Itero sobre los periodos de cada lectura
                for(let periodData of item.periods){
                    this.heatMapSeries.push({
                        fecha,
                        period: `P${index}`,
                        value: Math.round(periodData)
                    })
                    index++;
                }
            }
        },
        calcMaxPot() {
            this.orderedPot = this.powers.toSorted(function (a, b) {
                return b - a
            });
            this.maxPot = this.orderedPot[0]
        },
        calcConsumeTotal() {
            this.tConsume = 0;
            for (let i = 0; i < this.consumptions.length; i++) {
                this.tConsume += this.consumptions[i];
            }
        },
        calcDemandedPowers(){
            this.tPowers = [0,0,0,0,0,0];
            for(let consume of this.consumes ){
                this.tPowers = this.tPowers.map((value, index) =>
                    Math.max(value, consume.powers[index])
                )
            }
        },
        async searchCupsByAddress(){
            //Usando searchCups, usar el metodo en el sips
            try {
                this.searchCupsLoading = true;
                const response = await axios.get('/api/sips/getGasCupsByAddress', {params: { zipCode: this.searchCups.zipCode, address: this.searchCups.address}})

                this.searchCupsList = response.data;
            } catch (error) {
                console.error("Error al obtener los datos del CUPS", error)
            }finally {
                this.searchCupsLoading = false;
                this.viewSearchCupsList = true;
            }
        },
        getPrettyDate(date) {
            return moment(date).format("D/M/YY");
        },
    }
};

</script>

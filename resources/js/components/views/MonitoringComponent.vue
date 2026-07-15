<template>
    <section class="datadis-tool min-h-100-vh px-200 py-50 d-flex justify-center items-center">

        <!--Spinner de carga de suministros-->
        <Teleport to=".boxBody" v-if="loadingSupplies">
            <div class="floating-box z-100">
                <div class="d-flex column justify-center register-pos w-auto h-auto h-98-max round" data-round="20">
                    <div class="text" data-color="principal" data-weight="600" data-size="36">Cargando suministros...</div>
                    <div class="text text-center" data-size="26"><i class="fa-solid fa-spinner-third fa-spin"></i></div>
                </div>
            </div>
        </Teleport>
        <div v-if="showSupplies" class="w-100">
            <template v-if="supplies.length > 0">
                <!--HEADER-->
                <div class="datadis-table table-header mt-30 mb-10">
                    <div data-color="principal">
                        <p class="text mr-5 ellipsis noWidth" data-weight="600">CUPS</p>
                    </div>
                    <div data-color="principal">
                        <p class="text mr-5 ellipsis noWidth" data-weight="600">Provincia</p>
                    </div>
                    <div data-color="principal">
                        <p class="text mr-5 ellipsis noWidth" data-weight="600">Localidad</p>
                    </div>
                    <div data-color="principal">
                        <p class="text mr-5 ellipsis noWidth" data-weight="600">Dirección</p>
                    </div>
                    <div data-color="principal">
                        <p class="text mr-5 ellipsis noWidth" data-weight="600">Código postal</p>
                    </div>
                </div>
                <!--Suministros-->
                <div v-for="supply of supplies" >
                    <div class="datadis-table">
                        <p class="text ellipsis pointer" data-color="azul" data-weight="600" @dblclick="selectSupply(supply)">{{ supply.cups }}</p>
                        <p class="text ellipsis">{{ supply.province }}</p>
                        <p class="text ellipsis">{{ supply.municipality }}</p>
                        <p class="text ellipsis">{{ supply.address }}</p>
                        <p class="text ellipsis">{{ supply.postalCode }}</p>
                        <div class="text pointer" @click="selectSupply(supply)"><i class="far fa-eye"></i></div>
                    </div>
                    <div class="separator my-10"></div>
                </div>
            </template>
            <template v-else>
                <div class="mt-50 ml-30 text" data-size="21"><i class="far fa-circle-info mr-10"></i>No hay suministros autorizados para este NIF/CIF.</div>
            </template>
        </div>
        <!--Spinner de carga de datos de suministro-->
        <Teleport to=".boxBody" v-if="loadingConsumptionData">
            <div class="floating-box z-100">
                <div class="d-flex column justify-center register-pos w-auto h-auto h-98-max round" data-round="20">
                    <div class="text" data-color="principal" data-weight="600" data-size="36">Calculando consumo...</div>
                    <div class="text text-center" data-size="26"><i class="fa-solid fa-spinner-third fa-spin"></i></div>
                </div>
            </div>
        </Teleport>
        <div v-if="supplySelected" class="w-100">
            <!--Datos del suministro-->
            <div class="d-flex justify-between">
                <div class="d-flex align-center" data-gap="10">
                    <button class="custom-button my-5" data-size="medium" @click="deselectSupply"><i
                        class="fa-solid fa-arrow-left"></i> Volver
                    </button>
                    <p class="text" data-weight="600" data-size="20">{{ supplySelected.address }}</p>
                </div>
                <div class="d-flex align-center" data-gap="10">
                    <p class="text opacity-6">{{supplySelected.municipality}} {{supplySelected.postalCode}} ({{supplySelected.province}})</p>
                    <p class="text opacity-6">CUPS {{supplySelected.cups}}</p>
                </div>
            </div>
            <div class="separator my-10"></div>
            <!--Selector de fechas-->
            <div class="d-flex justify-between mt-10 mx-5 form">
                <div class="slider justify-start">
                    <div :class="[{ 'selected': dateType === 'day' },'d-flex align-center']" @click="selectDateType('day')">Diario</div>
                    <div :class="[{ 'selected': dateType === 'isoWeek' },'d-flex align-center']" @click="selectDateType('isoWeek')">Semanal</div>
                    <div :class="[{ 'selected': dateType === 'month' },'d-flex align-center']" @click="selectDateType('month')">Mensual</div>
                </div>
                <div class="form-group">
                    <div class="input-group">
                        <input v-if="dateType === 'day'" type="date" v-model="dateInput" @change="updateDate" />
                        <input v-if="dateType === 'isoWeek'" type="week" v-model="dateInput" @change="updateDate" />
                        <input v-if="dateType === 'month'" type="month" v-model="dateInput" @change="updateDate" />
                    </div>
                </div>
            </div>
            <!--Resultados-->
            <template v-if="consumptions && stackedChartSeries.length > 1">
                <div class="dashboard-card w-100 mt-10 d-flex justify-around align-center">
                    <div>
                        <p v-if="totalConsumption" class="text text-center" data-weight="600" data-size="30">{{ Intl.NumberFormat('en',{minimumFractionDigits: 0, maximumFractionDigits: 2}).format(totalConsumption) }}</p>
                        <p class="text text-center" data-size="12">Consumo total(kWh)</p>
                    </div>
                    <div>
                        <p v-if="consumptionPerInterval" class="text text-center" data-weight="600" data-size="30">{{ Intl.NumberFormat('en',{minimumFractionDigits: 0, maximumFractionDigits: 2}).format(consumptionPerInterval) }}</p>
                        <p class="text text-center" data-size="12">Consumo medio diario(kWh)</p>
                    </div>
                    <chart-datadis-donut-component :series="donutSeries"></chart-datadis-donut-component>
                    <chart-datadis-summarybars-component :series="summaryBarsSeries"></chart-datadis-summarybars-component>
                </div>
                <chart-datadis-stackedbars-component :series="stackedChartSeries"></chart-datadis-stackedbars-component>
                <div class="d-flex justify-between pb-10">
                    <div class="text pointer" @click="changeDateSelected('prev')"><i class="fa-solid fa-arrow-left"/> {{dateType === 'month' ? 'Mes' : dateType === 'isoWeek' ? 'Semana' : 'Día' }} anterior</div>
                    <div class="text pointer" @click="changeDateSelected('next')">{{dateType === 'month' ? 'Mes' : dateType === 'isoWeek' ? 'Semana' : 'Día' }} siguiente <i class="fa-solid fa-arrow-right"/></div>
                </div>
            </template>
            <template v-else>
                <div class="mt-50 ml-30 text" data-size="21"><i class="far fa-circle-info mr-10"></i>No hay datos para la fecha consultada.</div>
            </template>
        </div>
    </section>
</template>

<script>
export default {
    name: "MonitoringComponent",
    data(){
        return {
            token: "",
            isTokenLoaded: false,
            cif: "",
            supplies: [],
            showSupplies: false,
            loadingSupplies: false,
            supplySelected: null,
            dateType: null,
            dateSelected: null,
            dateInput: null,
            consumptions: null,
            loadingConsumptionData: false,
            contract: null,
            totalConsumption: null,
            consumptionPerInterval: null,
            dataFees: [
                {name: "2T", intervals:{low: [1,2,3,4,5,6,7,8], mid: [9,10,15,16,17,18,23,24], high: [11,12,13,14,19,20,21,22]}},
                {name: "3T", intervals:{low: [1,2,3,4,5,6,7,8], mid: [9,15,16,17,18,23,24], high: [10,11,12,13,14,19,20,21,22]},
                    months: [{mid: "P2",high: "P1"},{mid: "P2", high: "P1"},{mid: "P3", high: "P2"},{mid: "P5", high: "P4"},{mid: "P5", high: "P4"},{mid: "P4", high: "P3"},{mid: "P2", high: "P1"},{mid: "P4", high: "P3"},{mid: "P4", high: "P3"},{mid: "P5", high: "P4"},{mid: "P3", high: "P2"},{mid: "P2", high: "P1"}]
                },
            ],
            holidayDates: ['2025/12/25','2025/12/08','2025/08/15','2025/05/01','2025/01/01','2024/12/25','2024/12/06','2024/11/01','2024/10/12','2024/08/15','2024/05/01','2024/01/01','2023/12/25','2023/12/08','2023/12/06','2023/11/01','2023/10/12','2023/08/15','2023/05/01'],
            stackedChartSeries: [],
            donutSeries: [],
            summaryBarsSeries: [],
        }
    },
    created() {
        Promise.all([
            this.getAccount(),
            this.getToken()
        ]).then(() => {
            this.getSupplies();
        })
    },
    methods:{
        async getAccount(){
            await axios.get(`/api/accounts/${this.$route.params.id}`)
                .then((res) => {
                    this.cif = res.data.account.CIF
                })
        },
        async getToken(){
            this.isTokenLoaded = false;
            await axios.get("/api/tools/obtainDatadisToken").then((res) => {
                this.token = res.data
                this.isTokenLoaded = true;
            })
        },
        async getSupplies(){
            this.supplySelected = null; //Borro el suministro seleccionado si hubiera
            this.consumptions = null; //Borro los consumos del suministro

            //Compruebo que el nif/cif es válido
            let cif = this.cif.toLowerCase();
            let regexNifCif = /^(?:(\d{8})([A-Z])|[XYZ]\d{7,8}[A-Z]|([ABCDEFGHJKLMNPQRSUVW])(\d{7})([0-9A-J]))$/;
            //En caso de ser el nif de Segenet, borrar para poder ver sus suministros
            if(cif === "b56037518"){
                cif = "";
            }else if(!regexNifCif.test(this.cif)){
                Swal.fire({
                    icon: "error",
                    title: "NIF o CIF inválido",
                    text: "El NIF o CIF no es válido",
                });
                return;
            }
            this.loadingSupplies = true;
            //Obtener suministros de Datadis
            await axios.get(`https://datadis.es/api-private/api/get-supplies?authorizedNif=${cif}`, {
                headers:{
                    "Authorization": `Bearer ${this.token}`
                }
            }).then(res => {
                this.supplies = res.data;
            }).catch(res => {
                console.log(res.error)
            }).finally(() => {
                this.showSupplies = true;
                this.loadingSupplies = false;
            })
        },
        selectSupply(supply){
            this.supplySelected = supply;
            this.showSupplies = false;
            this.dateType = "month";
            this.dateSelected = moment().subtract(2, 'days').format("YYYY/MM/DD");
            this.dateInput = moment().subtract(2, 'days').format("YYYY-MM");

            this.getConsumptionData();
        },
        deselectSupply(){
            this.supplySelected = null;
            this.showSupplies = true;

            //Borro los datos de consumo
            this.consumptions = null;
        },
        async getConsumptionData(){
            let cif = this.cif.toLowerCase();
            //En caso de ser el cif de Segenet, borrar para poder ver sus suministros
            if(cif === "b56037518"){
                cif = "";
            }

            this.loadingConsumptionData = true;
            let formData = new FormData();
            formData.append("token", this.token);
            formData.append("cif", cif);
            formData.append("supply", JSON.stringify(this.supplySelected));
            formData.append("date", this.dateSelected);
            formData.append("dateType", this.dateType);

            await axios.post("/api/tools/getDatadisConsumptionData",formData
            ).then(res => {
                this.consumptions = res.data.consumption;
                this.contract = res.data.contract[0];
                if(this.consumptions){
                    this.calcData();
                }
            }).finally(() => {
                this.loadingConsumptionData = false;
            })
        },
        selectDateType(dateType){
            if(this.dateType !== dateType){
                this.dateType = dateType;

                switch (dateType){
                    case "day":
                        this.dateInput = moment(this.dateSelected).format("YYYY-MM-DD");
                        break;
                    case "isoWeek":
                        this.dateInput = moment(this.dateSelected).format("GGGG-[W]WW");
                        break;
                    case "month":
                        this.dateInput = moment(this.dateSelected).format("YYYY-MM");
                        break;
                }

                this.getConsumptionData();
            }

        },
        updateDate(){
            this.dateSelected = moment(this.dateInput).format("YYYY/MM/DD");
            this.getConsumptionData();
        },
        calcData(){
            //Reinicio datos de gráficas
            this.stackedChartSeries = [];
            this.donutSeries = [{value: 0, category: "Valle"}, {value: 0, category: "Llano"}, {value: 0, category: "Punta"}];
            this.summaryBarsSeries = [];

            //Obtengo fechas de inicio y fin de intervalo, y formatos y metodos para mostrar/calcular los datos
            let startDate, endDate, dateFormat, summaryFormat, dateChangeMethod, intervalChangeMethod;
            switch(this.dateType){
                case "day":
                    startDate = moment(this.dateSelected).subtract(2,"days").startOf("day");
                    endDate = moment(this.dateSelected).add(2,"days").endOf("day");
                    dateFormat = "HH";
                    dateChangeMethod = "hour";
                    summaryFormat = "DD/MM";
                    intervalChangeMethod = "date";
                    break;
                case "isoWeek":
                    startDate = moment(this.dateSelected).subtract(2,"weeks").startOf("isoWeek");
                    endDate = moment(this.dateSelected).add(2,"weeks").endOf("isoWeek");
                    dateFormat = "DD/MM";
                    dateChangeMethod = "date";
                    summaryFormat = "[S]WW";
                    intervalChangeMethod = "isoWeek";
                    break;
                case "month":
                    startDate = moment(this.dateSelected).subtract(2,"months").startOf("month");
                    endDate = moment(this.dateSelected).add(2,"months").endOf("month");
                    dateFormat = "D";
                    dateChangeMethod = "date";
                    summaryFormat = "MMM";
                    intervalChangeMethod = "month";
                    break;
            }

            //Obtengo intervalos de la tarifa del contrato
            if(this.contract.codeFare === "2T"){
                fee = this.dataFees[0];
            }else{
                fee = this.dataFees[1];
                this.donutSeries = [{value: 0, category: "P6"},{value: 0, category: fee.months[moment(this.dateSelected).month()].mid},{value: 0, category: fee.months[moment(this.dateSelected).month()].high}]
            }

            let date, intervalDate, stackedData, summaryData, isWeekend, isHoliday, fee;
            let donutData = {low: 0, mid: 0, high: 0};

            //Recorro todos los registros
            for(let consumption of this.consumptions){
                //Fecha del registro
                let consumptionDate = moment(`${consumption.date} ${consumption.time}`, "YYYY/MM/DD HH:mm").subtract(1, "hours");

                //Compruebo si es la fecha seleccionada
                if(consumptionDate.isSame(moment(this.dateSelected),this.dateType)){
                    //Si es el primer registro de la fecha seleccionada
                    if(typeof date === "undefined"){
                        this.summaryBarsSeries.push(summaryData);
                        summaryData = {date: consumptionDate.format(summaryFormat), low: 0, mid: 0, high: 0, columnSettings: {}};
                    }

                    //Si ha cambiado la hora en caso de dia, o el dia en caso de semana/mes
                    if(consumptionDate[dateChangeMethod]() !== date){
                        if(typeof date !== "undefined"){
                            this.stackedChartSeries.push(stackedData);
                        }
                        date = consumptionDate[dateChangeMethod]();
                        isWeekend = consumptionDate.day() === 6 || consumptionDate.day() === 0; //TODO: Añadir festivos
                        isHoliday = this.holidayDates.includes(consumptionDate.format('YYYY/MM/DD'));
                        stackedData = {date: consumptionDate.format(dateFormat), low: 0, mid: 0, high: 0};
                    }

                    //Si es fin de semana siempre es valle
                    if(isWeekend || isHoliday){
                        stackedData.low += consumption.consumptionKWh;
                        donutData.low += consumption.consumptionKWh;
                        summaryData.low += consumption.consumptionKWh;
                    }else{
                        //Añado a valle, llano, punta dependiendo de la hora del consumo
                        for (let interval in fee.intervals) {
                            if (fee.intervals[interval].includes(parseInt(consumption.time))) {
                                stackedData[interval] += consumption.consumptionKWh;
                                donutData[interval] += consumption.consumptionKWh;
                                summaryData[interval] += consumption.consumptionKWh;
                            }
                        }
                    }
                    //Comprobar si es en el intervalo para la gráfica de barras del resumen
                }else if(consumptionDate.isBetween(startDate,endDate,null,[])){
                    //Compruebo si cambia el intervalo
                    if(consumptionDate[intervalChangeMethod]() !== intervalDate){
                        //Si cambio de intervalo, pero no es el primero, añado el primero al array
                        if(intervalDate !== undefined){
                            this.summaryBarsSeries.push(summaryData);
                        }
                        intervalDate = consumptionDate[intervalChangeMethod]();
                        summaryData = {date: consumptionDate.format(summaryFormat), low: 0, mid: 0, high: 0, columnSettings: {fill: "#ccc", stroke: "#ccc"}};
                    }
                    summaryData.low += consumption.consumptionKWh;
                }
            }

            //Añado el último subIntervalo calculado a las gráficas de barras
            this.stackedChartSeries.push(stackedData);
            this.summaryBarsSeries.push(summaryData);

            //Formateo los datos de gráfica donut
            Object.values(donutData).forEach((value, i) => {
                this.donutSeries[i].value = value;
            });

            //Calculo el consumo total, y lo divido entre los periodos con esas fechas
            this.totalConsumption = Object.values(donutData).reduce((total,interval) => total + interval,0);

            let interval = this.stackedChartSeries.length;

            this.consumptionPerInterval = this.totalConsumption/interval;

            //Relleno los datos de gráficas en caso de no tener el tamaño adecuado
            let dataLength, dateTypeToAdd;
            switch(this.dateType){
                case "day":
                    dataLength = 24;
                    dateTypeToAdd = "hours";
                    break;
                case "isoWeek":
                    dataLength = 7;
                    dateTypeToAdd = "days";
                    break;
                case "month":
                    dataLength = moment(this.dateSelected).daysInMonth();
                    dateTypeToAdd = "days";
                    break;

            }

            //Obtengo la última fecha introducida
            let lastDate = moment(this.stackedChartSeries[this.stackedChartSeries.length - 1].date, dateFormat);
            while(this.stackedChartSeries.length < dataLength){
                lastDate = lastDate.add(1,dateTypeToAdd);
                this.stackedChartSeries.push({date: lastDate.format(dateFormat), low: 0, mid: 0, high: 0});
            }
        },
        changeDateSelected(direction){
            switch (this.dateType){
                case "day":
                    this.dateSelected = direction === 'prev' ? moment(this.dateSelected).subtract(1,'days').format('YYYY/MM/DD') : moment(this.dateSelected).add(1,'days').format('YYYY/MM/DD');
                    this.dateInput = moment(this.dateSelected).format("YYYY-MM-DD");
                    break;
                case "isoWeek":
                    this.dateSelected = direction === 'prev' ? moment(this.dateSelected).subtract(1,'weeks').format('YYYY/MM/DD') : moment(this.dateSelected).add(1,'weeks').format('YYYY/MM/DD');
                    this.dateInput = moment(this.dateSelected).format("GGGG-[W]WW");
                    break;
                case "month":
                    this.dateSelected = direction === 'prev' ? moment(this.dateSelected).subtract(1,'months').format('YYYY/MM/DD') : moment(this.dateSelected).add(1,'months').format('YYYY/MM/DD');
                    this.dateInput = moment(this.dateSelected).format("YYYY-MM");
                    break;
            }

            this.getConsumptionData();
        }
    }
}
</script>

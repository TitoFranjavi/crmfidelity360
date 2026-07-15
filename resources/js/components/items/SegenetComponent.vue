<template>
    <div class="h-100" @click="isSeeingDropdownDowloads = false">

        <!--Selector suministro (parte 1)-->
        <form v-if="part === 0" class="form h-100" @submit.prevent="getProbeInfo(); getProbeData()">
            <div class="d-flex justify-center">
                <div class="form-group w-250-px-min relPos">
                    <div class="input-group" :class="{'boxDownActive': isDropdownOpen || (searchText.trim().length > 0 && !this.probeSelected)}">

                        <!--Input-->
                        <input v-if="!probeSelected" class="w-400-px text" data-size="15" v-model="searchText" @focus="isDropdownOpen = true"
                               @blur="isDropdownOpen = false" placeholder="Busca tu contador por CUPS o nombre" :disabled="false" type="text">

                        <div v-else class="w-400-px text d-flex justify-between">
                            <p data-size="15" data-color="azul">{{ probesAvailable.find((p) => p.serial === probeSelected).name }}</p>
                            <p data-size="15"><i class="far fa-x pointer" @click="probeSelected = null; isDropdownOpen = true"></i></p>
                        </div>


                        <!--Desplegable contadores-->
                        <div class="dropdown">
                            <ul v-if="!isLoading && filteredProbes.length > 0">
                                <li v-for="probe in filteredProbes" @click="toggleSelectProbe(probe.serial)" class="text d-flex justify-between">
                                    <div class="w-100">
                                        <p data-size="14">{{ probe.name }}</p>
                                        <p class="opacity-5" data-size="9">{{ probe.cups }}</p>
                                    </div>
                                    <div class="my-auto">
                                        <i class="far fa-eye pointer" @click.stop="openNewWindow('/contracts?_id=' + probe.order_id)"></i>
                                    </div>
                                </li>
                            </ul>

                            <div v-else class="text-center p-5">
                                <span v-if="!!isLoading" class="loading-dots text opacity-5">Cargando<span>.</span><span>.</span><span>.</span></span>
                                <span v-else class="text opacity-5">No hay ningún contador disponible</span>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="ml-20 my-auto">
                    <button type="submit" class="custom-button my-5 mobile-item" data-size="medium"><i class="fa-solid fa-arrow-down"></i></button>
                    <button type="submit" class="custom-button my-5 desktop-item" data-size="medium" :disabled="!probeSelected">Cargar</button>
                </div>
            </div>
        </form>


        <!--Suministro cargado-->
        <div v-else-if="part === 1">

            <!--Información suministro-->
            <div class="my-20 d-flex justify-between" v-if="probeData">
                <!--Nombre contador-->
                <p class="text italic" data-size="18" data-weight="600" >{{ probeData.name }}</p>


                <!--Herramienta-->
                <div class="d-flex">
                    <p @click.prevent="graphs.selected = typeInd" v-for="(type, typeInd) in graphs.types" class="text mx-10 pointer" :data-color="typeInd === graphs.selected ? 'azul' : 'principal'" :data-weight="typeInd === graphs.selected ? '600' : '400'"><i class="mr-5" :class="[ typeInd === graphs.selected ? 'fas' : 'far', type.icon ]"></i>{{ type.title }}</p>
                </div>
            </div>


            <div class="d-flex justify-between align-center my-40" v-if="!!dates">
                <!--Excels y selector-->
                <div>
                    <!--Dropdown descargas-->
                    <div class="custom-select no-hover"
                         @click.stop="isSeeingDropdownDowloads = !isSeeingDropdownDowloads"
                         :class="{ seeing: isSeeingDropdownDowloads }">
                        <div class="custom-button" data-size="regular" data-bg="principal">
                            Descargar <i class="far fa-chevron-down"></i>
                        </div>

                        <!--Opción entre crear nuevo y meter en cuenta existente-->
                        <div v-if="isSeeingDropdownDowloads" class="select-content left w-max-content form">

                            <!--Opciones-->
                            <div class="text">
                                <p @click.stop="downloadMockInvoice"><i class="far fa-calendar w-20-px mr-10"></i>Simulado</p>

                                <p @click.stop="downloadExcelQuarters"><i class="far fa-database w-20-px mr-10"></i>Cuarto-horario</p>

                                <p @click.stop="isSeeingFloatingBox = true"><i class="far fa-money-bill-wave w-20-px mr-10"></i>Cierres</p>
                            </div>
                        </div>
                    </div>

                </div>


                <!--Fechas-->
                <div class="d-flex align-center my-3 form">

                    <!--Fec. inicio-->
                    <div class="d-flex justify-between align-center mx-10">
                        <label class="text mr-10">Desde:</label>

                        <div  class="form-group">
                            <div class="input-group">
                                <input
                                    type="datetime-local"
                                    v-model="dates.start"
                                    :min="limitDates.start"
                                    :max="dates.end ?? limitDates.end"
                                    @change="normalizeToQuarterHour('start')"
                                />
                            </div>
                        </div>
                    </div>


                    <!--Fec. fin-->
                    <div class="d-flex justify-between align-center mx-10">
                        <label class="text mr-10">Hasta:</label>

                        <div  class="form-group">
                            <div class="input-group">
                                <input
                                    type="datetime-local"
                                    v-model="dates.end"
                                    :min="dates.start ?? limitDates.start"
                                    :max="limitDates.end"
                                    @change="normalizeToQuarterHour('end')"
                                />
                            </div>
                        </div>
                    </div>

                    <button @click.prevent="getProbeData" class="custom-button my-5 desktop-item" data-size="medium" :disabled="!(this.dates && (this.dates.start !== this.datesNow.start || this.dates.end !== this.datesNow.end))">Cargar</button>
                </div>
            </div>



            <!--Gráfica simple-->
            <div v-show="graphs.selected === 0">
                <!--Leyenda periodos-->
                <div class="d-flex justify-center align-center my-20" v-show="!!dates">
                    <div v-for="(period, periodInd) in periodColors" class="mx-20 d-flex align-center">
                        <div class="w-15-px h-15-px round mr-5" data-round="5" :style="{'background-color': period}"></div>
                        <p>P{{ periodInd }}</p>
                    </div>
                </div>

                <div id="simpleGraph" class="w-100 h-500-px"></div>
            </div>


            <!--Mapa de calor-->
            <div v-show="graphs.selected === 1">

                <!--En. activa-->
                <p class="italic text mb-20" data-size="20" data-weight="700"><i class="fas fa-bolt-lightning"></i> En. activa (kWh)</p>
                <div id="activeHeatMap" class="w-100" style="height: auto; min-height: 400px"></div>
            </div>
        </div>

        <!--Box flotante superpuesto-->
        <div class="floating-box z-11" v-bind:class="{ 'seeing': isSeeingFloatingBox, 'd-none': !isSeeingFloatingBox }" @click="isSeeingFloatingBox = false; closeDate = null">


            <div @click.stop="" class="register-pos w-auto w-15-min h-auto h-98-max round p-40" data-round="20" data-border-color="principal">

                <div class="top-part d-flex column">

                    <!--Fechas-->
                    <p class="text mb-10" data-size="20" data-weight="700">Cierres del contador</p>

                    <!--Selector mes y año-->
                    <div class="form" v-if="probeData">
                        <div class="d-flex justify-center align-center mx-10">
                            <div  class="form-group">
                                <div class="input-group">
                                    <input
                                        v-model="closeDate"
                                        type="month"
                                        :min="probeData.min_closes_from_date.slice(0, 7)"
                                        :max="probeData.max_closes_from_date.slice(0, 7)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--Descargar excel cierres-->
                    <button @click.prevent="downloadExcelCloses" class="custom-button mx-auto my-5 desktop-item" data-size="medium" :disabled="!closeDate">Descargar</button>

                </div>


            </div>
        </div>


        <!--Cargando-->
        <div class="loader-box" v-if="isLoading || (part === 1 && !probeValues)">
            <div class="loader"></div>
        </div>
    </div>
</template>

<script>
import * as am5 from '@amcharts/amcharts5';
import * as am5xy from '@amcharts/amcharts5/xy';
import am5themes_Animated from '@amcharts/amcharts5/themes/Animated';

export default{
    name: 'SegenetComponent',
    props:['basicData'],
    data(){
        return{
            searchText: '',
            isLoading: false,
            isDropdownOpen: false,
            part: 0,
            probesAvailable: [],
            probeSelected: null,
            probeData: null,
            probeValues: null,
            probeValuesInfo: null,
            dates: null,
            datesNow: null,
            limitDates: null,
            chart: null,
            activeHeatMap: null,
            graphs: {
                selected: 0,
                types: [
                    {
                        title: 'Gráfica simple',
                        icon: 'fa-chart-area',
                        code: 0
                    },
                    {
                        title: 'Mapa de calor',
                        icon: 'fa-heat',
                        code: 1
                    },
                ]
            },
            isSeeingDropdownDowloads: false,
            isSeeingFloatingBox: false,
            closeDate: null,
            periodColors: {
                1: "#ff000b",
                2: "#ffe72b",
                3: "#0066b5",
                4: "#ff00fb",
                5: "#ff8d1c",
                6: "#00a64b"
            }
        }
    },
    created(){
        this.getProbesSAvailable();
        //this.getProbeInfo();
        //this.getProbeData();
    },
    methods:{
        async getProbesSAvailable(){
            this.isLoading = true;

            await axios.get('/api/tools/segenet/getProbesAvailable')
                .then((res) => {
                    this.probesAvailable = res.data.probes;
                })
                .catch((err) => {
                    console.log(err)
                })
                .finally(() => {
                    this.isLoading = false;
                })
        },
        toggleSelectProbe(serial){
            if (this.probeSelected !== serial){
                this.probeSelected = serial
                this.isDropdownOpen = false;
                this.searchText = '';
            } else{
                this.probeSelected = null
            }
        },
        async getProbeInfo(){
            await axios.get('/api/tools/segenet/getProbeInfo', {
                params:{ 'serial': this.probeSelected }
            })
                .then((res) => {
                    this.probeData = res.data.probe
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        async getProbeData(){
            this.part = 1;
            this.isLoading = true;

            await axios.get('/api/tools/segenet/getProbeData', {
                params:{ 'serial': this.probeSelected, dates: this.dates }
            })
                .then((res) => {
                    this.probeValues = res.data.quarters
                    this.probeValuesInfo = res.data.info

                    this.dates = {
                        start: this.probeValuesInfo.first_value_date,
                        end: this.probeValuesInfo.last_value_date
                    }

                    this.datesNow = JSON.parse(JSON.stringify(this.dates));

                    if (!this.limitDates?.start)
                        this.limitDates = {
                            start: this.probeValuesInfo.first_value_date_database,
                            end: this.probeValuesInfo.last_value_date_database
                        }

                    //Gráficas simples
                    this.destroyGraph()
                    this.createSimpleGraph()

                    //Creo el mapa de calor
                    this.destroyActiveHeatMap()
                    this.createActiveHeatMap()
                })
                .catch((err) => {
                    console.log(err)
                })
                .finally(() => {
                    this.isLoading = false;
                })
        },
        createSimpleGraph() {

            if (!this.probeValues || Object.keys(this.probeValues).length === 0) return;

            // Destruir gráfica anterior si existe
            if (this.chart) this.chart.dispose();

            // Crear raíz
            const root = am5.Root.new("simpleGraph");
            root.setThemes([am5themes_Animated.new(root)]);

            // 🔹 Contenedor principal vertical (leyenda arriba + gráfico debajo)
            const mainContainer = root.container.children.push(
                am5.Container.new(root, {
                    layout: root.verticalLayout,
                    width: am5.p100,
                    height: am5.p100,
                    paddingTop: 0,
                    paddingBottom: 0
                })
            );

            // Crear gráfico base dentro del contenedor
            const chart = mainContainer.children.push(
                am5xy.XYChart.new(root, {
                    panX: false,
                    panY: false,
                    wheelX: "panX",
                    wheelY: "zoomX",
                    pinchZoomX: true,
                    paddingLeft: 10,
                    paddingRight: 10
                })
            );


            // --- LEYENDA SUPERIOR ---
            const legend = mainContainer.children.unshift(
                am5.Legend.new(root, {
                    nameField: "name",
                    layout: root.horizontalLayout,
                    centerX: am5.p50,
                    x: am5.p50,
                    useDefaultMarker: false, // 👈 elimina el cuadro de color
                    marginBottom: 10,
                })
            );

            // 🔹 Estilo general
            legend.labels.template.setAll({
                fontSize: 13,
                fill: am5.color(0x1e1e1e),
                fontWeight: "500",
                opacity: 1,
                paddingLeft: 0, // elimina espacio que dejaba el cuadro
            });
            legend.valueLabels.template.set("forceHidden", true);



            // -- CURSOR --
            const cursor = chart.set("cursor", am5xy.XYCursor.new(root, {
                behavior: "zoomX"
            }));

            //lo estilizo
            cursor.lineY.set("strokeOpacity", 0.2);
            cursor.lineX.set("strokeOpacity", 0.2);
            cursor.lineX.set("strokeDasharray", [3, 3]);
            cursor.lineY.set("strokeDasharray", [3, 3]);

            // Eje X
            const xAxis = chart.xAxes.push(
                am5xy.DateAxis.new(root, {
                    baseInterval: { timeUnit: "minute", count: 15 },
                    renderer: am5xy.AxisRendererX.new(root, {}),
                    tooltip: am5.Tooltip.new(root, {}),
                })
            );
            //Escondo el grid del eje X
            xAxis.get("renderer").grid.template.set("forceHidden", true);


            // Ejes Y izquierdo y derecho
            const yAxisLeft = chart.yAxes.push(
                am5xy.ValueAxis.new(root, {
                    renderer: am5xy.AxisRendererY.new(root, {}),
                    tooltip: am5.Tooltip.new(root, {}),
                })
            );

            const yAxisRight = chart.yAxes.push(
                am5xy.ValueAxis.new(root, {
                    renderer: am5xy.AxisRendererY.new(root, {
                        opposite: true,
                    }),
                    tooltip: am5.Tooltip.new(root, {}),
                    min: 0,
                    max: 1,

                })
            );

            //Escondo el grid del eje derecho
            yAxisRight.get("renderer").grid.template.set("forceHidden", true);


            // -- COLORES DE PERIODOS --
            let periodColors = {
                1: am5.color(this.periodColors[1]), // rojo
                2: am5.color(this.periodColors[2]), // amarillo
                3: am5.color(this.periodColors[3]), // azul
                4: am5.color(this.periodColors[4]), // fucsia
                5: am5.color(this.periodColors[5]), // naranja
                6: am5.color(this.periodColors[6])  // verde
            };


            // 🔹 Transformar datos
            const data = [];
            for (const [date, q] of Object.entries(this.probeValues)) {
                data.push({
                    date: new Date(date).getTime(),
                    ea: q.relative_active ?? 0,
                    ei: q.relative_inductive ?? 0,
                    ec: q.relative_capacitive ?? 0,
                    p: q.potency ?? 0,
                    p_hired: q.hired_potency ?? 0,
                    cos_phi_inductive: q.cos_phi_inductive ?? 1,
                    cos_phi_capacitive: q.cos_phi_capacitive ?? 1,
                    num_period: q.num_period,
                    strokeSettings: { stroke: periodColors[q.num_period] },
                    fillSettings: { fill: periodColors[q.num_period] }
                });
            }


            console.log('data --> ', data)



            // -- SERIES --

                // --- Agrupar datos por periodo (respetando cortes entre tramos) ---
                const groupedData = {};
                let prevPeriod = null;
                let prevData = null;

                data.sort((a, b) => a.date - b.date);

                for (let d of data) {
                    const p = d.num_period || 0;
                    if (!groupedData[p]) groupedData[p] = [];

                    // Si hay cambio de periodo
                    if (prevPeriod !== null && prevPeriod !== p && prevData) {
                        // 🔹 Añadimos el punto de unión al periodo anterior
                        groupedData[prevPeriod].push({
                            date: d.date,
                            ea: d.ea,
                            ei: d.ei,
                            ec: d.ec,
                            num_period: prevPeriod,
                            fromPeriod: prevPeriod,
                            toPeriod: p,
                            isTransition: true
                        });

                        // 🔹 Evitamos duplicar el primer punto del nuevo periodo
                        const prevQuarter = Math.floor(prevData.date / (1000 * 60 * 15));
                        const currentQuarter = Math.floor(d.date / (1000 * 60 * 15));
                        if (prevQuarter === currentQuarter) {
                            // Es el mismo cuarto horario ⇒ saltamos este punto
                            prevPeriod = p;
                            prevData = d;
                            continue;
                        }
                    }

                    // Añadimos el punto normalmente
                    groupedData[p].push(d);
                    prevPeriod = p;
                    prevData = d;
                }


            // --- SERIES DE ENERGÍA ACTIVA ---
            Object.keys(groupedData).forEach(period => {
                groupedData[period].sort((a, b) => a.date - b.date);

                const active = chart.series.push(am5xy.LineSeries.new(root, {
                    name: `En. activa (P${period})`,
                    xAxis,
                    yAxis: yAxisLeft,
                    valueYField: "ea",
                    valueXField: "date",
                    connect: false, //gracias a esto hago que cuando hay cortes de periodo se corte la línea hasta la siguiente
                    stroke: periodColors[period],
                    fill: periodColors[period],
                    tooltip: am5.Tooltip.new(root, {
                        labelText: `En. activa (P${period}): [bold]{valueY}[/]`
                    })
                }));

                //Relleno con degradado
                active.fills.template.setAll({
                    visible: true,
                    fillOpacity: 0.5,
                    fillGradient: am5.LinearGradient.new(root, {
                        rotation: 90,
                        stops: [
                            { color: periodColors[period], opacity: 1 },
                            { color: am5.color(0xffffff), opacity: 0.5 }
                        ]
                    })
                });

                active.data.setAll(groupedData[period]);
            });



            // --- SERIES DE ENERGÍA INDUCTIVA ---
            Object.keys(groupedData).forEach(period => {
                groupedData[period].sort((a, b) => a.date - b.date);

                // Generamos una versión pastel (aclarada y ligeramente desaturada)
                let pastelColor = am5.Color.brighten(periodColors[period], 0.4);

                const inductive = chart.series.push(am5xy.LineSeries.new(root, {
                    name: `En. inductiva (P${period})`,
                    xAxis,
                    yAxis: yAxisLeft,
                    valueYField: "ei",
                    valueXField: "date",
                    connect: false,
                    stroke: pastelColor,
                    fill: pastelColor,
                    tooltip: am5.Tooltip.new(root, {
                        labelText: `En. inductiva (P${period}): [bold]{valueY}[/]`
                    })
                }));

                //Relleno
                inductive.fills.template.setAll({
                    visible: true,
                    fill: pastelColor,
                    fillOpacity: 1,
                    strokeOpacity: 0
                });

                inductive.data.setAll(groupedData[period]);
            });



            // --- SERIES DE ENERGÍA CAPACITIVA ---
            Object.keys(groupedData).forEach(period => {
                groupedData[period].sort((a, b) => a.date - b.date);

                const capacitive = chart.series.push(am5xy.LineSeries.new(root, {
                    name: `En. capacitiva (P${period})`,
                    xAxis,
                    yAxis: yAxisLeft,
                    valueYField: "ec",
                    valueXField: "date",
                    connect: false,
                    stroke: periodColors[period],
                    fill: periodColors[period],
                    tooltip: am5.Tooltip.new(root, {
                        labelText: `En. capacitiva (P${period}): [bold]{valueY}[/]`
                    })
                }));

                capacitive.fills.template.setAll({
                    visible: true,
                    fillOpacity: 1
                });

                capacitive.data.setAll(groupedData[period]);
            });


            // --- POTENCIA ---
            const p = chart.series.push(am5xy.LineSeries.new(root, {
                name: "Potencia",
                xAxis, yAxis: yAxisLeft,
                valueYField: "p", valueXField: "date",
                tooltip: am5.Tooltip.new(root, { labelText: "Potencia: [bold]{valueY}[/]" }),
                stroke: am5.color(0x03506f)
            }));
            p.data.setAll(data);


            // --- POTENCIA CONTRATADA ---
            const pHired = chart.series.push(am5xy.LineSeries.new(root, {
                name: "Pot. contratada",
                xAxis, yAxis: yAxisLeft,
                valueYField: "p_hired", valueXField: "date",
                tooltip: am5.Tooltip.new(root, { labelText: "Pot. contratada: [bold]{valueY}[/]" }),
                stroke: am5.color(0x312c51)
            }));
            pHired.data.setAll(data);


            // --- COSENO DE PHI INDUCTIVA ---
            const cosPhiInd = chart.series.push(am5xy.LineSeries.new(root, {
                name: "Cos Phi inductiva",
                xAxis, yAxis: yAxisRight,
                valueYField: "cos_phi_inductive", valueXField: "date",
                tooltip: am5.Tooltip.new(root, { labelText: "Cos Phi inductiva: [bold]{valueY}[/]" }),
                stroke: am5.color(0x74c7b8)
            }));
            cosPhiInd.data.setAll(data);


            // --- COSENO DE PHI CAPACITIVA ---
            const cosPhiCap = chart.series.push(am5xy.LineSeries.new(root, {
                name: "Cos Phi capacitiva",
                xAxis,
                yAxis: yAxisRight,
                valueYField: "cos_phi_capacitive",
                valueXField: "date",
                stroke: am5.color(0x77acf1),
                tooltip: am5.Tooltip.new(root, {
                    labelText: "Cos Phi capacitiva: [bold]{valueY}[/]"
                })
            }));
            cosPhiCap.data.setAll(data);


            // Muestro solo la energía activa
            chart.series.each(series => {
                const name = series.get("name");

                if (name.includes("En. activa")) {
                    // series de energía activa -> mostrar
                    series.show(0);
                } else {
                    // todas las demás -> ocultar
                    series.hide(0);
                }
            });


            // --- AGRUPAR SERIES POR TIPO Y CONFIGURAR LEYENDA ---
            const groupedSeries = {
                "En. activa": chart.series.values.filter(s => s.get("name").includes("En. activa")),
                "En. inductiva": chart.series.values.filter(s => s.get("name").includes("En. inductiva")),
                "En. capacitiva": chart.series.values.filter(s => s.get("name").includes("En. capacitiva")),
                "Potencia": chart.series.values.filter(s => s.get("name").includes("Potencia")),
                "Pot. contratada": chart.series.values.filter(s => s.get("name").includes("Pot. contratada")),
                "Cos Phi inductiva": chart.series.values.filter(s => s.get("name").includes("Cos Phi inductiva")),
                "Cos Phi capacitiva": chart.series.values.filter(s => s.get("name").includes("Cos Phi capacitiva"))
            };

            // 🔹 Limpiar leyenda previa
            legend.data.clear();

            // 🔹 Crear entradas manuales
            Object.entries(groupedSeries).forEach(([groupName, seriesGroup]) => {
                if (seriesGroup.length === 0) return;

                legend.data.push({
                    name: groupName,
                    stroke: am5.color(0x333333),
                    fill: am5.color(0xffffff),
                    seriesGroup, // guardamos referencia al grupo
                });
            });


            // 🔹 Ajustar estado inicial de las leyendas según visibilidad de sus series
            legend.events.once("datavalidated", () => {
                legend.dataItems.forEach((dataItem) => {
                    const seriesGroup = dataItem.dataContext.seriesGroup;
                    const label = dataItem.get("label");

                    // Verificar si hay alguna serie visible
                    const anyVisible = seriesGroup.some((s) => s.get("visible"));

                    // Si están ocultas, bajar opacidad de la etiqueta
                    label.set("opacity", anyVisible ? 1 : 0.4);
                });
            });

            // 🔹 Activar recalculado automático del eje Y
            chart.set("arrangeTooltips", true);
            chart.set("adjustLayout", true);

            // 🔹 Esperar a que la leyenda esté lista
            legend.events.once("datavalidated", () => {
                legend.dataItems.forEach((dataItem) => {
                    const seriesGroup = dataItem.dataContext.seriesGroup;
                    const container = dataItem.get("itemContainer");
                    const label = dataItem.get("label");

                    container.set("cursorOverStyle", "pointer");

                    container.events.on("click", () => {
                        const anyVisible = seriesGroup.some((s) => s.get("visible"));

                        seriesGroup.forEach((s) => {
                            if (anyVisible) s.hide(500);
                            else s.show(500);
                        });

                        // 🔹 Animación y actualización de opacidad del texto
                        label.animate({
                            key: "opacity",
                            to: anyVisible ? 0.4 : 1, // 👈 texto semitransparente si está oculto
                            duration: 400,
                            easing: am5.ease.out(am5.ease.cubic),
                        });

                        // 🔹 Recalcular ejes de forma suave
                        setTimeout(() => {
                            yAxisLeft.markDirtyExtremes();
                            yAxisRight.markDirtyExtremes();
                        }, 550);
                    });
                });
            });


            // Scrollbar
            const scrollbarX = am5.Scrollbar.new(root, {
                orientation: "horizontal",
                maxHeight: 5
            });
            chart.set("scrollbarX", scrollbarX);
            chart.bottomAxesContainer.children.push(scrollbarX);
            scrollbarX.startGrip.set("scale", 0.8);
            scrollbarX.endGrip.set("scale", 0.8);


            // Animaciones
            chart.appear(1000, 100);

            //Quito el logo
            root._logo.dispose();

            this.chart = root;
        },
        destroyGraph() {
            if (this.chart) {
                try {
                    this.chart.dispose(); // Destruye el root de amCharts
                } catch (e) {
                    console.warn("Error disposing chart:", e);
                }
                this.chart = null;
            }

            // 🔹 Limpieza total del contenedor
            const oldDiv = document.getElementById("simpleGraph");
            if (oldDiv) {
                const newDiv = oldDiv.cloneNode(false); // crea un div vacío con el mismo id
                oldDiv.parentNode.replaceChild(newDiv, oldDiv);
            }
        },
        createActiveHeatMap() {
            if (!this.probeValues || Object.keys(this.probeValues).length === 0) return;

            // Destruir gráfica anterior si existe
            if (this.activeHeatMap) this.activeHeatMap.dispose();

            let root = am5.Root.new("activeHeatMap");

            // Calcular altura dinámica según número de días únicos
            let groupedDates = Object.keys(this.probeValues).map(d => {
                const date = new Date(d);
                return date.toLocaleDateString("es-ES", { day: "2-digit", month: "short" });
            });
            let numDays = [...new Set(groupedDates)].length;

            // Altura base por día (ajústala según gusto)
            let cellHeight = 45;

            // Añadimos espacio extra para la leyenda (la barra inferior)
            let legendPadding = 120;

            // Altura total: alto del contenido + espacio para leyenda
            let totalHeight = Math.max(400, numDays * cellHeight + legendPadding);

            // Ajustar el alto del contenedor del gráfico dinámicamente
            let container = document.getElementById("activeHeatMap");
            if (container) container.style.height = `${totalHeight}px`;

            // Temas
            root.setThemes([am5themes_Animated.new(root)]);

            // Crear el gráfico sin scroll
            let chart = root.container.children.push(
                am5xy.XYChart.new(root, {
                    panX: false,
                    panY: false,
                    wheelX: "none",
                    wheelY: "none",
                    layout: root.verticalLayout,
                    paddingTop: 0,
                    paddingBottom: 100, // espacio para la leyenda
                    paddingLeft: 0,
                    paddingRight: 0,
                })
            );

            // Eje Y (días)
            let yRenderer = am5xy.AxisRendererY.new(root, {
                inversed: true,
                minGridDistance: 25,
            });
            yRenderer.grid.template.set("visible", false);

            let yAxis = chart.yAxes.push(
                am5xy.CategoryAxis.new(root, {
                    maxDeviation: 0,
                    renderer: yRenderer,
                    categoryField: "day",
                })
            );

            // Eje X (horas)
            let xRenderer = am5xy.AxisRendererX.new(root, {
                minGridDistance: 30,
            });
            xRenderer.grid.template.set("visible", false);

            let xAxis = chart.xAxes.push(
                am5xy.CategoryAxis.new(root, {
                    renderer: xRenderer,
                    categoryField: "hour",
                })
            );

            // Serie principal
            let series = chart.series.push(
                am5xy.ColumnSeries.new(root, {
                    calculateAggregates: true,
                    stroke: am5.color(0xffffff),
                    clustered: false,
                    xAxis,
                    yAxis,
                    categoryXField: "hour",
                    categoryYField: "day",
                    valueField: "value",
                })
            );

            // Texto dentro de cada celda
            series.bullets.push((root, series, dataItem) => {
                const value = dataItem.dataContext.value;
                const period = dataItem.dataContext.period ? `P${dataItem.dataContext.period}` : "";
                return am5.Bullet.new(root, {
                    sprite: am5.Label.new(root, {
                        text: `[fontSize:14][bold]${value}[/]\n[fontSize:7][#666]${period}[/]`,
                        populateText: true,
                        textAlign: "center",
                        centerX: am5.p50,
                        centerY: am5.p50,
                        fill: am5.color(0x333333),
                    }),
                });
            });

            // Estilo de las columnas
            series.columns.template.setAll({
                tooltipText: "{hour}h",
                strokeOpacity: 1,
                strokeWidth: 1,
                width: am5.percent(100),
                height: am5.percent(100),
            });

            // Colores
            let colorLow = am5.color(0xfffb77);
            let colorHigh = am5.color(0xfe131a);

            // Leyenda inferior
            let heatLegend = chart.bottomAxesContainer.children.push(
                am5.HeatLegend.new(root, {
                    orientation: "horizontal",
                    startColor: colorLow,
                    endColor: colorHigh,
                    stepCount: 1,
                    height: 50,
                    width: am5.percent(100),
                    labels: {
                        fill: am5.color(0x555555),
                        fontWeight: "600",
                        fontSize: 12,
                    },
                })
            );

            // Vincular valores reales a la leyenda
            series.events.on("datavalidated", function () {
                const low = series.getPrivate("valueLow");
                const high = series.getPrivate("valueHigh");
                if (low != null && high != null) {
                    heatLegend.set("startValue", low);
                    heatLegend.set("endValue", high);
                    heatLegend.startLabel.set("text", `${low.toFixed(1)} kWh`);
                    heatLegend.endLabel.set("text", `${high.toFixed(1)} kWh`);
                }
            });

            // Colores dinámicos
            series.set("heatRules", [
                {
                    target: series.columns.template,
                    min: colorLow,
                    max: colorHigh,
                    dataField: "value",
                    key: "fill",
                },
            ]);

            // Agrupar datos
            let groupedData = {};
            Object.entries(this.probeValues).forEach(([datetime, q]) => {
                let date = new Date(datetime);
                let localDate = new Date(date.getFullYear(), date.getMonth(), date.getDate(), date.getHours());
                let key = `${localDate.getFullYear()}-${String(localDate.getMonth() + 1).padStart(2, "0")}-${String(localDate.getDate()).padStart(2, "0")} ${String(localDate.getHours()).padStart(2, "0")}:00:00`;

                if (!groupedData[key]) {
                    groupedData[key] = {
                        day:
                            localDate.toLocaleDateString("es-ES", {
                                day: "2-digit",
                                month: "short",
                            }) +
                            " " +
                            String(localDate.getFullYear()).slice(2),
                        hour: String(localDate.getHours()).padStart(2, "0"),
                        value: q.relative_active ?? 0,
                        period: q.num_period,
                    };
                } else {
                    groupedData[key].value += q.relative_active ?? 0;
                }
            });

            let data = Object.values(groupedData);
            series.data.setAll(data);

            let days = [...new Set(data.map(d => d.day))];
            let hours = [...new Set(data.map(d => d.hour))].sort((a, b) => Number(a) - Number(b));

            yAxis.data.setAll(days.map(day => ({ day })));
            xAxis.data.setAll(hours.map(hour => ({ hour })));

            // Forzar alto real de las celdas (evita compresión)
            yAxis.get("renderer").set("cellStartLocation", 0);
            yAxis.get("renderer").set("cellEndLocation", 1);
            yAxis.set("maxHeight", days.length * cellHeight);
            chart.set("maxHeight", days.length * cellHeight + 200);
            chart.set("height", chart.get("maxHeight"));
            root.container.set("height", chart.get("maxHeight"));

            // Animaciones
            chart.appear(1000, 100);
            root._logo.dispose();

            this.activeHeatMap = chart;
        },
        destroyActiveHeatMap() {
            if (this.activeHeatMap) {
                try {
                    this.activeHeatMap.dispose(); // Destruye el root de amCharts
                } catch (e) {
                    console.warn("Error disposing chart:", e);
                }
                this.activeHeatMap = null;
            }

            // 🔹 Limpieza total del contenedor
            const oldDiv = document.getElementById("activeHeatMap");
            if (oldDiv) {
                const newDiv = oldDiv.cloneNode(false); // crea un div vacío con el mismo id
                oldDiv.parentNode.replaceChild(newDiv, oldDiv);
            }
        },
        normalizeToQuarterHour(field) {
            if (!this.dates[field]) return;

            let date = new Date(this.dates[field]);
            let minutes = date.getMinutes();

            // Calcular el cuarto de hora más cercano dentro de la misma hora
            let rounded = Math.round(minutes / 15) * 15;
            if (rounded === 60) rounded = 45; // 🔹 evita pasar a la siguiente hora

            date.setMinutes(rounded);
            date.setSeconds(0);
            date.setMilliseconds(0);

            // Formatear a string local (YYYY-MM-DDTHH:mm)
            let pad = n => n.toString().padStart(2, "0");
            let local =
                `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T` +
                `${pad(date.getHours())}:${pad(date.getMinutes())}`;

            this.dates[field] = local;
        },
        //Excel cuarto-horario
        async downloadExcelQuarters(){
            this.isLoading = true;

            await axios.get('/api/tools/segenet/excelQuarters',
                {
                    params: {
                        serial: this.probeSelected,
                        dates: this.dates
                    },
                    responseType: 'blob'
                })
                .then((res) => {
                    // Crear Blob del Excel
                    const blob = new Blob([res.data], {
                        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    });

                    // Crear enlace temporal y forzar descarga
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `cuartos_${this.probeData.name}.xlsx`;
                    document.body.appendChild(link);
                    link.click();

                    // Limpieza
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);
                })
                .catch((err) => {
                    console.log(err)
                })
                .finally((res) => {
                    this.isSeeingDropdownDowloads = false;
                    this.isLoading = false;
                })
        },
        //Excel cierres
        async downloadExcelCloses(){
            this.isLoading = true;

            await axios.get('/api/tools/segenet/excelCloses',
                {
                    params: {
                        serial: this.probeSelected,
                        date: this.closeDate
                    },
                    responseType: 'blob'
                })
                .then((res) => {
                    // Crear Blob del Excel
                    const blob = new Blob([res.data], {
                        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    });

                    // Crear enlace temporal y forzar descarga
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `cierres_mes_${this.probeData.name}.xlsx`;
                    document.body.appendChild(link);
                    link.click();

                    // Limpieza
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);
                    this.closeDate = null;
                })
                .catch((err) => {
                    console.log(err)
                })
                .finally((res) => {
                    this.isSeeingFloatingBox = false;
                    this.isSeeingDropdownDowloads = false;
                    this.isLoading = false;
                })
        },
        //Factura simulada
        async downloadMockInvoice(){

            this.isLoading = true;

            await axios.get('/api/tools/segenet/excelMockInvoice',
                {
                    params: {
                        serial: this.probeSelected,
                        dates: this.dates,
                        probe: this.probeData
                    },
                    responseType: 'blob'
                })
                .then((res) => {

                    // Crear Blob del Excel
                    const blob = new Blob([res.data], {
                        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    });

                    // Crear enlace temporal y forzar descarga
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `informe_simulado_${this.probeData.name}.xlsx`;
                    document.body.appendChild(link);
                    link.click();

                    // Limpieza
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);
                    this.closeDate = null;
                })
                .catch((err) => {
                    console.log(err)
                })
                .finally((res) => {
                    this.isSeeingFloatingBox = false;
                    this.isSeeingDropdownDowloads = false;
                    this.isLoading = false;
                })
        },
        openNewWindow(url){
            window.open(url, "_blank");
        }
    },
    computed:{
        filteredProbes(){
            //Si no tiene nada en el buscador devuelve el array completo ordenado
            if (!this.searchText.trim()){
                return this.probesAvailable.sort((a, b) =>
                    a.name.localeCompare(b.name, 'es', { sensitivity: 'base' })
                );
            }


            //Sino filtro y ordeno
            const search = this.searchText.toLowerCase().trim();

            return this.probesAvailable
                .filter((probe) => {
                    const nameMatch = probe.name?.toLowerCase().includes(search);
                    const cupsMatch = probe.cups?.toLowerCase().includes(search);
                    return nameMatch || cupsMatch;
                })
                .sort((a, b) =>
                    a.name.localeCompare(b.name, 'es', { sensitivity: 'base' })
                );
        }
    }
}
</script>

<style scoped>

</style>

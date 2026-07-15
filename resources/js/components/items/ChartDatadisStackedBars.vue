<template>
    <div class="stacked-chart-wrapper">
        <div class="stacked-chart" ref="stackedBars" :style="stackedChartStyle"></div>
    </div>
</template>
<script>
import * as am5 from '@amcharts/amcharts5';
import * as am5xy from "@amcharts/amcharts5/xy";
import am5themes_Animated from '@amcharts/amcharts5/themes/Animated';

export default {
    name: 'DatadisStackedBarsComponent',
    props: ["series"],
    data() {
        return {
            windowWidth: window.innerWidth,
            isMobileChart: window.innerWidth <= 810,
        }
    },
    computed: {
        stackedChartStyle() {
            if (this.windowWidth > 810) {
                return { width: "100%" };
            }

            const points = Array.isArray(this.series) ? this.series.length : 0;
            const minHeight = Math.max(420, points * 32);

            return { width: "100%", height: `${minHeight}px` };
        }
    },
    watch:{
        series: {
            handler(data){
                if(this.root){
                    this.updateChart(data)
                    this.$nextTick(() => {
                        if (this.root) {
                            this.root.resize();
                        }
                    });
                }
            },
            deep:true
        }
    },
    methods: {
        recreateChart() {
            if (this.root) {
                this.root.dispose();
                this.root = null;
                this.chart = null;
            }

            this.createChart(this.series || []);
        },
        updateWindowWidth() {
            const wasMobile = this.isMobileChart;
            this.windowWidth = window.innerWidth;
            this.isMobileChart = this.windowWidth <= 810;

            if (wasMobile !== this.isMobileChart) {
                this.$nextTick(() => {
                    this.recreateChart();
                });
                return;
            }

            this.$nextTick(() => {
                if (this.root) {
                    this.root.resize();
                }
            });
        },
        createChart(data) {
            const safeData = (data || []).filter(item => item && item.date !== undefined);
            let root = am5.Root.new(this.$refs.stackedBars);

            root.setThemes([
                am5themes_Animated.new(root)
            ]);

            const intlOptions = {
                style: "decimal",
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            };

            // Asigna el formato al NumberFormatter global:
            root.numberFormatter.set("numberFormat", intlOptions);
            root.numberFormatter.set("intlLocales", "es-ES");

            const isMobile = this.windowWidth <= 810;

            let chart = root.container.children.push(am5xy.XYChart.new(root, {
                panX: false,
                panY: isMobile,
                wheelX: "none",
                wheelY: "none",
                paddingLeft: 0,
                layout: root.verticalLayout
            }));

            let xAxis, yAxis;

            if (isMobile) {
                let xRenderer = am5xy.AxisRendererX.new(root, {
                    minorGridEnabled: false,
                    strokeOpacity: 0.1,
                });

                xAxis = chart.xAxes.push(am5xy.ValueAxis.new(root, {
                    min: 0,
                    renderer: xRenderer
                }));

                let yRenderer = am5xy.AxisRendererY.new(root, {
                    minorGridEnabled: false,
                    minGridDistance: 24,
                    inversed: true,
                });

                yAxis = chart.yAxes.push(am5xy.CategoryAxis.new(root, {
                    categoryField: "date",
                    renderer: yRenderer,
                    tooltip: am5.Tooltip.new(root, {})
                }));

                yRenderer.grid.template.setAll({ visible: false });
                yRenderer.labels.template.setAll({ fontSize: 13 });
                yAxis.data.setAll(safeData);
            } else {
                let xRenderer = am5xy.AxisRendererX.new(root, {
                    minorGridEnabled: true,
                    minGridDistance: 0,
                });

                xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                    categoryField: "date",
                    renderer: xRenderer,
                    tooltip: am5.Tooltip.new(root, {})
                }));

                xRenderer.grid.template.setAll({
                    visible: false
                })

                xAxis.data.setAll(safeData);

                yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                    min: 0,
                    renderer: am5xy.AxisRendererY.new(root, {
                        strokeOpacity: 0.1
                    })
                }));
            }

            function makeSeries(name, fieldName, mobileMode) {
                let series = chart.series.push(am5xy.ColumnSeries.new(root, {
                    name: name,
                    stacked: true,
                    xAxis: xAxis,
                    yAxis: yAxis,
                    valueYField: mobileMode ? undefined : fieldName,
                    categoryXField: mobileMode ? undefined : "date",
                    valueXField: mobileMode ? fieldName : undefined,
                    categoryYField: mobileMode ? "date" : undefined,
                }));

                series.columns.template.setAll({
                    tooltipText: mobileMode ? "{valueX} kWh" : "{valueY} kWh",
                    tooltipY: am5.percent(10),
                    height: mobileMode ? am5.percent(84) : undefined,
                });
                series.data.setAll(safeData);

                series.appear();

                //No mostrar barras con valor 0
                series.columns.template.adapters.add("visible", function(visible, target) {
                    return mobileMode ? target.dataItem.get("valueX") !== 0 : target.dataItem.get("valueY") !== 0;
                });
            }

            makeSeries("Valle", "low", isMobile);
            makeSeries("Llano", "mid", isMobile);
            makeSeries("Punta", "high", isMobile);

            chart.appear(1000, 100);

            //Eliminar logo
            root._logo.dispose();

            this.isMobileChart = isMobile;
            this.chart  = chart;
            this.root = root;
        },
        updateChart(data){
            const safeData = (data || []).filter(item => item && item.date !== undefined);

            if(this.isMobileChart){
                //Actualizo valores eje Y (categorías) en móvil
                this.chart.yAxes.getIndex(0).data.setAll(safeData);
            }else{
                //Actualizo valores eje X en desktop
                this.chart.xAxes.getIndex(0).data.setAll(safeData);
            }

            //Actualizo valores de las series
            for(let i = 0; i < 3; i++){
                this.chart.series.getIndex(i).data.setAll(safeData);
                //Lanzo la animacion
                this.chart.series.getIndex(i).appear(1000, 100);
            }
        }
    },
    mounted(){
        window.addEventListener('resize', this.updateWindowWidth);
        this.createChart(this.series)
    },
    beforeDestroy() {
        window.removeEventListener('resize', this.updateWindowWidth);
        if (this.root) {
            this.root.dispose();
        }
    }
}
</script>

<style scoped>
.stacked-chart-wrapper {
    width: 100%;
}

.stacked-chart {
    width: 100%;
    height: 500px;
}

@media (max-width: 810px) {
    .stacked-chart-wrapper {
        overflow-y: visible;
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
        max-height: none;
    }

    .stacked-chart {
        min-height: 420px;
    }
}
</style>

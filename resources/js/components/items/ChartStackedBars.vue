<template>
    <div class="chart" ref="stackedchart">
    </div>
</template>

<script>
import * as am5 from '@amcharts/amcharts5';
import * as am5xy from '@amcharts/amcharts5/xy';
import am5themes_Animated from '@amcharts/amcharts5/themes/Animated';

export default {
    name: 'StackedBarsChartComponent',
    props:["series","modelValue", "type"],
    data(){
        return{
            chart: null
        }
    },
    computed: {
        root: {
            get() {
                return this.modelValue;
            },
            set(newValue) {
                this.$emit('update:modelValue', newValue);
            },
        },
    },
    watch:{
        series: {
            handler(data){
                if(this.root){
                    this.updateChart(data)
                }
            },
            deep:true
        }
    },
    methods:{
        createChart(data){
            console.log(data);
            let ejeX = data.length > 0 ? Object.keys(data[0])[0] : "consumos";

            let root = am5.Root.new(this.$refs.stackedchart);

            // Definir tema
            root.setThemes([
                am5themes_Animated.new(root)
            ]);

            // Crear gráfica
            let chart = root.container.children.push(am5xy.XYChart.new(root, {
                panX: false,
                panY: false,
                paddingLeft: 0,
                wheelX: "panX",
                wheelY: "zoomX",
                layout: root.verticalLayout,
            }));

            // Definir leyenda
            let legend = chart.children.push(
                am5.Legend.new(root, {
                    centerX: am5.p50,
                    x: am5.p50
                })
            );

            // Crear ejes
            let xRenderer = am5xy.AxisRendererX.new(root, {
                minorGridEnabled: true,
                minGridDistance: 20
            })

            let xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                categoryField: ejeX,
                renderer: xRenderer,
                tooltip: am5.Tooltip.new(root, {})
            }));

            xRenderer.grid.template.setAll({
                location: 1
            })

            xAxis.onPrivate("cellWidth", function(cellWidth) {
                let rotated = false;
                xRenderer.labels.each(function(label) {
                    if (label.width() > cellWidth) {
                    rotated = true;
                    }
                });

                if (rotated) {
                    xRenderer.labels.template.setAll({
                    rotation: -45,
                    centerX: am5.p100
                    });
                }
                else {
                    xRenderer.labels.template.setAll({
                    rotation: 0,
                    centerX: am5.p50
                    });
                }
                });

            xAxis.data.setAll(data);

            let yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                min: 0,
                extraMax: 0.1,
                calculateTotals: true,
                renderer: am5xy.AxisRendererY.new(root, {})
            }));

            // Configurar series
            function makeSeries(name, fieldName, showTotal) {
                let series = chart.series.push(am5xy.ColumnSeries.new(root, {
                    name: name,
                    xAxis: xAxis,
                    yAxis: yAxis,
                    stacked: true,
                    valueYField: fieldName,
                    categoryXField: ejeX,
                    maskBullets: false,
                    minBulletDistance: 35
                }));

                series.columns.template.setAll({
                    tooltipText: "{name}, {valueY}",
                    width: am5.percent(80),
                    tooltipY: 0
                });

                if(showTotal) {
                    series.columns.template.setAll({
                        fillOpacity: 0,
                        strokeOpacity: 0
                    })
                }

                if(showTotal){
                    series.bullets.push(function () {
                        return am5.Bullet.new(root, {
                            locationY: 1,
                            sprite: am5.Label.new(root, {
                                text: "{valueYTotal.formatNumber('#.')}",
                                fill: am5.color(0x000000),
                                centerY: am5.p100,
                                centerX: am5.p50,
                                populateText: true,
                            })
                        });
                    });
                }

                series.data.setAll(data);

                series.appear();

                if (!showTotal) {
                    legend.data.push(series);
                }
            }


            //Crear series
            let isFirst = true;
            for(let item in data[0]){
                //El primer objeto no se crea serie, pues es el campo x
                if(isFirst) {isFirst = !isFirst; continue}
                //Si es el objeto con nombre none, se pasa showTotal true
                if(item == "none"){
                    makeSeries(item,item,true);
                    continue;
                }
                makeSeries(item, item);
            }

            // Animar
            chart.appear(1000, 100);

            //Eliminar logo
            root._logo.dispose();

            this.chart  = chart;
            this.root = root;
        },
        updateChart(data){
            let seriesIndex = 7
            if(this.type == "gas"){
                seriesIndex = 2
            }
            //Actualizo valores eje X
            this.chart.xAxes.getIndex(0).data.setAll(data);
            //Actualizo valores de las series
            for(let i = 0; i < seriesIndex; i++){
                this.chart.series.getIndex(i).data.setAll(data);
            }
        }
    },
    mounted(){
        this.createChart(this.series)
    },
    beforeDestroy() {
        if (this.root) {
            this.root.dispose();
        }
    }
}
</script>

<style scoped>
    .chart {
        width: 100%;
        height: 40vw;
        min-height: 250px;
        max-height: 500px;
    }
</style>

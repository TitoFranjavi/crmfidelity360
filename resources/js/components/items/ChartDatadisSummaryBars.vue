<template>
    <div class="chart" ref="summaryBars">
    </div>
</template>
<script>
import * as am5 from '@amcharts/amcharts5';
import * as am5xy from "@amcharts/amcharts5/xy";
import am5themes_Animated from '@amcharts/amcharts5/themes/Animated';

export default {
    name: 'DatadisSummaryBarsComponent',
    props: ["series"],
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
    methods: {
        createChart(data) {
            let root = am5.Root.new(this.$refs.summaryBars);

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

            // Create chart
            // https://www.amcharts.com/docs/v5/charts/xy-chart/
            let chart = root.container.children.push(am5xy.XYChart.new(root, {
                panX: false,
                panY: false,
                wheelX: "none",
                wheelY: "none",
                pinchZoomX: false,
                pinchZoomY: false,
                paddingLeft: 0,
                paddingBottom: 0,
                paddingTop: 0,
                layout: root.verticalLayout
            }));

            // Create axes
            // https://www.amcharts.com/docs/v5/charts/xy-chart/axes/
            let xRenderer = am5xy.AxisRendererX.new(root, {
                strokeOpacity: 0.1,
                minorGridEnabled: true,
                minGridDistance: 0,
            });
            let xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                categoryField: "date",
                renderer: xRenderer,
                tooltip: am5.Tooltip.new(root, {})
            }));

            xRenderer.grid.template.setAll({
                visible: false
            })

            xRenderer.labels.template.setAll({

            })

            xAxis.data.setAll(data);

            let yRenderer = am5xy.AxisRendererY.new(root, {
            });

            let yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                min: 0,
                renderer: yRenderer,
            }));

            yRenderer.grid.template.setAll({
                visible: false,
            })

            yRenderer.labels.template.setAll({
                visible: false,
            })

            // Add series
            // https://www.amcharts.com/docs/v5/charts/xy-chart/series/
            function makeSeries(name, fieldName) {
                let series = chart.series.push(am5xy.ColumnSeries.new(root, {
                    name: name,
                    stacked: true,
                    xAxis: xAxis,
                    yAxis: yAxis,
                    valueYField: fieldName,
                    categoryXField: "date"
                }));

                series.columns.template.setAll({
                    tooltipText: "{valueY} kWh",
                    tooltipY: am5.percent(10),
                    templateField: "columnSettings"
                });

                series.data.setAll(data);

                series.appear();

                //No mostrar barras con valor 0
                series.columns.template.adapters.add("visible", function(visible, target) {
                    return target.dataItem.get("valueY") !== 0;
                });
            }

            makeSeries("Valle", "low");
            makeSeries("Llano", "mid");
            makeSeries("Punta", "high");

            chart.appear(1000, 100);

            //Eliminar logo
            root._logo.dispose();

            this.chart  = chart;
            this.root = root;
        },
        updateChart(data){
            //Actualizo valores eje X
            this.chart.xAxes.getIndex(0).data.setAll(data);
            //Actualizo valores de las series
            for(let i = 0; i < 3; i++){
                this.chart.series.getIndex(i).data.setAll(data);
                //Lanzo la animacion
                this.chart.series.getIndex(i).appear(1000, 100);
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
    width: 350px;
    height: 140px;
}
</style>

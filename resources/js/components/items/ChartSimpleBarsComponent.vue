<template>
    <div class="chart" ref="simplechart">
    </div>
</template>

<script>
import * as am5 from '@amcharts/amcharts5';
import * as am5xy from '@amcharts/amcharts5/xy';
import am5themes_Animated from '@amcharts/amcharts5/themes/Animated';


export default {
    name: 'SimpleBarsChartComponent',
    props: ["series"],
    methods:{
        createChart(data){

            let root = am5.Root.new(this.$refs.simplechart);

            // Definir Tema
            // https://www.amcharts.com/docs/v5/concepts/themes/
            root.setThemes([
                am5themes_Animated.new(root)
            ]);

            // Create chart
            // https://www.amcharts.com/docs/v5/charts/xy-chart/
            let chart = root.container.children.push(am5xy.XYChart.new(root, {
                panX: false,
                panY: false,
                //wheelX: "panX",
                //wheelY: "zoomX",
                //pinchZoomX:true,
                paddingLeft: 0,
                paddingRight: 0,
            }));

            //elimino el botón de zoom
            chart.zoomOutButton.set("forceHidden", true);

            // Add cursor
            // https://www.amcharts.com/docs/v5/charts/xy-chart/cursor/
            let cursor = chart.set("cursor", am5xy.XYCursor.new(root, {}));
            cursor.lineY.set("visible", false);
            cursor.lineX.set("visible", false);

            // Create axes
            // https://www.amcharts.com/docs/v5/charts/xy-chart/axes/
            let xRenderer = am5xy.AxisRendererX.new(root, {
                minGridDistance: 30,
                minorGridEnabled: true
            });

            xRenderer.labels.template.setAll({
                centerY: am5.p50,
                centerX: am5.p50,
            });

            xRenderer.grid.template.setAll({
                location: 1
            })

            let xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                maxDeviation: 0.3,
                categoryField: "period",
                renderer: xRenderer,
                tooltip: am5.Tooltip.new(root, {})
            }));

            let yRenderer = am5xy.AxisRendererY.new(root, {
                strokeOpacity: 0.1
            })

            let yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                maxDeviation: 0.3,
                min: 0,
                strictMinMax: true,
                renderer: yRenderer
            }));

            // Create series
            // https://www.amcharts.com/docs/v5/charts/xy-chart/series/
            let series = chart.series.push(am5xy.ColumnSeries.new(root, {
                name: "Series 1",
                xAxis: xAxis,
                yAxis: yAxis,
                valueYField: "value",
                sequencedInterpolation: true,
                categoryXField: "period",
                tooltip: am5.Tooltip.new(root, {
                    labelText: "{valueY}"
                })
            }));

            series.columns.template.setAll({ cornerRadiusTL: 5, cornerRadiusTR: 5, strokeOpacity: 0 });
            series.columns.template.adapters.add("fill", function (fill, target) {
                return chart.get("colors").getIndex(series.columns.indexOf(target));
            });

            series.columns.template.adapters.add("stroke", function (stroke, target) {
                return chart.get("colors").getIndex(series.columns.indexOf(target));
            });


            // Ejemplo Extrucutra de Datos
            /**
                let showData = [{
                    period : "P1",
                    value : 2
                },{
                    period : "P2",
                    value : 1
                },{
                    period : "P3",
                    value : 2
                },{
                    period : "P4",
                    value : 1
                },{
                    period : "P5",
                    value : 2
                },{
                    period : "P6",
                    value : 1
                }];
            */

            xAxis.data.setAll(data);
            series.data.setAll(data);


            // Make stuff animate on load
            // https://www.amcharts.com/docs/v5/concepts/animations/
            series.appear(1000);
            chart.appear(1000, 100);

            //Eliminar logo
            root._logo.dispose();

            this.root = root;

        }
    },
    mounted(){
        this.createChart(this.series)
    },
    beforeUpdate(){
        if(this.root){
            this.root.dispose();
        }
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

<template>
    <div class="chart" ref="linechart">
    </div>
</template>

<script>
import * as am5 from '@amcharts/amcharts5';
import * as am5xy from '@amcharts/amcharts5/xy';
import am5themes_Animated from "@amcharts/amcharts5/themes/Animated";

export default {
    name: 'LineChartComponent',
    props: ["series","modelValue"],
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

            let ejeX = Object.keys(data[0])[0];
            let value1 = Object.keys(data[0])[1];
            let value2 = Object.keys(data[0])[2];

            let root = am5.Root.new(this.$refs.linechart);

            root.setThemes([
                am5themes_Animated.new(root)
            ]);

            let chart = root.container.children.push(am5xy.XYChart.new(root, {
                panX: true,
                panY: true,
                wheelX: "panX",
                wheelY: "zoomX",
                pinchZoomX:true,
                paddingBottom: 0,
            }));

            chart.get("colors").set("step", 3);


            let cursor = chart.set("cursor", am5xy.XYCursor.new(root, {}));
            cursor.lineY.set("visible", false);


            let xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                categoryField: ejeX,
                renderer: am5xy.AxisRendererX.new(root, { minorGridEnabled: false, marginLeft: 0, minGridDistance: 20 }),
                startLocation: 0.5,
                endLocation: 0.5
            }));

            xAxis.data.setAll(data);

            let yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                maxDeviation: 0.3,
                renderer: am5xy.AxisRendererY.new(root, {})
            }));

            let xRenderer = xAxis.get("renderer");

            xRenderer.labels.template.setAll({
                fontSize: "12px",
                location: 0.5,
                multiLocation: 0.5
            });

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

            let yRenderer = yAxis.get("renderer");

            yRenderer.labels.template.setAll({
                fontSize: "12px"
            });


            let series = chart.series.push(am5xy.LineSeries.new(root, {
                name: value1,
                xAxis: xAxis,
                yAxis: yAxis,
                valueYField: value1,
                categoryXField: ejeX,
                tooltip: am5.Tooltip.new(root, {
                    labelText: `${ejeX}: {${ejeX}}\n${value1}: {${value1}}\n${value2}: {${value2}}`
                })
            }));

            series.strokes.template.setAll({
                strokeWidth: 2
            });

            series.fills.template.setAll({
                fillOpacity: 0.2,
                visible: true
            });

            series.get("tooltip").get("background").set("fillOpacity", 0.5);

            let series2 = chart.series.push(am5xy.LineSeries.new(root, {
                name: value2,
                xAxis: xAxis,
                yAxis: yAxis,
                valueYField: value2,
                categoryXField: ejeX
            }));

            series2.strokes.template.setAll({
                strokeWidth: 2
            });


            series2.fills.template.setAll({
                fillOpacity: 0.2,
                visible: true
            });

            series.data.setAll(data);
            series2.data.setAll(data);

            let legend = chart.children.push(
                am5.Legend.new(root, {
                    centerX: am5.p50,
                    x: am5.p50
                })
            );

            legend.data.setAll(chart.series.values);

            series.appear(1000);
            series2.appear(1000);
            chart.appear(1000, 100);

            //Eliminar logo
            root._logo.dispose();

            this.chart = chart;
            this.root = root;

        },
        updateChart(data){
            //Actualizo valores eje X
            this.chart.xAxes.getIndex(0).data.setAll(data);
            //Actualizo valores de las series
            this.chart.series.getIndex(0).data.setAll(data);
            this.chart.series.getIndex(1).data.setAll(data);
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
    .chart{
        width: 50%;
        height: 25%;
        max-height: 500px;
        min-width: 320px;
        min-height: 250px;
    }
</style>

<template>
    <div class="chart" ref="heatmapchart">
    </div>
</template>

<script>
import * as am5 from '@amcharts/amcharts5';
import * as am5xy from '@amcharts/amcharts5/xy';
import am5themes_Animated from '@amcharts/amcharts5/themes/Animated';

export default {
    name: 'HeatMapChartComponent',
    props:["series"],
    methods:{
        createChart(data){

            let ejeX = Object.keys(data[0])[0];
            let ejeY = Object.keys(data[0])[1];

            let root = am5.Root.new(this.$refs.heatmapchart);

            root.setThemes([
            am5themes_Animated.new(root)
            ]);

            let chart = root.container.children.push(am5xy.XYChart.new(root, {
            panX: false,
            panY: false,
            wheelX: "none",
            wheelY: "none",
            paddingLeft: 0,
            layout: root.verticalLayout
            }));


            // Create axes and their renderers
            let yRenderer = am5xy.AxisRendererY.new(root, {
            visible: false,
            minGridDistance: 20,
            inversed: true,
            minorGridEnabled: true
            });

            yRenderer.grid.template.set("visible", false);

            let yAxis = chart.yAxes.push(am5xy.CategoryAxis.new(root, {
            maxDeviation: 0,
            renderer: yRenderer,
            categoryField: ejeY
            }));

            let xRenderer = am5xy.AxisRendererX.new(root, {
            visible: false,
            minGridDistance: 30,
            opposite:true,
            minorGridEnabled: true
            });

            xRenderer.grid.template.set("visible", false);

            // xRenderer.labels.template.setAll({
            //     rotation: -45,
            //     centerX: am5.p0
            // });

            let xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
            renderer: xRenderer,
            categoryField: ejeX
            }));


            // Create series
            // https://www.amcharts.com/docs/v5/charts/xy-chart/#Adding_series
            let series = chart.series.push(am5xy.ColumnSeries.new(root, {
            calculateAggregates: true,
            stroke: am5.color(0xffffff),
            clustered: false,
            xAxis: xAxis,
            yAxis: yAxis,
            categoryXField: ejeX,
            categoryYField: ejeY,
            valueField: "value"
            }));

            series.columns.template.setAll({
            tooltipText: "{value}",
            strokeOpacity: 1,
            width: am5.percent(100),
            height: am5.percent(100)
            });

            series.columns.template.events.on("pointerover", function(event) {
            let di = event.target.dataItem;
            if (di) {
                heatLegend.showValue(di.get("value", 0));
            }
            });

            series.events.on("datavalidated", function() {
            heatLegend.set("startValue", series.getPrivate("valueHigh"));
            heatLegend.set("endValue", series.getPrivate("valueLow"));
            });

            // https://www.amcharts.com/docs/v5/concepts/settings/heat-rules/
            series.set("heatRules", [{
            target: series.columns.template,
            min: am5.color(	"#40b8dd"),
            max: am5.color("#012C68"),
            dataField: "value",
            key: "fill"
            }]);


            // Add heat legend
            // https://www.amcharts.com/docs/v5/concepts/legend/heat-legend/
            let heatLegend = chart.bottomAxesContainer.children.push(am5.HeatLegend.new(root, {
            orientation: "horizontal",
            endColor: am5.color("#40b8dd"),
            startColor: am5.color("#012C68")
            }));

            series.data.setAll(data);

            // Auto-populate X and Y axis category data
            let yAxisLabels = [];
            let xAxisLabels = [];
            am5.array.each(data, function(row) {
            if (yAxisLabels.indexOf(row[ejeY]) == -1) {
                yAxisLabels.push(row[ejeY]);
            }
            if (xAxisLabels.indexOf(row[ejeX]) == -1) {
                xAxisLabels.push(row[ejeX]);
            }
            });


            yAxis.data.setAll(yAxisLabels.map(function(item) {
            return { [ejeY]: item }
            }));

            xAxis.data.setAll(xAxisLabels.map(function(item) {
            return { [ejeX]: item }
            }));


            // Make stuff animate on load
            // https://www.amcharts.com/docs/v5/concepts/animations/#Initial_animation
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
.chart{
    width: 100%;
    height:500px;
}
</style>

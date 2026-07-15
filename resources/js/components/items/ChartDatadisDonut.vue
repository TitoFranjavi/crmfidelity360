<template>
    <div class="chart" ref="donutchart">
    </div>
</template>
<script>
import * as am5 from '@amcharts/amcharts5';
import * as am5percent from "@amcharts/amcharts5/percent";
import am5themes_Animated from '@amcharts/amcharts5/themes/Animated';

export default {
    name: 'DonutChartComponent',
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
    methods:{
        createChart(data){
            const isMobile = window.innerWidth <= 810;
            let root = am5.Root.new(this.$refs.donutchart);

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

            let chart = root.container.children.push(am5percent.PieChart.new(root, {
                layout: root.horizontalLayout,
                radius: am5.percent(95),
                innerRadius: am5.percent(80),
            }));

            let series = chart.series.push(am5percent.PieSeries.new(root, {
                valueField: "value",
                categoryField: "category",
                legendLabelText: "[{fill} bold fontSize: 14px]{category}[/]",
                legendValueText: ""  // No mostrar el valor en la leyenda
            }));

            series.slices.template.set("tooltipText", "{value} kWh");

            // Desabilitar efecto hover
            series.slices.template.states.create("hover", {
                scale: 1,
            });

            // Desabilitar efecto click
            series.slices.template.set("toggleKey", "none");

            //No mostrar etiquetas ni la linea que las une
            series.labels.template.set("forceHidden", true);
            series.ticks.template.set("forceHidden", true);

            series.data.setAll(data);

            //No mostrar barras con valor 0
            series.events.on("datavalidated", function() {
                am5.array.each(series.dataItems, function(dataItem) {
                    if (dataItem.get("value") == 0) {
                        dataItem.hide();
                    }
                })
            });

            let legend = chart.children.push(am5.Legend.new(root, {
                layout: root.verticalLayout,
                y: am5.percent(25),
                width: 70,
                marginLeft: isMobile ? 24 : 10,
            }));

            legend.markers.template.setAll({
                width:14,
                height:14,
            });
            legend.data.setAll(series.dataItems);

            series.appear(1000, 100);

            //Eliminar logo
            root._logo.dispose();

            this.chart  = chart;
            this.root = root;
        },
        updateChart(data){
            //Actualizo valores de la serie
            this.chart.series.getIndex(0).data.setAll(data);
            //Lanzo la animacion
            this.chart.series.getIndex(0).appear(1000, 100);
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
    width: 250px;
    height: 125px;
}

@media (max-width: 810px) {
    .chart {
        width: 100%;
        min-width: 0;
        height: 180px;
    }
}
</style>

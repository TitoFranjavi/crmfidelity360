<template>
    <div class="chart" ref="radio"></div>
</template>

<script>
import * as am5 from '@amcharts/amcharts5';
import * as am5percent from "@amcharts/amcharts5/percent";
import am5themes_Animated from "@amcharts/amcharts5/themes/Animated";

export default {
    name: 'ChartSemiRadioComponent',
    props: ["series"],
    data() {
        return {
            root: null
        };
    },
    methods: {
        createChart(data) {
            // Inicializar el gráfico sólo si ref existe
            let root = am5.Root.new(this.$refs.radio);

            root.setThemes([am5themes_Animated.new(root)]);

            let chart = root.container.children.push(
                am5percent.PieChart.new(root, {
                    startAngle: -180,
                    endAngle: 0
                })
            );

            let series = chart.series.push(
                am5percent.PieSeries.new(root, {
                    name: "Series",
                    valueField: "value",
                    categoryField: "category",
                    startAngle: -180,
                    endAngle: 0,
                    tooltip: am5.Tooltip.new(root, {
                        labelText: `{category}: {value}`,
                    }),
                    legendLabelText: `{category} :`,
                    legendValueText: "{value}"
                })
            );
            
            series.data.setAll(data);

            // Ocultar las etiquetas
            series.labels.template.set("forceHidden", true);
            series.ticks.template.set("forceHidden", true);

            // Agregar leyenda encima del gráfico
            let legend = chart.children.push(
                am5.Legend.new(root, {
                    layout: root.horizontalLayout,
                    layout: root.verticalLayout,
                    legendLabelText: "{category}",
                    legendValueText: "{value}"
                })
            );
            legend.data.setAll(series.dataItems);

            series.states.create("hidden", { endAngle: -90 });

            // Desactivar el Toggle
            series.slices.template.set("toggleKey", "none");


            series.labels.template.setAll({
                radius: 20
            });

            // Definir datos de muestra
            /**
                let showData = [
                    { category: "Lithuania", value: 501.9 },
                    { category: "Czechia", value: 301.9 },
                    { category: "Ireland", value: 201.1 },
                    { category: "Germany", value: 165.8 },
                    { category: "Australia", value: 139.9 },
                    { category: "Austria", value: 128.3 },
                    { category: "UK", value: 99 }
                ];
            */

            

            series.appear(1000, 100);

            // Quitar Logo
            root._logo.dispose();

            this.root = root;
        }
    },
    mounted() {
        this.createChart(this.series);
    },
    watch: {
        series: {
            handler(data) {
                if (this.root) {
                    this.root.dispose();
                }
                this.createChart(data);
            },
            deep: true
        }
    },
    beforeDestroy() {
        if (this.root) {
            this.root.dispose();
        }
    }
};
</script>

<style scoped>
    .chart {
        width: 50%;
        height: 20vw;
        min-width: 320px;
        min-height: 250px;
    }
</style>

<template>
    <div :class="[$attrs.class, 'w-1000-px']" style="top:0;left:0px">
        <chart-stackedbars-component v-model="chartConsumes" :series="consumesSeries"></chart-stackedbars-component>
    </div>
</template>

<script>
import * as am5plugins_exporting from "@amcharts/amcharts5/plugins/exporting";

export default{
    name: "ComparatorGasPDF",
    props: [
        'basicData',
        'pdfForm',
        'optionSelected',
        'consumption',
        'cupsIntervalsData',
        'prices',
        'currentTotal',
        'currentSubtotal', // 👈 FALTABA ESTA
        'dates',
        'period',
        'offer',
        'fixedPricePeriod',
        'taxes',
        'applyTaxes',
        'otherConcepts',
        'meterDevicePricePeriod'
    ],


    emits: ['closeForm', 'loading'],
    data(){
        return {
            
            chartConsumes: null,
            consumesSeries: [],
            totalPeriods: [],
            totalMonths: [],
        }
    },
    methods: {
        calc(){
            this.calcConsumes();
        },
        calcConsumes(){
            //Iteramos sobre las lecturas
            for(let item of this.cupsIntervalsData){
                //Iteramos sobre los periodos de cada lectura
                let periodData = {fecha:item.fechaFinMesConsumo, consumo: item.consumoEnWhP1, none: 0}
                this.consumesSeries.push(periodData)
            }
        },
        parseStringToNumber(number){
            if(typeof number === "number"){
                return number;
            }else if(typeof number === "string"){
                return number === "" ? 0 : parseFloat(number.replace(",","."));
            }else{
                return 0
            }
        },
        getBase64ImageFromURL(url) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.src = url;
                img.onload = () => {
                    const canvas = document.createElement("canvas");
                    const ctx = canvas.getContext("2d");
                    canvas.height = img.height;
                    canvas.width = img.width;
                    ctx.drawImage(img, 0, 0);
                    const base64String = canvas.toDataURL("image/png"); // Convert to base64
                    resolve(base64String);
                };
                img.onerror = (error) => reject(error);
            });
        },
        fileToBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader(); // Creamos un FileReader

                // Cuando la lectura termine, se resuelve la promesa con la base64.
                reader.onloadend = () => resolve(reader.result);

                // En caso de error, se rechaza la promesa.
                reader.onerror = reject;

                // Leemos el archivo como un Data URL (base64)
                reader.readAsDataURL(file);
            });
        },
        async savePDF() {

    const form = new FormData();

    const payload = {
        basicData: this.basicData,
        pdfForm: this.pdfForm,
        consumption: this.consumption,
        cupsIntervalsData: this.cupsIntervalsData,
        prices: this.prices,
        offer: this.offer,
        period: this.period,
        dates: this.dates,
        optionSelected: this.optionSelected,
        fixedPricePeriod: this.fixedPricePeriod,
        currentTotal: this.currentTotal,
        currentSubtotal: this.currentSubtotal,
        applyTaxes: this.applyTaxes,
        taxes: this.taxes,
        otherConcepts: this.otherConcepts,
        meterDevicePricePeriod: this.meterDevicePricePeriod,
        enterpriseId: this.basicData.enterprise?._id
    };

    form.append('payload', JSON.stringify(payload));

    // 🔥 LOGO EXACTAMENTE COMO LUZ
    if (this.pdfForm.enterpriseImg instanceof File) {
        form.append('enterpriseImg', this.pdfForm.enterpriseImg);
    }

    this.$emit('loading', true);

    try {

        const response = await axios.post(
            '/api/tools/generateGasPDF',
            form,
            {
                responseType: 'blob'
            }
        );

        const blob = new Blob([response.data], { type: 'application/pdf' });
        const url = URL.createObjectURL(blob);

        const link = document.createElement('a');
        link.href = url;
        link.download = `comparativa_gas_${this.pdfForm.order?.CUPS ?? 'gas'}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);

    } catch (err) {

        Swal.fire({
            icon: 'error',
            title: 'Error al generar el PDF',
            text: err.response?.data?.message || err.message
        });

    } finally {

        this.$emit('loading', false);
        this.$emit('closeForm');
    }
}


    },
    created(){
        this.calc();
        setTimeout(() => {
            this.savePDF();
        }, "1500")
    }
}
</script>

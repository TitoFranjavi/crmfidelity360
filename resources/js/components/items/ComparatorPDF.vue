<template>

</template>

<script>
import * as am5plugins_exporting from "@amcharts/amcharts5/plugins/exporting";

export default {
    name: "ComparatorPDF",
    props: ['fee', 'adjustmentServiceValue', 'basicData', 'cupsData', 'cupsIntervalsData', 'prices', 'currentTotal', 'manualTotal', 'dates', 'period', 'offer', 'optionSelected', 'pdfForm', 'powerPricePeriod', 'topOffers', 'currentSubtotal', 'offerSelected', 'filteredOffers', 'cupsInterval', 'selectedOffers', 'includeOffersInPdf', 'totalDays', 'includeCurrentInPdf', 'currentMonth', 'surplus'],
    emits: ['closeForm', 'loading'],
    data() {
        return {
            chartConsumes: null,
            consumesSeries: [],
            chartPeriods: null,
            periodsSeries: [],
            chartMonths: null,
            monthsSeries: [],
            totalPeriods: [],
            totalMonths: [],
        }
    },

    methods: {

        async savePDF() {
            const form = new FormData()

            const toNumber = (v) => {
                if (typeof v === 'number') return v;
                if (typeof v === 'string') return v === '' ? '' : parseFloat(v.replace(',', '.'));
                return v;
            };

            const powerOfferPrices = this.offer.prices.power.map(toNumber);
            const energyOfferPrices = (
                this.offer.prices?.history?.[this.currentMonth]
                    ? Object.values(this.offer.prices?.history?.[this.currentMonth]?.consume)
                    : this.offer.prices.energy
            ).map(toNumber);
            const payload = {
                basicData: this.basicData,
                pdfForm: this.pdfForm,
                fee: this.fee,
                cupsData: this.cupsData,
                cupsIntervalsData: this.cupsIntervalsData,
                prices: {
                    power: this.prices.power,
                    energy: this.prices.energy,
                    energyDiscount: this.prices.energyDiscount,
                    powerDiscount: this.prices.powerDiscount
                },
                offer: {
                    power: this.offer.prices.power,
                    energy: energyOfferPrices,
                    subTotal: {
                        extras: this.offer.subTotal.surplus.total ?? 0 + this.offer.subTotal.taxes.total ?? 0
                    },
                    fees: this.offer.fees
                },
                period: this.period,
                topOffers: this.topOffers,
                currentSubtotal: this.currentSubtotal,
                powerPricePeriod: this.powerPricePeriod,
                offerSelected: {
                    ...this.offerSelected,
                    prices: {
                        ...this.offerSelected.prices,
                        energy: energyOfferPrices,
                    },
                },
                filteredOffers: this.filteredOffers,
                currentTotal: this.currentTotal,
                manualTotal: this.manualTotal,
                offerFees: this.offer.fees,
                adjustmentServiceValue: this.adjustmentServiceValue,
                cupsInterval: this.cupsInterval,
                enterpriseId: this.basicData.enterprise._id,
                selectedOffers: this.selectedOffers,
                includeOffersInPdf: this.includeOffersInPdf,
                surplus: this.surplus,
                observaciones: this.pdfForm.observaciones || null,
                totalDays: this.totalDays,
                includeCurrentInPdf: this.includeCurrentInPdf
            }




            const payloadClean = { ...payload };

            console.log(payload, "Holaolallal")

            delete payloadClean.basicData
            delete payloadClean.filteredOffers



            console.log(payloadClean);
            if(this.optionSelected === 'manual' || this.totalDays !== this.dates.end.diff(this.dates.start, 'days')){
                payload['totalDays'] = this.totalDays;
            }

            form.append('payload', JSON.stringify(payload))

            if (this.pdfForm.enterpriseImg instanceof File) {
                form.append('enterpriseImg', this.pdfForm.enterpriseImg)
            }

            this.$emit('loading', true )

            try {
                const response = await axios.post(
                    '/api/generatePDF',
                    form,
                    {
                        responseType: 'blob'

                    }
                )

                const blob = new Blob([response.data], { type: 'application/pdf' })
                const url = URL.createObjectURL(blob)
                const link = document.createElement('a')
                link.href = url
                link.download = `comparativa_${this.pdfForm.name}_${this.pdfForm.order.CUPS}.pdf`
                document.body.appendChild(link)
                link.click()
                document.body.removeChild(link)
                URL.revokeObjectURL(url)
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al generar el PDF',
                    text: err.response?.data?.message || err.message
                })
            } finally {
                this.$emit('loading', false )
                this.$emit('closeForm')
            }
        }



    },

    created() {

        this.$emit('loading', true);
        setTimeout(() => {

            this.savePDF();
        }, "1500")
    }
}
</script>

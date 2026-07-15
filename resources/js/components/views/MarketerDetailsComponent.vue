<template>
    <div class="content-white">

        <!--Componente luz y gas-->
        <general-product-component v-if="type === 'electricity' || type === 'gas'" :basicData="basicData"/>

        <!--Componente dual-->
        <dual-product-component v-if="type === 'dual'" :basicData="basicData"/>

        <!--Componente de telefonía-->
        <telephony-product-component v-if="type === 'telephony'" :basicData="basicData"/>

        <!--Componente de alarmas-->
        <alarm-product-component v-if="type === 'alarm'" :basicData="basicData"/>

        <!--Componente de autoconsumo-->
        <self-supply-product-component v-if="type === 'selfSupply'" :basicData="basicData" />

    </div>
</template>

<script>

export default {
    name: "MarketerDetailsComponent",
    props: ["basicData"],
    data() {
        return {
            type: "",
        };
    },
    async created() {
        this.type = this.$route.query.type;
    },
};
</script>

<style>

</style>

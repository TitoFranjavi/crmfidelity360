<template>
    <div v-if="width < 810">

        <div class="arrow-border arrow-top my-10" data-position="left" />

        <!--Agentes-->
        <FilterDropdown :model-value="filtersApplied.agents" @update:model-value="onAgentsChange" labelSingular="agente" label="Agentes" :items="filters.agents" mobile />

        <!--Estados-->
        <FilterDropdown v-model="filtersApplied.statuses" label-singular="estado" label="Estados" item-value="code" :items="filters.statuses" mobile />

        <!--Fecha creación-->
        <FilterDates v-model="filtersApplied.creationDates" label="Fec. creación" mobile />

        <!--Fecha activación-->
        <FilterDates v-model="filtersApplied.activationDates" label="Fec. activación" mobile />

        <!--Fecha renovación-->
        <FilterDates v-model="filtersApplied.renewalDates" label="Fec. renovación" mobile />

        <!--Fecha baja-->
        <FilterDates v-model="filtersApplied.lowDates" label="Fec. baja" mobile />

        <!--Tipo de producto-->
        <FilterDropdown v-model="filtersApplied.productTypes" label-singular="tipo de producto" label="Tipos de producto" item-value="code" :items="filters.productTypes" mobile />

        <!--Tarifa-->
        <FilterDropdown v-model="filtersApplied.fees" label-singular="tarifa" label="Tarifas" :items="filters.fees" mobile />

        <!--Comercializadora-->
        <FilterDropdown v-model="filtersApplied.marketers" label-singular="comercializadora" label="Comercializadoras" :items="filters.marketers" mobile />

        <!--Productos comercializadora-->
        <FilterDropdown v-if="filtersApplied?.marketers?.length > 0" v-model="filtersApplied.products" label-singular="producto" label="Productos" :items="productsFilter" mobile />

        <!--Estado de liquidación-->
        <FilterDropdown v-model="filtersApplied.liquidationStatuses" label-singular="estado de liquidación" label="Estados de liquidación" item-value="code" :items="filters.liquidationStatuses" mobile />

        <!--Contratos de subdominios (solo para Zoco)-->
        <FilterDropdown v-if="basicData?.userLogged === '65cb57489c2c285441086a43'" v-model="filtersApplied.subdomains" single label="Contratos de subdominios" item-value="value" :items="subdomainOrdersFilter" mobile />

    </div>
    <div v-else class="filters-box d-flex column justify-between">

        <!--Header-->
        <div class="d-flex justify-between">
            <p class="text opacity-7 select-none" data-size="20" data-weight="600">Filtros</p>
            <i class="fas fa-x text my-auto pointer" data-size="20" data-weight="600" @click="$emit('update:seeFiltersMenu', false)" />
        </div>

        <!--Listado de filtros-->
        <div class="filters scroll-y h-90-min ml-20">
            <!--Agentes-->
            <FilterDropdown :model-value="filtersApplied.agents" @update:model-value="onAgentsChange" labelSingular="agente" label="Agentes" :items="filters.agents" />

            <!--Estados-->
            <FilterDropdown v-model="filtersApplied.statuses" label-singular="estado" label="Estados" item-value="code" :items="filters.statuses" />

            <!--Fecha creación-->
            <FilterDates v-model="filtersApplied.creationDates" label="Fec. creación" />

            <!--Fecha activación-->
            <FilterDates v-model="filtersApplied.activationDates" label="Fec. activación" />

            <!--Fecha renovación-->
            <FilterDates v-model="filtersApplied.renewalDates" label="Fec. renovación" />

            <!--Fecha baja-->
            <FilterDates v-model="filtersApplied.lowDates" label="Fec. baja" />

            <!--Tipo de producto-->
            <FilterDropdown v-model="filtersApplied.productTypes" label-singular="tipo de producto" label="Tipos de producto" item-value="code" :items="filters.productTypes" />

            <!--Tarifa-->
            <FilterDropdown v-model="filtersApplied.fees" label-singular="tarifa" label="Tarifas" :items="filters.fees" />

            <!--Comercializadora-->
            <FilterDropdown v-model="filtersApplied.marketers" label-singular="comercializadora" label="Comercializadoras" :items="filters.marketers" />

            <!--Productos comercializadora-->
            <FilterDropdown v-if="filtersApplied?.marketers?.length > 0" v-model="filtersApplied.products" label-singular="producto" label="Productos" :items="productsFilter" />

            <!--Estado de liquidación-->
            <FilterDropdown v-model="filtersApplied.liquidationStatuses" label-singular="estado de liquidación" label="Estados de liquidación" item-value="code" :items="filters.liquidationStatuses" />

            <!--Contratos de subdominios (solo para Zoco)-->
            <FilterDropdown v-if="basicData?.userLogged === '65cb57489c2c285441086a43'" v-model="filtersApplied.subdomains" single label="Contratos de subdominios" item-value="value" :items="subdomainOrdersFilter" />
        </div>

        <!--Botón borrar filtros-->
        <div class="d-flex justify-end">
            <div class="custom-button before-search" data-size="small" data-bg="rojo" data-mode="translucent" @click="$emit('resetFilters')">Borrar filtros</div>
        </div>

    </div>
</template>

<script>
import FilterDropdown from "@/components/items/FilterDropdown.vue";
import FilterDates from "@/components/items/FilterDates.vue";
import {useWindowSize} from "@vueuse/core";

export default{
    name: 'OrderFiltersComponent',
    components: {FilterDates, FilterDropdown},
    props: ['filters', 'filtersApplied','seeFiltersMenu', 'basicData', 'agentsWithSubordinates'],
    emits: ['resetFilters', 'update:filtersApplied' ,'update:seeFiltersMenu'],
    data() {
        return {
            subdomainOrdersFilter: [
                {name: 'Mostrar', value: true},
                {name: 'Ocultar', value: false}
            ]
        }
    },
    mounted() {
        setTimeout(() => {
            document.addEventListener('click', this.handleOutsideClick)
        })
    },
    beforeUnmount() {
        document.removeEventListener('click', this.handleOutsideClick)
    },
    methods: {
        handleOutsideClick(e) {
            if (!this.$el.contains(e.target)) {
                this.$emit('update:seeFiltersMenu', false)
            }
        },
        onAgentsChange(newValue) {
            if (newValue.length === 0) {
                this.filtersApplied.agents = []
                return
            }

            if (this.filtersApplied.agents.length === 0 && newValue.length > 1) {
                this.filtersApplied.agents = newValue
                return
            }

            // Detectar qué id cambió comparando newValue con appliedFilters.agents
            const added = newValue.filter(id => !this.filtersApplied.agents.includes(id))
            const removed = this.filtersApplied.agents.filter(id => !newValue.includes(id))

            if (added.length) {
                const group = this.agentsWithSubordinates[added[0]] || [added[0]]
                this.filtersApplied.agents = [...new Set([...this.filtersApplied.agents, ...group])].filter(id => this.filters.agents.some(a => a.id === id))
            } else if (removed.length) {
                const group = this.agentsWithSubordinates[removed[0]] || [removed[0]]
                this.filtersApplied.agents = this.filtersApplied.agents.filter(id => !group.includes(id))
            }
        }
    },
    computed: {
        productsFilter() {
            return this.filters.products.filter(product => this.filtersApplied.marketers.includes(product.marketerId))
        }
    },
    setup() {
        const { width } = useWindowSize()
        return { width }
    },
}
</script>

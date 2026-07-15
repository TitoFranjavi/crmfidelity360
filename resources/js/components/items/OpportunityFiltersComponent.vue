<template>
    <div v-if="width < 810">
        <div class="arrow-border arrow-top my-10" data-position="left" />
        <FilterDropdown v-model="filtersApplied.agents"       item-value="_id" label-singular="agente"           label="Agentes"           :items="agentItems"       mobile />
        <FilterDropdown v-model="filtersApplied.statuses"     item-value="_id" label-singular="estado"           label="Estados"           :items="statusItems"      mobile />
        <FilterDropdown v-model="filtersApplied.productTypes" item-value="_id" label-singular="tipo de producto" label="Tipos de producto" :items="productTypeItems" mobile />
        <FilterDropdown v-model="filtersApplied.marketers"    item-value="_id" label-singular="comercializadora" label="Comercializadoras" :items="marketerItems"    mobile />
        <FilterDropdown v-model="filtersApplied.tariffs"      item-value="_id" label-singular="tarifa"           label="Tarifas"           :items="tariffItems"      mobile />
        <FilterDropdown v-model="filtersApplied.products"     item-value="_id" label-singular="producto"         label="Productos"         :items="productItems"     mobile />
        <FilterDates    v-model="filtersApplied.dates"        label="Fec. creación"                                                                                  mobile />
        <FilterDropdown v-model="filtersApplied.view"         single label="Vista" item-value="value" :items="viewOptions"                                           mobile />
        <FilterDropdown v-if="canUseOriginFilter" v-model="filtersApplied.originTypes" item-value="_id" label-singular="origen" label="Origen" :items="originTypeItems" mobile />
    </div>
    <div v-else class="filters-box d-flex column justify-between">
        <div class="d-flex justify-between">
            <p class="text opacity-7 select-none" data-size="20" data-weight="600">Filtros</p>
            <i class="fas fa-x text my-auto pointer" data-size="20" @click="$emit('update:seeFiltersMenu', false)" />
        </div>
        <div class="filters scroll-y h-90-min ml-20">
            <FilterDropdown v-model="filtersApplied.agents"       item-value="_id" label-singular="agente"           label="Agentes"           :items="agentItems"       />
            <FilterDropdown v-model="filtersApplied.statuses"     item-value="_id" label-singular="estado"           label="Estados"           :items="statusItems"      />
            <FilterDropdown v-model="filtersApplied.productTypes" item-value="_id" label-singular="tipo de producto" label="Tipos de producto" :items="productTypeItems" />
            <FilterDropdown v-model="filtersApplied.marketers"    item-value="_id" label-singular="comercializadora" label="Comercializadoras" :items="marketerItems"    />
            <FilterDropdown v-model="filtersApplied.tariffs"      item-value="_id" label-singular="tarifa"           label="Tarifas"           :items="tariffItems"      />
            <FilterDropdown v-model="filtersApplied.products"     item-value="_id" label-singular="producto"         label="Productos"         :items="productItems"     />
            <FilterDates    v-model="filtersApplied.dates"        label="Fec. creación"                                                                                 />
            <FilterDropdown v-model="filtersApplied.view"         single label="Vista" item-value="value" :items="viewOptions"                                          />
            <FilterDropdown v-if="canUseOriginFilter" v-model="filtersApplied.originTypes" item-value="_id" label-singular="origen" label="Origen" :items="originTypeItems" />
        </div>
        <div class="d-flex justify-end">
            <div class="custom-button before-search" data-size="small" data-bg="rojo" data-mode="translucent" @click="$emit('resetFilters')">Borrar filtros</div>
        </div>
    </div>
</template>

<script>
import FilterDropdown from "@/components/items/FilterDropdown.vue";
import FilterDates    from "@/components/items/FilterDates.vue";
import { useWindowSize } from "@vueuse/core";

export default {
    name: 'OpportunityFiltersComponent',
    components: { FilterDropdown, FilterDates },
    props: ['filters', 'filtersApplied', 'seeFiltersMenu', 'basicData'],
    emits: ['resetFilters', 'update:filtersApplied', 'update:seeFiltersMenu'],
    data() {
        return {
            viewOptions: [
                { name: 'Todos',      value: 'all'      },
                { name: 'Agenda',     value: 'agenda'   },
                { name: 'Archivados', value: 'archived' },
            ]
        }
    },
    mounted() {
        setTimeout(() => document.addEventListener('click', this.handleOutsideClick))
    },
    beforeUnmount() {
        document.removeEventListener('click', this.handleOutsideClick)
    },
    methods: {
        handleOutsideClick(e) {
            if (!this.$el.contains(e.target)) this.$emit('update:seeFiltersMenu', false)
        }
    },
    computed: {
        canUseOriginFilter() {
            return this.basicData?.userSubdomain?._id === '65cb57489c2c285441086a43'
        },
        agentItems() {
            return (this.filters?.agents || []).map(a => ({ _id: String(a._id), name: a.name }))
        },
        statusItems() {
            return (this.filters?.statuses || []).map(s => ({ _id: s, name: s }))
        },
        marketerItems() {
            return (this.filters?.marketers || [])
                .filter(m => m !== null && m !== undefined)
                .map(m => ({ _id: m || '', name: m || 'Sin comercializadora' }))
        },
        productTypeItems() {
            const map = {
                'cl': 'Contrato de luz', 'cg': 'Contrato de gas', 'cd': 'Contrato dual',
                'ct': 'Telefonía',       'sa': 'Alarmas',         'a':  'Autoconsumo',
                'bc': 'Batería condensadores', 'ce': 'Coche eléctrico',
                'c':  'Contador',        'i':  'Iluminación',     'sp': 'Sin tipo',
            }
            return (this.filters?.productTypes || []).filter(Boolean).map(pt => ({ _id: pt, name: map[pt] || pt }))
        },
        tariffItems() {
            return (this.filters?.tariffs || []).filter(Boolean).map(t => ({ _id: t, name: t }))
        },
        productItems() {
            return (this.filters?.products || []).map(p => ({ _id: p, name: p === '' ? 'Sin producto' : p }))
        },
        originTypeItems() {
            return (this.filters?.originTypes || []).map(o => ({ _id: o.code, name: o.title }))
        }
    },
    setup() {
        const { width } = useWindowSize()
        return { width }
    }
}
</script>
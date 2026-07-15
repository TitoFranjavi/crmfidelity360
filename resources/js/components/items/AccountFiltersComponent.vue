<template>
    <div v-if="width < 810">
        <div class="arrow-border arrow-top my-10" data-position="left" />
        <FilterDropdown v-model="filtersApplied.agents" label-singular="agente" label="Agentes" item-value="_id" :items="filters.agents" mobile />
        <FilterDates v-model="filtersApplied.dates" label="Fec. creación" mobile />
        <FilterDropdown v-model="filtersApplied.view" single label="Vista" item-value="value" :items="viewOptions" mobile />
    </div>
    <div v-else class="filters-box d-flex column justify-between">
        <div class="d-flex justify-between">
            <p class="text opacity-7 select-none" data-size="20" data-weight="600">Filtros</p>
            <i class="fas fa-x text my-auto pointer" data-size="20" @click="$emit('update:seeFiltersMenu', false)" />
        </div>
        <div class="filters scroll-y h-90-min ml-20">
            <FilterDropdown v-model="filtersApplied.agents" label-singular="agente" label="Agentes" item-value="_id" :items="filters.agents" />
            <FilterDates v-model="filtersApplied.dates" label="Fec. creación" />
            <FilterDropdown v-model="filtersApplied.view" single label="Vista" item-value="value" :items="viewOptions" />
        </div>
        <div class="d-flex justify-end">
            <div class="custom-button before-search" data-size="small" data-bg="rojo" data-mode="translucent" @click="$emit('resetFilters')">Borrar filtros</div>
        </div>
    </div>
</template>

<script>
import FilterDropdown from "@/components/items/FilterDropdown.vue";
import FilterDates from "@/components/items/FilterDates.vue";
import { useWindowSize } from "@vueuse/core";

export default {
    name: 'AccountFiltersComponent',
    components: { FilterDropdown, FilterDates },
    props: ['filters', 'filtersApplied', 'seeFiltersMenu'],
    emits: ['resetFilters', 'update:filtersApplied', 'update:seeFiltersMenu'],
    data() {
        return {
            viewOptions: [
                { name: 'Todos',      value: 0 },
                { name: 'Agenda',     value: 1 },
                { name: 'Archivados', value: 2 },
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
            if (!this.$el.contains(e.target)) {
                this.$emit('update:seeFiltersMenu', false)
            }
        }
    },
    setup() {
        const { width } = useWindowSize()
        return { width }
    }
}
</script>
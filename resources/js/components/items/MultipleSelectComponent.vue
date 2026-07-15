<template>
    <div class="relative">
        <!--Título-->
        <p class="text">{{ title }}</p>


        <!--Cuadro select-->
        <div class="input-group d-flex justify-between pointer" @click="isOpened = !isOpened">

            <!--Opciones seleccionadas-->
            <div v-if="selectedOptions && selectedOptions.length > 0" class="d-flex f-wrap" data-gap="5">
                <div v-for="option in selectedOptions" @click.stop :key="option" class="mx-5 px-10 py-5 d-flex jusitfy-between align-center text round" data-round="15" data-gap="5" data-bg="principalOpacidad">
                    <p>{{ options.find(opt => opt.code === option).title }}</p>
                    <i class="far fa-x" data-size="8" @click="toggle(option)"></i>
                </div>
            </div>

            <!--Si no hay opciones seleccionadas-->
            <div v-else class="text opacity-6">No hay opciones seleccionadas</div>

            <i class="fas text pointer my-auto pointer" :class="isOpened ? ' fa-chevron-up' : ' fa-chevron-down'"></i>
        </div>


        <!--Desplegable de opciones-->
        <div v-if="isOpened" class="round p-10 mt-10" data-round="15" data-bg="blanco" data-border-color="principalOpacidad">

            <!--Buscador-->
            <div class="form-group my-5">
                <div class="input-group">
                    <div class="mb-2 d-flex items-center w-100">
                        <i class="fa-regular fa-magnifying-glass text my-auto mr-10"/>
                        <input type="text" v-model="searchOptionText" class="form-control" placeholder="Escribe para buscar..."/>
                    </div>
                </div>
            </div>

            <!--Seleccionable todos-->
            <div class="d-flex justify-content align-center pointer" @click="toggleAll">
                <!--check-->
                <div class="custom-checkbox my-auto">
                    <div v-bind:class="{ selected: this.selectedOptions && this.options && this.selectedOptions.length === this.options.length }"></div>
                </div>

                <p class="text my-auto ml-10">Todos</p>
            </div>

            <!--separador-->
            <div class="separator my-5"></div>

            <!--Cada uno de los productos-->
            <div class="h-200-px-max scroll-y">
                <div v-for="option in filteredOptions" @click="toggle(option.code)" class="d-flex justify-content align-center">
                    <!--check-->
                    <div class="custom-checkbox my-auto">
                        <div v-bind:class="{ selected: selectedOptions && selectedOptions.includes(option.code) }"></div>
                    </div>

                    <p class="text my-auto ml-10">{{ option.title }}</p>

                </div>
            </div>

        </div>
    </div>
</template>

<script>
export default {
    name: "MultipleSelectComponent",
    props: {
        title: String,
        options: Array,
        selectedOptions: Array,
        canEdit: {
            type: Boolean,
            default: false
        }
    },
    emits: ['update:selectedOptions'],
    data(){
        return {
            isOpened: false,
            searchOptionText: ''
        }
    },
    methods: {
        toggle(code) {

            if (!this.canEdit) return

            if (this.selectedOptions.includes(code)){
                let key = this.selectedOptions.indexOf(code)
                this.selectedOptions.splice(key, 1)
            }else {
                this.selectedOptions.push(code)
                //Reordenos el array por nombre
                this.selectedOptions.sort()
            }
        },
        toggleAll() {

            if (!this.canEdit) return

            if (this.internalValue.length === this.options.length)
                this.internalValue = []
            else
                this.internalValue = this.options.map(o => o.code)

        },
        normalizeText(text) {
            return text
                .toString()
                .toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .trim();
        }
    },
    computed: {
        internalValue: {
            get() {
                return this.selectedOptions
            },
            set(value) {
                this.$emit('update:selectedOptions', value)
            }
        },
        filteredOptions() {
            if (!this.searchOptionText) return this.options;

            const search = this.normalizeText(this.searchOptionText);

            return this.options.filter(option => this.normalizeText(option.title).includes(search))
        }
    }
}
</script>

<style scoped>

</style>

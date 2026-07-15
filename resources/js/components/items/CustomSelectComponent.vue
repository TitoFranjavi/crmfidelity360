<template>
    <div v-bind:class="{ wrong: errors[errorType], seeing: isSeeingCheckbox}" class="form-group checkbox-with-search pointer custom-select no-hover">
        <p v-if="necesary" class="my-auto"><label>{{ title }}</label> <span data-color="rojo">*</span></p>
        <label v-else class="text">{{ title }}</label>

        <!--Opción seleccionada-->
        <div class="input-group d-flex justify-between" v-on:click="isInputsDisabled ? '' : (isSeeingCheckbox = !isSeeingCheckbox)">
            <p class="text ellipsis" data-size="12">{{ selected ? selected : 'Selecciona una opción' }}</p>

            <i class="fas fa-chevron-down text pointer my-auto"></i>
        </div>

        <!--Cuando se abre-->
        <div class="bottom-part" v-bind:class="toTop ? 'to-top' : ''">

            <div class="content">

                <!--Input busqueda-->
                <input type="text" class="px-10 mx-auto w-90" v-model="searchText" placeholder="¿No lo encuentras?">

                <div class="separator my-10"></div>


                <!--Opciones-->
                <div class="options">

                    <!--Cada una de las opciones-->
                    <div v-for="option in filteredResults" class="text pointer" v-on:click="selectElement(option.title)">

                        <!--Contenido opción-->
                        <div class="d-flex justify-between">
                            <p class="text" data-size="10" :data-weight="selected === option.title ? '600' : '400'">{{ option.title }}</p>

                            <i v-if="option.isAdded" class="fas fa-x ml-10" data-color="rojo" data-size="10" v-on:click.prevent="delElement(option.title)"></i>
                        </div>

                        <!--separador-->
                        <div class="separator opacity-5 my-5"></div>
                    </div>

                    <div v-if="filteredResults.length === 0" class="text opacity-3 text-center" data-size="10">No hay ningún resultado</div>
                </div>

                <!--Añadir nuevo-->
                <div v-if="searchText" class="d-flex column justify-center">
                    <div class="separator my-10"></div>

                    <!--Botón añadir nuevo-->
                    <div class="custom-button text-center" data-size="small" data-bg="azul" data-mode="outline" v-on:click="addElement">Añade una nueva</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "CustomSelectComponent",
    props:['title','type', 'options', 'addedOptions', 'selected', 'errors', 'errorType','toTop', 'necesary', 'isInputsDisabled'],
    data(){
        return{
            searchText: '',
            isSeeingCheckbox: false
        }
    },
    methods:{
        addElement(){
            this.$emit('addElement', {type: this.type, value: this.searchText})

            this.isSeeingCheckbox = false;
        },
        delElement(option){
            this.$emit('delElement', {type: this.type, value: option})
        },
        selectElement(option){
            this.$emit('selectElement', {type: this.type, value: option})

            this.isSeeingCheckbox = false;
        }
    },
    computed:{
        filteredResults(){

            let filteredResults = []

            //A cada una de las opciones añadidas le pongo un atributo para saber que es añadida
            if (this.addedOptions){
                this.addedOptions.forEach((addedOption) => {
                    addedOption = {
                        title : addedOption,
                        isAdded : true
                    }

                    let AddedOptionSeach = addedOption.title.replace(' ', '').toLowerCase()

                    if (AddedOptionSeach.includes(this.searchText.replace(' ', '').toLowerCase())) filteredResults.push(addedOption)
                })
            }

            //Opciones por defecto
            this.options.forEach((option) => {

                option = {
                    title : option,
                    isAdded : false
                }

                let OptionSeach = option.title.replace(' ', '').toLowerCase()

                if (OptionSeach.includes(this.searchText.replace(' ', '').toLowerCase())) filteredResults.push(option)
            })

            filteredResults.sort((a,b) => a.title.localeCompare(b.title))

            return filteredResults
        }
    }
}
</script>

<style scoped>

</style>

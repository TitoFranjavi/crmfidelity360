<template>
    <div class="form-group">

        <div class="d-flex w-100">


            <!--Movil-->
            <div class="mobile-item w-100">

                <!--Título-->
                <div class="form-group" v-bind:class="{ wrong: field.errors}">
                    <div class="input-group my-10">
                        <input type="text" v-model="field.title" :disabled="isReadOnly" v-on:focus="delete field.errors" placeholder="Título">
                    </div>
                </div>


                <!--¿Tipo?-->
                <div class="input-group text my-10">

                    <select v-model="field.type" :disabled="isReadOnly" class="w-100">
                        <option value="text">Texto</option>
                        <option value="date">Fecha</option>
                        <option value="image">Imagen</option>
                    </select>
                </div>

                <!--Texto y date-->
                <div class="input-group my-10" v-if="field.type === 'text' || field.type === 'date'">
                    <input  :type="field.type" v-model="field.value" :disabled="isReadOnly" placeholder="Contenido">
                </div>

                <!--Imagen-->
                <div v-else class="w-100 d-flex">

                    <div class="custom-button mx-auto my-auto" data-size="small" data-bg="blanco" data-color="principal" v-if="!isReadOnly" v-on:click="openDialog">{{ field.value ? 'Cambiar' : 'Añadir' }}</div>


                    <input :id="'image' + fieldInd" type="file" style="display: none" v-on:change="pickupImage" multiple>

                </div>
            </div>

            <!--Pc-->
            <div class="desktop-item w-100" v-bind:class="{'d-flex': field.type === 'image'}">

                <!--Título y tipo-->
                <div class="my-auto d-flex w-100" data-gap="20"> <!--v-bind:class="{'d-flex': (field.type === 'text' || field.type === 'date'), 'w-70': field.type === 'image'}"-->
                    <!--Título-->
                    <div class="form-group "  v-bind:class="{ wrong: field.errors, 'w-70': (field.type === 'text' || field.type === 'date'), 'mb-10 w-100': field.type === 'image'}">
                        <div class="input-group ">
                            <input type="text" v-model="field.title" :disabled="isReadOnly" v-on:focus="delete field.errors" placeholder="Título">
                        </div>
                    </div>

                    <!--Fecha-->
                    <div class="form-group" v-if="field.type === 'date'">
                        <div class="input-group">
                            <input  type="date" v-model="field.value" :disabled="isReadOnly" v-on:focus="delete field.errors">
                        </div>
                    </div>



                    <!--¿Tipo?-->
                    <div class="form-group" v-bind:class="{'w-30 ml-20': (field.type === 'text' || field.type === 'date')}">
                        <div class="input-group text" v-on:change="delete field.errors">

                            <select v-model="field.type" :disabled="isReadOnly" class="w-100">
                                <option value="text">Texto</option>
                                <option value="date">Fecha</option>
                                <option value="image">Imagen o documento</option>
                            </select>

                        </div>
                    </div>

                    <div v-if="field.type === 'image'" class="d-flex">

                        <div class="custom-button my-auto" data-size="small" data-bg="blanco" data-color="principal" v-if="!isReadOnly" v-on:click="openDialog">{{ field.value ? 'Cambiar' : 'Adjuntar' }}</div>

                        <input :id="'image' + fieldInd" type="file" style="display: none" v-on:change="pickupImage" multiple>


                    </div>
                </div>

                <!--Contenido-->
                <div class="input-group" v-if="field.type === 'text'">
                    <textarea  type="text" v-model="field.value" :disabled="isReadOnly" v-on:focus="delete field.errors" placeholder="Contenido">
                    </textarea>
                </div>
            </div>

            <!--Boton eliminar campo personalizado-->
            <div class="ml-20 pointer d-flex justify-between" v-bind:class="{'mt-10 mb-auto': field.type === 'text', 'my-auto': field.type !== 'text'}">

                <a v-if="field.value && field.type !== 'text' && field.type !== 'date'" class="text mx-7 my-5" :href="urlImage" target="_blank"><i class="fas fa-eye"></i></a>

                <a v-if="field.value && field.type !== 'text' && field.type !== 'date'" class="text mx-7 my-5" :href="urlImage" :download="field.title"><i class="fas fa-download"></i></a>

                <div class="text mx-7 my-5" v-if="!isReadOnly" v-on:click="delCustomField(fieldInd)"><i class="fas fa-trash"></i></div>
            </div>
        </div>

        <span v-if="field.errors" class="error">{{ field.errors }}</span>


        <div class="separator my-10"></div>

    </div>
</template>

<script>
export default {
    name: "CustomFieldsComponent",
    props:['field', 'fieldInd', 'type', 'isReadOnly'],
    data(){
        return{
            urlImage: '',
            fileType: ''
        }
    },
    mounted() {
        this.resolveImage()
    },
    watch: {
        'field.value': {
            handler() {
                this.resolveImage();
            },
            immediate: true,
        }
    },
    methods:{
        delCustomField(fieldInd){
            this.$emit('delCustomField', fieldInd);
        },
        openDialog() {
            $('#image' + this.fieldInd).click();
        },
        pickupImage() {
            let input = $('#image' + this.fieldInd);

            if (input.prop('files')) {
                let file = input.prop('files')[0];

                this.field.fileType = file['type'].split("/")[0]

                //Asigno el valor antiguo ( si tenia ) para poder borrar la imagen despues
                if (this.field.value !== '')
                    this.field.imageToDelete = this.field.value;

                this.field.value = file;
                this.urlImage = URL.createObjectURL(this.field.value);
            }

            //Si hay más de uno cogido se mandan de vuelta y se crean nuevos
            if (input.prop('files').length > 1)
                this.$emit('addCustomFields', Array.from(input.prop('files')).slice(1))

        },
        resolveImage() {
            if (typeof this.field.value === 'string') {
                // Si es una cadena, imagen local en assets
                this.urlImage = '/assets/' +
                    (this.type === 'acc' ? 'account_images' :
                        this.type === 'opp' ? 'opportunity_images' : 'contact_images')
                    + '/' + this.field.value;
            } else if (this.field.value && this.urlImage === '') {
                // Si es un objeto tipo File y aún no se ha creado el blob
                this.urlImage = URL.createObjectURL(this.field.value);
            }
        },
    },
    computed:{
        async image(){
            return this.urlImage ? this.urlImage : ('/assets/' + (this.type === 'acc' ? 'account_images' : (this.type ===  'opp' ? 'opportunity_images' : 'contact_images')) +'/' + this.field.value);
        }
    }
}
</script>

<style scoped>

</style>

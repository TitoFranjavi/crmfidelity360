<template>
    <div class="form-group my-10" data-size="small">
        <div class="d-flex w-100">

            <div class="d-flex column w-100">

                <div class="d-flex">

                    <!--Archivo-->
                    <div class="w-15 text d-flex justify-center align-center">
                        <i class="fal" :class="!!doc.icon ? doc.icon : 'fa-file'" data-size="18"></i>
                    </div>

                    <!--Título doc.-->
                    <div v-bind:class="{ wrong: doc.errors?.title}" class="form-group w-75 my-auto">
                        <label>Título <i v-if="!!publicOrPrivate" class="fas" :class="{'fa-lock-open' : doc.privacyType === 'public', 'fa-lock' : doc.privacyType === 'private'}"></i></label>
                        <div class="input-group">
                            <input v-on:focus="doc.errors && delete doc.errors.title" data-size="10" :disabled="isReadOnly" v-model="doc.title" type="text">
                        </div>
                        <span v-if="doc.errors?.title" class="error">{{ doc.errors?.title }}</span>
                    </div>

                    <!--Previsualizar el archivo-->
                    <a class="d-flex justify-center align-center ml-20 mt-20" data-color="principal" :href="file" target="_blank" v-if="doc.value && (typeof doc.value === 'string')"> <i class="fas fa-eye"></i></a>
                    <!--Descargar el archivo-->
                    <a class="d-flex justify-center align-center ml-20 mt-20" data-color="principal" :href="file" :download="doc.title" target="_blank" v-if="doc.value && (typeof doc.value === 'string')"> <i class="fas fa-download"></i></a>
                    <!--Eliminar el archivo-->
                    <div class="d-flex justify-center align-center ml-20 mt-20" data-color="rojo" v-if="!isReadOnly" v-on:click="delDoc"> <i class="fas fa-trash"></i></div>
                </div>


                <!--Botones-->
                <div class="w-100 mt-10 d-flex">
                    <!--<a v-if="doc.value && (typeof doc.value === 'string')" class="custom-button" data-size="small" data-bg="azul" :href="file" :download="doc.title">Descargar archivo</a>-->
                    <div v-if="doc.fileValue" class="opacity-5 mx-20" data-size="10">¡Archivo adjuntado! - <span>{{ doc.defaultTitle }}</span></div>
                </div>
            </div>

            <!--Botón eliminar-->
            <!--<div class="my-auto mx-auto">
                <div class="custom-button ml-10 w-10 my-auto" data-size="small" data-bg="rojo" v-if="!isReadOnly" v-on:click="delDoc"><i class="fas fa-trash"></i></div>
            </div>-->
        </div>
    </div>
</template>

<script>
export default {
    name: "DocComponent",
    props:['doc', 'docInd', 'isReadOnly', 'publicOrPrivate', 'directory'],
    methods:{
        delDoc(){
            this.$emit('delDoc', this.docInd)
        }
    },
    computed:{
        file(){
            return `/assets/${!!this.directory ? this.directory : 'order_files'}/` + this.doc.value
        }
    }
}
</script>

<style scoped>

</style>

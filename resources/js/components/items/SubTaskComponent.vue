<template>
    <div  class="my-10">

        <div class="d-flex">
            <!--Asunto y descripción-->
            <div class="mr-auto w-100">

                    <!--Movil-->
                    <div class="mobile-item">
                        <!--Asunto-->
                        <div class="w-100">
                            <div  v-bind:class="{ wrong: subTask.errors}" class=" mb-10">
                                <div class="form-group">
                                    <input type="text" class="input-group text ellipsis " placeholder="Asunto" v-on:focus="delete subTask.errors" v-on:focusin="isInputDisabled = false" v-on:focusout="isInputDisabled = true" v-model="subTask.subject" :disabled="isReadOnly">
                                </div>

                                <!--¿Completado?-->
                                <div class="form-group text">

                                    <select class="input-group w-100" v-model="subTask.isCompleted" :disabled="isReadOnly">
                                        <option :value="true">Completado</option>
                                        <option :value="false">Sin completar</option>
                                    </select>

                                </div>
                            </div>
                            <span v-if="subTask.errors" class="error">{{ subTask.errors }}</span>
                        </div>
                    </div>

                    <!--Pc-->
                    <div class="desktop-item">
                        <!--Asunto-->
                        <div class="w-100">
                            <div  v-bind:class="{ wrong: subTask.errors}" class="d-flex mb-10">

                                <div class="form-group w-70">
                                    <input type="text" class="input-group text ellipsis" placeholder="Asunto" v-on:focus="delete subTask.errors" v-on:focusin="isInputDisabled = false" v-on:focusout="isInputDisabled = true" v-model="subTask.subject" :disabled="isReadOnly">
                                </div>

                                <!--¿Completado?-->
                                <div class="form-group text w-30 ml-20">

                                    <select v-model="subTask.isCompleted" :disabled="isReadOnly" class="input-group w-100">
                                        <option :value="true">Completado</option>
                                        <option :value="false">Sin completar</option>
                                    </select>

                                </div>

                            </div>
                            <span v-if="subTask.errors" class="error">{{ subTask.errors }}</span>
                        </div>
                    </div>



                <!--Descripción-->
                <div class="form-group">
                    <div class="input-group">
                        <textarea class="text ellipsis opacity-6" placeholder="Descripción" v-on:focusin="isInputDisabled = false" v-on:focusout="isInputDisabled = true" v-model="subTask.desc" :disabled="isReadOnly"></textarea>
                    </div>
                </div>
            </div>

            <!--Boton eliminar subtarea-->
            <div class="my-auto ml-40 pointer">
                <div class="text" v-on:click="deleteSubTask(subTaskInd)" v-if="!isReadOnly"><i class="fas fa-trash"></i></div>
            </div>
        </div>


        <div class="separator my-10"></div>
    </div>
</template>

<script>
export default {
    name: "SubTaskComponent",
    props:['subTask', 'subTaskInd', 'isReadOnly'],
    data(){
        return{
            isInputDisabled: true
        }
    },
    methods:{
        deleteSubTask(subTaskInd){
            this.$emit('deleteSubTask', subTaskInd)
        }
    }

}
</script>

<style scoped>

</style>

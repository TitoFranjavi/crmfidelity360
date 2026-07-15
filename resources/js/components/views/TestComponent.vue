<script>
    export default{
        data(){
            return{
                car:{
                    licensePlate: '',
                    kms: '',
                    status: '',
                    crashType: '',
                    coments: '',
                    missingsPieces: [],
                    address: '',
                    owner:{
                        name: '',
                        surname: '',
                        phone: '',
                        email: ''
                    }
                },
                kmsTypes:['Bajo (menos de 50.000kms)', 'Medio ( entre de 50.000kms y 100.000kms)', 'Alto (más de 100.000kms)'],
                statuses: [
                    {
                        code: 's',
                        title: 'Siniestrado o golpeado'
                    },
                    {
                        code: 'a',
                        title: 'Averiado'
                    },
                    {
                        code: 'pd',
                        title: 'Parcialmente desmontado'
                    },
                    {
                        code: 'pe',
                        title: 'Perfecto estado'
                    }
                    ],
                crashTypes: ['Golpe delantero', 'Golpe trasero', 'Golpe lateral', 'Vuelco', 'Siniestro total', 'Inundado', 'Calcinado'],
                missingPieces: ['Catalizador', 'Bateria', 'Neumáticos', 'Motor', 'Otros'],
                errors: []
            }
        },
        methods:{
            toggleSelectPiece(piece){

                let indexPiece = this.car.missingsPieces.indexOf(piece);

                if (indexPiece === -1)
                    this.car.missingsPieces.push(piece)
                else
                    this.car.missingsPieces.splice(indexPiece, 1);
            }
        }
    }
</script>

<template>
    <div class="content-white">

        <pre>{{ car }}</pre>


        <div class="form w-50 mx-auto">

            <!--matricula-->
            <div class="form-group ">
                <p class="my-auto"><label>Matricula</label> <span data-color="rojo">*</span></p>
                <div class="input-group">
                    <input data-size="12" v-model="car.licensePlate" type="text">
                </div>
            </div>

            <!--kilometros-->
            <div class="form-group">
                <p class="my-auto"><label>Kilometros</label> <span data-color="rojo">*</span></p>
                <div class="input-group">
                    <select name="" id="" v-model="car.kms">
                        <option value="">Selecciona los kilometros que tiene tu coche</option>
                        <option v-for="kms in kmsTypes" :value="kms" >{{ kms }}</option>
                    </select>
                </div>
            </div>

            <!--Estado-->
            <div class="form-group">
                <p class="my-auto"><label>Estado de tu vehículo</label> <span data-color="rojo">*</span></p>
                <div class="input-group">
                    <select  v-model="car.status">
                        <option value="">Selecciona el estado</option>
                        <option v-for="status in statuses" :value="status.code" >{{ status.title }}</option>
                    </select>
                </div>
            </div>


            <!--Tipo de golpe-->
            <div class="form-group" v-if="car.status === 's'">
                <p class="my-auto"><label>Tipo de golpe</label> <span data-color="rojo">*</span></p>
                <div class="input-group">
                    <select v-model="car.crashType">
                        <option value="">Selecciona el tipo de golpe</option>
                        <option v-for="crashType in crashTypes" :value="crashType">{{ crashType }}</option>
                    </select>
                </div>
            </div>


            <!--Chexbox-->
            <div v-for="missingPiece in missingPieces" class="d-flex ">
                <div class="custom-checkbox mr-10" v-on:click="toggleSelectPiece(missingPiece)" v-bind:class="{ 'selected': this.car.missingsPieces.includes(missingPiece) }"></div>
                <p class="text">{{ missingPiece }}</p>
            </div>


        </div>
    </div>
</template>

<style scoped>

</style>

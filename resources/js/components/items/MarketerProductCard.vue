<template>
    <div>
        <div>
            <div class="d-flex justify-between">


                <div class="d-flex" >
                    <i v-if="(!isReadOnly && isEditing) && productInd > 0" class="fa-solid fa-arrow-up m-5 pointer" v-on:click="changePlace(-1)"></i>
                    <i v-if="(!isReadOnly && isEditing) && productInd < products.length - 1" class="fa-solid fa-arrow-down m-5 pointer" v-on:click="changePlace(1)"></i>
                </div>

                <div class="d-flex">

                    <!--Tarifas-->
                    <div class="relPos my-auto z-1">
                        <div class="custom-button my-auto mx-5" data-size="small" data-bg="principal" v-on:click="showFees = !showFees">Tarifas</div>

                        <div v-if="showFees" class="absPos left w-max-content round p-10 z1" data-round="10" data-bg="blanco">

                            <div class="d-flex" v-for="fee in fees">
                                <div class="custom-checkbox my-auto" v-on:click="toggleSelectFee(fee)">
                                    <div v-bind:class="{selected: (productNow.fees && productNow.fees.includes(fee.name))}"></div>
                                </div>

                                <div class="pointer ml-10">{{ fee.name }}</div>
                            </div>

                            <div v-if="fees.length === 0">
                                <p class="opacity-5" data-size="10">¡No hay tarifas creadas todavía!</p>
                            </div>

                        </div>
                    </div>


                    <div v-if="!productNow.consumptionBreakdown && !isInputsDisabled" class="d-flex">

                        <div class="custom-button my-auto mx-5" data-size="small" data-bg="azul" v-on:click="addConsumptionBreakdown">Añadir desglose de comisiones</div>

                        <div v-if="!isInputsDisabled" class="custom-button my-auto mx-5" data-size="small" data-bg="rojo" v-on:click="deleteProduct"><i class="fa-solid fa-trash"></i></div>
                    </div>


                    <div v-else-if="!!productNow.consumptionBreakdown" class="d-flex">

                        <div class="custom-button my-auto mx-5" data-size="small" data-bg="azul" v-on:click="isOpened = !isOpened">{{ isOpened ? 'Ocultar' : 'Mostrar' }} intervalos</div>

                        <div v-if="!isInputsDisabled" class="custom-button my-auto mx-5" data-size="small" data-bg="amarillo" v-on:click="addConsumptionBreakdown(productNow)">Añadir intervalo</div>

                        <div v-if="!isInputsDisabled" class="custom-button my-auto mx-5" data-size="small" data-bg="rojo" v-on:click="deleteProduct"><i class="fa-solid fa-trash"></i></div>
                    </div>

                </div>


            </div>

            <div class="d-grid" data-column="2" data-gap="20">

                <!--Nombre producto-->
                <div v-bind:class="{ wrong: productNow.errors.name}" class="form-group" :id="productNow.name.replace(/\s+/g, '-')">

                    <div class="d-flex justify-between mb-10">
                        <label class="my-auto">Nombre de producto</label>
                    </div>

                    <div class="input-group">
                        <input v-if="!isReadOnly" v-on:focus="delete productNow['errors']['name']" data-size="12" v-model="productNow.name" :disabled="!isEditing" type="input">
                        <div v-else class="text opacity-5"  data-size="12">{{ productNow.name }}</div>
                    </div>
                    <span v-if="productNow.errors.name" class="error">{{ productNow.errors.name }}</span>
                </div>


                <!--Tipo de comisión-->
                <div  class="form-group" :id="productNow.name.replace(/\s+/g, '-')">

                    <div class="d-flex justify-between mb-10">
                        <label class="my-auto">Tipo de comisión</label>
                    </div>

                    <div class="input-group">
                        <select v-if="!isReadOnly" data-size="12" v-model="productNow.commissionType" :disabled="!isEditing" >
                            <option v-for="commissionType in commissionTypes" :value="commissionType.code">{{ commissionType.name }}</option>
                        </select>

                        <div v-else class="text opacity-5"  data-size="12">{{ productNow.name }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!--Precios por potencia-->
        <div class="opacity-5 my-10" data-size="15">Precios</div>

        <div class="d-flex" data-gap="20">

            <!--Comisión Asercord-->
            <div v-for="periodInd in 6" v-bind:class="{ wrong: productNow.errors['pricePeriod' + periodInd]}" class="form-group">
                <label>P{{ periodInd }}</label>
                <div class="input-group">
                    <input v-if="!isReadOnly" v-on:focus="delete productNow['errors']['pricePeriod' + periodInd]" data-size="12" v-model="productNow.prices['period' + periodInd]" :disabled="!isEditing" type="input">
                    <div v-else class="text opacity-5"  data-size="12">{{ productNow.prices['period' + periodInd] }}</div>
                </div>
                <span v-if="productNow.errors['pricePeriod' + periodInd]" class="error">{{ productNow.errors['pricePeriod' + periodInd] }}</span>
            </div>

        </div>

        <!--Comisiones-->
        <div class="opacity-5 my-10" data-size="15" v-if="!productNow.consumptionBreakdown || (productNow.consumptionBreakdown && isOpened)">Comisiones</div>

        <!--cosumo básico-->
        <div class="d-flex" data-gap="20" v-if="!productNow.consumptionBreakdown">

            <!--Comisión Asercord-->
            <div v-if="!!productNow.comAs || productNow.comAs === ''" v-bind:class="{ wrong: productNow.errors.comAs}" class="form-group">
                <label>Asercord</label>
                <div class="input-group">
                    <input v-if="!isReadOnly" v-on:focus="delete productNow['errors']['comAs']" data-size="12" v-model="productNow.comAs" v-on:blur="calculateUserComissions(productNow)" :disabled="!isEditing" type="input">
                    <div v-else class="text opacity-5"  data-size="12">{{ productNow.comAs }}</div>
                </div>
                <span v-if="productNow.errors.comAs" class="error">{{ productNow.errors.comAs }}</span>
            </div>


            <!--VIP-->
            <div v-if="!!productNow.com3 || productNow.com3 === ''" v-bind:class="{ wrong: productNow.errors.com3 }" class="form-group">
                <label>VIP</label>
                <div class="input-group">
                    <input v-if="!isReadOnly" v-on:focus="delete productNow['errors']['com3']" data-size="12" v-model="productNow.com3" :disabled="!isEditing" type="input">
                    <div v-else class="text opacity-5"  data-size="12">{{ productNow.com3 }}</div>
                </div>
                <span v-if="productNow.errors.com3" class="error">{{ productNow.errors.com3 }}</span>
            </div>


            <!--Senior-->
            <div v-if="!!productNow.com2 || productNow.com2 === ''" v-bind:class="{ wrong: productNow.errors.com2}" class="form-group">
                <label>Senior</label>
                <div class="input-group">
                    <input v-if="!isReadOnly" v-on:focus="delete productNow['errors']['com2']" data-size="12" v-model="productNow.com2" :disabled="!isEditing" type="input">
                    <div v-else class="text opacity-5"  data-size="12">{{ productNow.com2 }}</div>
                </div>
                <span v-if="productNow.errors.com2" class="error">{{ productNow.errors.com2 }}</span>
            </div>


            <!--Junior-->
            <div v-if="!!productNow.com1 || productNow.com1 === ''" v-bind:class="{ wrong: productNow.errors.com1}" class="form-group">
                <label>Junior</label>
                <div class="input-group">
                    <input v-if="!isReadOnly" v-on:focus="delete productNow['errors']['com1']" data-size="12" v-model="productNow.com1" :disabled="!isEditing" type="input">
                    <div v-else class="text opacity-5"  data-size="12">{{ productNow.com1 }}</div>
                </div>
                <span v-if="productNow.errors.com1" class="error">{{ productNow.errors.com1 }}</span>
            </div>
        </div>


        <!--desglose por tramos-->
        <div v-if="productNow.consumptionBreakdown && isOpened" v-for="interval in productNow.consumptionBreakdown">

            <div class="d-flex" data-gap="10">

                <!--Potencia 1-->
                <div v-if="productNow.commissionType === 'p' || productNow.commissionType === 'cyp'" v-bind:class="{ wrong: interval.errors.pot1}" class="form-group">
                    <label>Pot. 1</label>
                    <div class="input-group">
                        <input v-if="!isReadOnly" v-on:focus="delete interval['errors']['pot1']" data-size="12" v-model="interval.pot1" placeholder="Nº" :disabled="!isEditing" type="input">
                        <div v-else class="text opacity-5"  data-size="12">{{ interval.pot1 }}</div>
                    </div>
                    <span v-if="interval.errors.pot1" class="error">{{ interval.errors.pot1 }}</span>
                </div>

                <!--Potencia 2-->
                <div v-if="productNow.commissionType === 'p' || productNow.commissionType === 'cyp'" v-bind:class="{ wrong: interval.errors.pot2}" class="form-group">
                    <label>Pot. 2</label>
                    <div class="input-group">
                        <input v-if="!isReadOnly" v-on:focus="delete interval['errors']['pot2']" data-size="12" v-model="interval.pot2" placeholder="Nº o >" :disabled="!isEditing" type="input">
                        <div v-else class="text opacity-5"  data-size="12">{{ interval.pot2 }}</div>
                    </div>
                    <span v-if="interval.errors.pot2" class="error">{{ interval.errors.pot2 }}</span>
                </div>

                <!--Consumo 1-->
                <div v-if="productNow.commissionType === 'c' || productNow.commissionType === 'cyp'" v-bind:class="{ wrong: interval.errors.con1}" class="form-group">
                    <label>Con. 1</label>
                    <div class="input-group">
                        <input v-if="!isReadOnly" v-on:focus="delete interval['errors']['con1']" data-size="12" v-model="interval.con1" placeholder="Nº" :disabled="!isEditing" type="input">
                        <div v-else class="text opacity-5"  data-size="12">{{ interval.con1 }}</div>
                    </div>
                    <span v-if="interval.errors.con1" class="error">{{ interval.errors.con1 }}</span>
                </div>

                <!--Consumo 2-->
                <div v-if="productNow.commissionType === 'c' || productNow.commissionType === 'cyp'" v-bind:class="{ wrong: interval.errors.con2}" class="form-group">
                    <label>Con. 2</label>
                    <div class="input-group">
                        <input v-if="!isReadOnly" v-on:focus="delete interval['errors']['con2']" data-size="12" v-model="interval.con2" placeholder="Nº o >" :disabled="!isEditing" type="input">
                        <div v-else class="text opacity-5"  data-size="12">{{ interval.con2 }}</div>
                    </div>
                    <span v-if="interval.errors.con2" class="error">{{ interval.errors.con2 }}</span>
                </div>

                <!--Comisión Asercord-->
                <div v-bind:class="{ wrong: interval.errors.comAs}" class="form-group">
                    <label>Asercord</label>
                    <div class="input-group">
                        <input v-if="!isReadOnly" v-on:focus="delete interval['errors']['comAs']" data-size="12" v-model="interval.comAs" v-on:blur="calculateUserComissions(interval)" :disabled="!isEditing" type="input">
                        <div v-else class="text opacity-5"  data-size="12">{{ interval.comAs }}</div>
                    </div>
                    <span v-if="interval.errors.comAs" class="error">{{ interval.errors.comAs }}</span>
                </div>

                <!--Comisión 3-->
                <div v-bind:class="{ wrong: interval.errors.com3}" class="form-group">
                    <label>VIP</label>
                    <div class="input-group">
                        <input v-if="!isReadOnly" v-on:focus="delete interval['errors']['com3']" data-size="12" v-model="interval.com3" :disabled="!isEditing" type="input">
                        <div v-else class="text opacity-5"  data-size="12">{{ interval.com3 }}</div>
                    </div>
                    <span v-if="interval.errors.com3" class="error">{{ interval.errors.com3 }}</span>
                </div>



                <!--Comisión 2-->
                <div v-bind:class="{ wrong: interval.errors.com2}" class="form-group">
                    <label>Senior</label>
                    <div class="input-group">
                        <input v-if="!isReadOnly" v-on:focus="delete interval['errors']['com2']" data-size="12" v-model="interval.com2" :disabled="!isEditing" type="input">
                        <div v-else class="text opacity-5"  data-size="12">{{ interval.com2 }}</div>
                    </div>
                    <span v-if="interval.errors.com2" class="error">{{ interval.errors.com2 }}</span>
                </div>


                <!--Comisión 1-->
                <div v-bind:class="{ wrong: interval.errors.com1}" class="form-group">
                    <label>Junior</label>
                    <div class="input-group">
                        <input v-if="!isReadOnly" v-on:focus="delete interval['errors']['com1']" data-size="12" v-model="interval.com1" :disabled="!isEditing" type="input">
                        <div v-else class="text opacity-5"  data-size="12">{{ interval.com1 }}</div>
                    </div>
                    <span v-if="interval.errors.com1" class="error">{{ interval.errors.com1 }}</span>
                </div>


                <!--Basura-->
                <div class="mt-auto mb-16">
                    <i class="fas fa-trash pointer" v-on:click="deleteInterval(interval)"></i>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "MarketerProductCard",
        props:['productNow', 'productInd', 'products', 'isInputsDisabled', 'isReadOnly', 'isEditing', 'fees'],
        data(){
            return {
                isOpened: false,
                showFees: false,
                commissionTypes: [
                    {
                        'name': 'Consumo',
                        'code': 'c'
                    },
                    {
                        'name': 'Potencia',
                        'code': 'p'
                    },
                    {
                        'name': 'Consumo y potencia',
                        'code': 'cyp'
                    },
                ]
            }
        },
        created() {
            if (!this.productNow.errors)
                this.productNow.errors = {}
        },
        mounted() {
            if (!this.productNow.commissionType)
                this.productNow.commissionType = 'c'


        },
        methods: {
            addConsumptionBreakdown() {
                this.$emit('addConsumptionBreakdown', this.productNow)

                this.isOpened = true
            },
            calculateUserComissions(productOrInterval) {

                if (!!productOrInterval.comAs && !isNaN(productOrInterval.comAs)) {
                    productOrInterval.com3 = Math.floor(productOrInterval.comAs - (productOrInterval.comAs * 0.1));  // Resta el 10% y redondea hacia abajo
                    productOrInterval.com2 = Math.floor(productOrInterval.comAs - (productOrInterval.comAs * 0.2));  // Resta el 20% y redondea hacia abajo
                    productOrInterval.com1 = Math.floor(productOrInterval.comAs - (productOrInterval.comAs * 0.4));  // Resta el 40% y redondea hacia abajo
                }

            },
            deleteInterval(interval) {
                let index = this.productNow.consumptionBreakdown.indexOf(interval);
                if (index !== -1) {
                    // Elimina el intervalo del array usando splice
                    this.productNow.consumptionBreakdown.splice(index, 1);
                }
            },
            toggleSelectFee(fee) {

                if (this.isReadOnly || !this.isEditing) return false

                if (!this.productNow.fees)
                    this.productNow.fees = []

                if (this.productNow.fees.includes(fee.name)) {
                    // Se elimina del array
                    this.productNow.fees = this.productNow.fees.filter(item => item !== fee.name);
                } else {
                    // Se mete en el array
                    this.productNow.fees.push(fee.name);
                }
            },
            changePlace(value){

                let product = JSON.parse(JSON.stringify(this.productNow));

                //borro del indice donde esta
                this.products.splice(this.productInd, 1);

                //tengo que coger del array el producto y moverlo uno hacia el principio si value es -1 y uno hacia el final si es 1
                switch (value) {
                    case 1:
                        this.products.splice((this.productInd + 1), 0, product)
                        break;

                    case -1:
                        this.products.splice((this.productInd - 1), 0, product)
                        break;
                }
            },
            deleteProduct(){
                this.products.splice(this.productInd,1)
            }
        }
    }
</script>

<style scoped>

</style>

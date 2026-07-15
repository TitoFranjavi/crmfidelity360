<template>
    <div class="floating-box z-100">

        <div class="d-flex column register-pos w-70 h-70 scroll-y">

            <!-- Header -->
            <div class="mt-20 mx-20 d-flex align-center justify-between">
                <div class="text" data-size="30" data-weight="700">Nuevo producto de {{ dataMarketer.name }}</div>
                <div class="mx-30"><img :src="'/assets/marketers_logo/'+dataMarketer.logo" :alt="'Logo '+dataMarketer.name" class="h-40-px-max w-80-px-max contain-img"></div>
            </div>

            <div class="separator"></div>

            <!-- Formulario -->
            <form class="form mx-30" action="" @keydown.enter.prevent>

                <div class="text d-flex align-center" data-size="18" v-if="typeProduct === 'dual'">
                    <i class="fa-regular fa-lightbulb mr-10"></i> Luz
                </div>


                <div class="d-flex justify-between align-center">
                    <!--Nombre-->
                    <div class="form-group w-80" :class="{wrong : errors.nameError}">
                        <p>Nombre</p>
                        <div class="input-group">
                            <input type="text" name="name" placeholder="Nombre Producto" v-model.trim="newProduct.name">
                        </div>
                        <span v-if="errors.nameError === true" data-color="rojo">Este campo es necesario</span>
                    </div>
                    <div class="w-20 text text-center">
                        <div v-if="typeProduct === 'electricity' || typeProduct === 'dual'"><i class="fas fa-bolt"></i></div>
                        <div v-else-if="typeProduct === 'gas'"><i class="fas fa-fire-flame-simple"></i></div>
                        <div v-else-if="typeProduct === 'telephony'"><i class="fas fa-phone"></i></div>
                        <div v-else-if="typeProduct === 'alarm'"><i class="fas fa-bell-on"></i></div>
                        <div v-else-if="typeProduct === 'selfSupply'"><i class="fas fa-solar-panel"></i></div>
                    </div>
                </div>

                <!--Tarifas-->
                <div class="form-group" v-if="typeProduct !== 'alarm'" :class="{wrong : errors.feeError}">
                    <p>Tarifa</p>
                    <div class="input-group f-wrap d-flex justify-around" data-gap="20">
                        <template v-for="(fee, i) in feesList">
                            <div class="d-flex justify-center">
                                <label class="mr-10" :for="fee.name" >{{ fee.name }}</label>
                                <input type="checkbox" :id="fee.name" :value="fee.id" v-model="checkFees[i]" @change="onChangeRadioFee(i)">
                            </div>
                        </template>
                    </div>
                    <span v-if="errors.feeError === true" data-color="rojo">Es necesaria al menos una tarifa</span>
                </div>


                <!--PARTE GAS DUAL-->
                <div v-if="typeProduct === 'dual'">

                    <div class="text d-flex align-center mt-30" data-size="18">
                        <i class="fa-regular fa-fire-flame-simple mr-10"></i> Gas
                    </div>

                    <!--Nombre-->
                    <div class="d-flex justify-between align-center">

                        <!--Nombre-->
                        <div class="form-group w-80" :class="{wrong : errors.nameSecondaryError}">
                            <p>Nombre</p>
                            <div class="input-group">
                                <input type="text" name="name" placeholder="Nombre Producto" v-model="newProduct.nameSecondary">
                            </div>
                            <span v-if="errors.nameSecondaryError === true" data-color="rojo">Este campo es necesario</span>
                        </div>
                        <div class="w-20 text text-center">
                            <div><i class="fas fa-fire-flame-simple"></i></div>
                        </div>
                    </div>

                    <!--Tarifas-->
                    <div class="form-group" :class="{wrong : errors.feeError}">
                        <p>Tarifa</p>
                        <div class="input-group f-wrap d-flex justify-around" data-gap="20">
                            <template v-for="(fee, i) in feesListSecondary">
                                <div class="d-flex justify-center">
                                    <label class="mr-10" :for="fee.name" >{{ fee.name }}</label>
                                    <input type="checkbox" :id="fee.name" :value="fee.id" v-model="checkFeesSecondary[i]" @change="onChangeRadioFee(i, true)">
                                </div>
                            </template>
                        </div>
                        <span v-if="errors.feeError === true" data-color="rojo">Es necesaria al menos una tarifa</span>
                    </div>

                </div>

                <div class="form-group mt-30" :class="{wrong : errors.typeErr}">
                    <p>Tipo</p>

                    <div class="input-group justify-around">
                        <div>
                            <label class="mr-10" for="r">Residencial</label>
                            <input type="checkbox" id="r" v-model="newFee.type.residencial">
                        </div>
                        <div>
                            <label class="mr-10" for="p">PYME</label>
                            <input type="checkbox" id="p" v-model="newFee.type.pyme">
                        </div>
                    </div>

                    <span v-if="errors.typeErr === true" data-color="rojo">Es necesaria al menos un tipo</span>
                </div>

            </form>

            <!-- Botones -->
            <div class="mt-auto">
                <div class="separator"></div>
                <div class="d-flex justify-end" data-gap="20">
                    <div class="custom-button" data-size="big" data-bg="rojo" v-on:click="closeWindow()">Cancelar</div>
                    <div class="custom-button" data-size="big" v-on:click="addProduct()">Crear</div>
                </div>
            </div>

        </div>
    </div>
</template>

<script>

export default{
    name : 'NewProduct',
    props : ['dataMarketer', 'typeProduct', 'visibility'],
    data(){
        return{
            newProduct : {
                _id : "",
                name: '',
                //nameSecondary: '',
                fees : [],
                //feesSecondary : [],
                errors : []
            },
            dualProduct: null,
            feesList : [],
            feesListSecondary : [],
            type : '',
            checkFees : [],
            checkFeesSecondary : [],
            newFee : {
                id : null,
                prices : {},
                type: {
                    'pyme': true,
                    'residencial': true
                },
                priceType: 'fixed',
                surplus: 'none',
                commissions: [],
                commissionType: 'f',
            },
            commissionRow: {
                con1: null,
                con2: null,
                pot1: null,
                pot2: null,
                multiply: false,
                base: null,
                breakdown: []
            },
            errors : {
                totalErrors : 0,
                nameError : false,
                nameSecondaryError : false,
                typeErr: false,
                feeError : false,
                typeComError : false,
            }
        }
    },
    created(){
        if (this.typeProduct === 'dual') {
            this.newProduct.nameSecondary = ''
            this.newProduct.feesSecondary = []
        }
    },
    mounted(){
        this.selectType();
        this.listFees();
    },
    methods: {
        selectType(){
            this.type = this.typeProduct
        },
        closeWindow(){
            this.$emit('closeWindow', false);
        },
        listFees(){
            if (this.typeProduct === 'alarm') return

            if (this.typeProduct !== 'dual'){

                if (this.typeProduct === 'electricity' || this.typeProduct === 'gas'){
                    for (let i = 0; i < this.dataMarketer.fees[this.type].length; i++) {
                        this.feesList.push(this.dataMarketer.fees[this.type][i])
                    }
                }else
                    this.feesList = this.$storage.FEES[this.type].map(fee => ({name:fee}));

            }else {

                //Tarifas luz
                this.feesList = this.$storage.FEES.electricity.map(fee => ({name:fee}));

                //Tarifas gas
                this.feesListSecondary = this.$storage.FEES.gas.map(fee => ({name:fee}));
            }
        },
        actionLink(route) {
            this.$router.push(route)
        },
        async addProduct(){
            let stop = false;
            await this.checkInputs();

            if(this.errors.totalErrors === 0){

                if (this.typeProduct === 'dual') {
                    this.createDualProduct();
                    stop = await this.checkIfDualProductExists();

                    if (stop === true) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Ya existe un producto dual con estos nombres'
                        });
                        return;
                    }

                    this.newProduct = this.dualProduct;

                }
                else {
                    let exists = await this.checkIfProductExists()

                    if (exists === true) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Ya existe un producto con ese nombre'
                        });
                        return;
                    }

                    if (this.typeProduct !== 'alarm')
                        this.createFees();
                    else{
                        delete this.newProduct.fees;
                        this.newProduct.commissions = [this.commissionRow];
                        this.commissionType = 'f';
                        this.newProduct.type = this.newFee.type
                    }
                }

                if (stop === false){
                    this.dataMarketer.products[this.type].push(this.newProduct)
                    this.saveMarketer();

                    //Limpio
                    this.newProduct = {
                        _id : "",
                        name: '',
                        fees : [],
                        errors : []
                    }
                    this.dualProduct = null
                }
            }
        },
        checkInputs(){
            this.errors.totalErrors = 0;

            //Nombre principal
            if(!this.newProduct.name){
                this.errors.nameError = true;
                this.errors.totalErrors++;
            }else{
                this.errors.nameError = false;
            }

            //Tarifas principales - Al menos una marcada
            let mainFeesChecked = this.checkFees.filter(fee => fee === true).length;

            if (this.typeProduct === 'dual') {
                // En dual → EXACTAMENTE 1
                if (mainFeesChecked !== 1) {
                    this.errors.feeError = true;
                    this.errors.totalErrors++;
                } else {
                    this.errors.feeError = false;
                }
            } else {
                // Resto → AL MENOS 1
                if (mainFeesChecked === 0) {
                    this.errors.feeError = true;
                    this.errors.totalErrors++;
                } else {
                    this.errors.feeError = false;
                }
            }


            //Comprobaciones producto dual
            if (this.typeProduct === 'dual') {

                // Nombre secundario
                if (!this.newProduct.nameSecondary) {
                    this.errors.nameSecondaryError = true;
                    this.errors.totalErrors++;
                } else {
                    this.errors.nameSecondaryError = false;
                }

                // Tarifas secundarias → 1 sola
                const secondaryFeesChecked =
                    this.checkFeesSecondary.filter(fee => fee === true).length;

                if (secondaryFeesChecked !== 1) {
                    this.errors.feeSecondaryError = true;
                    this.errors.totalErrors++;
                } else {
                    this.errors.feeSecondaryError = false;
                }
            }

            if(this.typeProduct === "alarm"){
                this.errors.feeError = false
                this.errors.totalErrors--
            }

        },
        createFees(){
            let fee = this.newFee;

            if (this.typeProduct !== 'telephony' && this.typeProduct !== 'alarm'){
                if(this.typeProduct !== 'electricity'){
                    delete fee.surplus;
                }
                this.letPrices(fee);
            }else{
                delete fee.priceType
                delete fee.prices
                delete fee.surplus
            }

            for (let i = 0; i < this.checkFees.length; i++) {
                if(this.checkFees[i] === true){
                    let newFee = JSON.parse(JSON.stringify(fee));
                    newFee.id = this.feesList[i].id;

                    //Si no es de luz ni de gas, es decir de los nuevos que no tienen que mirar las tarifas en fees
                    if (this.typeProduct !== 'electricity' && this.typeProduct !== 'gas')
                        newFee.name = this.feesList[i].name;

                    this.newProduct.fees.push(newFee)
                }
            }
        },
        createDualProduct(){

            //Tarifa luz
            let indexElec = this.checkFees.findIndex(checked => checked === true);
            let elecFeeName = this.$storage.FEES.electricity[indexElec];

            //Tarifa gas
            let indexGas = this.checkFeesSecondary.findIndex(checked => checked === true);
            let gasFeeName = this.$storage.FEES.gas[indexGas];

            this.dualProduct = {
                electricity: this.newProduct.name,
                gas: this.newProduct.nameSecondary,
                fees: [
                    {
                        electricity: {
                            name: elecFeeName,
                            prices: {
                                consume: {
                                    P1: 0,
                                    P2: 0,
                                    P3: 0,
                                    P4: 0,
                                    P5: 0,
                                    P6: 0,
                                },
                                power: {
                                    P1: 0,
                                    P2: 0,
                                    P3: 0,
                                    P4: 0,
                                    P5: 0,
                                    P6: 0,
                                }
                            },
                            commissionType: 'f',
                            commissions: [this.commissionRow],
                            surplus: 'none',
                            priceType: 'fixed'
                        },
                        gas: {
                            name: gasFeeName,
                            prices: {
                                fixed: 0,
                                variable: 0
                            },
                            commissionType: 'f',
                            commissions: [this.commissionRow],
                            priceType: 'fixed'
                        },
                        type: this.newFee.type
                    }
                ]
            }
        },
        async checkIfProductExists() {
            try {
                const res = await axios.get('/api/marketers/checkIfProductExists', {
                    params: {
                        marketerId: this.dataMarketer._id,
                        typeProduct:this.typeProduct,
                        name: this.newProduct.name
                    }
                });

                return res.data.exists;

            } catch (err) {
                console.error(err);
                return false;
            }
        },
        async checkIfDualProductExists() {

            try {
                const res = await axios.get('/api/marketers/checkIfDualProductExists', {
                    params: {
                        marketerId: this.dataMarketer._id,
                        elecName: this.dualProduct.electricity,
                        gasName: this.dualProduct.gas
                    }
                });

                return res.data.exists;
            } catch (err) {
                console.error(err);
                return false;
            }
        },
        letPrices(fee){
            if(this.typeProduct === "electricity"){
                fee.prices = {
                    power : {
                        P1 : "0",
                        P2 : "0",
                        P3 : "0",
                        P4 : "0",
                        P5 : "0",
                        P6 : "0",
                    },
                    consume : {
                        P1 : "0",
                        P2 : "0",
                        P3 : "0",
                        P4 : "0",
                        P5 : "0",
                        P6 : "0",
                    },
                }
            }else if(this.typeProduct === "gas"){
                //delete(this.newProduct.commissionType);
                fee.prices = {
                    fixed : 0,
                    variable : 0,
                }
            }
        },
        async saveMarketer(){
            let formData = new FormData();
            formData.append('marketer', JSON.stringify(this.dataMarketer));

            await axios.post(`/api/marketers/${this.dataMarketer._id}`, formData)
            .then((res) => {
                Swal.fire({
                    icon: "success",
                    title: "Se ha guardado correctamente",
                });
                this.closeWindow();

                this.$emit('refreshList')
            })
            .catch((err) => {
                console.error(err)
                Swal.fire({
                    icon: "error",
                    title: "Ha ocurrido un error",
                    text: "No se ha guardado correctamente"
                });
            })
        },
        onChangeRadioFee(index, secondary = false) {

            if (this.typeProduct !== 'dual' && this.typeProduct !== 'telephony') return;

            const target = secondary ? 'checkFeesSecondary' : 'checkFees';

            this[target] = this[target].map((_, i) => i === index);
        }
    }
}

</script>

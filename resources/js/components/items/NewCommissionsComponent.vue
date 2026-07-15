<template>
    <div class="floating-box z-100" @click.self="closeWindow">
        <div class="register-pos w-70 h-70 round d-flex column py-20" data-round="15" data-border-color="principal">
            <p class="text" data-size="24" data-weight="600">Cargar comisiones</p>
            <!--Subida/descargar de excel-->
            <div class="d-flex mt-20" data-gap="15">
                <div class="custom-button" data-bg="principal" data-mode="translucent" data-size="regular" @click="$refs.excel.click()">
                    Adjuntar excel <i class="fa fas fa-paperclip ml-5"></i>
                </div>
                <input type="file" ref="excel" style="display: none" accept=".xls, .xlsx, .csv" @change="getFileSelected">

                <div class="custom-button" data-bg="principal" data-mode="translucent" data-size="regular">
                    <a class="text" href="/assets/templates/marketer_commissions.xlsx" download="Plantilla comisiones">
                        Descargar plantilla <i class="far fa-file-spreadsheet ml-5"></i>
                    </a>
                </div>

            </div>

            <!--Selector de tarifas para la comercializadora-->
            <div class="mt-20 flex-1 h-80-max scroll-y">

                <div class="d-flex w-100 justify-between mt-10" data-gap="10">
                    <div class="d-flex column w-100" data-gap="5" v-for="(fee, id) in products" :key="id">
                        <!--Checkbox de tarifa-->
                        <div class="d-flex align-center justify-center" @click="toggleFee(id)">

                            <div class="custom-checkbox mr-10" :class="{ 'selected': selectedFees.includes(id) }">
                            </div>

                            <div class="text pointer" data-size="13">{{ dataMarketer.fees[typeProduct].find(fee => fee.id.$oid === id)?.name }}</div>
                        </div>
                        <div class="separator mx-auto w-80" />
                        <!--Productos de la tarifa-->
                        <div v-for="product in fee" class="d-flex px-40" @click="toggleProduct(product)">

                            <div class="custom-checkbox mr-10 mt-5" :class="{ 'selected': selectedProducts[id]?.includes(product.id.$oid) }">
                            </div>
                            <div class="text pointer line-clamp-2" data-size="13">{{ product.name }}</div>
                        </div>
                    </div>
                </div>


            </div>

            <!-- Botones -->
            <div class="separator" />
            <div class="d-flex justify-end" data-gap="20">
                <div class="custom-button" data-size="medium" data-bg="rojo" @click="closeWindow()">Cancelar</div>
                <div class="custom-button" data-size="medium" @click="loadCommissions" >Cargar</div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'NewCommissions',
    props: ['dataMarketer', 'typeProduct', 'basicData'],
    data(){
        return {
            fileSelected: false,
            selectedFees: [],
            selectedProducts: {},
        }
    },
    methods: {
        toggleFee(id){
            if(this.selectedFees.includes(id)){
                //Borro la tarifa y todos los productos de ella del array
                this.selectedFees = this.selectedFees.filter(f => f !== id);

                delete this.selectedProducts[id];
            } else {
                //Añado la tarifa y todos los productos de ella al array que todavia no estén
                this.selectedFees.push(id)

                const newProducts = this.products[id].map(p => p.id.$oid);

                if(this.selectedProducts[id] === undefined){
                    this.selectedProducts[id] = [...new Set(newProducts)];
                }else{
                    this.selectedProducts[id] = [...new Set([...this.selectedProducts[id], ...newProducts])];
                }
            }
        },
        toggleProduct(product){
            //Si no está definida la tarifa, añado el producto
            if(this.selectedProducts[product.fee] === undefined){
                this.selectedProducts[product.fee] = [product.id.$oid];
            //Si la tarifa no tiene el producto, lo añado
            }else if (!this.selectedProducts[product.fee].includes(product.id.$oid) ){
                this.selectedProducts[product.fee].push(product.id.$oid);

                //En caso de tener todos los productos de la tarifa, añado la tarifa a selectedFees
                if(this.selectedProducts[product.fee].length === this.products[product.fee].length){
                    this.selectedFees.push(product.fee);
                }
            //Si la tarifa tiene el producto, lo borro
            }else{
                this.selectedProducts[product.fee] = this.selectedProducts[product.fee].filter(p => p !== product.id.$oid);

                //En caso de estar la tarifa vacía, la borro
                if(this.selectedProducts[product.fee].length === 0){
                    delete this.selectedProducts[product.fee];
                }

                //En caso de estar la tarifa en selectedFees, la borro
                if(this.selectedFees.includes(product.fee)){
                    this.selectedFees = this.selectedFees.filter(f => f !== product.fee);
                }
            }
        },
        loadCommissions(){
            if(!this.fileSelected) {
                Swal.fire({
                    icon: 'warning',
                    title:'Selecciona un archivo',
                    text: 'Selecciona un archivo excel con las comisiones',
                    confirmButtonText: 'Aceptar'
                })
            }else if(Object.keys(this.selectedProducts).length === 0){
                Swal.fire({
                    icon: 'warning',
                    title:'Selecciona algún producto',
                    text: 'Selecciona al menos un producto',
                    confirmButtonText: 'Aceptar'
                })
            }else {
                let formData = new FormData();

                formData.append('marketer', this.dataMarketer._id);
                formData.append('typeProduct', this.typeProduct);
                formData.append('products', JSON.stringify(this.selectedProducts));
                formData.append('excel', this.$refs.excel.files[0]);
                formData.append('enterprise', this.basicData.enterprise._id);

                axios.post('/api/marketers/dumpMarketerCommissions', formData).then(res => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Comisiones cargadas',
                        text: 'Las comisiones han sido cargadas correctamente',
                        confirmButtonText: 'Aceptar',
                        timerProgressBar:true,
                        timer: 1500,
                    })
                })
                    .catch(err => {
                        console.log(err)
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: err.response.data.error,
                            confirmButtonText: 'Aceptar'
                        })
                    });
            }
        },
        getFileSelected(event) {
            this.fileSelected = event.target.files.length > 0;
        },
        closeWindow(){
            this.$emit('closeWindow', false);
        },
    },
    computed: {
        products(){
            //Obtengo los productos agrupados por tarifa, y almaceno id de fee, id de producto y nombre del producto
            const fees = this.dataMarketer.fees[this.typeProduct];
            return fees.reduce((result, fee) => {
                const productsList = this.dataMarketer.products[ this.typeProduct];
                result[fee.id.$oid] = productsList.filter(product => {
                    const matchedFee = product.fees?.find((f) => f.id?.$oid === fee.id.$oid);
                    return matchedFee && !matchedFee.archived;
                }).map(product => ({ id: product._id, name: product.name, fee: fee.id.$oid }))
                return result;
            }, {});
        }
    }
}
</script>

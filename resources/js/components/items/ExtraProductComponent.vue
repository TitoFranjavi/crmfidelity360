<template>
    <div>

        <!--Sin abrir-->
        <div class="extraProducts my-5 mx-25 text" data-gap="5" v-if="!isOpened" @click="toggleIsOpened">
            <p class="ellipsis px-10">{{ extra.name }}</p>
            <p class="px-10">{{ extra.price.amount }} {{ unities[extra.price.unit] }}</p>
            <p class="px-10">{{ extra.commission }}</p>
            <p class="px-10">{{ to }}</p>
            <div class="d-flex " :class="errors['extras' + i] && Object.keys(errors['extras' + i]).length > 0 ? 'justify-between' : 'justify-end'">

                <!--Si tiene algún error-->
                <div v-if="errors['extras' + i] && Object.keys(errors['extras' + i]).length > 0" class="mx-10 my-auto">
                    <i class="fa-solid fa-triangle-exclamation fa-bounce" data-color="rojo" data-size="20"></i>
                </div>

                <!--Flecha abrir-->
                <p class="px-10"><i class="far fa-chevron-down"></i></p>

            </div>
        </div>

        <!--Abierto-->
        <div class="extraProducts opened my-5 mx-25 d-flex text" data-gap="5" v-else>

            <!--Nombre-->
            <div v-bind:class="{ wrong: errors['extras' + i]?.name }" class="form-group p-10">
                <p class="my-auto"><label>Nombre</label></p>
                <div class="input-group">
                    <input v-on:focus="delete errors['extras' + i]?.name" data-size="10" v-model="extra.name" type="text">
                </div>
                <span v-if="errors['extras' + i]?.name" class="error">{{ errors['extras' + i]?.name }}</span>
            </div>

            <!--Aplicado a-->
            <div v-bind:class="{ wrong: errors['extras' + i]?.to }" class="form-group p-10">
                <label>Aplicado a:</label>
                <div class="input-group d-flex justify-around"2>

                    <!--PYME-->
                    <div class="d-flex my-5">
                        <div class="custom-checkbox my-auto mr-10" v-on:click="toggleToSelect('pyme'); delete errors['extras' + i]?.to">
                            <div v-bind:class="{ selected: extra.to.pyme }"></div>
                        </div>

                        <p class="my-auto mr-15" data-color="principal" data-size="10">Pyme</p>
                    </div>

                    <!--RESIDENCIAL-->
                    <div class="d-flex my-5">
                        <div class="custom-checkbox my-auto mr-10" v-on:click="toggleToSelect('residential'); delete errors['extras' + i]?.to">
                            <div v-bind:class="{ selected: extra.to.residential }"></div>
                        </div>

                        <p class="my-auto mr-15" data-color="principal" data-size="10">Residencial</p>
                    </div>

                    <!--PRODUCTO A PRODUCTO-->
                    <div class="d-flex my-5">
                        <div class="custom-checkbox my-auto mr-10" v-on:click="toggleToSelect('product'); delete errors['extras' + i]?.to">
                            <div v-bind:class="{ selected: extra.to.product }"></div>
                        </div>

                        <p class="my-auto mr-15" data-color="principal" data-size="10">Producto a producto</p>
                    </div>

                </div>
                <span v-if="errors['extras' + i]?.to" class="error">{{ errors['extras' + i]?.to }}</span>
            </div>

            <!--Precios y comisiones-->
            <div class="p-10 d-flex justify-around" data-gap="10">

                <!--Precio-->
                <div class="d-flex justify-between w-50-min" data-gap="10">
                    <!--Cantidad-->
                    <div v-bind:class="{ wrong: false }" class="form-group w-70">
                        <p class="my-auto"><label>Precio</label></p>
                        <div class="input-group">
                            <input v-on:focus="delete false" data-size="10" v-model="extra.price.amount" type="text">
                        </div>
                        <span v-if="false" class="error">{{ false }}</span>
                    </div>

                    <!--Unidad-->
                    <div v-bind:class="{ wrong: false }" class="form-group w-30">
                        <label>Unidad</label>
                        <div class="input-group">
                            <select v-model="extra.price.unit">
                                <option v-for="(unit, unitCode) in unities" :value="unitCode">
                                    {{ unit }}
                                </option>
                            </select>
                        </div>
                        <span v-if="false" class="error">{{ false }}</span>
                    </div>
                </div>

                <!--Comisión-->
                <div v-bind:class="{ wrong: false }" class="form-group w-50-min">
                    <p class="my-auto"><label>Comisión</label></p>
                    <div class="input-group">
                        <input v-on:focus="delete false" data-size="10" v-model="extra.commission" type="text">
                    </div>
                    <span v-if="false" class="error">{{ false }}</span>
                </div>
            </div>

            <!--T. productos-->
            <div v-bind:class="{ wrong: errors['extras' + i]?.to }" class="form-group p-10">
                <label>Tipos de productos:</label>
                <div class="input-group d-flex justify-around f-wrap">

                    <div class="d-flex my-5" v-for="productType in filteredProductTypes" :key="productType.code">
                        <div class="custom-checkbox my-auto mr-10" v-on:click="toggleProductType(productType.code); delete errors['extras' + i]?.productTypes">
                            <div v-bind:class="{ selected: extra.productTypes.includes(productType.code) }"></div>
                        </div>

                        <p class="my-auto mr-15" data-color="principal" data-size="10">{{ productType.title }}</p>
                    </div>

                </div>
                <span v-if="errors['extras' + i]?.productTypes" class="error">{{ errors['extras' + i]?.productTypes }}</span>
            </div>

            <!--Botones-->
            <div class="double d-flex justify-end" data-gap="10">
                <button class="custom-button px-15 py-10" data-size="big" data-bg="rojo" v-on:click.prevent="deleteExtraProduct">Borrar</button>
                <button class="custom-button px-15 py-10" data-size="big" data-bg="azul" v-on:click.prevent="toggleIsOpened">Cerrar</button>
            </div>

        </div>

        <div class="separator my-10" v-if="i < length - 1"></div>

    </div>
</template>

<script>

export default {
    name: 'ExtraProductComponent',
    props:['extra', 'isOpened', 'i', 'length', 'errors'],
    data(){
        return{
            unities: {
                'total' : '€',
                'month' : '€/mes',
                'day' : '€/día',
                'year' : '€/año',
            }
        }
    },
    methods: {
        toggleToSelect(type) {

            switch (type) {

                case 'pyme':
                    this.extra.to.pyme = !this.extra.to.pyme;

                    if (this.extra.to.product) {
                        this.extra.to.product = false;
                    }
                    break;

                case 'residential':
                    this.extra.to.residential = !this.extra.to.residential;

                    if (this.extra.to.product) {
                        this.extra.to.product = false;
                    }
                    break;

                case 'product':
                    this.extra.to.product = !this.extra.to.product;

                    if (this.extra.to.pyme || this.extra.to.residential) {
                        this.extra.to.pyme = false;
                        this.extra.to.residential = false;
                    }
                    break;

                default:
                    break;
            }
        },
        toggleProductType(productType) {
            if (this.extra.productTypes.includes(productType)) {
                this.extra.productTypes = this.extra.productTypes.filter(type => type !== productType);
            } else {
                this.extra.productTypes.push(productType);
            }
        },
        toggleIsOpened() {
            this.$emit('toggle')
        },
        deleteExtraProduct() {
            this.$emit('deleteExtraProduct', this.extra.id ? this.extra.id : this.extra.temporalId)
        }
    },
    computed: {
        to(){
            let to = this.extra?.to || {}

            let labels = {
                pyme: 'Pyme',
                residential: 'Residencial',
                product: 'Producto'
            }

            let selected = Object.keys(labels)
                .filter(key => to[key])
                .map(key => labels[key])

            return selected.length ? selected.join(', ') : 'Ninguno'
        },
        filteredProductTypes() {
            return this.$storage.PRODUCT_TYPES.filter(productType => productType.inDatabase)
        }
    }
}

</script>

<style scoped>

</style>

<template>
    <div v-if="marketer" @click="hideCustomSelects">
        <form v-if="product.fees[feeSelect]" class="form register-pos">

            <!-- Nombre de la Comercializadora -->
            <div class="d-flex mt-10 mb-25 align-center f-wrap">

                <!--Logo y nombres-->
                <div class="d-flex align-center mr-50">
                    <img :src="'/assets/marketers_logo/' + marketer.logo" alt="Logo" class="h-50-px-max w-100-px-max contain-img"/>

                    <div class="d-flex align-center text ml-20" data-size="20" data-weight="700" v-on:click.stop="">
                        <!--Nombre comercializadora-->
                        <div>
                            <p class="ellipsis">{{ marketer.name }} -</p>
                        </div>

                        <!--Nombre producto telefonía-->
                        <div class="d-flex">
                            <i class="fa-regular fa-phone ml-10 mr-5 my-auto"></i>
                            <p class="mx-5 ellipsis w-500-px-max">{{ product.name }}</p>
                            <!--<div v-else class="form-group">
                                <div class="input-group">
                                    <input type="text" v-model="product.name" />
                                </div>
                            </div>-->
                        </div>

                    </div>
                </div>

            </div>

            <!-- Datos tarifa y producto -->
            <div class="d-flex justify-between align-center">
                <div class="w-100 d-flex justify-between align-center">
                    <!--Desplegable tarifa-->
                    <div class="d-flex justify-between align-center">
                        <div class="custom-select"
                             :class="{ seeing: selectFeesActive }">
                            <div class="text mr-20"
                                 data-weight="400"
                                 v-on:click.stop="selectFees()">
                                Tarifa
                                <i class="fas ml-5" :class="[selectFeesActive? 'fa-chevron-up': 'fa-chevron-down',]"></i>
                            </div>

                            <!-- Despegable de Tarifas -->
                            <div v-if="selectFeesActive === true">
                                <div class="select-content left">
                                    <div v-for="(fee, i) in product.fees">
                                        <div class="d-flex justify-between">
                                            <div :data-color="i === feeSelect ? 'azul' : 'principal'" v-on:click.stop="changeFee(i, fee.id?.$oid)">
                                                {{ fee.name }}
                                            </div>
                                            <div v-if="isEdit"
                                                 @click="deleteProductFee(fee)">
                                                <i class="far fa-trash"
                                                   data-color="rojo"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!--botón añadir tarifa-->
                                    <div class="custom-button d-flex justify-between mt-10" data-size="small" data-bg="amarillo" v-if="isEdit" v-on:click.stop="isShowingFeeModal = true">
                                        Añadir Tarifa
                                        <i class="fa-solid fa-plus my-auto"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tarifa Activa -->
                        <div class="text">
                            {{ product.fees[feeSelect].name }}
                        </div>
                    </div>

                    <!--Modal añadir tarifa-->
                    <Teleport to=".boxBody" v-if="isShowingFeeModal">
                        <div class="floating-box z-100">
                            <div class="register-pos small w-50 h-auto h-98-max round" data-round="20" data-border-color="principal">
                                <div class="select-content filterMarketers">
                                    <div class="orderMarkers three" v-if="filteredFeesToAdd.length > 0">
                                        <template v-for="fee in filteredFeesToAdd">
                                            <div class="cell pointer w-100" v-on:click.stop="selectedFeeToAdd = fee">
                                                <p :data-color="fee === selectedFeeToAdd ? 'azul' : 'principal'" >
                                                    {{ fee }}
                                                </p>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="text-center opacity-5" v-else>
                                        ¡No hay más tarifas por añadir!
                                    </div>
                                </div>

                                <!-- Botones -->
                                <div class="separator"></div>

                                <div class="d-flex justify-end" data-gap="20">
                                    <div class="custom-button" data-size="regular" data-bg="rojo" v-on:click.stop="closeFeeModal">Cancelar</div>
                                    <div v-if="selectedFeeToAdd" class="custom-button" data-size="regular" v-on:click.stop="addProductFee">Crear</div>
                                </div>
                            </div>
                        </div>
                    </Teleport>

                    <!--Otras opciones-->
                    <div class="d-flex" data-gap="10">

                        <!-- Tipo de Producto -->
                        <div class="mx-20 d-flex form-group">
                            <div class="text my-auto">Tipo de producto:</div>

                            <div v-if="isEdit" class="d-flex f-wrap" data-gap="5">
                                <div class="input-group mx-10">
                                    <label class="mx-5" for="r"><i class="far fa-house mr-5"/>Residencial</label>
                                    <input type="checkbox" id="r" v-model="product.fees[feeSelect].type.residencial"/>
                                </div>
                                <div class="input-group mx-10">
                                    <label class="mx-5" for="p"><i class="far fa-building mr-5"/>PYME</label>
                                    <input type="checkbox" id="p" v-model="product.fees[feeSelect].type.pyme"/>
                                </div>
                            </div>

                            <template v-else>
                                <div class="text mx-5 d-flex align-center" v-if="product.fees[feeSelect].type.residencial === true &&
                                    product.fees[feeSelect].type.pyme === true">
                                    <i class="far fa-house-building mr-5"/>Residencial y PYME
                                </div>
                                <div class="text mx-5 d-flex align-center" v-else-if="product.fees[feeSelect].type.residencial === true">
                                    <i class="far fa-house mr-5" />Residencial
                                </div>
                                <div class="text mx-5 d-flex align-center" v-else-if="product.fees[feeSelect].type.pyme === true">
                                    <i class="far fa-building mr-5" />PYME
                                </div>
                            </template>
                        </div>

                        <!--Archivado-->
                        <div class="d-flex justify-center align-center">
                            <div v-if="product.fees[feeSelect]?.archived && !isEdit" class="d-flex form-group">
                                <div class="input-group mx-10">
                                    <label class="mx-5"><i class="far fa-box-archive mr-5"/>Archivado</label>
                                </div>
                            </div>
                            <div v-if="isEdit" class="d-flex form-group">
                                <div class="input-group mx-10">
                                    <label class="mx-5" for="archived"><i class="far fa-box-archive mr-5"/>Archivado</label>
                                    <input type="checkbox" id="archived" v-model="product.fees[feeSelect].archived"/>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Botones -->
                    <div v-if="canManage('products.edit') && canEdit">
                        <div class="custom-button" data-size="regular" v-if="!isEdit" v-on:click.stop="changeEdit()">Editar</div>
                        <div class="d-flex justify-between" data-gap="10" v-else-if="isEdit">
                            <div class="custom-button" data-size="regular" data-bg="rojo" v-on:click.stop="changeEdit(true)">Cancelar</div>
                            <div class="custom-button" data-size="regular" v-on:click.stop="saveProd()">Guardar</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="separator"></div>


            <!-- Comentario -->
            <template v-if="product.fees[feeSelect].comment || isEdit">
                <div class="text ml-20 pb-10" data-size="20" data-weight="400">Observaciones</div>

                <p class="break-spaces" data-size="12" v-if="!isEdit">{{ product.fees[this.feeSelect].comment }}</p>

                <div class="form-group" v-else>
                    <div class="input-group">
                        <textarea v-model="product.fees[this.feeSelect].comment"></textarea>
                    </div>
                </div>

                <div class="separator"></div>
            </template>


            <!-- Datos del Comparador -->
            <div id="comparador" class="pb-10">
                <div class="text ml-20 pb-10" data-size="20" data-weight="400">
                    <i class="far fa-chart-bar mr-10"></i>Información del producto
                </div>

                <!-- Vista (no edición) -->
                <template v-if="!isEdit">
                    <div class="d-flex f-wrap" data-gap="20" style="padding: 0 20px 10px;">

                        <div class="d-flex align-center" data-gap="8" v-if="product.fees[feeSelect].comparador?.price != null">
                            <i class="far fa-euro-sign mr-5 opacity-5"></i>
                            <span class="text" data-size="13" data-weight="400">Precio:</span>
                            <span class="text" data-size="13">{{ product.fees[feeSelect].comparador.price }} €/mes</span>
                        </div>

                        <div class="d-flex align-center" data-gap="8" v-if="product.fees[feeSelect].comparador?.fibraMb != null">
                            <i class="far fa-wifi mr-5 opacity-5"></i>
                            <span class="text" data-size="13" data-weight="400">Fibra:</span>
                            <span class="text" data-size="13">{{ product.fees[feeSelect].comparador.fibraMb }} Mb</span>
                        </div>

                        <div class="d-flex align-center" data-gap="8" v-if="product.fees[feeSelect].comparador?.datosGB != null">
                            <i class="far fa-mobile mr-5 opacity-5"></i>
                            <span class="text" data-size="13" data-weight="400">Datos móvil:</span>
                            <span class="text" data-size="13">{{ product.fees[feeSelect].comparador.datosGB }} GB</span>
                        </div>

                        <div class="d-flex align-center" data-gap="8" v-if="product.fees[feeSelect].comparador?.lineasIncluidas != null">
                            <i class="far fa-sim-card mr-5 opacity-5"></i>
                            <span class="text" data-size="13" data-weight="400">Líneas:</span>
                            <span class="text" data-size="13">{{ product.fees[feeSelect].comparador.lineasIncluidas }}</span>
                        </div>

                        <div class="d-flex align-center" data-gap="8" v-if="product.fees[feeSelect].comparador?.permanenciaMeses != null">
                            <i class="far fa-calendar-check mr-5 opacity-5"></i>
                            <span class="text" data-size="13" data-weight="400">Permanencia:</span>
                            <span class="text" data-size="13">
                                {{ product.fees[feeSelect].comparador.permanenciaMeses === 0 ? 'Sin permanencia' : product.fees[feeSelect].comparador.permanenciaMeses + ' meses' }}
                            </span>
                        </div>

                        <div class="d-flex align-center" data-gap="8" v-if="product.fees[feeSelect].comparador?.cobertura">
                            <i class="far fa-tower-broadcast mr-5 opacity-5"></i>
                            <span class="text" data-size="13" data-weight="400">Cobertura:</span>
                            <span class="text" data-size="13">{{ product.fees[feeSelect].comparador.cobertura }}</span>
                        </div>

                        <div class="opacity-5 text" data-size="13"
                             v-if="!product.fees[feeSelect].comparador || Object.keys(product.fees[feeSelect].comparador || {}).length === 0">
                            Sin datos de comparador configurados
                        </div>
                    </div>
                </template>

                <!-- Edición -->
                <template v-else>
                    <div class="d-flex f-wrap" data-gap="15" style="padding: 0 20px 10px;">

                        <!-- Precio -->
                        <div class="form-group" style="min-width: 130px;">
                            <label class="text" data-size="13" data-weight="400">Precio (€/mes)</label>
                            <div class="input-group mt-5">
                                <input type="number" step="0.01" min="0"
                                       v-model.number="product.fees[feeSelect].comparador.price"
                                       placeholder="Ej: 27.00"/>
                            </div>
                        </div>

                        <!-- Fibra Mb -->
                        <div class="form-group" style="min-width: 140px;">
                            <label class="text" data-size="13" data-weight="400">Fibra (Mb)</label>
                            <div class="input-group mt-5">
                                <input type="number" min="0"
                                       v-model.number="product.fees[feeSelect].comparador.fibraMb"
                                       placeholder="Ej: 600"/>
                            </div>
                        </div>

                        <!-- Datos GB -->
                        <div class="form-group" style="min-width: 140px;">
                            <label class="text" data-size="13" data-weight="400">Datos móvil (GB)</label>
                            <div class="input-group mt-5">
                                <input type="number" step="0.1" min="0"
                                       v-model.number="product.fees[feeSelect].comparador.datosGB"
                                       placeholder="Ej: 50"/>
                            </div>
                        </div>

                        <!-- Líneas incluidas -->
                        <div class="form-group" style="min-width: 130px;">
                            <label class="text" data-size="13" data-weight="400">Líneas incluidas</label>
                            <div class="input-group mt-5">
                                <input type="number" min="0" step="1"
                                       v-model.number="product.fees[feeSelect].comparador.lineasIncluidas"
                                       placeholder="Ej: 1"/>
                            </div>
                        </div>

                        <!-- Permanencia -->
                        <div class="form-group" style="min-width: 150px;">
                            <label class="text" data-size="13" data-weight="400">Permanencia (meses)</label>
                            <div class="input-group mt-5">
                                <input type="number" min="0" step="1"
                                       v-model.number="product.fees[feeSelect].comparador.permanenciaMeses"
                                       placeholder="0 = sin permanencia"/>
                            </div>
                        </div>


                    </div>
                </template>
            </div>

            <div class="separator"></div>

            <!--Productos extra-->
            <div id="prices" v-if="isEdit && canManage('products.edit') && canEdit">
                <div class="text ml-20 pb-10" data-size="20" data-weight="400">Productos extra</div>

                <div class="d-flex">
                    <!--Disponibles-->
                    <div class="w-50">

                        <p class="text ml-30">Disponibles</p>

                        <div class="p-10">
                            <!--header-->
                            <div class="extraProducts head toSelect mt-10 mx-25 mt-5 mb-15" data-gap="20">
                                <p class="opacity-5 text">Nombre</p>
                                <p class="opacity-5 text">Precio</p>
                                <p class="opacity-5 text">Comisión</p>
                            </div>


                            <!--contenido-->
                            <div v-for="(extra, i) in extraProductsToSelect" @click="toggleSelectExtraProduct(extra)" class="extraProducts toSelect my-5 mx-25 text pointer" data-gap="5">
                                <p class="ellipsis px-10">{{ extra.name }}</p>
                                <p class="px-10">{{ extra.price.amount }} {{ unities[extra.price.unit] }}</p>
                                <p class="px-10">{{ extra.commission }}</p>
                            </div>

                        </div>
                    </div>

                    <!--Seleccionados-->
                    <div class="w-50">
                        <p class="text ml-30">Seleccionados</p>

                        <div class="p-10">
                            <!--header-->
                            <div class="extraProducts head toSelect mt-10 mx-25 mt-5 mb-15" data-gap="20">
                                <p class="opacity-5 text">Nombre</p>
                                <p class="opacity-5 text">Precio</p>
                                <p class="opacity-5 text">Comisión</p>
                            </div>


                            <!--contenido-->
                            <div v-for="(extra, i) in extraProductsSelected" @click="toggleSelectExtraProduct(extra)" class="extraProducts toSelect my-5 mx-25 text pointer" data-gap="5">
                                <p class="ellipsis px-10">{{ extra.name }}</p>
                                <p class="px-10">{{ extra.price.amount }} {{ unities[extra.price.unit] }}</p>
                                <p class="px-10">{{ extra.commission }}</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="separator"></div>

            <!-- Comisiones -->
            <div id="commission">
                <CommissionsTableComponent
                    :canEdit="canManage('products.edit') && canEdit"
                    :type="type"
                    v-model:commissions="product['fees'][feeSelect].commissions"
                    v-model:commissionType="product['fees'][feeSelect].commissionType"
                    v-model:seeCommissionTypes="seeCommissionTypes"
                    :price-type="product['fees'][feeSelect].priceType"
                    :marketer="marketer"
                    :product="product"
                    :isEditing="isEdit"
                    :basicData="basicData"
                    :feeSelect="feeSelect"
                />
            </div>
        </form>
    </div>
</template>

<script>

import CommissionsTableComponent from "@/components/items/CommissionsTableComponent.vue";

export default {
    name: "TelephonyProductComponent",
    components: {CommissionsTableComponent},
    props: ["basicData"],
    data() {
        return {
            marketer: "",
            product: {},
            productDefault: {},
            type: "",
            errors: {},
            isSeeingCreateProduct: false,
            isSeeingCreateFee: false,
            marketerToReset: "",
            isEditing: false,
            isUpdating: false,
            indexProduct: "",
            feeSelect: "",
            productId: "",
            feeId: "",
            selectFeesActive: false,
            productTerms: [],
            isEdit: false,
            isShowingFeeModal: false,
            selectedFeeToAdd: "",
            selectedFeeId: null,
            newFeeOptions: {
                breakdownType: "basic",
                commissionType: "c",
            },
            seeCommissionTypes: false,
            breakdown: {
                interval: null,
                intervalNumber: null,
                pot1: null,
                pot2: null,
            },
            unities: {
                'total' : '€',
                'month' : '€/mes',
                'day' : '€/día',
                'year' : '€/año',
            }
        }
    },
    async mounted() {
        this.productId = this.$route.query.productId;
        this.type = this.$route.query.type;

        // Esperamos a que se cargue el marketer y el producto
        await this.fetchMarketer();

        // Sacamos el indice del feeSelect
        this.setFeeInd()
    },
    methods: {
        async fetchMarketer() {
            try {
                const {data} = await axios.get(
                    "/api/marketers/" + this.$route.params.id
                );
                this.marketer = data.marketer;
                this.marketerToReset = {...this.marketer};

                const products = this.marketer?.products?.telephony || [];

                // productId viene en la query
                const productId = this.$route.query.productId;

                // buscar producto por _id (acepta string o {$oid})
                const product = products.find(
                    (p) => p?._id === productId || p?._id?.$oid === productId
                );

                if (!product) {
                    console.error("Producto no encontrado con id", productId);
                    // decide qué hacer si no existe: volver al listado, mostrar alerta, etc.
                    this.$router.push("/marketers");
                    return;
                }

                // buscar índice del producto por _id
                this.productSelect = products.findIndex(
                    (p) => p?._id === productId || p?._id?.$oid === productId
                );

                if (this.productSelect === -1) {
                    console.error("Producto no encontrado con id", productId);
                    this.$router.push("/marketers");
                    return;
                }

                // asignar producto al estado
                this.product = product;
                this.productDefault = JSON.parse(JSON.stringify(product));

                // anclaje visual si viene refName
                if (this.$route.query.refName) {
                    this.$nextTick(() => {
                        const element = document.getElementById(
                            this.$route.query.refName
                        );
                        if (element) {
                            element.scrollIntoView({behavior: "smooth"});
                            element.classList.add("blink-border");
                            setTimeout(
                                () => element.classList.remove("blink-border"),
                                2900
                            );
                        }
                    });
                }
            } catch (err) {
                console.error(err);
            }
        },
        setFeeInd(){
            let index = this.product.fees.findIndex((f) => (f.id?.$oid || f.id) === this.$route.query.feeId);

            if (index !== -1) {
                console.log("🔹 Seleccionando tarifa por URL:", this.feeSelect);
                this.changeFee(index, this.$route.query.feeId);
            } else {
                console.warn("⚠️ feeId no encontrado:", this.feeSelect);
            }
        },
        changeFee(index, feeId) {
            this.feeSelect = index;

            // Garantiza retrocompatibilidad: tarifas antiguas sin comparador
            const fee = this.product.fees[index];
            if (fee && !fee.comparador) {
                fee.comparador = {
                    price: null,
                    fibraMb: null,
                    datosGB: null,
                    lineasIncluidas: null,
                    permanenciaMeses: 0,
                    cobertura: "",
                };
            }

            this.$router.replace({
                query: {
                    ...this.$route.query,
                    feeId: feeId
                }
            });

            this.selectFeesActive = false;
        },
        hideCustomSelects() {
            this.seeCommissionTypes = false;
        },
        selectFees() {
            this.selectFeesActive = !this.selectFeesActive;
        },
        async addProductFee() {
            let newFee = {
                commissions: [{
                    con1: null,
                    con2: null,
                    pot1: null,
                    pot2: null,
                    multiply: false,
                    base: null,
                    breakdown: [],
                }],
                commissionType: 'f',
                name: this.selectedFeeToAdd,
                comment: "",
                type: {
                    pyme: true,
                    residencial: true,
                },
                comparador: {
                    price: null,
                    fibraMb: null,
                    datosGB: null,
                    lineasIncluidas: null,
                    permanenciaMeses: 0,
                    cobertura: "",
                },
            };;

            this.marketer.products.telephony[this.productSelect].fees.push(newFee);

            //Ordeno
            this.marketer.products.telephony[this.productSelect].fees.sort((a, b) => {
                const feeA = a?.name || "";
                const feeB = b?.name || "";

                return feeA.localeCompare(feeB)
            });


            //le vinculo en bbdd la nueva tarifa
            await axios
                .put(`/api/marketers/addProductFee`, {
                    marketer: this.marketer,
                    productInd: this.productSelect,
                    fee: newFee,
                    type: 'telephony'
                })
                .then((res) => {
                    // Vuelvo a calcular el indice del feeSelect
                    this.setFeeInd()
                })
                .catch((err) => {
                    console.log(err);
                });

            //cierro la venta
            this.closeFeeModal();
        },
        deleteProductFee(fee) {
            const list = this.marketer?.products?.telephony || [];

            // Índice del producto por _id
            const pid = this.product?._id?.$oid || this.product?._id;
            const pIdx = list.findIndex((p) => (p?._id?.$oid || p?._id) === pid);
            if (pIdx === -1) return;

            // Índice de la fee a borrar por id
            const fid = fee?.id?.$oid || fee?.id;
            const fees = list[pIdx]?.fees || [];
            const feeSelectedInd = fees.findIndex((f) => (f?.id?.$oid || f?.id) === fid);
            if (feeSelectedInd === -1) return;

            const confirmAndCall = async (deleteProd) => {
                try {
                    await axios.put("/api/marketers/deleteProductFee", {
                        marketer: this.marketer,
                        productInd: pIdx, // el backend lo espera por índice
                        feeInd: feeSelectedInd, // idem
                        deleteProd,
                        type: 'telephony',
                    });

                    if (this.feeSelect === feeSelectedInd){
                        this.$router.push(`/marketers`);
                        return
                    }else //Sino borro la eliminada para no verla temporalmente
                        this.product.fees.splice(feeSelectedInd, 1);


                    // Vuelvo a calcular el indice del feeSelect
                    this.setFeeInd()

                    // (Opcional) toast rápido
                    Swal.fire({
                        icon: "success",
                        title: "Eliminado",
                        timer: 1000,
                        timerProgressBar: true
                    });
                } catch (err) {
                    console.error("Error borrando en backend:", err);
                    Swal.fire({
                        icon: "error",
                        title: "No se pudo eliminar",
                        text: "Inténtalo de nuevo",
                    });
                }
            };

            if (fees.length === 1) {
                // Se elimina el producto completo
                Swal.fire({
                    icon: "warning",
                    title: "Se eliminará el producto",
                    text: "No quedarán tarifas vinculadas.",
                    confirmButtonText: "Desvincular y eliminar",
                    confirmButtonColor: "red",
                    showCancelButton: true,
                    cancelButtonText: "Cancelar",
                    focusCancel: true,
                }).then((res) => {
                    if (res.isConfirmed) confirmAndCall(true);
                });
            } else {
                // Solo se desvincula la tarifa
                Swal.fire({
                    icon: "warning",
                    title: "¿Estás seguro?",
                    text: "Se desvinculará la tarifa del producto.",
                    confirmButtonText: "Desvincular",
                    confirmButtonColor: "red",
                    showCancelButton: true,
                    cancelButtonText: "Cancelar",
                    focusCancel: true,
                }).then((res) => {
                    if (res.isConfirmed) confirmAndCall(false);
                });
            }
        },
        async saveProd() {

            // Comisiones
            const toFloat = (val) => {
                if (val === null || val === undefined || val === "") return null;
                const parsed = parseFloat(val.toString().replace(",", "."));
                return isNaN(parsed) ? null : parsed;
            };

            const fee = this.product.fees[this.feeSelect];

            if (fee.commissions?.length) {
                fee.commissions.forEach((commission) => {
                    commission.pot1 = toFloat(commission.pot1);
                    commission.pot2 = toFloat(commission.pot2);
                    commission.con1 = toFloat(commission.con1);
                    commission.con2 = toFloat(commission.con2);
                    commission.base = toFloat(commission.base);

                    if (commission.breakdown) {
                        Object.keys(commission.breakdown).forEach((key) => {
                            commission.breakdown[key] = toFloat(commission.breakdown[key]);
                        });
                    }
                });
            }


            try {

                const list = this.marketer?.products?.telephony || [];
                const pid = this.product?._id?.$oid || this.product?._id; // productId actual
                const pIdx = list.findIndex((p) => (p?._id?.$oid || p?._id) === pid);

                // Reemplaza el producto dentro del array del marketer (evita refs reactivas raras)
                const productClean = JSON.parse(JSON.stringify(this.product));

                if (pIdx !== -1)
                    this.marketer.products.telephony.splice(pIdx, 1, productClean);
                else
                    this.marketer.products.telephony.push(productClean);


                // Guardar en bbdd
                await axios.post(`/api/marketers/${this.marketer._id}`, {marketer: JSON.stringify(this.marketer)});

                Swal.fire({
                    icon: "success",
                    title: "Se ha guardado correctamente",
                    timer: 1500,
                });

                // Releer para sincronizar y reconstruir charts
                await this.fetchMarketer();

                this.isEdit = !this.isEdit;
            } catch (err) {
                console.error(err);
                Swal.fire({
                    icon: "error",
                    title: "Ha ocurrido un error",
                    text: "No se ha guardado correctamente",
                });
            }
        },
        changeEdit(cancel) {
            if (cancel)
                this.product = JSON.parse(JSON.stringify(this.productDefault));

            this.isEdit = !this.isEdit;
        },
        closeFeeModal() {
            this.isShowingFeeModal = false;
            this.selectedFeeToAdd = "";
            this.newFeeOptions = {
                breakdownType: "basic",
                commissionType: "c",
            };
        },
        canManage(code) {
            const user = this.basicData?.userLogged;
            const subdomain = this.basicData?.userSubdomain;

            if (!user || !subdomain) return false;

            const label = user.label;
            const labelsPermissions = subdomain.labels_permissions;

            if (!label || !labelsPermissions) return false;
            if (!labelsPermissions[label]) return false;
            if (!code || !code.includes('.')) return false;

            const [module, action] = code.split('.');

            const modulePermissions = labelsPermissions[label][module];

            return Array.isArray(modulePermissions) && modulePermissions.includes(action);
        },
        canEdit() {
            //Compruebo si el producto es de zoco y es zoco, en caso negativo devuelvo false
            return !(this.marketer.createdBy === "65cb57489c2c285441086a43" && this.basicData.userLogged._id !== "65cb57489c2c285441086a43");
        },
        toggleSelectExtraProduct(extra) {
            if (!this.product.fees[this.feeSelect].extras) this.product.fees[this.feeSelect].extras = []

            if (this.product.fees[this.feeSelect].extras.includes(extra.id.$oid))
                this.product.fees[this.feeSelect].extras = this.product.fees[this.feeSelect].extras.filter(extraNow => extraNow !== extra.id.$oid);
            else
                this.product.fees[this.feeSelect].extras.push(extra.id.$oid);
        }
    },
    computed:{
        filteredFeesToAdd() {
            if (!this.product) return [];

            return this.$storage.FEES.telephony.filter((feeNow) => {
                let isAdded = this.product.fees.findIndex((feeAddedNow) => feeAddedNow.name === feeNow);
                return isAdded === -1;
            });
        },
        userCommission() {
            const user = this.basicData.userLogged;
            if ( this.marketer.createdBy === "65cb57489c2c285441086a43" && this.basicData.userLogged._id !== "65cb57489c2c285441086a43" ) {
                return `com${user.commissions[this.marketer._id].value}`;
            } else if (
                user.label === "Usuario subdominio" ||
                user._id === "65d47559aa2d0448c308e252" ||
                user._id === "65d48ac808c6cf0254066c42" ||
                user._id === "6617a7ffc4f2475a7a010d32"
            ) return "comAs";

            return `com${user.commissions[this.marketer._id].value}`;
        },
        extraProductsToSelect() {
            if(!this.marketer?.extras) return []
            return this.marketer.extras.filter(extra => extra.productTypes.includes('ct') && extra.to.product && (!this.product.fees[this.feeSelect].extras || !this.product.fees[this.feeSelect].extras.includes(extra.id.$oid)))
        },
        extraProductsSelected() {
            if(!this.marketer?.extras) return []
            return this.marketer.extras.filter(extra => extra.productTypes.includes('ct') && extra.to.product && this.product.fees[this.feeSelect].extras && this.product.fees[this.feeSelect].extras.includes(extra.id.$oid))
        }
    }
}

</script>

<style scoped>

</style>

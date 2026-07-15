<template>
    <div v-if="marketer" @click="hideCustomSelects">
        <form v-if="product" class="form register-pos">

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

                        <!--Nombre producto alarmas-->
                        <div class="d-flex">
                            <i class="fa-regular fa-bell-on ml-10 mr-5 my-auto"></i>
                            <p class="mx-5 ellipsis w-500-px-max">{{ product.name }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Datos tarifa y producto -->
            <div class="d-flex justify-between align-center">
                <div class="w-100 d-flex justify-between align-center">

                    <!--Otras opciones-->
                    <div class="d-flex" data-gap="10">

                        <!-- Tipo de Producto -->
                        <div class="mx-20 d-flex form-group">
                            <div class="text my-auto">Tipo de producto:</div>

                            <div v-if="isEdit" class="d-flex f-wrap" data-gap="5">
                                <div class="input-group mx-10">
                                    <label class="mx-5" for="r"><i class="far fa-house mr-5"/>Residencial</label>
                                    <input type="checkbox" id="r" v-model="product.type.residencial"/>
                                </div>
                                <div class="input-group mx-10">
                                    <label class="mx-5" for="p"><i class="far fa-building mr-5"/>PYME</label>
                                    <input type="checkbox" id="p" v-model="product.type.pyme"/>
                                </div>
                            </div>

                            <template v-else>
                                <div class="text mx-5 d-flex align-center" v-if="product.type.residencial === true && product.type.pyme === true">
                                    <i class="far fa-house-building mr-5"/>Residencial y PYME
                                </div>
                                <div class="text mx-5 d-flex align-center" v-else-if="product.type.residencial === true">
                                    <i class="far fa-house mr-5" />Residencial
                                </div>
                                <div class="text mx-5 d-flex align-center" v-else-if="product.type.pyme === true">
                                    <i class="far fa-building mr-5" />PYME
                                </div>
                            </template>
                        </div>


                        <!--Archivado-->
                        <div class="d-flex justify-center align-center">
                            <div v-if="product?.archived && !isEdit" class="d-flex form-group">
                                <div class="input-group mx-10">
                                    <label class="mx-5"><i class="far fa-box-archive mr-5"/>Archivado</label>
                                </div>
                            </div>
                            <div v-if="isEdit" class="d-flex form-group">
                                <div class="input-group mx-10">
                                    <label class="mx-5" for="archived"><i class="far fa-box-archive mr-5"/>Archivado</label>
                                    <input type="checkbox" id="archived" v-model="product.archived"/>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Botones -->
                    <div v-if="canManage('products.edit') && canEdit" class="d-flex" data-gap="10">
                        <div class="custom-button" data-size="regular" v-if="!isEdit" v-on:click.stop="changeEdit()">Editar</div>
                        <div class="d-flex justify-between" data-gap="10" v-else-if="isEdit">
                            <div class="custom-button" data-size="regular" data-bg="rojo" v-on:click.stop="changeEdit(true)">Cancelar</div>
                            <div class="custom-button" data-size="regular" v-on:click.stop="saveProd()">Guardar</div>
                        </div>
                        <div class="custom-button" data-size="regular" data-bg="rojo" data-mode="translucent" v-on:click.stop="deleteProduct">Eliminar</div>
                    </div>
                </div>
            </div>

            <div class="separator"></div>


            <!-- Comentario -->
            <template v-if="product.comment || isEdit">
                <div class="text ml-20 pb-10" data-size="20" data-weight="400">Observaciones</div>

                <p class="break-spaces" data-size="12" v-if="!isEdit">{{ product.comment }}</p>

                <div class="form-group" v-else>
                    <div class="input-group">
                        <textarea v-model="product.comment"></textarea>
                    </div>
                </div>

                <div class="separator"></div>
            </template>


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
                    v-model:commissions="product.commissions"
                    v-model:commissionType="product.commissionType"
                    v-model:seeCommissionTypes="seeCommissionTypes"
                    :marketer="marketer"
                    :product="product"
                    :isEditing="isEdit"
                    :basicData="basicData"
                />
            </div>
        </form>
    </div>
</template>

<script>
import CommissionsTableComponent from "@/components/items/CommissionsTableComponent.vue";
export default {
    name: "AlarmProductComponent",
    props: ["basicData"],
    components: {CommissionsTableComponent},
    data() {
        return {
            marketer: "",
            product: {},
            productDefault: {},
            type: "",
            errors: {},
            isSeeingCreateProduct: false,
            marketerToReset: "",
            indexProduct: "",
            productId: "",
            productTerms: [],
            isEdit: false,
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
    async created() {
        this.productId = this.$route.query.productId;
        this.type = this.$route.query.type;

        // Esperamos a que se cargue el marketer y el producto
        await this.fetchMarketer();
    },
    methods: {
        async fetchMarketer() {
            try {
                const {data} = await axios.get("/api/marketers/" + this.$route.params.id);
                this.marketer = data.marketer;
                this.marketerToReset = {...this.marketer};

                const products = this.marketer?.products?.alarm || [];

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
                this.productSelect = products.findIndex((p) => p?._id === productId || p?._id?.$oid === productId);

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
        hideCustomSelects() {
            this.seeCommissionTypes = false;
        },
        async saveProd() {

            // Comisiones
            const toFloat = (val) => {
                if (val === null || val === undefined || val === "") return null;
                const parsed = parseFloat(val.toString().replace(",", "."));
                return isNaN(parsed) ? null : parsed;
            };

            if (this.product.commissions?.length) {
                this.product.commissions.forEach((commission) => {
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

                const list = this.marketer?.products?.alarm || [];
                const pid = this.product?._id?.$oid || this.product?._id; // productId actual
                const pIdx = list.findIndex((p) => (p?._id?.$oid || p?._id) === pid);

                // Reemplaza el producto dentro del array del marketer (evita refs reactivas raras)
                const productClean = JSON.parse(JSON.stringify(this.product));

                if (pIdx !== -1)
                    this.marketer.products.alarm.splice(pIdx, 1, productClean);
                else
                    this.marketer.products.alarm.push(productClean);


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
        async deleteProduct() {

            let productInd = this.marketer?.products?.alarm?.findIndex(alProd => alProd._id.$oid === this.productId);

            // Se elimina el producto completo
            Swal.fire({
                icon: "warning",
                title: "Se eliminará el producto",
                confirmButtonText: "Eliminar",
                confirmButtonColor: "red",
                showCancelButton: true,
                cancelButtonText: "Cancelar",
                focusCancel: true,
            }).then((res) => {
                if (res.isConfirmed) {
                    try {
                        axios.put("/api/marketers/deleteProductFee", {
                            marketer: this.marketer,
                            productInd: productInd,
                            feeInd: null,
                            deleteProd: true,
                            type: 'alarm'
                        });

                        this.$router.push(`/marketers`);
                    } catch (err) {
                        console.error("Error borrando en servidor:", err);
                        Swal.fire({
                            icon: "error",
                            title: "No se pudo eliminar",
                            text: "Inténtalo de nuevo",
                        });
                    }
                }
            });
        },
        changeEdit(cancel) {
            if (cancel)
                this.product = JSON.parse(JSON.stringify(this.productDefault));

            this.isEdit = !this.isEdit;
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
            if (!this.product.extras) this.product.extras = []

            if (this.product.extras.includes(extra.id.$oid))
                this.product.extras = this.product.extras.filter(extraNow => extraNow !== extra.id.$oid);
            else
                this.product.extras.push(extra.id.$oid);
        }
    },
    computed:{
        userCommission() {
            const user = this.basicData.userLogged;
            if ( this.marketer.createdBy === "65cb57489c2c285441086a43" && this.basicData.userLogged._id !== "65cb57489c2c285441086a43" )
                return `com${user.commissions[this.marketer._id].value}`;
            else if (user.label === "Usuario subdominio" || user._id === "65d47559aa2d0448c308e252" || user._id === "65d48ac808c6cf0254066c42" || user._id === "6617a7ffc4f2475a7a010d32")
                return "comAs";

            return `com${user.commissions[this.marketer._id].value}`;
        },
        extraProductsToSelect() {
            if(!this.marketer?.extras) return []
            return this.marketer.extras.filter(extra => extra.productTypes.includes('sa') && extra.to.product && (!this.product.extras || !this.product.extras.includes(extra.id.$oid)))
        },
        extraProductsSelected() {
            if(!this.marketer?.extras) return []
            return this.marketer.extras.filter(extra => extra.productTypes.includes('sa') && extra.to.product && this.product.extras && this.product.extras.includes(extra.id.$oid))
        }
    }
}

</script>

<style scoped>

</style>

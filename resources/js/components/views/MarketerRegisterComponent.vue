<template>
    <div class="content-white" v-on:click="hideCustomSelects">

        <!--Contenedor padre para distribución-->
        <form v-on:submit.prevent="createMarketer" class="form register-pos" @keydown.enter.prevent>

            <!-- Header -->
            <div>
                <div class="text" data-size="30" data-weight="700" v-if="!isEdit">Crear Comercializadora</div>
                <div class="text" data-size="30" data-weight="700" v-else>Editar Comercializadora</div>
            </div>

            <div class="separator"></div>

            <!-- Contenido -->
            <div>
                <div class="d-flex column">
                    <!-- Comercializadora -->
                    <div class="w-100 form-group no-margin">
                        <div class="contact header-card text" data-size="18"> Comercializadora </div>

                        <div class="input-group py-15 d-flex" data-gap="40">
                            <div class="form-group">
                                <div class="d-flex">

                                    <!--preview img-->
                                    <div class="w-80-px-max h-80-px h-80-px-max round d-flex justify-center align-center hidden" data-border-color="principal" data-round="15">
                                        <!--sin seleccionar-->
                                        <div class="w-150-px text-center" v-if="!imgTemporal"><i class="fal fa-camera text" data-size="40"></i></div>

                                        <!--seleccionada-->
                                        <img v-else class="h-80-px-max w-80-px-max contain-img" :src="imgTemporal" alt="Preview imagen">
                                    </div>


                                    <div class="d-flex align-end ml-10">
                                        <button type="submit" class="custom-button w-105-px mt-10" data-size="medium" data-bg="principal" data-mode="translucent" @click="openInput">Adjuntar <i class="far fa-paperclip"></i></button>

                                        <input type="file" id="marketerImage" style="display: none" accept=".jpg, .jpeg, .png" @change="pickupFile">
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex column" data-gap="5">
                                <input type="text" v-on:focus="delete errors['name']" v-model="marketer.name" style="font-size: 25px" placeholder="Nombre Comercializadora" required class="w-350-px">
                                <span v-if="errors.name" class="error">{{ errors.name }}</span>
                            </div>
                        </div>


                    </div>

                    <div class="separator mx-10"></div>

                    <!-- Excedentes -->
                    <div class="w-100 no-margin">
                        <div class="contact header-card text" data-size="18">Excedentes</div>

                        <div class="d-flex justify-center mt-10" data-gap="20">
                            <div class="form-group">
                                <p class="my-auto"><label>Bateria virtual (€ mes)</label></p>
                                <div class="input-group">
                                    <input data-size="10" v-model="marketer.surplus.virtualBattery" type="text">
                                </div>
                            </div>
                            <div class="form-group">
                                <p class="my-auto"><label>Precio con bateria (€ kWh)</label></p>
                                <div class="input-group">
                                    <input data-size="10" v-model="marketer.surplus.priceWithVB" type="text">
                                </div>
                            </div>
                            <div class="form-group">
                                <p class="my-auto"><label>Precio sin bateria (€ kWh)</label></p>
                                <div class="input-group">
                                    <input data-size="10" v-model="marketer.surplus.priceWithoutVB" type="text">
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Cobertura -->
                    <div class="w-100 no-margin">
                        <div class="contact header-card text" data-size="18">Cobertura</div>

                        <div class="d-flex justify-center mt-10" data-gap="20">

                            <div class="form-group">
                                <p class="my-auto"><label>Compañía</label></p>
                                <div class="input-group">
                                    <select v-model="marketer.coverage">
                                        <option value="">Ninguna</option>
                                        <option value="Movistar">Movistar</option>
                                        <option value="Orange">Orange</option>
                                        <option value="Vodafone">Vodafone</option>
                                    </select>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Retrocomisión -->
                    <div class="w-100 no-margin" v-if="isReminderByMarketerMode">
                        <div class="contact header-card" data-size="18">Retrocomisión</div>

                        <div class="d-flex justify-center mt-10" data-gap="20">

                            <!-- PYME -->
                            <div class="form-group">
                                <p class="my-auto"><label>Pyme (días)</label></p>
                                <div class="input-group">
                                    <input
                                        type="number"
                                        min="1"
                                        max="365"
                                        step="1"
                                        v-model.number="marketer.retrocommission.first"
                                        placeholder="1 - 365"
                                    >
                                </div>
                            </div>

                            <!-- RESIDENCIAL -->
                            <div class="form-group">
                                <p class="my-auto"><label>Residencial (días)</label></p>
                                <div class="input-group">
                                    <input
                                        type="number"
                                        min="1"
                                        max="365"
                                        step="1"
                                        v-model.number="marketer.retrocommission.second"
                                        placeholder="1 - 365"
                                    >
                                </div>
                            </div>

                        </div>

                        <!-- ERROR -->
                        <div v-if="errors.retrocommission" class="w-100 text-center mt-10">
                            <span class="error">{{ errors.retrocommission }}</span>
                        </div>
                    </div>

                    <!--Productos extra-->
                    <div class="w-100 no-margin">
                        <div class="contact header-card two" data-size="18">
                            <p class="text">Productos extra</p>
                            <p @click="addExtraProduct" class="ml-auto pointer text"><i class="far fa-square-plus" aria-hidden="true"/></p>
                        </div>

                        <!--Header-->
                        <div class="extraProducts head mt-10 mx-25 mt-5 mb-15" data-gap="20">
                            <p class="opacity-5 text">Nombre</p>
                            <p class="opacity-5 text">Precio</p>
                            <p class="opacity-5 text">Comisión</p>
                            <p class="opacity-5 text">Aplica</p>
                        </div>

                        <!--Contenido-->
                        <extra-product-component v-for="(extra, i) in extraProducts" :extra="extra" :i="i" :length="extraProducts.length" :isOpened="extraProductOpened === i" :errors="errors ?? {}" @toggle="extraProductOpened = extraProductOpened === i ? null : i" @deleteExtraProduct="deleteExtraProduct"></extra-product-component>
                    </div>

                    <!--Fechas de validez-->
                    <div v-if="marketer.validDates" class="w-100 no-margin">
                        <div class="contact header-card two" data-size="18">
                            <p class="text">Fechas de validez</p>
                        </div>

                        <div class="d-grid" data-column="3">
                            <div v-for="(productType, key) in typeFeed" :key="key" class="d-flex align-center" data-gap="10">
                                <div class="d-flex align-center justify-end mt-20 w-140-px" data-gap="5">
                                    <i :class="`text far fa-${productType.icon}`" />
                                    <p class="text" data-weight="500">{{ productType.title }}</p>
                                </div>
                                <div class="form-group">
                                    <p class="opacity-6 ml-5">Fecha de inicio</p>
                                    <div class="input-group">
                                        <input type="date" v-model="marketer.validDates[key].start" placeholder="Fecha de inicio"></input>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <p class="opacity-6 ml-5">Fecha de fin</p>
                                    <div class="input-group">
                                        <input type="date" v-model="marketer.validDates[key].end" placeholder="Fecha de inicio"></input>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="separator"></div>

            <!--Solo aparece para BTV-->
            <template v-if="basicData && basicData.userLogged._id === '67e26f1dc20d526af10eda92'">
                <div class="my-20">
                    <p class="text" data-size="20" data-weight="400">Comisiones</p>
                </div>

                <!-- BOTONES INTERVALOS -->
                <div class="d-flex my-10" data-gap="15">
                    <button class="custom-button" :data-bg="contractsInterval === 0 ? 'principal' : 'azul'" :data-mode="contractsInterval === 0 ? '' : 'translucent'" data-size="small" data-weight="600" @click="contractsInterval = 0">0 - 5 kW</button>
                    <button class="custom-button" :data-bg="contractsInterval === 5 ? 'principal' : 'azul'" :data-mode="contractsInterval === 5 ? '' : 'translucent'" data-size="small" data-weight="600" @click="contractsInterval = 5">5 - 10 kW</button>
                    <button class="custom-button" :data-bg="contractsInterval === 10 ? 'principal' : 'azul'" :data-mode="contractsInterval === 10 ? '' : 'translucent'" data-size="small" data-weight="600" @click="contractsInterval = 10">10 > kW</button>
                    <button class="custom-button" :data-bg="contractsInterval === 'gas' ? 'principal' : 'azul'" :data-mode="contractsInterval === 'gas' ? '' : 'translucent'" data-size="small" data-weight="600" @click="contractsInterval = 'gas'">Gas</button>
                    <button class="custom-button" :data-bg="contractsInterval === 'maintenance' ? 'principal' : 'azul'" :data-mode="contractsInterval === 'maintenance' ? '' : 'translucent'" data-size="small" data-weight="600" @click="contractsInterval = 'maintenance'">Mantenimiento</button>
                </div>

                <!-- Tabla -->
                <div class="d-flex p-5 commissions-table" data-bg="azul-claro" data-gap="15">
                    <div class="pl-10 w-800-px">Usuario</div>
                    <div class="pl-10 flex-1">1º tramo</div>
                    <div class="pl-10 flex-1">2º tramo</div>
                    <div class="pl-10 flex-1">3º tramo</div>
                </div>

                <div v-for="user in basicData.userList" class="d-flex productSeparator py-5 pl-5 form-group align-center" data-gap="15">
                    <div class="d-flex align-center pl-10 w-800-px text" data-gap="15">
                        <div class="initials" data-style="initials" :class="[{image: user.profileImage}, ' h-40-px-min w-40-px-min w-40-px h-40-px']">
                            <img :src="'/assets/profile_images/' + user.profileImage" class="profile-image">
                        </div>
                        {{`${user.firstName} ${user.lastName}`}}
                    </div>

                    <template v-if="marketer.commissions">
                        <div class="pl-10 flex-1 input-group"><input class="h-20-px" v-model="marketer.commissions[contractsInterval][user._id][0]"></div>
                        <div class="pl-10 flex-1 input-group"><input class="h-20-px" v-model="marketer.commissions[contractsInterval][user._id][1]"></div>
                        <div class="pl-10 flex-1 input-group"><input class="h-20-px" v-model="marketer.commissions[contractsInterval][user._id][2]"></div>
                    </template>
                </div>

                <div class="separator"></div>
            </template>

            <!--Botones-->
            <div class="d-flex justify-between pb-40" v-if="!isUpdatingMarketer">
                <div v-if="isEdit" class="d-flex" data-gap="10">
                    <button class="custom-button" data-size="big" data-bg="rojo" @click.prevent="deleteMarketer">Borrar</button>
                    <div class="d-flex form-group align-center m-0 pointer" @click.prevent="marketer.archived = !marketer.archived">
                        <div class="input-group align-center mx-10 h-100 pointer">
                            <label class="mx-5 select-none pointer" for="archived" data-size="14"><i class="far fa-box-archive mr-5"/>Archivada</label>
                            <input type="checkbox" class="pointer w-16-px h-16-px" id="archived" :checked="marketer.archived" @click.stop="marketer.archived = !marketer.archived"/>
                        </div>
                    </div>
                </div>
                <div v-else></div>
                <div class="d-flex" data-gap="10">
                    <button class="custom-button" data-size="big" data-bg="rojo" v-on:click.prevent="actionLink('/marketers')">Cancelar</button>
                    <button class="custom-button" v-if="!isReadOnly" data-size="big" v-on:click.prevent="saveMarketer()">Guardar <i class="fas fa-chevron-right ml-10"></i></button>
                </div>
            </div>

            <div class="btn-part" v-else>
                <button class="custom-button" data-size="big"><i class="fa-solid fa-spinner-third fa-spin mr-5"></i> Espere un momento</button>
            </div>
        </form>
    </div>
</template>


<script>
export default {
    name: "MarketerRegisterComponent",
    props:['basicData'],
    data(){
        return {
            marketer: {
                surplus: {},
                retrocommission: {
                    first: '',
                    second: ''
                },
                extras: [],
                coverage: ''
            },
            marketers: [],
            marketerImage: null,
            errors:{},
            isUpdatingMarketer: false,
            isEdit : false,
            imgTemporal: null,
            selectFeesActive: false,
            contractsInterval: 0,
            extraProductOpened: null,
            typeFeed: {
                electricity : {
                    title: 'Luz',
                    icon: 'bolt'
                },
                gas : {
                    title: 'Gas',
                    icon: 'fire-flame-simple'
                },
                dual: {
                    title: 'Dual',
                    icon: ''
                },
                telephony: {
                    title: 'Telefonía',
                    icon: 'phone'
                }
                ,
                alarm: {
                    title: 'Alarmas',
                    icon: 'bell-on'
                },
                selfSupply: {
                    title: 'Autoconsumo',
                    icon: 'solar-panel'
                }
            },
        }
    },
    created() {
        this.fetchAllMarketers().then(() => {
            if (this.$route.query.id) {
                this.fetchMarketer();
            }else{
                this.initializeMarketer();
            }
        });
    },
    methods:{
        async fetchAllMarketers() {
            await axios.get(`/api/marketers`)
                .then((res) => {
                    this.marketers = res.data.marketers;
                })
                .catch(err => console.log(err))
        },
        fetchMarketer() {
            this.marketer = this.marketers.find(marketer => marketer._id === this.$route.query.id);

            // Aseguramos estructuras
            if (!this.marketer.retrocommission || typeof this.marketer.retrocommission !== 'object') {
                this.marketer.retrocommission = { first: '', second: '' };
            }
            if (!this.marketer.surplus || typeof this.marketer.surplus !== 'object') {
                this.marketer.surplus = {};
            }

            // Asegurar que existe validDates
            this.marketer.validDates = this.marketer.validDates || {}

            Object.keys(this.typeFeed).forEach(key => {
                // Si no existe ese tipo, lo añadimos
                if (!this.marketer.validDates[key]) {
                    this.marketer.validDates[key] = {
                        start: null,
                        end: null
                    }
                }
            });

            // 👇 MAPEO: de lo que hay en BD (surplus.rCommission*) a los selects
            this.marketer.retrocommission.first  = this.marketer.surplus.rCommissionPyme || '';
            this.marketer.retrocommission.second = this.marketer.surplus.rCommissionRes  || '';

            this.addCommissions();
            this.imgTemporal = `/assets/marketers_logo/${this.marketer.logo}`;
            this.isEdit = true;
        },
        initializeMarketer(){
            this.marketer = {
                name: '',
                products: {
                    electricity: [],
                    gas: []
                },
                fees: {
                    electricity: [
                        {id: null, name : "Tarifa 2.0TD"},
                        {id: null, name : "Tarifa 3.0TD"},
                        {id: null, name : "Tarifa 6.1TD"},
                    ],
                    gas: [
                        {id: null, name : "Tarifa RL1"},
                        {id: null, name : "Tarifa RL2"},
                        {id: null, name : "Tarifa RL3"},
                        {id: null, name : "Tarifa RL4"},
                        {id: null, name : "Tarifa RL5"},
                        {id: null, name : "Tarifa RL6"},
                    ]
                },
                surplus: {
                    virtualBattery: null,
                    priceWithVB: null,
                    priceWithoutVB: null,
                },

                retrocommission: {
                    first: '',
                    second: ''
                },

                extras: []
            };
            this.addCommissions();
        },
        actionLink(route) {
            this.$router.push(route)
        },
        selectFees(type){
            this.selectFeesActive = type;
        },
        async saveMarketer() {

            this.errors = {};
            this.isUpdatingMarketer = true;
            this.extraProductOpened = null;

            if (this.marketer.name === '') {
                this.errors.name = 'El campo nombre es obligatorio';
                this.isUpdatingMarketer = false;
                return;
            }

            if (this.marketers.some(marketer => marketer.name === this.marketer.name && marketer._id !== this.marketer._id)) {
                this.errors.name = 'Ya existe una comercializadora con ese nombre';
                this.isUpdatingMarketer = false;
                return;
            }

            // 👇 SOLO si el subdominio está en modo comercializadora
            if (this.isReminderByMarketerMode) {

                const f = Number(this.marketer.retrocommission?.first);
                const s = Number(this.marketer.retrocommission?.second);

                // Validación obligatoria + rango 1-365
                if (
                    !f || !s ||
                    isNaN(f) || isNaN(s) ||
                    f < 1 || f > 365 ||
                    s < 1 || s > 365
                ) {
                    this.errors.retrocommission = 'Pyme y Residencial deben estar entre 1 y 365 días.';

                    Swal.fire({
                        icon: 'error',
                        title: 'Valor inválido',
                        text: 'Debe indicar días entre 1 y 365 para Pyme y Residencial.',
                    });

                    this.isUpdatingMarketer = false;
                    return;
                }

                // Aseguramos estructura surplus
                if (!this.marketer.surplus || typeof this.marketer.surplus !== 'object') {
                    this.marketer.surplus = {};
                }

                // Guardamos como días
                this.marketer.surplus.rCommissionPyme = f;
                this.marketer.surplus.rCommissionRes  = s;
            }

            //Compruebo los productos extra
            if (this.extraProducts?.length) {

                // Limpie errores previos de extras
                Object.keys(this.errors)
                    .filter(k => k.startsWith('extras'))
                    .forEach(k => delete this.errors[k])

                let hasErrors = false

                for (const [i, extra] of this.extraProducts.entries()) {

                    this.errors['extras' + i] = {}

                    // Nombre
                    if (!extra.name?.trim()) {
                        this.errors['extras' + i].name = 'El nombre del producto extra es obligatorio'
                        hasErrors = true
                    }

                    // Aplicado a
                    const anySelected = Object.values(extra.to || {}).some(Boolean)
                    if (!anySelected) {
                        this.errors['extras' + i].to = 'Tiene que seleccionarse al menos 1 opción'
                        hasErrors = true
                    }

                    // Si no tiene errores, elimino el objeto vacío para no ensuciar
                    if (Object.keys(this.errors['extras' + i]).length === 0) {
                        delete this.errors['extras' + i]
                    }
                }

                if (hasErrors) {
                    this.isUpdatingMarketer = false
                    return
                }
            }

            let formData = new FormData();
            formData.append('marketer', JSON.stringify(this.marketer));
            formData.append('image', this.marketerImage);

            const url = this.isEdit
                ? `/api/marketers/${this.marketer._id}`
                : `/api/marketers`;

            await axios.post(url, formData)
                .then(() => {
                    Swal.fire({
                        icon: "success",
                        title: this.isEdit ? "Se ha guardado correctamente" : "Se ha creado correctamente",
                    });
                    this.actionLink('/marketers')
                })
                .catch(() => {
                    Swal.fire({
                        icon: "error",
                        title: "Ha ocurrido un error",
                        text: this.isEdit ? "No se ha guardado correctamente" : "No se ha creado correctamente"
                    });
                })
                .finally(() => {
                    this.isUpdatingMarketer = false;
                })
        },
        deleteMarketer(){
            const data = {
                marketer: this.marketer.name,
                userList: this.basicData.userList.map((user) => user._id),
            }

            axios.post('/api/marketers/haveMarketerOrders', data).then((res) => {
                let text = "¿Estás seguro que quieres borrar la comercializadora?";

                if(res.data > 0){
                    text = `Hay ${res.data} contratos con esta comercializadora. ¿Estás seguro que quieres borrarla?`;
                }

                Swal.fire({
                    icon: 'warning',
                    title: 'Borrar comercializadora',
                    text: text,
                    showCancelButton: true,
                    confirmButtonText: 'Borrar',
                    cancelButtonText: 'Cancelar',
                }).then((res) => {
                    if(res.isConfirmed){
                        axios.delete(`/api/marketers/${this.marketer._id}`).then(() => {
                            Swal.fire({
                                icon: 'success',
                                title: 'Comercializadora borrada',
                                text: 'La comercializadora ha sido borrada correctamente',
                                timer: 1500,
                            });
                            this.actionLink('/marketers')
                        })
                    }
                })
            })
        },
        openInput(){
            $('#marketerImage').click()
        },
        pickupFile() {
            let input = $('#marketerImage');

            if (input.prop('files') && input.prop('files').length > 0) {
                this.marketerImage = input.prop('files')[0];
                this.imgTemporal = URL.createObjectURL(this.marketerImage);
            }
        },
        addCommissions(){
            if (this.basicData.userList && this.basicData.userSubdomain._id === '67e26f1dc20d526af10eda92' ) {
                if (!this.marketer.commissions) {
                    this.marketer.commissions = { 0: {}, 5: {}, 10: {}, gas: {}, maintenance: {} }
                }

                for (const interval in this.marketer.commissions) {
                    const userCommissions = {};
                    this.basicData.userList.forEach(user => {
                        userCommissions[user._id] = this.marketer.commissions[interval][user._id] || [null,null,null];
                    })
                    this.marketer.commissions[interval] = userCommissions;
                }
            }
        },
        addExtraProduct(){
            //Si no tuviera se crea
            if(!this.marketer.extras) this.marketer.extras = [];

            this.marketer.extras.push(
                {
                    temporalId: 'id-' + Math.random().toString(36).substr(2, 9),
                    name: '',
                    price: {
                        amount: 0,
                        unit: 'month'
                    },
                    commission: 0,
                    to: {
                        pyme: false,
                        residential: false,
                        product: false
                    },
                    productTypes: [],
                    createdAt: new Date()
                }
            );

            //Dejo abierto el último que he creado que es el 0 ya que salen ordenados por fecha
            this.extraProductOpened = 0;
        },
        deleteExtraProduct(id){
            let index = this.marketer.extras.findIndex(extra => (extra.id ? extra.id : extra.temporalId)  === id);
            this.marketer.extras.splice(index, 1);
            this.extraProductOpened = null;
        }
    },

    computed:{
        isReminderByMarketerMode () {
            const sub = this.basicData && this.basicData.userSubdomain;
            return !!(sub && sub.settings && sub.settings.orderRenewalReminderByMarketer);
        },
        isReadOnly(){
            return !this.basicData.userLogged ||
                this.basicData.userLogged.permissions.includes('READONLY')
        },
        isInputsDisabled(){
            return !this.basicData.userLogged ||
                this.basicData.userLogged.permissions.includes('READONLY')
        },
        extraProducts(){
            if (!this.marketer.extras) return [];

            return [...this.marketer.extras].sort((a, b) => {

                const dateA = new Date(a.createdAt);
                const dateB = new Date(b.createdAt);

                if (dateA.getTime() !== dateB.getTime()) {
                    return dateB - dateA;
                }

                return (a.name || '').localeCompare(b.name || '', 'es', { sensitivity: 'base' });

            });
        }
    }
}
</script>

<style scoped>
</style>

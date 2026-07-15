<template>
    <div :class="{ 'log-highlight': highlightLogId && log._id === highlightLogId }">
        <!--Preview-->
        <div class="d-flex align-center justify-between pointer" @click="log.event !== 'delete' && (isOpenBox = !isOpenBox)">


            <!--Mensaje-->
            <p class="text">
                <!--Si es comparativa y ha salido errónea-->
                <i v-if="log.type === 'comparatives' && log.metadata.status === 'error'" class="fas fa-exclamation" data-color="rojo" data-size="18"></i>
                {{ logMessage }}
            </p>

            <!--Fecha y desplegable-->
            <div class="d-flex p-10" data-gap="10">
                <!--Fecha-->
                <p class="text">{{ prettyDate }}</p>

                <!--Desplegable-->
                <p class="text" v-if="log.event !== 'delete'"><i class="far" :class="isOpenBox? 'fa-chevron-up' :'fa-chevron-down'"></i></p>
            </div>
        </div>

        <!--Box abierta-->
        <div v-if="isOpenBox" class="d-flex align-center justify-between p-10">

            <!--Comparativa-->
            <div v-if="log.type === 'comparatives'" class="d-grid w-100" data-column="2">

                <!--Si ha sido success-->
                <template v-if="log.metadata.status === 'success'">
                    <!--Input-->
                    <div>

                        <!-- CUPS -->
                        <p v-if="log.metadata.CUPS"><span data-weight="600">CUPS:</span> {{ log.metadata?.CUPS }}</p>

                        <!-- Total -->
                        <p><span data-weight="600">Total:</span> {{ Number(log.metadata?.total).toFixed(2) }}€</p>


                        <!-- Potencias contratadas -->
                        <div class="my-20" v-if="power['consumed']">
                            <p data-weight="600">Potencia contratada (kW)</p>
                            <div class="d-grid px-40" data-gap="10" data-column="6">
                                <div v-for="(value, key) in Object.values(power['consumed'])" :key="key">
                                    <p>P{{ key + 1 }}</p>
                                    <p class="ellipsis">{{ value ?? 0 }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Energía consumida -->
                        <div class="my-20" v-if="consumption['consumed']">
                            <p data-weight="600">Energía consumida (kWh)</p>
                            <div class="d-grid px-40" data-gap="10" data-column="6">
                                <div v-for="(value, key) in Object.values(consumption['consumed'])" :key="key">
                                    <p>P{{ key + 1 }}</p>
                                    <p class="ellipsis">{{ value ?? 0 }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Precios de potencia -->
                        <div class="my-20" v-if="power['prices']">
                            <p data-weight="600">Precios de potencia (kW/d)</p>
                            <div class="d-grid px-40" data-gap="10" data-column="6">
                                <div v-for="(value, key) in Object.values(power['prices'])" :key="key">
                                    <p>P{{ key + 1 }}</p>
                                    <p class="ellipsis">{{ value ?? 0 }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Precios de energía -->
                        <div class="my-20" v-if="consumption['prices']">
                            <p data-weight="600">Precios de energía (kWh)</p>
                            <div class="d-grid px-40" data-gap="10" data-column="6">
                                <div v-for="(value, key) in Object.values(consumption['prices'])" :key="key">
                                    <p>P{{ key + 1 }}</p>
                                    <p class="ellipsis">{{ value ?? 0 }}</p>
                                </div>
                            </div>
                        </div>


                        <!--Excedentes-->
                        <div class="my-20">
                            <p data-weight="600">Excedentes (€ kWh)</p>
                            <div class="d-grid px-40" data-gap="10" data-column="3">
                                <!--Batería virtual-->
                                <div>
                                    <p>Batería virtual</p>
                                    <p>{{ log.metadata.inputType === 'bill' ? 0 : (log.metadata?.input?.manual?.surplus?.virtualBattery ?? 0) }}</p>
                                </div>

                                <!--Cantidad-->
                                <div>
                                    <p>Cantidad</p>
                                    <p>{{ log.metadata.inputType === 'bill' ? (log.metadata?.input?.pdf?.otros?.kwh_excedentes ?? 0) : (log.metadata?.input?.manual?.surplus?.amount ?? 0) }}</p>
                                </div>

                                <!--Precio-->
                                <div>
                                    <p>Precio</p>
                                    <p>{{ log.metadata.inputType === 'bill' ? (log.metadata?.input?.pdf?.otros?.precio_excedentes ?? 0) : (log.metadata?.input?.manual?.surplus?.price ?? 0) }}</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!--Output-->
                    <div>

                        <!--Ofertas-->
                        <p class="" data-size="22" data-weight="700">Ofertas</p>


                        <!--Ahorro-->
                        <div class="px-30" data-gap="20">
                            <p>Ahorro</p>
                            <div class="dashboard-card column p-16 mb-10 w-100 px-30" data-bg="blanco" v-for="savingOffer in log.metadata.output.total.slice(0,2)">

                                <div class="d-flex justify-between align-center">

                                    <div class="d-flex">
                                        <!--Icono-->
                                        <img class="h-25-px-max w-50-px-max contain-img mr-10" v-if="savingOffer.marketer !== null"
                                             :src="`/assets/marketers_logo/${marketers.find(marketer => marketer._id === savingOffer.marketerId).logo}`"
                                             alt="logo" />

                                        <!--Comercializadora-->
                                        <p>{{ savingOffer.product }}</p>
                                    </div>



                                    <!--Precio-->
                                    <p>{{ (log.metadata.total - savingOffer.total).toFixed(2) }} €</p>
                                </div>

                            </div>
                        </div>


                        <!--Comisión-->
                        <div class="px-30" data-gap="20">
                            <p>Comisión</p>
                            <div class="dashboard-card column p-16 mb-10 w-100 px-30" data-bg="blanco" v-for="commissionOffer in log.metadata.output.commission.slice(0,2)">

                                <div class="d-flex justify-between align-center">

                                    <div class="d-flex">
                                        <!--Icono-->
                                        <img class="h-25-px-max w-50-px-max contain-img mr-10" v-if="commissionOffer.marketer !== null"
                                             :src="`/assets/marketers_logo/${marketers.find(marketer => marketer._id === commissionOffer.marketerId).logo}`"
                                             alt="logo" />

                                        <!--Comercializadora-->
                                        <p>{{ commissionOffer.product }}</p>
                                    </div>



                                    <!--Precio-->
                                    <p>{{ parseStringToNumber(commissionOffer.commission).toFixed(2) }} €</p>
                                </div>

                            </div>
                        </div>


                        <!--Eficiencia-->
                        <div class="px-30" data-gap="20">
                            <p>Eficiencia</p>
                            <div class="dashboard-card column p-16 mb-10 w-100 px-30" data-bg="blanco" v-for="savingOffer in log.metadata.output.efficiency.slice(0,2)">

                                <div class="d-flex justify-between align-center">

                                    <div class="d-flex">
                                        <!--Icono-->
                                        <img class="h-25-px-max w-50-px-max contain-img mr-10" v-if="savingOffer.marketer !== null"
                                             :src="`/assets/marketers_logo/${marketers.find(marketer => marketer._id === savingOffer.marketerId).logo}`"
                                             alt="logo" />

                                        <!--Comercializadora-->
                                        <p>{{ savingOffer.product }}</p>
                                    </div>


                                    <!--Precio-->
                                    <p>{{ (log.metadata.total - savingOffer.total).toFixed(2) }} €</p>
                                </div>

                            </div>
                        </div>
                    </div>
                </template>

                <!--Si ha salido errónea-->
                <template v-else>
                    <!--Mensaje-->
                    <p><span data-weight="600">Mensaje de error:</span> {{ log.metadata.messageError }}</p>

                    <!--Parte código ( solo para desarrollador )-->
                    <p v-if="log.metadata.codePart && basicData.userLogged && (basicData.userLogged._id === '65cb57489c2c285441086a43' || basicData.userLogged._id === '65d704c63d2a9cbfd79e549a')"><span data-weight="600">Parte código:</span> {{ log.metadata.codePart }}</p>

                </template>

            </div>
            <!-- Invoice checker -->
        <div v-if="log.type === 'invoice_checker'" class="w-100">

            <!-- ✔️ CHECK OK -->
            <template v-if="log.event === 'check_ok'">

                <p><strong>CUPS:</strong> {{ log.metadata.cups }}</p>

                <p v-if="log.metadata.period">
                    <strong>Periodo:</strong>
                    {{ log.metadata.period.from }} → {{ log.metadata.period.to }}
                </p>

                <!-- GRID PRINCIPAL -->
                <div class="d-grid w-100 my-20" data-column="2" data-gap="40">

                    <!-- FACTURA -->
                    <div>
                        <p class="title-left">Factura</p>

                        <template
                            v-for="(block, section) in log.metadata.checked_data.invoice"
                            :key="'inv-' + section"
                        >
                            <p class="section-title">
                                {{ sectionLabel(section) }}
                            </p>

                            <div class="d-grid px-40" data-column="6" data-gap="10">
                                <div v-for="(val, key) in block" :key="key">
                                    {{ formatPeriod(key).toUpperCase() }}
                                    <p
                                        :class="valueDiffClass(
                                            val,
                                            log.metadata.checked_data.check?.[section]?.[key]
                                        )"
                                    >
                                        {{ formatValue(val) }}
                                    </p>
                                </div>
                            </div>

                            <div class="my-15"></div>
                        </template>
                    </div>

                    <!-- COMPROBACIÓN -->
                    <div>
                        <p class="title-right">Comprobación</p>

                        <template
                            v-for="(block, section) in log.metadata.checked_data.check"
                            :key="'chk-' + section"
                        >
                            <p class="section-title">
                                {{ sectionLabel(section) }}
                            </p>

                            <div class="d-grid px-40" data-column="6" data-gap="10">
                                <div v-for="(val, key) in block" :key="key">
                                    <p>{{ formatPeriod(key).toUpperCase() }}</p>
                                    <p
                                        :class="valueDiffClass(
                                            log.metadata.checked_data.invoice?.[section]?.[key],
                                            val
                                        )"
                                    >
                                        {{ formatValue(val) }}
                                    </p>
                                </div>
                            </div>

                            <div class="my-15"></div>
                        </template>
                    </div>

                </div>

                <!-- RESUMEN -->
                <div class="summary-box mt-30">

                    <p class="summary-title-center">Resumen</p>

                    <div class="summary-grid">

                        <div class="summary-item">
                            <span class="summary-label">Potencias</span>
                            <span class="summary-value">
                                {{ log.metadata.summary?.potencias ?? 0 }}
                            </span>
                        </div>

                        <div class="summary-item">
                            <span class="summary-label">Consumos</span>
                            <span class="summary-value">
                                {{ log.metadata.summary?.consumos ?? 0 }}
                            </span>
                        </div>

                        <div class="summary-item">
                            <span class="summary-label">Precios</span>
                            <span class="summary-value">
                                {{ log.metadata.summary?.precios ?? 0 }}
                            </span>
                        </div>

                        <div
                            class="summary-item total"
                            :class="log.metadata.summary?.total === 0 ? 'ok' : 'error'"
                        >
                            <span class="summary-label">Total</span>
                            <span class="summary-value">
                                {{ log.metadata.summary?.total ?? 0 }}
                            </span>
                        </div>

                    </div>
                </div>


            </template>

            <template v-else-if="log.event === 'check_error'">
                <p data-color="rojo">
                    <strong>Error:</strong> {{ log.metadata.error }}
                </p>
            </template>

        </div>

            <!--Demás-->
            <!--TODO: Restaurar botón para comparativas-->
            <div v-else-if="log.type !== 'comparatives'" class="w-100 scroll-x">


                <div v-if="log.type === 'ev_charger_budget'" class="w-100">
                    <div class="d-grid" data-column="2" data-gap="20">
                        <div>
                            <p class="section-title">Cliente</p>
                            <p><strong>Nombre:</strong> {{ log.metadata.name || '—' }}</p>
                            <p><strong>Teléfono:</strong> {{ log.metadata.phone || '—' }}</p>
                            <p><strong>Email:</strong> {{ log.metadata.email || '—' }}</p>
                        </div>
                        <div>
                            <p class="section-title">Cargador</p>
                            <p><strong>Modelo:</strong> {{ log.metadata.chargerModel || '—' }}</p>
                            <p><strong>Potencia:</strong> {{ log.metadata.chargerPower || '—' }}</p>
                            <p><strong>Instalación:</strong> {{ log.metadata.installationType || '—' }}</p>
                            <p><strong>Metros cable:</strong> {{ log.metadata.cableMeters ?? 0 }} m</p>
                            <p><strong>Fotovoltaica:</strong> {{ log.metadata.hasPhotovoltaic ? 'Sí' : 'No' }}</p>
                            <p v-if="log.event === 'progress'"><strong>Se quedó en:</strong> {{ log.metadata.stepLabel }}</p>
                        </div>
                    </div>
                    <div v-if="log.metadata.totals && log.event === 'generate'" class="summary-box mt-30">
                        <p class="summary-title-center">Totales</p>
                        <div class="summary-grid">
                            <div class="summary-item">
                                <span class="summary-label">Base imponible</span>
                                <span class="summary-value">{{ Number(log.metadata.totals.subtotal ?? 0).toFixed(2) }} €</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">IVA</span>
                                <span class="summary-value">{{ Number(log.metadata.totals.vat ?? 0).toFixed(2) }} €</span>
                            </div>
                            <div class="summary-item total ok">
                                <span class="summary-label">Total</span>
                                <span class="summary-value">{{ Number(log.metadata.totals.total ?? 0).toFixed(2) }} €</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!--Actualizar-->
                <div v-if="log.event === 'update' && Object.keys(this.log.changes).length > 0">

                    <!--Cada uno de los campos normales editados-->
                    <table class="custom-table" data-gap="15" v-if="Object.keys(normalChanges).length">
                        <thead>
                            <tr>
                                <th>Campo</th>
                                <th>Antes</th>
                                <th>Después</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(change, field) in normalChanges" :key="field">
                                <td class="text-start">{{ fieldsNames[field] ?? field }}</td>
                                <td class="text-start">{{ getField(field, change.before) }}</td>
                                <td class="text-start">{{ getField(field, change.after) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!--Por array-->
                    <table class="custom-table" data-gap="15" v-if="Object.keys(arrayChanges).length">
                        <thead>
                            <tr>
                                <th>Campo</th>
                                <th>Añadido</th>
                                <th>Eliminado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(change, field) in arrayChanges" :key="field">
                                <td class="text-start">{{ fieldsNames[field] ?? field }}</td>

                                <!--Añadidos-->
                                <td class="text-start">
                                    <div v-for="added in change.added" :key="added.id || added.title">
                                        {{ added.title }}
                                    </div>
                                </td>

                                <!--Eliminados-->
                                <td class="text-start">
                                    <div v-for="removed in change.removed" :key="removed.id || removed.title">
                                        {{ removed.title }}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                </div>

                <!--Creación/Eliminación-->
                <div v-else>

                    <!--Contrato-->
                    <div class="d-grid" data-column="2" v-if="log.type === 'contracts'">
                        <!--CUPS-->
                        <p v-if="log.metadata.CUPS"><span data-weight="600">CUPS:</span> {{ log.metadata.CUPS }}</p>
                    </div>

                    <!--Cuenta/oportunidad-->
                    <div v-else-if="log.type === 'accounts' && log.type === 'opportunities'">
                        <!--CIF-->
                        <p><span data-weight="600">CIF:</span> {{ log.metadata.CIF }}</p>
                    </div>



                </div>

                <!--Visualizar-->
                <div v-if="log.event !== 'delete'" class="w-100 d-flex justify-center mt-15 mb-35" data-gap="10">
                    <button @click="newWindow(getLogLink(log))" class="custom-button" data-size="medium" data-bg="azul">Ver {{ categories[log.type] }}</button>

                    <!--Si es contrato y tiene cuenta vinculada también-->
                    <button v-if="log.type === 'contracts' && log.metadata?.account" @click="newWindow('/accounts/' + log.metadata.account)" class="custom-button" data-size="medium" data-bg="azul">Ver cuenta relacionada</button>
                </div>
            </div>
        </div>

        <!--Separator-->
        <div v-if="(logInd + 1) < logs.length" class="separator m-0"></div>
    </div>
</template>

<script>
    export default{
        name: "ToolsComponent",
        props:['basicData', 'log', 'logInd', 'logs', 'marketers', 'highlightLogId'],
        data(){
            return {
                isOpenBox: false,
                categories: {
                    contracts: 'el contrato',
                    accounts: 'la cuenta',
                    opportunities: 'la oportunidad',
                    comparatives: 'una comparativa',
                    invoice_checker: 'una revisión de factura',
                    ev_charger_budget: 'un presupuesto de cargador',

                },
                fieldsNames:{
                    //Contratos
                    'name' : 'Nombre',
                    'direc' : 'Dirección',
                    'zip' : 'Cod. postal',
                    'town' : 'Población',
                    'province' : 'Provincia',
                    'observations': 'Observaciones',
                    'source' : 'Fuente de venta',
                    'processingDate' : 'Fec. de tramitación',
                    'activationDate' : 'Fec. de activación',
                    'lowDate' : 'Fec. de baja',
                    'liquidationStatus' : 'Estado de liquidación',
                    'productType' : 'Tipo de producto',
                    'marketer' : 'Comercializadora',
                    'fee' : 'Tarifa',
                    'product' : 'Producto',
                    'commissions.breakdown[0].commission' : 'Comisión de venta',
                    'commissions.subdomain' : 'Comisión subdominio',
                    'decommissions.breakdown[0].commission': 'Decomisión de venta',
                    'decommissions.subdomain': 'Decomisión subdominio',
                    'consumption' : 'Consumo',
                    'docs' : 'Documentos',
                    'verifications' : 'Verificaciones',
                    'renewalDate': 'Fec. de renovación',
                    'renewalOption': 'Opc. de renovación',
                    'hiredPotency': 'Potencia contratada',
                    'currentPotencyVerification': 'Pot. actual',
                    'requestedPotencyVerification': 'Pot. solicitada',
                    'status' : 'Estado',
                    //Cuentas
                    'phone': 'Teléfono',
                    'email': 'Correo',
                    'address': 'Dirección',
                    //Oportunidad
                    'order.productType': 'Tipo de producto contrato',
                    'order.marketer': 'Comercializadora contrato',
                    'order.fee': 'Tarifa contrato',
                    'order.product': 'Producto contrato',
                    //Otros
                    'customFields': 'Campos personalizados'
                },
                productTypes: [
                    {
                        code: 'cl',
                        title: 'Contrato de luz',
                        verificationsAvailable: ['nw', 'pc', 'tc', 'vb', 'mc', 're']
                    },
                    {
                        code: 'cg',
                        title: 'Contrato de gas',
                        verificationsAvailable: ['nw', 'pc', 'tc', 'vb', 'mc', 're']
                    },
                    {
                        code: 'a',
                        title: 'Autoconsumo',
                        productToSee: 'n',
                        verificationsAvailable: []
                    },
                    {
                        code: 'bc',
                        title: 'Bateria de condensadores',
                        productToSee: 'n',
                        verificationsAvailable: []
                    },
                    {
                        code: 'ce',
                        title: 'Coche eléctrico',
                        productToSee: 'n',
                        verificationsAvailable: []
                    },
                    {
                        code: 'c',
                        title: 'Contador',
                        productToSee: 'n',
                        verificationsAvailable: []
                    },
                    {
                        code: 'i',
                        title: 'Iluminación',
                        productToSee: 'n',
                        verificationsAvailable: []
                    },
                    {
                        code: 'ct',
                        title: 'Contrato de telefonía',
                        productToSee: 'ct',
                        verificationsAvailable: ['nw', 'tc']
                    },
                    {
                        code: 'sp',
                        title: 'Sin producto',
                        productToSee: 'sp',
                        verificationsAvailable: []
                    },
                ],
                liquidationStatuses: [
                    {
                        code: 'nl',
                        title: 'No liquidado'
                    },
                    {
                        code: 'al',
                        title: 'Liquidado agente'
                    },
                    {
                        code: 'cl',
                        title: 'Liquidado comerc.'
                    },
                    {
                        code: 'tl',
                        title: 'Total liquidado'
                    },
                    {
                        code: 'ad',
                        title: 'Decomisionado agente'
                    },
                    {
                        code: 'md',
                        title: 'Decomisionado comercializadora'
                    },
                    {
                        code: 'tm',
                        title: 'Total decomisionado'
                    },
                ]
            }
        },
        created(){
            //Si es el log al que se quiere saltar desde la oportunidad, lo abrimos
            if (this.highlightLogId && this.log._id === this.highlightLogId)
                this.isOpenBox = true;
        },
        methods:{
            normalizeNumber(value, decimals = 4) {
            if (value === null || value === undefined || value === '') return 0

            const n = parseFloat(
                typeof value === 'string' ? value.replace(',', '.') : value
            )

            return Number.isNaN(n) ? 0 : Number(n.toFixed(decimals))
            },

            areDifferent(a, b, tolerance = 0.0001) {
            const na = this.normalizeNumber(a)
            const nb = this.normalizeNumber(b)
            return Math.abs(na - nb) > tolerance
            },

            formatPeriod(key) {
                if (!key) return "";
                return key.replace(/^Pp/i, "P");
            },

            valueDiffClass(a, b) {
            // tolerancias por defecto
            const tolerance = 0.0001

            const na = this.normalizeNumber(a)
            const nb = this.normalizeNumber(b)

            // ambos cero o vacíos
            if (na === 0 && nb === 0) return 'val-neutral'

            return this.areDifferent(na, nb, tolerance)
                ? 'val-diff'
                : 'val-ok'
            },

             sectionLabel(section) {
        const map = {
            potencias_contratadas: 'Potencia contratada',
            energia_consumida: 'Energía consumida',
            precios_potencias: 'Precio potencia',
            precios_energia: 'Precio energía',
        };
        return map[section] || section;
    },

            formatValue(val) {
                if (val === null || val === undefined || val === '—') return '0';
                if (typeof val === 'number') return val.toFixed(4);
                return val;
            },
            parseStringToNumber(number) {
                if (typeof number === "number") {
                    return number;
                } else if (typeof number === "string") {
                    return number === "" ? 0 : parseFloat(number.replace(",", "."));
                } else {
                    return 0
                }
            },
            getField(field, value){

                //Si es un estado lo saco del subdominio
                if (field === 'status' || field === 'statuses')
                    return this.basicData.userSubdomain.statuses.find(status => status.code === value)?.title ?? 'Estado no encontrado';
                else if(field === 'productType')
                    return this.productTypes.find(productType => productType.code === value)?.title ?? 'Tipo de producto no encontrado';
                else if(field === 'liquidationStatus')
                    return this.liquidationStatuses.find(statusLiquidation => statusLiquidation.code === value)?.title ?? 'Estado de liquidación no encontrado';
                else
                    return value;

            },
            getLogLink(log) {
                const id = log.metadata._id;

                if (log.type === 'contracts') {
                    return `/contracts?_id=${id}`;
                }

                return `/${log.type}/${id}`;
            },
            actionLink(route){
                this.$router.push(route)
            },
            newWindow(route){
                window.open(route, '_blank');
            }
        },
        computed:{
            userResponsible(){
                if (!this.basicData.userList) return null

                //Compruebo si es el mismo que inicia sesión
                if (this.log.createdBy === this.basicData.userLogged._id) return this.basicData.userLogged

                //Busco en el listado de usuarios por debajo
                return this.basicData.userList.find((user) => user._id === this.log.createdBy) ?? null
            },
            logMessage() {

                let user = this.userResponsible
                    ? `${this.userResponsible.firstName} ${this.userResponsible.lastName}`
                    : 'Un usuario';


                let actions = {
                    create: () =>
                        `ha creado ${this.categories[this.log.type]} ${this.log.metadata?.name ?? ''}`,

                    delete: () =>
                        `ha eliminado ${this.categories[this.log.type]} ${this.log.metadata?.name ?? ''}`,

                    update: () => {
                        let changedFields = Object.keys(this.log.changes ?? {}).length;
                        return `ha modificado ${changedFields} campo${changedFields > 1 ? 's' : ''} de ${this.categories[this.log.type]} ${this.log.metadata?.name ?? ''}`;
                    },

                    generate: () => {
                        if (this.log.type === 'ev_charger_budget') {
                            const model = this.log.metadata?.chargerModel ?? '';
                            const name  = this.log.metadata?.name ?? '';
                            return `ha generado un presupuesto de cargador${name ? ` para ${name}` : ''}${model ? ` · ${model}` : ''}`;
                        }
                        if (this.log.metadata.inputType === 'bill')
                            return `ha generado una comparativa de tipo FACTURA con nombre ${this.log.metadata?.name ?? ''}`;

                        if (this.log.metadata.inputType === 'cups')
                            return `ha generado una comparativa de tipo CUPS ${this.log.metadata?.CUPS ?? ''}`;

                        if (this.log.metadata.inputType === 'manual')
                            return `ha generado una comparativa de tipo DATOS`;
                    },

                    check_ok: () => {
                        const total = this.log.metadata?.summary?.total ?? 0;

                        if (total === 0) {
                            return `ha comprobado una factura sin diferencias${this.log.metadata?.cups ? ` (${this.log.metadata.cups})` : ''}`;
                        }

                        return `ha comprobado una factura con diferencias${this.log.metadata?.cups ? ` (${this.log.metadata.cups})` : ''}`;
                    },

                    check_error: () => {
                        return `ha intentado comprobar una factura y se produjo un error`;
                    },
                    progress: () => {
                        const step = this.log.metadata?.stepLabel ?? `Paso ${this.log.metadata?.step}`;
                        const name = this.log.metadata?.name ?? '';
                        return `se quedó en "${step}"${name ? ` · ${name}` : ''}`;
                    },
                };

                let generate = actions[this.log.event];

                return generate ? `${user} ${generate().trim()}` : `${user} realizó una acción desconocida`;
            },
            prettyDate(){
                return moment(this.log.createdAt).format('DD/MM/YYYY HH:mm')
            },
            consumption(){

                let consumed, prices = null;

                switch (this.log.metadata.inputType) {
                    case 'bill':
                        consumed = this.log.metadata?.input?.pdf?.energia_consumida
                        prices = this.log.metadata?.input?.pdf?.precios_energia
                        break;

                    case 'cups':
                        consumed = this.log.metadata?.input?.sips?.cupsData?.consumption
                        prices = this.log.metadata?.input?.manual?.prices?.energy
                        break;

                    case 'manual':
                        consumed = this.log.metadata?.input?.manual?.consumptionData?.consumption
                        prices = this.log.metadata?.input?.manual?.prices?.energy
                        break;
                }

                return {consumed, prices}

            },
            power(){
                let consumed, prices = null;

                switch (this.log.metadata.inputType) {
                    case 'bill':
                        consumed = this.log.metadata?.input?.pdf?.potencias_contratadas
                        prices = this.log.metadata?.input?.pdf?.precios_potencias
                        break;

                    case 'cups':
                        consumed = this.log.metadata?.input?.sips?.cupsData?.power
                        prices = this.log.metadata?.input?.manual?.prices?.power
                        break;

                    case 'manual':
                        consumed = this.log.metadata?.input?.manual?.consumptionData?.power
                        prices = this.log.metadata?.input?.manual?.prices?.power
                        break;
                }

                return {consumed, prices}
            },
            normalChanges(){
                if (!this.log.changes) return {};
                const out = {};

                for (const [field, change] of Object.entries(this.log.changes)) {
                    // solo cambios normales
                    if (!change.added && !change.removed) {
                        out[field] = change;
                    }
                }
                return out;
            },
            arrayChanges(){
                if (!this.log.changes) return {};
                const out = {};

                for (const [field, change] of Object.entries(this.log.changes)) {
                    // solo campos con added/removed
                    if (change.added || change.removed) {
                        out[field] = change;
                    }
                }
                return out;
            }
        }
    }
</script>

<style scoped>
/* ============================= */
/* BLOQUES FACTURA / COMPROBACIÓN */
/* ============================= */

.block-title {
  font-size: 18px;
  font-weight: 700;
  color: #111827; /* mismo color para ambos */
  margin-bottom: 16px;
}

.section-title {
  font-weight: 600;
  margin-bottom: 8px;
  color: #374151;
}

/* Grid de periodos Pp1..Pp6 */
.period-grid {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 10px;
  padding-left: 40px;
}

/* ============================= */
/* VALORES */
/* ============================= */

.val-ok {
  color: #16a34a; /* verde */
  font-weight: 600;
}

.val-diff {
  color: #dc2626; /* rojo */
  font-weight: 700;
}

.val-neutral {
  color: #6b7280; /* gris */
}

/* ============================= */
/* RESUMEN */
/* ============================= */

.summary-box {
  border-top: 1px solid #e5e7eb;
  padding-top: 24px;
  margin-top: 30px;
}

.summary-title-center {
  text-align: center;
  font-size: 20px;
  font-weight: 800;
  color: #111827;
  margin-bottom: 20px;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
}

.summary-item {
  background: #f9fafb;
  border-radius: 12px;
  padding: 14px;
  text-align: center;
}

.summary-label {
  display: block;
  font-size: 13px;
  color: #6b7280;
  margin-bottom: 6px;
}

.summary-value {
  font-size: 22px;
  font-weight: 800;
  color: #111827;
}

/* Total OK */
.summary-item.total.ok {
  background: #ecfdf5;
  color: #166534;
}

/* Total con errores */
.summary-item.total.error {
  background: #fef2f2;
  color: #991b1b;
}

/* ============================= */
/* UTILIDADES */
/* ============================= */

.mt-15 {
  margin-top: 15px;
}

.mt-20 {
  margin-top: 20px;
}

.mt-30 {
  margin-top: 30px;
}

.mb-10 {
  margin-bottom: 10px;
}

.mb-20 {
  margin-bottom: 20px;
}

.opacity-6 {
  opacity: 0.6;
}

.ellipsis {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

/* ============================= */
/* RESPONSIVE */
/* ============================= */

@media (max-width: 1024px) {
  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .period-grid {
    grid-template-columns: repeat(3, 1fr);
    padding-left: 0;
  }
}

@media (max-width: 640px) {
  .summary-grid {
    grid-template-columns: 1fr;
  }

  .block-title {
    text-align: center;
  }
}

/* ============================= */
/* LOG RESALTADO (salto directo) */
/* ============================= */

.log-highlight {
  background: #eff6ff;
  border: 1px solid #3b82f6;
  border-radius: 12px;
  padding: 8px 12px;
  margin: 6px 0;
  scroll-margin-top: 100px;
}

</style>

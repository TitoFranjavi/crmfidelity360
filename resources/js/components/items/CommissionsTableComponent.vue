<template>
    <!--Cabecera-->
    <div class="d-flex justify-between">
        <div class="text ml-20 pb-10" data-size="20" data-weight="400">Comisiones</div>
        <div v-if="isEditing" class="d-flex form-group" data-gap="15">
            <!-- Botón de copiar comisiones -->
            <div class="input-group">
                <select v-model="commissionProduct">
                    <option :value="undefined">Selecciona un producto</option>
                    <option v-for="(product, index) of marketerProducts" :value="index">
                        {{ product.feeName ? `${product.feeName} -` : '' }}{{ product.productName }}
                    </option>
                </select>
            </div>
            <div class="custom-button text-center mr-20" data-size="small" @click="copyNewCommissions">
                Copiar comisiones
            </div>
        </div>
    </div>

    <!-- Tipo de Comisión-->
    <div v-if="!isEditing" class="text ml-20 pb-10" data-size="16" data-weight="400">
        Tipo de Comisión:
        {{
            commissionType === "f" ? "Fija"
                : commissionType === "p" ? "Potencia"
                : commissionType === "c" ? "Consumo"
                : commissionType === "cyp" ? "Potencia y consumo"
                : commissionType === "fp" ? "Porcentaje del fee"
                : ""
        }}
    </div>
    <div v-else class="text ml-10 pb-10" data-size="16" data-weight="400">
        <div class="custom-select no-hover w-fit-content" @click.stop="seeOptions" :class="{seeing: seeCommissionTypes}">
            <div class="ml-10" data-color="azul">
                {{
                    commissionType === "f" ? "Fija"
                        : commissionType === "p" ? "Potencia"
                        : commissionType === "c" ? "Consumo"
                        : commissionType === "cyp" ? "Potencia y consumo"
                        : commissionType === "fp" ? "Porcentaje del fee"
                        : ""
                }}
                <i class="far fa-chevron-down ml-10" />
            </div>

            <div class="select-content left form">
                <div v-for="cType of commissionTypes" class="d-flex align-center">
                    <div class="custom-checkbox mr-10" @click.stop="selectCommissionType(cType.value)"
                        :class="{ selected: commissionType === cType.value}"
                    />
                    <div class="text">{{ cType.label }}</div>
                </div>
            </div>
        </div>
    </div>
    <template v-if="canEdit">
        <div class="pb-20">
            <!--Cabecera-->
            <div class="d-flex px-10 py-5 round w-fit-content mx-auto" data-gap="20" data-bg="azul-claro" data-round="10">
                <template v-if="commissionType === 'p' || commissionType === 'cyp'">
                    <div class="w-150-px">Pot. mín. (kW)</div>
                    <div class="w-150-px">Pot. máx (kW)</div>
                </template>
                <template v-if="commissionType === 'c' || commissionType === 'cyp'">
                    <div class="w-150-px">Con. mín. (kWh)</div>
                    <div class="w-150-px">Con. máx. (kWh)</div>
                </template>
                <template v-if="commissionType === 'fp'">
                    <div class="w-150-px">Fee</div>
                </template>
                <div class="w-150-px">{{ basicData?.enterprise?.name }}</div>
                <div v-if="basicData?.userSubdomain?.settings?.manualCommissions" v-for="range in basicData?.enterprise?.commissionRanges" class="w-150-px">{{range.name}}</div>
                <div v-if="(isEditing && commissionType === 'c') || commissions.some(interval => interval.multiply)" class="w-115-px" />
                <div v-else-if="isEditing && (commissionType !== 'f' && commissionType !== 'fp')" class="w-20-px" />
            </div>
            <!--Visualización de comisiones-->
            <div v-if="!isEditing" class="d-flex column my-5 w-fit-content mx-auto h-500-px-max scroll-y" data-gap="5">
                <div v-for="(interval, index) of commissions" class="d-flex align-center px-10 py-5 productSeparator" data-gap="20">
                    <template v-if="commissionType === 'p' || commissionType === 'cyp'">
                        <div class="text w-150-px">{{ interval.pot1 }}</div>
                        <div class="text w-150-px">{{ interval.pot2 }}</div>
                    </template>
                    <template v-if="commissionType === 'c' || commissionType === 'cyp'">
                        <div class="text w-150-px">{{ interval.con1 }}</div>
                        <div class="text w-150-px">{{ interval.con2 }}</div>
                    </template>
                    <template v-if="commissionType === 'fp'">
                        <div v-if="index === 0" class="text w-150-px">{{ type === 'electricity' ? 'Potencia' : 'Término fijo' }}</div>
                        <div v-if="index === 1" class="text w-150-px">{{ type === 'electricity' ? 'Energía' : 'Término variable' }}</div>
                    </template>
                    <div class="text w-150-px">{{ interval.base }}</div>
                    <div v-if="basicData?.userSubdomain?.settings?.manualCommissions" v-for="range in basicData?.enterprise?.commissionRanges" class="text w-150-px">{{interval.breakdown?.[range._id]}}</div>
                    <div v-if="interval.multiply" class="d-flex align-center" data-gap="5">
                        <i class="text fas fa-check" />
                        <p class="text" data-size="12">Multiplicar</p>
                    </div>
                </div>
            </div>
            <!--Edición de comisiones-->
            <template v-else>
                <div class="w-fit-content mx-auto h-500-px-max scroll-y">
                    <div v-for="(interval, index) of commissions" class="d-flex align-center px-10 py-5" data-gap="20">
                        <template v-if="commissionType === 'p' || commissionType === 'cyp'">
                            <div class="form-group w-150-px">
                                <div class="input-group">
                                    <input type="text" v-model="interval.pot1" />
                                </div>
                            </div>
                            <div class="form-group w-150-px">
                                <div class="input-group">
                                    <input type="text" v-model="interval.pot2" />
                                </div>
                            </div>
                        </template>
                        <template v-if="commissionType === 'c' || commissionType === 'cyp'">

                            <div class="form-group w-150-px">
                                <div class="input-group">
                                    <input type="text" v-model="interval.con1" />
                                </div>
                            </div>
                            <div class="form-group w-150-px">
                                <div class="input-group">
                                    <input type="text" v-model="interval.con2" />
                                </div>
                            </div>
                        </template>
                        <template v-if="commissionType === 'fp'">
                            <div v-if="index === 0" class="pl-10 text w-150-px">{{ type === 'electricity' ? 'Potencia' : 'Término fijo' }}</div>
                            <div v-if="index === 1" class="pl-10 text w-150-px">{{ type === 'electricity' ? 'Energía' : 'Término variable' }}</div>
                        </template>
                        <div class="form-group w-150-px">
                            <div class="input-group">
                                <input type="text" v-model="interval.base" @blur="basicData?.userSubdomain?.settings?.manualCommissions && calcCommissionRanges(index)"/>
                            </div>
                        </div>
                        <div v-if="basicData?.userSubdomain?.settings?.manualCommissions" v-for="range in basicData?.enterprise?.commissionRanges" class="form-group w-150-px">
                            <div class="input-group">
                                <input type="text" v-model="interval.breakdown[range._id]" />
                            </div>
                        </div>
                        <!--Opciones-->
                        <div class="d-flex align-center justify-center" data-gap="5">
                            <template v-if="commissionType === 'c'">
                                <div @click.stop="interval.multiply = !interval.multiply" :class="['custom-checkbox',{ selected: interval.multiply}]" />
                                <p @click.stop="interval.multiply = !interval.multiply" class="text pointer" data-size="12">
                                    Multiplicar
                                </p>
                            </template>
                            <div v-if="commissionType !== 'f' && commissionType !== 'fp'" @click="deleteInterval(index)">
                                <i class="far fa-trash pointer" data-color="rojo" />
                            </div>
                        </div>
                    </div>
                </div>
                <!--Añadir intervalos-->
                <div v-if="commissionType === 'c' || commissionType === 'p' || commissionType === 'cyp'" class="d-flex justify-center my-20 align-center form-group" data-gap="10">
                    <template v-if="commissionType === 'cyp'">
                        <div class="d-flex align-center" data-gap="10">
                            <p class="text">Potencia mín:</p>
                            <input class="input-group text" type="number" v-model.number="newInterval.pot1" step="any" />
                        </div>
                        <div class="d-flex align-center" data-gap="10">
                            <p class="text">Potencia max:</p>
                            <input class="input-group text" type="number" v-model.number="newInterval.pot2" step="any" />
                        </div>
                    </template>
                    <div class="d-flex align-center" data-gap="10">
                        <p class="text">{{ `Rango (${commissionType === 'p' ? 'kW' : 'kWh'}):` }}</p>
                        <input class="input-group text" type="number" v-model="newInterval.range" />
                    </div>
                    <div class="d-flex align-center" data-gap="10">
                        <p class="text">Nº de intervalos:</p>
                        <input class="input-group text" type="number" v-model="newInterval.quantity" />
                    </div>
                    <div class="custom-button" data-size="medium" data-bg="amarillo" @click.stop="addCommissionInterval()">
                        Añadir Intervalos
                        <i class="fa-solid fa-plus" />
                    </div>
                </div>
            </template>
        </div>
    </template>
    <template v-else>
        <div class="pb-20">
            <!--Cabecera-->
            <div class="d-flex px-10 py-5 round w-fit-content mx-auto" data-gap="20" data-bg="azul-claro" data-round="10">
                <template v-if="commissionType === 'p' || commissionType === 'cyp'">
                    <div class="w-150-px">Pot. mín. (kW)</div>
                    <div class="w-150-px">Pot. máx (kW)</div>
                </template>
                <template v-if="commissionType === 'c' || commissionType === 'cyp'">
                    <div class="w-150-px">Con. mín. (kWh)</div>
                    <div class="w-150-px">Con. máx. (kWh)</div>
                </template>
                <template v-if="commissionType === 'fp'">
                    <div class="w-150-px">Fee</div>
                </template>
                <div class="w-150-px">Comisión</div>
                <div v-if="commissions.some(interval => interval.multiply)" class="w-115-px" />
            </div>
            <!--Visualización de comisiones-->
            <div class="d-flex column my-5 w-fit-content mx-auto h-500-px-max scroll-y" data-gap="5">
                <div v-for="(interval,index) of commissions" class="d-flex align-center px-10 py-5 productSeparator" data-gap="20">
                    <template v-if="commissionType === 'p' || commissionType === 'cyp'">
                        <div class="text w-150-px">{{ interval.pot1 }}</div>
                        <div class="text w-150-px">{{ interval.pot2 }}</div>
                    </template>
                    <template v-if="commissionType === 'c' || commissionType === 'cyp'">
                        <div class="text w-150-px">{{ interval.con1 }}</div>
                        <div class="text w-150-px">{{ interval.con2 }}</div>
                    </template>
                    <template v-if="commissionType === 'fp'">
                        <div v-if="index === 0" class="text w-150-px">{{ type === 'electricity' ? 'Potencia' : 'Término fijo' }}</div>
                        <div v-if="index === 1" class="text w-150-px">{{ type === 'electricity' ? 'Energía' : 'Término variable' }}</div>
                    </template>
                    <div class="text w-150-px" v-if="zocoCommissionRanges">{{ calcUserCommission(interval) }}</div>
                    <div v-if="interval.multiply" class="d-flex align-center" data-gap="5">
                        <i class="text fas fa-check" />
                        <p class="text" data-size="12">Multiplicar</p>
                    </div>
                </div>
            </div>
        </div>
    </template>
</template>

<script>
import {calculateCommission} from "@/utils/calcCommission";

export default {
    name: "CommissionsTableComponent",
    props: ['canEdit', 'isEditing', 'type', 'commissions', 'commissionType', 'seeCommissionTypes', 'priceType', 'marketer', 'product', 'basicData', 'dualEnergySelected', 'feeSelect'],
    emits: ['update:commissions','update:commissionType','update:seeCommissionTypes'],
    data() {
        return {
            commissionProduct: undefined,
            newInterval: {},
            zocoCommissionRanges: null
        }
    },
    created(){
        this.fetchZocoCommissionRanges();
    },
    methods:{
        copyNewCommissions(){
            const newCommissions = JSON.parse(
                JSON.stringify(this.marketerProducts[this.commissionProduct].commissions)
            );

            this.$emit('update:commissions', newCommissions)
            this.$emit('update:commissionType', this.marketerProducts[this.commissionProduct].commissionType)
        },
        selectCommissionType(type){
            const cloneBreakdown = (breakdown) =>
                breakdown ? JSON.parse(JSON.stringify(breakdown)) : {};

            const createEmptyInterval = (data = {}) => ({
                pot1: null,
                pot2: null,
                con1: null,
                con2: null,
                base: null,
                multiply: false,
                breakdown: {},
                ...data
            });

            let newCommissions = this.commissions;

            switch (type) {
                case 'f': {
                    const sourceCommission = this.commissions?.[0] || {};
                    newCommissions = [
                        createEmptyInterval({
                            base: sourceCommission.base ?? null,
                            breakdown: cloneBreakdown(sourceCommission.breakdown)
                        })
                    ];
                    break;
                }
                case 'p':
                    newCommissions = this.commissions.map(c => ({
                        ...c, con1: null, con2: null, multiply: false
                    }));
                    break;
                case 'c':
                    newCommissions = this.commissions.map(c => ({
                        ...c, pot1: null, pot2: null
                    }));
                    break;
                case 'cyp':
                    newCommissions = this.commissions.map(c => ({
                        ...c, multiply: false
                    }));
                    break;
                case 'fp':
                    newCommissions = [createEmptyInterval(), createEmptyInterval()];
                    break;
            }

            this.$emit('update:commissions', newCommissions);
            this.$emit('update:commissionType', type);
        },
        seeOptions(){
            this.$emit('update:seeCommissionTypes', !this.seeCommissionTypes)
        },
        deleteInterval(index){
            this.commissions.splice(index, 1);
        },
        addCommissionInterval(){
            const quantity = Number(this.newInterval?.quantity ?? 1);
            const totalToAdd = quantity > 0 ? quantity : 1;

            const range = this.newInterval?.range !== null && this.newInterval?.range !== undefined
                ? Number(this.newInterval.range)
                : null;

            const hasRange = range !== null && !Number.isNaN(range);

            const createEmptyInterval = () => ({
                pot1: null,
                pot2: null,
                con1: null,
                con2: null,
                base: null,
                multiply: false,
                breakdown: {}
            });

            const getLastDefinedInterval = (field) => {
                return [...this.commissions]
                    .reverse()
                    .find(interval => interval[field] !== null && interval[field] !== undefined);
            };

            for (let i = 0; i < totalToAdd; i++) {
                const interval = createEmptyInterval();

                if (this.commissionType === 'cyp') {
                    interval.pot1 = this.newInterval?.pot1;
                    interval.pot2 = this.newInterval?.pot2;
                }

                if (hasRange) {
                    if (this.commissionType === 'p') {
                        const lastPotInterval = getLastDefinedInterval('pot2');

                        const start = lastPotInterval?.pot2 !== null && lastPotInterval?.pot2 !== undefined
                            ? Number(lastPotInterval.pot2)
                            : 0;

                        interval.pot1 = start;
                        interval.pot2 = start + range;
                    }

                    if (this.commissionType === 'c') {
                        const lastConInterval = getLastDefinedInterval('con2');

                        const start = lastConInterval?.con2 !== null && lastConInterval?.con2 !== undefined
                            ? Number(lastConInterval.con2)
                            : 0;

                        interval.con1 = start;
                        interval.con2 = start + range;
                    }

                    if (this.commissionType === 'cyp') {
                        const lastInterval = this.commissions[this.commissions.length - 1];

                        const samePowersAsLastInterval =
                            lastInterval &&
                            (Number(lastInterval.pot1) === Number(interval.pot1)) &&
                            (Number(lastInterval.pot2) === Number(interval.pot2));

                        const start = samePowersAsLastInterval &&
                        lastInterval.con2 !== null &&
                        lastInterval.con2 !== undefined
                            ? Number(lastInterval.con2)
                            : 0;

                        interval.con1 = start;
                        interval.con2 = start + range;
                    }
                }

                this.commissions.push(interval);
            }
        },
        calcUserCommission(interval){

            const result = calculateCommission({
                userListTop: this.basicData?.userListTop,
                assignedToZoco: this.marketer?.createdBy === '65cb57489c2c285441086a43' && this.basicData?.userSubdomain?._id !== '65cb57489c2c285441086a43',
                marketer: this.marketer?._id,
                commissionRanges: this.basicData?.enterprise?.commissionRanges,
                commissionRangesZoco: this?.zocoCommissionRanges,
                commissions: [interval],
                commissionType: this.commissionType,
                baseCommission: interval?.base,

                energyData: {
                    annual: null,
                    byPeriods: null
                },

                powerData: {
                    max: null,
                    byPeriods: null
                },

                fees: [],
                manualCommissions: this.basicData?.userSubdomain?.settings?.manualCommissions
            });

            const user = this.basicData?.userLogged;

            return result.breakdown.find(u => u.userId === user._id)?.commission
                ?? (user.label === 'Usuario subdominio' ? result.subdomain : 0);
        },
        calcCommissionRanges(index){
            let commission = null;
            if(this.type === 'alarm'){
                commission = this.product.commissions[index];
            }else{
                const fee = this.type === 'dual' ? this.product.fees[this.feeSelect][this.dualEnergySelected] : this.product.fees[this.feeSelect];

                if (!fee.commissions || !fee.commissions[index]) {
                    return;
                }

                commission = fee.commissions[index];
            }
            const ranges = this.basicData.enterprise.commissionRanges || [];

            const toNumber = (value) => {
                if (typeof value === "number") {
                    return Number.isFinite(value) ? value : 0;
                }

                if (typeof value === "string") {
                    const parsed = Number(value.trim().replace(",", "."));

                    return Number.isFinite(parsed) ? parsed : 0;
                }

                return 0;
            };

            const roundNumber = (value) => {
                return Number((value).toFixed(2));
            };

            const baseCommission = toNumber(commission.base);

            if (!commission.breakdown || Array.isArray(commission.breakdown)) {
                commission.breakdown = {};
            }

            ranges.forEach((range) => {
                const rangeId = range.id;

                if (!rangeId) {
                    return;
                }

                const percentage = toNumber(range.percentage);

                commission.breakdown[rangeId] = roundNumber(baseCommission * percentage / 100);
            });
        },
        async fetchZocoCommissionRanges() {
            await axios.get('/api/enterprises/65cb57489c2c285441086a43').then((res) => {
                this.zocoCommissionRanges = res.data.data.commissionRanges;
            })
        },
    },
    computed:{
        marketerProducts() {
            if (!this.marketer) return [];

            let fees = null;
            if(this.type === 'electricity' || this.type === 'gas'){
                fees = this.marketer.fees[this.type];
            }

            const result = [];

            this.marketer.products[this.type].forEach((product) => {
                if (this.type === 'alarm') {
                    result.push({
                        productName: product.name,
                        commissions: product.commissions,
                        commissionType: product.commissionType,
                    });
                    return;
                }

                product.fees.forEach((fee) => {
                    if (fee.archived) return;

                    let foundFee = null;

                    if(this.type === 'electricity' || this.type === 'gas'){
                        foundFee = fees.find(
                            (feeMarketer) => feeMarketer.id.$oid === fee.id.$oid
                        );
                    }

                    const feeName = foundFee?.name ?? fee.name;
                    if (!feeName) return;

                    // Caso especial: 'dual' saca commissions/commissionType del subtipo activo
                    const feeData = this.type === 'dual' ? fee[this.dualEnergySelected] : fee;

                    result.push({
                        productName: product.name,
                        feeName,
                        commissions: feeData.commissions,
                        commissionType: feeData.commissionType,
                    });
                });
            });

            return result;
        },
        commissionTypes() {
            const isElectricity = this.type === 'electricity' ||
                (this.type === 'dual' && this.dualEnergySelected === 'electricity');
            const isGas = this.type === 'gas' ||
                (this.type === 'dual' && this.dualEnergySelected === 'gas');

            if (isElectricity) {
                return [
                    { value: 'f', label: 'Fija' },
                    { value: 'p', label: 'Potencia' },
                    { value: 'c', label: 'Consumo' },
                    { value: 'cyp', label: 'Potencia y consumo' },
                    ...((['variable', 'indexed'].includes(this.priceType))
                            ? [{ value: 'fp', label: 'Porcentaje del fee' }]
                            : []
                    ),
                ];
            }

            if (isGas) {
                return [
                    { value: 'f', label: 'Fija' },
                    { value: 'c', label: 'Consumo' },
                    ...((['variable', 'indexed'].includes(this.priceType))
                            ? [{ value: 'fp', label: 'Porcentaje del fee' }]
                            : []
                    ),
                ];
            }

            return [{ value: 'f', label: 'Fija' }];
        },
    }
}
</script>

export function calculateCommission({
    userListTop,
    assignedToZoco,
    marketer,
    commissionRanges,
    commissionRangesZoco = null,
    commissions,
    commissionType,
    energyData,
    powerData,
    fees,
    productType = 'cl',
    baseCommission = null,
    manualCommissions = false,
    extras = []
}) {

    // Obtenemos la jerarquía
    const userHierarchy = resolveUserHierarchy(userListTop, assignedToZoco)

    const baseCommissionCtx = { commissions, commissionType, energyData, powerData, fees, productType };

    // Calculamos la comisión del subdominio
    if (baseCommission === null) {
        baseCommission = calculateBaseCommission(baseCommissionCtx);
    }

    if (extras?.length > 0) {
        baseCommission += extras.reduce((acc, extra) => acc + toNumber(extra), 0);
    }

    // Construimos la jerarquía de usuarios con su tipo de comisión asignada
    const hierarchy = userHierarchy.map((user) => {
        const config = getUserCommissionConfig({ user, marketer, commissionRanges, commissionRangesZoco, manualCommissions, baseCommissionCtx });

        if (!config) return { id: user._id, type: "fixed", value: 0 };

        return { id: user._id, type: config.type, value: config.value };
    });

    // Calculamos las comisiones para la jerarquía
    return calculateHierarchyBreakdown({ hierarchy, baseCommission });
}

//Obtener jerarquía vacía
export function buildEmptyBreakdown(userListTop, assignedToZoco) {
    const userHierarchy = resolveUserHierarchy(userListTop, assignedToZoco)

    return userHierarchy.map((user, index) => ({
            userId: user._id,
            level: index,
            commission: null
        }))
}

// Obtener jerarquía
function resolveUserHierarchy(userListTop, assignedToZoco) {
    //Usuario visualizador y Giacomo no participan en la comisión (cambiar a opción en el perfil en caso de ser necesario como opción genérica)
    const EXCLUDED_IDS = ['65fd4c2f05efc4aa4a050dc2', '698340c75525f31823005652'];

    const subdomainIndex = userListTop.findIndex(u => u.label === 'Usuario subdominio');
    const cutIndex = assignedToZoco ? subdomainIndex + 1 : subdomainIndex;
    const trimmed = subdomainIndex !== -1 ? userListTop.slice(0, cutIndex) : userListTop;

    return [...trimmed].filter(u => !EXCLUDED_IDS.includes(u._id)).reverse();
}

//Obtener la comisión asignada a un usuario
function getUserCommissionConfig({ user, marketer, commissionRanges, commissionRangesZoco, manualCommissions, baseCommissionCtx }) {

    const userCommission = user?.commissions?.[marketer];

    //Si no tiene comisión no devuelvo nada
    if (!userCommission) return null;

    const value = userCommission?.value;

    //Cambiar a rangos de Zoco si el usuario es subdominio
    const ranges =
        user.label === 'Usuario subdominio'
            ? commissionRangesZoco
            : commissionRanges;

    //Si el subdominio tiene asignado usar las comisiones guardadas en cada producto
    if(userCommission?.type === 'range' && manualCommissions){
        let commission = 0;
        if(value){
            commission = calculateBaseCommission({...baseCommissionCtx, commissionRange: value});
        }
        return {type: "manual", value: commission}
    }

    //En caso de tener que calcularse
    switch (userCommission.type) {
        case "fixed":
            return { type: "fixed", value };

        case "percentage":
            return { type: "percentage", value };

        case "range": {

            const range = ranges?.find( r => normalizeId(r._id) === normalizeId(value));

            const percentage = range?.percentage ?? 0;
            return { type: "range", value: toNumber(percentage) };
        }

        default:
            return null;
    }
}

function calculateBaseCommission({ commissions, commissionType, energyData, powerData, fees, productType, commissionRange = null }) {
    if (commissions && !Array.isArray(commissions)) {
        commissions = commissions.commissions ?? [];
    }
    if (!Array.isArray(commissions)) commissions = [];

    const totalConsumption = toNumber(energyData?.annual)
        ?? energyData?.byPeriods?.reduce((acc, v) => acc + toNumber(v), 0)
        ?? 0;

    const consumptionByPeriods = energyData?.byPeriods ?? [];

    const maxPower = powerData?.max
        ?? (powerData?.byPeriods?.length
            ? Math.max(...powerData.byPeriods.map(toNumber))
            : 0);

    const powerByPeriods = powerData?.byPeriods ?? [];

    let base = 0;
    let interval = null;


    // Comisión por tipo
    switch (commissionType) {
        case "f":
            base = commissionRange ? (commissions[0]?.breakdown?.[commissionRange] ?? 0) : commissions[0]?.base ?? 0;
            break;
        case "c":
            interval = commissions.find(
                (i) =>
                    i.con1 <= totalConsumption &&
                    (i.con2 >= totalConsumption || i.con2 === ">" || !i.con2)
            );

            base = commissionRange ? (interval?.breakdown?.[commissionRange] ?? 0) : (interval?.base ?? 0);
            break;

        case "p":
            interval = commissions.find(
                (i) =>
                    i.pot1 <= toNumber(maxPower) &&
                    (i.pot2 >= toNumber(maxPower) || i.pot2 === ">" || !i.pot2)
            );
            base = commissionRange ? (interval?.breakdown?.[commissionRange] ?? 0) : (interval?.base ?? 0);
            break;

        case "cyp":
            interval = commissions.find(
                (i) =>
                    i.pot1 <= toNumber(maxPower) &&
                    (i.pot2 >= toNumber(maxPower) || i.pot2 === ">" || !i.pot2) &&
                    i.con1 <= totalConsumption &&
                    (i.con2 >= totalConsumption || i.con2 === ">" || !i.con2)
            );
            base = commissionRange ? (interval?.breakdown?.[commissionRange] ?? 0) : (interval?.base ?? 0);
            break;

        case "fp":
            if(productType === 'cl'){
                const powerCommission = powerByPeriods.reduce(
                    (acc, value, index) =>
                        acc + toNumber(value) * toNumber(fees?.power[index]) / 30 * 365,
                    0
                );

                const energyCommission = toNumber(energyData?.annual)
                    ? energyData.annual * toNumber(fees?.energy[0]) / 1000
                    : consumptionByPeriods.reduce(
                        (acc, value, index) =>
                            acc + toNumber(value) * toNumber(fees?.energy[index]) / 1000,
                        0
                    );

                const percentagePower =
                    toNumber(commissionRange ? commissions[0]?.breakdown?.[commissionRange] : commissions[0]?.base) / 100;

                const percentageEnergy =
                    toNumber(commissionRange ? commissions[1]?.breakdown?.[commissionRange] : commissions[1]?.base) / 100;

                base = powerCommission * percentagePower + energyCommission * percentageEnergy;

            }else if (productType === 'cg'){
                const fixedCommission = toNumber(fees?.fixed) / 30 * 365;

                const variableCommission = toNumber(energyData?.annual)
                    ? energyData.annual * toNumber(fees?.variable) / 1000
                    : consumptionByPeriods.reduce(
                        (acc, value, index) =>
                            acc + toNumber(value) * toNumber(fees?.variable) / 1000,
                        0
                    );

                const percentageFixed =
                    toNumber(commissionRange ? commissions[0]?.breakdown?.[commissionRange] : commissions[0]?.base) / 100;

                const percentageVariable =
                    toNumber(commissionRange ? commissions[1]?.breakdown?.[commissionRange] : commissions[1]?.base) / 100;

                base = fixedCommission * percentageFixed + variableCommission * percentageVariable;
            }
            break;
    }

    // Multiplicar por consumo
    if (interval?.multiply) {
        base = (base * totalConsumption) / 1000;
    }

    return base;
}

//Calcular comisiones jerarquizadas
function calculateHierarchyBreakdown({ hierarchy, baseCommission }) {
    let currentValue = baseCommission;
    const breakdown = [];

    hierarchy.forEach((level, index) => {

        if (level.type === "fixed" || level.type === "manual") {
            currentValue = level.value;
        } else {
            currentValue = currentValue * (level.value / 100);
        }

        breakdown.push({
            userId: level.id,
            level: index,
            commission: round(currentValue)
        });
    });

    return {
        subdomain: round(baseCommission),
        breakdown
    };
}

//Funciones auxiliares

const toNumber = (value) => {
    if (typeof value === "number") return value;

    if (typeof value === "string") {
        if (value.trim() === "") return 0;
        return parseFloat(value.replace(",", ".")) || 0;
    }

    return 0;
};

const round = (num) => {
    const n = Number(num);
    return isNaN(n) ? 0 : Number(n.toFixed(2));
};

const normalizeId = (id) => {
    if (!id) return '';

    if (typeof id === 'string') return id;

    if (id.$oid) return id.$oid;

    if (typeof id.toHexString === 'function') return id.toHexString();

    if (typeof id.toString === 'function') return id.toString();

    return String(id);
};

<template>
    <div class="my-10" v-on:click="selectOrder">

        <div class="pointer d-flex">
            <!-- Título + CUPS -->
            <p class="text ellipsis my-auto mr-10" data-weight="600">
                {{ order.name ? order.name : '-' }}
                <span
                    v-if="order.CUPS"
                    class="opacity-5 ml-5"
                    data-size="11"
                    :title="order.CUPS"
                >
                    (CUPS: {{ shortCUPS }})
                </span>

                <span
                    v-if="versionsCount > 1"
                    class="ml-10 px-5"
                    data-size="10"
                    data-bg="grisClaro"
                    style="border-radius: 6px;"
                >
                    {{ versionsCount }} versiones
                </span>

            </p>

            <!-- Estado -->
            <div
                class="custom-button w-fit-content mx-20"
                data-size="small"
                data-color="principal"
                :data-mode="!isHex(orderStatus.color) ? 'translucent' : null"
                :data-bg="!isHex(orderStatus.color) ? orderStatus.color : null"
                :style="
                    isHex(orderStatus.color)
                        ? {
                              backgroundColor: hexToRgba(orderStatus.color, 0.1),
                              border: `1px solid ${orderStatus.color}`
                          }
                        : {}
                "
            >
                {{ orderStatus.title }}
            </div>

            <!-- Errores -->
            <div v-if="order.errors && Object.keys(order.errors).length > 0 || versionsErrors" class="mx-10 my-auto">
                <i class="fa-solid fa-triangle-exclamation fa-bounce" data-color="rojo" data-size="20"></i>
            </div>


            <!-- Duplicar pedido -->
            <div
                class="ml-10 my-auto mx-10"
                data-size="small"
                data-color="principal"
                v-if="!isReadOnly && (basicData.userSubdomain._id !== '6909faa9232c09035a03f3b2' || ['an', 'renovar', 'caducado', 'baja_anticipada_retrocomisionada', 'b'].includes(this.orderStatus.code))"
                v-on:click.stop="duplicateOrder"
            >
                <i class="fa-light fa-copy" v-if="canManage('contracts.create')"></i>
            </div>

            <!-- Eliminar pedido -->
            <div
                class="custom-button ml-10 my-auto mx-auto"
                data-size="small"
                data-bg="rojo"
                v-if="!isReadOnly && canManage('contracts.delete')"
                v-on:click.stop="delOrder"
            >
                <i class="fas fa-trash"></i>
            </div>

            <!-- Desplegar versiones anteriores -->
            <div
                class="ml-10 my-auto mx-10 pointer"
                data-size="small"
                data-color="principal"
                v-if="hasVersions"
                v-on:click.stop="toggleVersions"
            >
                <i class="fa-solid" :class="versionsExpanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </div>
        </div>

        <div class="separator my-20"></div>
    </div>
</template>

<script>
export default {
    name: "OrderItemComponent",
    props: ["basicData", "order", "orderInd", "isReadOnly", "canDelete", "versionsCount", "versionsErrors", "hasVersions", "versionsExpanded"],
    data() {
        return {
            statuses: [
                {
                    code: "r",
                    title: "Recibido",
                    color: "receivedStatus",
                    limitedTo: []
                },
                {
                    code: "p",
                    title: "Pendiente",
                    color: "pendingStatus",
                    limitedTo: []
                },
                {
                    code: "t",
                    title: "Tramitado",
                    color: "processedStatus",
                    limitedTo: []
                },
                {
                    code: "f",
                    title: "Firmado",
                    color: "signedStatus",
                    limitedTo: []
                },
                {
                    code: "fc",
                    title: "Firmado - Llamada verificada",
                    color: "signedAndVerifiedCallStatus",
                    limitedTo: []
                },
                {
                    code: "ac",
                    title: "Aceptado",
                    color: "aceptedStatus",
                    limitedTo: []
                },
                {
                    code: "ap",
                    title: "Pendiente de activacion",
                    color: "activatedPendingStatus",
                    limitedTo: []
                },
                {
                    code: "a",
                    title: "Activado",
                    color: "activatedStatus",
                    limitedTo: []
                },
                {
                    code: "c",
                    title: "Comisionado",
                    color: "commissionedStatus",
                    limitedTo: []
                },
                {
                    code: "i",
                    title: "Incidencia",
                    color: "incidenceStatus",
                    limitedTo: []
                },
                {
                    code: "s",
                    title: "Scoring",
                    color: "scoringStatus",
                    limitedTo: []
                },
                {
                    code: "b",
                    title: "Baja",
                    color: "lowStatus",
                    limitedTo: []
                },
                {
                    code: "bo",
                    title: "Borrador",
                    color: "lowStatus",
                    limitedTo: []
                },
                {
                    code: "an",
                    title: "Anulado",
                    color: "morado",
                    limitedTo: []
                }
            ]
        };
    },
    methods: {
        canManage(code) {
            const user = this.basicData?.userLogged;
            const subdomain = this.basicData?.userSubdomain;

            if (!user || !subdomain) return false;

            const label = user.label;
            const labelsPermissions = subdomain.labels_permissions;

            if (!label || !labelsPermissions) return false;
            if (!code || !code.includes('.')) return false;

            const [module, action] = code.split('.');

            const modulePermissions = labelsPermissions[label][module];

            return Array.isArray(modulePermissions) && modulePermissions.includes(action);
        }
        ,
        selectOrder() {
            this.$emit("selectOrder", this.orderInd);
        },
        duplicateOrder() {
            this.$emit("duplicateOrder", this.order);
        },
        delOrder() {
            this.$emit("delOrder", this.orderInd);
        },
        toggleVersions() {
            this.$emit("toggleVersions");
        },
        isHex(color) {
            return /^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(color);
        },
        hexToRgba(hex, alpha = 1) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }
    },
    computed: {
        orderStatus() {
            if (!this.order || !this.basicData || !this.basicData.userSubdomain)
                return {};

            let recentStatus = this.order.newStatus.code
                ? this.order.newStatus.code
                : this.order.statuses.reduce(
                      (latest, current) => {
                          return new Date(current.date) > new Date(latest.date)
                              ? current
                              : latest;
                      },
                      { date: "1970-01-01T00:00:00Z" }
                  );

            return (
                this.basicData.userSubdomain.statuses.find(
                    status =>
                        status.code ===
                        (this.order.newStatus.code || recentStatus.code)
                ) || {}
            );
        },
        shortCUPS() {
            if (!this.order || !this.order.CUPS) return "";
            // últimos 6 caracteres
            return this.order.CUPS.slice(-6);
        }
    }
};
</script>

<style scoped>
</style>
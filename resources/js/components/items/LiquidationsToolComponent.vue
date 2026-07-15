<template>
    <!-- TEXTO EXPLICATIVO -->
    <p class="opacity-5 my-10" data-size="11">
        Adjunta un excel para cambiar automáticamente el estado de liquidación
        de los contratos con CUPS dentro del excel
    </p>

    <!-- BOTONES -->
    <div class="d-flex mt-30">
        <div
            class="custom-button"
            data-bg="principal"
            data-mode="translucent"
            data-size="regular"
            @click="openDialog"
        >
            Adjunta excel <i class="fa fas fa-paperclip ml-5"></i>
        </div>

        <input
            type="file"
            ref="inputExcel"
            style="display: none"
            accept=".xls, .xlsx, .csv"
            @change="pickupFile"
        />

        <a
            class="custom-button ml-10"
            data-bg="principal"
            data-mode="translucent"
            data-size="regular"
            href="/assets/templates/Plantilla_Cambio_Estado_Liquidaciones.xlsx"
            download="Plantilla_Cambio_Estado_Liquidaciones.xlsx"
        >
            Descargar plantilla
            <i class="fas fa-file-arrow-down ml-4 mr-10"></i>
        </a>
    </div>

    <div class="separator my-20"></div>

    <div v-if="isLoading || orders.length > 0">
        <div class="d-flex mt-30 justify-end">
            <div class="search-div d-flex" style="width: 360px">
                <div class="search-bar w-100">
                    <i class="fa-regular fa-magnifying-glass mr-10"></i>
                    <input
                        type="text"
                        placeholder="Buscar contratos..."
                        v-model="searchText"
                    />
                </div>

                <i
                    class="pointer fas fa-trash ml-10 my-auto"
                    data-color="principal"
                    @click="resetSearch"
                ></i>
            </div>
        </div>
        <div class="d-flex mt-20 justify-end"></div>

        <!-- LOADING -->
        <div v-if="isLoading" class="mt-30">
            <div v-for="i in 8" :key="i">
                <div class="contact liquidations-grid">
                    <div v-for="j in 7" :key="j" class="loading h-20-px"></div>
                </div>
                <div class="separator my-10"></div>
            </div>
        </div>

        <!-- LISTADO -->
        <div v-else-if="paginatedOrders.length > 0" class="mt-30">
            <!-- CABECERA -->
            <div class="contact header-card liquidations-grid">
                <div class="text" data-weight="600">Título</div>
                <div class="text" data-weight="600">Agente</div>
                <div class="text" data-weight="600">Tarifa</div>
                <div class="text" data-weight="600">Producto</div>
                <div class="text" data-weight="600">CUPS</div>
                <div class="text" data-weight="600">Liq. antigua</div>
                <div class="text" data-weight="600">Liq. nueva</div>
            </div>

            <div class="separator my-10"></div>

            <!-- FILAS -->
            <template
                v-for="(item, index) in paginatedOrders"
                :key="item.order_id + '-' + index"
            >
                <div class="contact liquidations-grid pointer">
                    <div class="text ellipsis" data-weight="600">
                        {{ item.order?.name || "-" }}
                    </div>

                    <div class="text ellipsis">
                        {{ item.agent || "-" }}
                    </div>

                    <div class="text ellipsis">
                        {{ item.order?.fee || "-" }}
                    </div>

                    <div class="text ellipsis">
                        {{ item.order?.product || "-" }}
                    </div>

                    <div class="text">
                        {{ item.order?.CUPS || "-" }}
                    </div>

                    <div class="text ellipsis">
                        {{ getLiquidationTitle(item.liquidationStatus) }}
                    </div>

                    <div class="text">
                        <select
                            class="custom-select-liquidation"
                            v-model="item.order.liquidationStatus"
                            @change="onLiquidationChange(item)"
                        >
                            <option
                                v-for="status in liquidationStatuses"
                                :key="status.code"
                                :value="status.code"
                            >
                                {{ status.title }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="separator my-10"></div>
            </template>

            <!-- PAGINACIÓN -->
            <div class="d-grid mt-30 mb-40" data-column="2" data-layout="1auto">
                <!-- CENTRO -->
                <div
                    class="d-flex justify-center my-auto"
                    data-color="principal"
                >
                    <div
                        class="left pointer my-auto"
                        :class="{ 'opacity-5': currentPage === 1 }"
                        @click="changePage(-1)"
                    >
                        <i class="fa-solid fa-chevron-left"></i>
                    </div>

                    <div
                        class="cont mx-10 my-auto"
                        data-size="13"
                        data-weight="600"
                    >
                        {{ currentPage }} DE {{ totalPages }}
                    </div>

                    <div
                        class="right pointer my-auto"
                        :class="{ 'opacity-5': currentPage === totalPages }"
                        @click="changePage(1)"
                    >
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>
                </div>

                <!-- DERECHA -->
                <div class="my-auto ml-auto d-flex">
                    <p class="text my-auto mr-10" data-size="13">Por página:</p>

                    <div class="select-content my-auto">
                        <div class="form my-auto">
                            <div class="form-group">
                                <div class="input-group">
                                    <select
                                        v-model="perPage"
                                        @change="changePageSize"
                                    >
                                        <option
                                            v-for="opt in perPageOptions"
                                            :key="opt"
                                            :value="opt"
                                        >
                                            {{ opt }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-end mt-20 mb-40">
                <div
                    class="custom-button"
                    data-bg="principal"
                    data-size="regular"
                    @click="saveAllLiquidations"
                >
                    Guardar cambios
                    <i class="fas fa-save ml-6"></i>
                </div>
            </div>
        </div>

        <!-- SIN RESULTADOS -->
        <div v-else class="opacity-5 mt-30" data-align="center">
            No se han detectado contratos con esos criterios
        </div>
    </div>

    <div class="loader-box" v-if="isLoadingMassiveLoad">
        <div class="loader"></div>
    </div>
</template>

<script>
export default {
    name: "LiquidationsComponent",
    props: ["basicData"],

    data() {
        return {
            orders: [],
            isLoading: false,
            searchText: "",

            currentPage: 1,
            perPage: 50,
            perPageOptions: [50, 100, 200],
            isLoadingMassiveLoad: false,
            liquidationStatuses: [
                { code: "nl", title: "No liquidado" },
                { code: "al", title: "Liquidado agente" },
                { code: "cl", title: "Liquidado comerc." },
                { code: "tl", title: "Total liquidado" },
                { code: "ad", title: "Decomisionado agente" },
                { code: "md", title: "Decomisionado comercializadora" },
                { code: "tm", title: "Total decomisionado" },
            ],
        };
    },

    computed: {
        filteredOrders() {
            if (!this.searchText) return this.orders;

            const q = this.searchText.toLowerCase();

            return this.orders.filter(
                (o) =>
                    o.order?.name?.toLowerCase().includes(q) ||
                    o.agent?.toLowerCase().includes(q) ||
                    o.order?.CUPS?.toLowerCase().includes(q),
            );
        },

        totalPages() {
            return Math.max(
                1,
                Math.ceil(this.filteredOrders.length / this.perPage),
            );
        },

        paginatedOrders() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredOrders.slice(start, start + this.perPage);
        },
    },

    methods: {
        getInitialState() {
            return {
                orders: [],
                isLoading: false,
                searchText: "",
                currentPage: 1,
                perPage: 50,
                perPageOptions: [50, 100, 200],
                isLoadingMassiveLoad: false,
                liquidationStatuses: [
                    { code: "nl", title: "No liquidado" },
                    { code: "al", title: "Liquidado agente" },
                    { code: "cl", title: "Liquidado comerc." },
                    { code: "tl", title: "Total liquidado" },
                    { code: "ad", title: "Decomisionado agente" },
                    { code: "md", title: "Decomisionado comercializadora" },
                    { code: "tm", title: "Total decomisionado" },
                ],
            };
        },

        resetComponent() {
            Object.assign(this.$data, this.getInitialState());
        },
        async saveAllLiquidations() {
            const changedOrders = this.orders.filter(
                (o) =>
                    o.order.liquidationStatus !== o.originalLiquidationStatus,
            );

            if (changedOrders.length === 0) {
                Swal.fire("Sin cambios", "No hay cambios que guardar", "info");
                return;
            }

            try {
                this.isLoadingMassiveLoad = true;

                for (const item of changedOrders) {
                    
                    await axios.post("/api/orders/saveLiquidation", {
                    
                        order: {
                            ...item.order,
                            _id: item.order_id,
                        },
                        account: item.account_id || null,
                    });

                    // actualizamos snapshot
                    item.originalLiquidationStatus =
                        item.order.liquidationStatus;
                }

                Swal.fire(
                    "Guardado",
                    "Estados de liquidación guardados correctamente",
                    "success",
                );
            } catch (e) {
                Swal.fire("Error", "Error al guardar los cambios", "error");
            } finally {
                this.isLoadingMassiveLoad = false;
                this.resetComponent();
            }
        },
        resetSearch() {
            this.searchText = "";
        },

        changePage(value) {
            if (value === -1 && this.currentPage > 1) this.currentPage--;
            if (value === 1 && this.currentPage < this.totalPages)
                this.currentPage++;
        },

        changePageSize() {
            this.currentPage = 1;
        },

        openDialog() {
            this.$refs.inputExcel.click();
        },

        pickupFile(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.liquidateCUPS(file);
            event.target.value = null;
        },

        async liquidateCUPS(file) {
            this.isLoading = true;
            const formData = new FormData();
            formData.append("file", file);
            formData.append(
                "userLogged",
                JSON.stringify(this.basicData.userSubdomain),
            );
            formData.append(
                "userList",
                JSON.stringify(this.basicData.userList),
            );

            try {
                this.isLoadingMassiveLoad = true;
                const res = await axios.post(
                    "/api/tools/liquidateCUPS",
                    formData,
                );

                this.orders = res.data.orders || [];
                this.currentPage = 1;

                const failed = Array.isArray(res.data.failedRows)
                    ? res.data.failedRows
                    : [];

                if (failed.length === 0) {
                    Swal.fire({
                        icon: "success",
                        title: "Proceso completado",
                        text: "Todos los contratos se han procesado correctamente.",
                        timer: 1600,
                        timerProgressBar: true,
                    });
                    return;
                }

                Swal.fire({
                    icon: "warning",
                    title: "Hay incidencias",
                    html: `
    <div style="text-align:left">
        <p style="margin-bottom:8px">
            Se han detectado <b>${failed.length}</b> incidencias en el archivo:
        </p>

        <div style="
            max-height:280px;
            overflow:auto;
            border:1px solid #eee;
            border-radius:6px;
            padding:8px
        ">
            ${failed
                .map(
                    (f) => `
                <div style="
                    padding:6px 4px;
                    border-bottom:1px solid #f0f0f0;
                    font-size:13px
                ">
                    <div>
                        <b>Fila ${f.row}</b>
                        ${
                            f.cups
                                ? ` · <span style="color:#1a3cff">${f.cups}</span>`
                                : ""
                        }
                    </div>
                    <div style="color:#666; margin-top:2px">
                        ${f.reason}
                    </div>
                </div>
            `,
                )
                .join("")}
        </div>

        <p style="margin-top:10px; font-size:12px; color:#777">
            Corrige estas filas y vuelve a subir el archivo si es necesario.
        </p>
    </div>
    `,
                    confirmButtonText: "Entendido",
                });
            } finally {
                this.isLoading = false;
                this.isLoadingMassiveLoad = false;
            }
        },

        onLiquidationChange(item) {
            
        },

        getLiquidationTitle(code) {
            return (
                this.liquidationStatuses.find((s) => s.code === code)?.title ||
                "-"
            );
        },
    },
};
</script>

<style scoped>
.liquidations-grid {
    display: grid;
    grid-template-columns: 3fr 1.2fr 1fr 1.2fr 1.6fr 1.4fr 1.4fr;
    align-items: center;
    column-gap: 16px;
}

.custom-select-liquidation {
    width: 100%;
    padding: 4px 6px;
    font-size: 13px;
    border-radius: 6px;
}
</style>

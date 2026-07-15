<template>
    <div class="permissions-layout">
        <!-- COLUMNA IZQUIERDA -->
        <div class="labels-column">
            <p class="text mb-20" data-weight="700">Etiquetas</p>

            <div class="labels-list">
                <div
                    v-for="label in selectableLabels"
                    :key="label"
                    class="label-pill"
                    :class="{ selected: label === selectedLabel }"
                    @click="selectLabel(label)"
                >
                    {{ label }}
                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA -->
        <div class="modules-column" v-if="selectedLabel">
            <p class="text mb-25" data-size="15" data-weight="700">
                Permisos de <span class="highlight">{{ selectedLabel }}</span>
            </p>

            <!-- MÓDULOS -->
            <div
                v-for="module in orderedModules"
                :key="module.name"
                class="module-row"
                :class="{ 'liquidations-row': module.name === 'liquidations' }"
            >
                <div class="module-name">
                    {{ module.title }}
                </div>

                <div class="module-permissions">
                    <label
                        v-for="perm in module.permissions"
                        :key="perm.code"
                        class="permission-inline"
                    >
                        <input
                            type="checkbox"
                            :checked="isChecked(module.name, perm.code)"
                            @change="togglePermission(module.name, perm.code)"
                        />
                        <span>{{ perm.label }}</span>
                    </label>
                </div>
            </div>

            <!-- BOTÓN -->
            <div class="d-flex justify-end mt-30">
                <button
                    class="custom-button"
                    data-size="regular"
                    data-bg="principal"
                    @click="saveAllPermissions"
                >
                    Guardar permisos
                </button>
            </div>
        </div>

        <!-- PLACEHOLDER -->
        <div class="modules-column empty" v-else>
            <p class="text opacity-5">
                Selecciona una etiqueta para configurar permisos
            </p>
        </div>
    </div>
</template>

<script>
export default {
    name: "PermissionsEditorComponent",
    props: ["basicData"],

    data() {
        return {
            selectedLabel: null,
            localPermissions: {},
            modules: [
                {
                    name: "accounts",
                    title: "Cuentas",
                    permissions: [
                        { code: "view", label: "Ver" },
                        { code: "create", label: "Crear" },
                        { code: "massive", label: "Carga masiva" },
                        { code: "edit", label: "Editar" },
                        { code: "delete", label: "Eliminar" },
                    ],
                },
                {
                    name: "contracts",
                    title: "Contratos",
                    permissions: [
                        { code: "view", label: "Ver" },
                        { code: "create", label: "Crear" },
                        { code: "delete", label: "Eliminar" },
                        { code: "massive", label: "Carga masiva" },
                        { code: "edit", label: "Editar" },
                        {
                            code: "manageCommissions",
                            label: "Gestionar comisiones",
                        },
                        { code: "fees", label: "Ver/Editar fees" },
                        { code: "processor", label: "Tramitador" },
                        {code: "consumos", label: "Gestionar consumos"},
                        {code: "potencias", label: "Gestionar potencias"},
                        { code: "calls", label: "Llamadas de verificación" }
                    ],
                },
                {
                    name: "documents",
                    title: "Documentos",
                    permissions: [
                        { code: "view", label: "Ver" },
                        { code: "upload", label: "Subir archivos/carpetas" },
                        { code: "edit", label: "Editar" },
                        { code: "delete", label: "Eliminar" },
                    ],
                },
                {
                    name: "liquidations",
                    title: "Liquidaciones",
                    permissions: [{ code: "view", label: "Ver" }],
                },
                {
                    name: "users",
                    title: "Red",
                    permissions: [
                        { code: "view", label: "Ver" },
                        { code: "create", label: "Crear" },
                        { code: "massive", label: "Carga masiva" },
                        { code: "edit", label: "Editar" },
                        { code: "delete", label: "Borrar" },
                        {code: "admiWhiHier", label: "Administrar sin Jerarquia"}
                    ],
                },
                {
                    name: "products",
                    title: "Productos / Comercializadora",
                    permissions: [
                        { code: "view", label: "Ver" },
                        { code: "create", label: "Crear" },
                        { code: "edit", label: "Editar" },
                    ],
                },
                {
                    name: "tools",
                    title: "Herramientas",
                    permissions: [
                        {
                            code: "load-liquidations",
                            label: "Cargar liquidaciones",
                        },
                        { code: "comparator", label: "Comparador de luz" },
                        {
                            code: "comparator-multipoint",
                            label: "Comparador de luz multipunto",
                        },
                        { code: "comparator-gas", label: "Comparador de gas" },
                        { code: "power-optimizer", label: "Optimización de potencias" },
                        { code: "ad-creator", label: "Creador de anuncios" },
                        { code: "comparator-telephony", label: "Comparador de telefonía" },
                        { code: "datadis", label: "Monitorización" },
                        { code: "segenet", label: "Monitorización contadores" },
                        { code: "statuses", label: "Configuración de estados" },
                        { code: "massiveEmail", label: "Correo masivo" },
                        { code: "daily-signings", label: "Fichajes diarios" },
                        {
                            code: "states-massive",
                            label: "Cambio masivo de estados",
                        },
                        { code: "signings", label: "Fichajes" },
                        { code: "logs", label: "Registros" },
                        { code: "invoices", label: "Comprobador" },
                        {
                            code: "permissionsEditor",
                            label: "Editor de permisos",
                        },
                        { code: "view", label: "Ver" },
                    ],
                },
                {
                    name: "opportunities",
                    title: "Oportunidades",
                    permissions: [{ code: "view", label: "Ver" }],
                },
                {
                    name: "contacts",
                    title: "Contactos",
                    permissions: [{ code: "view", label: "Ver" }],
                },
                {
                    name: "tasks",
                    title: "Tareas",
                    permissions: [{ code: "view", label: "Ver" }],
                },
                {
                    name: "calendar",
                    title: "Calendario",
                    permissions: [{ code: "view", label: "Ver" }],
                },
            ],
        };
    },

    computed: {
        selectableLabels() {
            const allowed = [
                "Usuario",
                "Usuario drive",
                "Comercial",
                "Comercial Drive",
                "Administrador",
                "Jefe administrador",
                "Desarrollador",
                "Súper usuario",
                "Cliente",
            ];

            return [
                ...new Set(
                    this.$storage.LABELS.filter((label) =>
                        allowed.includes(label),
                    ),
                ),
            ];
        },

        orderedModules() {
            if (!Array.isArray(this.modules)) return [];

            const withoutSpecials = this.modules.filter(
                (m) => !["liquidations", "tools"].includes(m.name),
            );

            const liquidations = this.modules.find(
                (m) => m.name === "liquidations",
            );

            const tools = this.modules.find((m) => m.name === "tools");

            return [...withoutSpecials, liquidations, tools].filter(Boolean);
        },
    },

    methods: {
        selectLabel(label) {
            this.selectedLabel = label;

            if (!Array.isArray(this.modules)) return;

            const saved =
                this.basicData?.userSubdomain?.labels_permissions?.[label] ||
                {};

            this.localPermissions = {};

            this.modules.forEach((module) => {
                this.localPermissions[module.name] = saved[module.name]
                    ? [...saved[module.name]]
                    : [];
            });
        },

        isChecked(module, code) {
            return (
                this.localPermissions[module] &&
                this.localPermissions[module].includes(code)
            );
        },

        togglePermission(module, code) {
            if (!this.localPermissions[module]) {
                this.localPermissions[module] = [];
            }

            const idx = this.localPermissions[module].indexOf(code);
            idx === -1
                ? this.localPermissions[module].push(code)
                : this.localPermissions[module].splice(idx, 1);
        },

        async saveAllPermissions() {
            if (!this.selectedLabel) return;

            const modulesToSend = {};

            this.modules.forEach((module) => {
                const allowedCodes = module.permissions.map((p) => p.code);

                modulesToSend[module.name] = (
                    this.localPermissions[module.name] || []
                ).filter((code) => allowedCodes.includes(code));
            });

            try {
                Swal.fire({
                    title: "Guardando permisos...",
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });

                await axios.post("/api/user/labelsPermissions", {
                    label: this.selectedLabel,
                    modules: modulesToSend,
                });

                Swal.fire({
                    icon: "success",
                    title: "Permisos guardados",
                    text: "Los permisos se han guardado correctamente.",
                });
            } catch (error) {
                console.error(error);

                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "No se pudieron guardar los permisos.",
                });
            }
        },
    },
};
</script>

<style scoped>
.permissions-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 30px;
}

/* LEFT */
.labels-column {
    background: #f8fafc;
    padding: 20px;
    border-radius: 16px;
}

.labels-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.label-pill {
    padding: 12px 16px;
    border-radius: 12px;
    background: #e5e7eb;
    cursor: pointer;
}

.label-pill.selected {
    background: #3b82f6;
    color: #fff;
    font-weight: 600;
}

/* RIGHT */
.modules-column {
    background: #fff;
    padding: 20px;
}

.modules-column.empty {
    display: flex;
    align-items: center;
    justify-content: center;
}

/* MODULES */
.module-row {
    display: grid;
    grid-template-columns: 160px 1fr;
    align-items: center;
    padding: 14px 0;
    border-bottom: 1px solid #e5e7eb;
}

.module-name {
    font-weight: 600;
}

.module-permissions {
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
}

.permission-inline {
    display: flex;
    align-items: center;
    gap: 6px;
}

.liquidations-row {
    align-items: flex-start;
    border-radius: 8px;
}

.liquidations-row .module-permissions {
    max-height: 220px;
    overflow-y: auto;
    padding-right: 10px;
}

/* Scroll */
.liquidations-row .module-permissions::-webkit-scrollbar {
    width: 6px;
}

.liquidations-row .module-permissions::-webkit-scrollbar-thumb {
    border-radius: 6px;
}

.highlight {
    color: #2563eb;
}
</style>

<template>
    <div>
        <div class="content-white d-flex column mr-10 relPos" :class="[{'tools-mobile': !isDesktopView}]" id="content-white">

            <!-- TÍTULO -->
            <div class="d-flex f-wrap justify-between">
                <p class="text" data-size="30" data-weight="700">
                    {{ $route.meta.title }}
                    <span
                        class="italic ml-20"
                        data-weight="400"
                        data-size="15"
                        v-if="sections && sectionSelected !== undefined"
                    >
                        <i class="far mr-5" :class="sections[sectionSelected].icon"></i>
                        {{ sections[sectionSelected].title }}
                    </span>
                </p>

                <button
                    v-if="!showMenu"
                    class="custom-button"
                    data-size="medium"
                    data-bg="rojo"
                    @click="resetView"
                >
                    Volver
                </button>
            </div>

            <!-- MÓVIL (sin agrupar, simple) -->
            <div v-show="showMenu" class="d-flex justify-around">
                <div class="d-flex f-wrap justify-center mobile-item mt-30" data-gap="20">
                    <div
                        v-for="section in filteredSections"
                        :key="section.code"
                        class="pointer my-10"
                        data-color="principal"
                        @click="loadTool(section.code)"
                    >
                        <div class="d-flex column w-100-px centered" data-gap="4">
                            <i
                                data-size="25"
                                data-bg="principal"
                                data-round="15"
                                :class="`w-65-px text-center p-20 round mx-10 fas ${section.icon}`"
                            ></i>
                            <p class="text-center" data-size="12">
                                {{ section.title }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DESKTOP AGRUPADO -->
            <div class="mt-25" v-show="showMenu">

                <div
                    v-for="(groupSections, groupKey) in groupedSections"
                    :key="groupKey"
                    class="mt-25"
                >
                    <label class="text ml-15" data-weight="600">
                        {{ groupLabels[groupKey] }}
                    </label>

                    <div class="d-flex f-wrap mt-10 desktop-item" data-gap="20">
                        <div
                            v-for="section in groupSections"
                            :key="section.code"
                            data-round="30"
                            data-bg="gris-principal"
                            class="d-flex column round m-20 p-20 w-300-px drop-shadow"
                        >
                            <div class="d-flex mb-15 align-center">
                                <i data-size="25" :class="`fas ${section.icon} mr-10`"></i>
                                <h5 data-size="15">{{ section.title }}</h5>
                            </div>

                            <p class="mb-10" data-size="12">
                                {{ section.description }}
                            </p>

                            <button
                                class="custom-button mt-auto"
                                data-size="medium"
                                @click="loadTool(section.code)"
                            >
                                Cargar herramienta
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CONTENIDO -->
            <div v-if="!showMenu" class="d-flex mt-10 h-100">
                <div class="h-100" :class="['w-100', { 'px-40': isDesktopView }]">
                    <div class="h-100" v-if="sectionSelected !== undefined">
                        <component
                            :is="sections[sectionSelected].component"
                            :basicData="basicData"
                            :logId="sections[sectionSelected].code === 'logs' ? $route.query.logId : undefined"
                        />
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<script>
export default {
    name: "ToolsComponent",
    props: ["basicData"],

    data() {
        return {
            sections: [
                // UTILIDADES
                {
                    code: 'comparator',
                    title: 'Comparador de luz',
                    icon: 'fa-chart-sine',
                    description: "Analiza tu factura y detecta la tarifa más rentable.",
                    permission: "tools.comparator",
                    component: "comparator-component",
                    group: 'utilities'
                },
                {
                    code: 'comparator-multipoint',
                    title: 'Comparador de luz multipunto',
                    icon: 'fa-chart-scatter',
                    description: "Compara multiples suministros de luz.",
                    permission: "tools.comparator-multipoint",
                    component: "comparator-multipoint-component",
                    group: 'utilities'
                },
                {
                    code: 'comparator-gas',
                    title: 'Comparador de gas',
                    icon: 'fa-chart-scatter-bubble',
                    description: "Evalúa tu gasto en gas y encuentra un mejor precio.",
                    permission: "tools.comparator-gas",
                    component: "comparator-gas-component",
                    group: 'utilities'
                },
                {
                    code: 'comparator-telephony',
                    title: 'Comparador de telefonía',
                    icon: 'fa-phone-volume',
                    description: 'Compara tarifas de fibra, móvil y paquetes combinados para encontrar la mejor opción.',
                    permission: 'tools.comparator-telephony',
                    component: 'comparator-telephony-component',
                    group: 'utilities'
                },
                {
                    code: 'search-cups',
                    title: 'Buscador de CUPS',
                    icon: 'fa-magnifying-glass',
                    description: "Busca la información de potencia y consumo de un CUPS.",
                    permission: "tools.search-cups",
                    component: "search-cups-component",
                    group: 'utilities'
                },

                {
                    code: 'ad-creator',
                    title: 'Creador de banner',
                    icon: 'fa-regular fa-rectangle-ad',
                    description: "Cree anuncios personalizados y colóquelos en sus informes PDF.",
                    permission: "tools.ad-creator",
                    component: "ad-creator-component",
                    group: 'utilities'
                },

                {
                    code: 'power-optimizer',
                    title: 'Optimizador de Potencia',
                    icon: 'fa-bolt',
                    description: 'Calcula la potencia óptima de un CUPS 3.0TD.',
                    permission: 'tools.power-optimizer',
                    component: 'power-optimizer-component',
                    group: 'utilities'
                },

                // 🔹 MONITORIZACIÓN
                {
                    code: "datadis",
                    title: "Monitorización",
                    icon: "fa-lightbulb-cfl",
                    description: "Consulta tu consumo de manera detallada.",
                    permission: "tools.datadis",
                    component: "datadis-component",
                    group: 'monitoring'
                },
                {
                    code: "segenet",
                    title: "Monitorización contadores",
                    icon: "fa-meter",
                    description: "Analiza consumos cuarto horarios para un control preciso.",
                    permission: "tools.segenet",
                    component: "segenet-component",
                    group: 'monitoring'
                },

                // 🔹 ADMINISTRACIÓN
                {
                    code: 'load-liquidations',
                    title: 'Cargar liquidaciones',
                    icon: 'fa-file-excel',
                    description: "Cambia automáticamente el estado de liquidación.",
                    permission: "tools.load-liquidations",
                    component: "liquidations-tool-component",
                    group: 'admin'
                },
                {
                    code: "statuses",
                    title: "Configuración de estados",
                    icon: "fa-gears",
                    description: "Añade/elimina nuevos estados",
                    permission: "tools.statuses",
                    component: "statuses-component",
                    group: 'admin'
                },
                {
                    code: "massiveEmail",
                    title: "Correo masivo",
                    icon: "fa-envelopes-bulk",
                    description: "Envía un correo a tus agentes de manera masiva",
                    permission: "tools.massiveEmail",
                    component: "massive-email-component",
                    group: 'admin'
                },
                {
                    code: 'logs',
                    title: 'Registros',
                    icon: 'fa-clock-rotate-left',
                    description: "Historial de acciones y actividad de los usuarios.",
                    permission: "tools.logs",
                    component: "logs-tool-component",
                    group: 'admin'
                },
                {
                    code: "permissionsEditor",
                    title: "Editor de permisos",
                    icon: 'fa-user-shield',
                    description: "Gestiona los permisos del CRM",
                    permission: "tools.permissionsEditor",
                    component: 'permissions-component',
                    group: 'admin'
                },
                {
                    code: 'daily-signings',
                    title: 'Fichajes diarios',
                    icon: 'fa-calendar-day',
                    description: "Ficha la entrada y salida diaria.",
                    permission: "tools.daily-signings",
                    component: "daily-signings-tool-component",
                    group: 'utilities'
                },
                {
                    code: 'states-massive',
                    title: "Cambio masivo de estados",
                    icon: "fa-file-excel",
                    description: "Carga masivamente los estados de los contratos",
                    permission: "tools.states-massive",
                    component: "states-massive-component",
                    group: 'admin'
                },
                {
                    code: 'signings',
                    title: 'Fichajes',
                    icon: 'fa-clock',
                    description: "Gestiona los fichajes de los empleados.",
                    permission: "tools.signings",
                    component: "signings-tool-component",
                    group: 'admin'
                },
                {
                    code: 'invoices',
                    title: 'Comprobador',
                    icon: 'fa-clock-rotate-left',
                    description: "Comprobador de facturas",
                    permission: "tools.invoices",
                    component: "invoice-component",
                    onlyUserIds: ['65cb57489c2c285441086a43'],
                    group: 'utilities'
                }
                ,
            ],

            sectionSelected: undefined,
            showMenu: true,
            windowWidth: window.innerWidth,

            // 🔹 labels de grupo
            groupLabels: {
                utilities: 'Utilidades',
                monitoring: 'Monitorización',
                admin: 'Administración'
            }
        };
    },

    methods: {
        loadTool(sectionCode) {
            this.showMenu = false;
            this.sectionSelected = this.sections.findIndex(
                s => s.code === sectionCode
            );
        },
        resetView() {
            this.showMenu = true;
            this.sectionSelected = undefined;
        },
        updateWidth() {
            this.windowWidth = window.innerWidth;
        },
        canManage(code) {
            const user = this.basicData?.userLogged;
            const subdomain = this.basicData?.userSubdomain;

            if (!user || !subdomain) return false;

            const label = user.label;
            const labelsPermissions = subdomain.labels_permissions;

            if (!labelsPermissions?.[label]) return false;

            const [module, action] = code.split('.');
            return labelsPermissions[label][module]?.includes(action);
        }
    },

    computed: {
        filteredSections() {
            if (!this.basicData?.userLogged) return [];

            const userId = this.basicData.userLogged._id;

            return this.sections.filter(section => {
                if (section.onlyUserIds && !section.onlyUserIds.includes(userId)) {
                    return false;
                }
                if (!section.permission) return true;
                return this.canManage(section.permission);
            });
        },

        groupedSections() {
            const groups = {};

            this.filteredSections.forEach(section => {
                if (!groups[section.group]) {
                    groups[section.group] = [];
                }
                groups[section.group].push(section);
            });

            return groups;
        },

        isDesktopView() {
            return this.windowWidth > 810;
        }
    },

    created() {
        window.addEventListener('resize', this.updateWidth);

        //Si trae una sección la abro
        if (this.$route.query.section)
            this.loadTool(this.$route.query.section);

    },
    beforeUnmount() {
        window.removeEventListener('resize', this.updateWidth);
    }
};
</script>

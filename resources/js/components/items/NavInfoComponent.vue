<template>
    <div class="nav-bar" v-bind:class="{ 'mobile-open': isMobileNavBarOpen }">

        <!--Logo para escritorio-->
        <div class="desktop-item logo-top mx-auto w-100">
            <img
                :src="
                    '/assets/enterprises/' +
                    basicData.enterprise.asset_folder +
                    '/logos/mini-dark.png'
                "
                alt="Logo de crm"
            />
        </div>

        <!--Perfil para movil-->
        <div class="mobile-item profile-top" v-on:click="actionLink('profile')">
            <img
                :src="
                    '/assets/profile_images/' +
                    (basicData.userLogged.profileImage
                        ? basicData.userLogged.profileImage
                        : 'default.jpg')
                "
                alt="Imagen de perfil del usuario"
            />

            <!--Nombre y correo-->
            <div class="ml-10 my-auto">
                <p class="text" data-weight="600">
                    {{ basicData.userLogged.firstName }}
                </p>
                <p class="text opacity-6" data-size="10">
                    {{ basicData.userLogged.email }}
                </p>
            </div>
        </div>

        <!--Separador movil-->
        <div class="separator mobile-item"></div>

        <!--Links páginas-->
        <div class="routes-links">
            <a
                v-for="link in filteredLinks"
                v-on:click.prevent="actionLink(link.route)"
                :href="link.route"
                class="link text"
                v-bind:class="{
                    selected: this.$route.meta.group === link.group,
                    'hide-on-mobile': link.hideOnMobile,
                }"
            >
                <i
                    class="mb-5"
                    v-bind:class="
                        this.$route.meta.group === link.group ? 'fas' : 'far'
                    "
                    :class="link.icon"
                    data-size="25"
                ></i>

                <div class="text" data-weight="600">{{ link.title }}</div>
            </a>
        </div>

        <div class="mobile-item mobile-bars" v-on:click="toggleMobileNavbar">
            <i class="fa-solid fa-xmark text"></i>
        </div>
    </div>
</template>

<script>
import hasPermission from "../../middleware/hasPermission";

export default {
    name: "NavInfoComponent",
    props: ["basicData", "isMobileNavBarOpen"],
    data() {
        return {
            links: [
                {
                    icon: "fa-chart-tree-map",
                    title: "Escritorio",
                    group: "dashboard",
                    route: "/",
                },
                {
                    icon: "fa-file-lines",
                    title: "Contratos",
                    group: "contracts",
                    route: "/contracts",
                    permission: "contracts.view",
                },
                {
                    icon: "fa-buildings",
                    title: "Cuentas",
                    group: "accounts",
                    middleware: hasPermission,
                    permission: "accounts.view",
                    route: "/accounts",
                },
                {
                    icon: "fa-file-circle-question",
                    title: "Oportunid.",
                    group: "oportunities",
                    route: "/opportunities",
                    middleware: hasPermission,
                    permission: "opportunities.view",
                },
                {
                    icon: "fa-address-book",
                    title: "Contactos",
                    group: "contacts",
                    route: "/contacts",
                    middleware: hasPermission,
                    permission: "contacts.view",
                },
                {
                    icon: "fa-list-check",
                    title: "Tareas",
                    group: "tasks",
                    route: "/tasks",
                    middleware: hasPermission,
                    permission: "tasks.view",
                },
                {
                    icon: "fa-calendar",
                    title: "Calendario",
                    group: "calendar",
                    route: "/calendar",
                    middleware: hasPermission,
                    permission: "calendar.view",
                },
                {
                    icon: "fa-file-arrow-down",
                    title: "Liquidaciones",
                    group: "liquidations",
                    route: "/liquidations",
                    middleware: hasPermission,
                    permission: "liquidations.view",
                },
                {
                    icon: "fa-shop",
                    title: "Productos",
                    group: "marketers",
                    route: "/marketers",
                    middleware: hasPermission,
                    permission: "products.view",
                    hideOnMobile: true,
                },
                {
                    icon: "fa-folder",
                    title: "Documentos",
                    group: "documents",
                    route: "/documents",
                    middleware: hasPermission,
                    permission: "documents.view",
                },
                {
                    icon: "fa-users",
                    title: "Mi red",
                    group: "users",
                    route: "/users",
                    middleware: hasPermission,
                    permission: "users.view",
                },
                {
                    icon: "fa-screwdriver-wrench",
                    title: "Herramientas",
                    group: "tools",
                    route: "/tools",
                    middleware: hasPermission,
                    permission: "tools.view",
                },
            ],
        };
    },
    methods: {
        canManage(permission) {
            if (!permission) return true;

            const user = this.basicData.userLogged;
            const subdomain = this.basicData.userSubdomain;

            if (!user || !user.label) return false;

            const permissionsSource =
                user.label === "Usuario subdominio"
                    ? user.labels_permissions
                    : subdomain?.labels_permissions;

            if (!permissionsSource) return false;

            const labelPermissions = permissionsSource[user.label];
            if (!labelPermissions) return false;

            if (!permission.includes(".")) return false;

            const [module, action] = permission.split(".");

            return (
                Array.isArray(labelPermissions[module]) &&
                labelPermissions[module].includes(action)
            );
        },

        async actionLink(route) {
            if (this.$route.path == route) {
                await this.$router.replace("/temp-route");
                this.$router.push(route);
            } else {
                this.$router.push(route);
            }

            //Cierro la barra de navegacion si en movil
            if (window.innerWidth < 810) this.toggleMobileNavbar();
        },
        toggleMobileNavbar() {
            this.$emit("toggleMobileNavbar");
        },
    },
    computed: {
        hasActiveSubscription() {
            const status = this.basicData?.subdomainEnterprise?.stripe?.status;

            return ['active', 'trialing'].includes(status);
        },
        filteredLinks() {
            if (!this.basicData.userLogged) return [];

            return this.links.filter((link) => {
                if (!this.hasActiveSubscription)
                    return false;

                return this.canManage(link.permission);
            });
        },
        route() {
            return this.$route;
        },
    },
};
</script>


<style scoped>

@media (max-width: 809px) {
    .hide-on-mobile {
        display: none !important;
    }
}

</style>

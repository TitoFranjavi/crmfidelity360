import {createRouter, createWebHistory} from "vue-router";


// importar middleware
import hasPermission from "../middleware/hasPermission";
import checkSubscriptionStatus from "../middleware/isSubscriptionActive";
import { User } from "lucide-vue-next";


// [PORTAL]
const AuthComponentComponent = () => import('../components/views/PortalAuthComponent.vue');
const PortalRegisterComponent = () => import('../components/views/PortalRegisterComponent.vue');
const PortalRecoverComponent = () => import('../components/views/PortalRecoverComponent.vue');
const PortalUpdatePasswordComponent = () => import('../components/views/PortalUpdatePasswordComponent.vue');


// [APP]

const DashBoardComponent = () => import('../components/views/DashBoardComponent.vue');
const DesktopComponent = () => import('../components/views/DesktopComponent.vue');
const DesktopComponentFilters = () => import('../components/views/DesktopComponentFilters.vue');

const ProfileComponent = () => import('../components/views/ProfileComponent.vue');
const MessagesComponent = () => import('../components/views/MessagesComponent.vue');
const ContactListComponent = () => import('../components/views/ContactListComponent.vue');
const ContactRegisterComponent = () => import('../components/views/ContactRegisterComponent.vue');
const ContactDetailsComponent = () => import('../components/views/ContactDetailsComponent.vue');
const AccountListComponent = () => import('../components/views/AccountListComponent.vue');
const AccountRegisterComponent = () => import('../components/views/AccountRegisterComponent.vue');
const AccountDetailsComponent = () => import('../components/views/AccountDetailsComponent.vue');
const MarketerListComponent = () => import('../components/views/MarketerListComponent.vue');
const MarketerRegisterComponent = () => import('../components/views/MarketerRegisterComponent.vue');
const MarketerDetailsComponent = () => import('../components/views/MarketerDetailsComponent.vue');
const OrderListComponent = () => import('../components/views/OrderListComponent.vue');
const OrderDetailsComponent = () => import('../components/views/OrderDetailsComponent.vue');
const OpportunityListComponent = () => import('../components/views/OpportunityListComponent.vue');
const OpportunityRegisterComponent = () => import('../components/views/OpportunityRegisterComponent.vue');
const OpportunityDetailsComponent = () => import('../components/views/OpportunityDetailsComponent.vue');
const TaskListComponent = () => import('../components/views/TaskListComponent.vue');
const TaskRegisterComponent = () => import('../components/views/TaskRegister.vue');
const TaskDetailsComponent = () => import('../components/views/TaskDetailsComponent.vue');
const CalendarComponent = () => import('../components/views/CalendarComponent.vue');
const EventRegisterComponent = () => import('../components/views/EventRegisterComponent.vue');
const EventDetailsComponent = () => import('../components/views/EventDetailsComponent.vue');
const LiquidationsComponent = () => import('../components/views/LiquidationsComponent.vue');
const NewLiquidationsComponent = () => import('../components/views/NewLiquidationsComponent.vue');
const DocumentsComponent = () => import('../components/views/DocumentsComponent.vue');
const UserNetworkComponent = () => import('../components/views/UserNetworkComponent.vue');
const UserNetworkRegisterComponent = () => import('../components/views/UserNetworkRegisterComponent.vue');
const UserNetworkDetailsComponent = () => import('../components/views/UserNetworkDetailsComponent.vue');
const ToolsComponent = () => import('../components/views/ToolsComponent.vue');
const PricingComponent = () => import('../components/views/PricingComponent.vue');
const PricingSuccessComponent = () => import('../components/views/PricingSuccessComponent.vue');
const MonitoringComponent = () => import('../components/views/MonitoringComponent.vue');
const OpenComparatorComponent = () => import('../components/views/OpenComparatorComponent.vue');
const InstallersFormComponent = () => import('../components/items/InstallersFormComponent.vue')
const TelecoComparatorComponent = () => import("../components/views/TelecoComparatorComponent.vue");
const AlarmComparatorComponent = () => import("../components/views/AlarmComparatorComponent.vue");
const AutoconsumoComponent = () => import("../components/views/AutoconsumoComponent.vue");
const CarChargerComponent = () => import("../components/views/CarChargerComponent.vue");
const ClaimsComponent = () => import("../components/views/ClaimsComponent.vue");
const Terminos = () => import("../components/views/Terminos.vue");
const PvpcChart = () => import("../components/views/PvpcChart.vue");
//const AlarmComparatorComponent = () => import("../components/views/AlarmComparatorComponent.vue");
//const ServicesPageComponent = () => import('../components/views/ServicesPageComponent.vue');
//const TelefoniaComponent = () => import ('../components/views/TelecoComparatorWizard.vue');

const TestComponent = () => import('../components/views/TestComponent.vue');

const routes = [
    {
        path: '/portal',
        name: 'Inicio de sesión',
        meta: {
            title: 'portal',
            group: 'portal',
            section:'login'
        },
        component: AuthComponentComponent,
    },
    {
        path: '/portal/recover',
        name: 'recover',
        meta: {
            title: 'Recuperación de cuenta',
            group: 'portal',
            section:'login'
        },
        component: PortalRecoverComponent,
    },
    {
        path: '/portal/recover/update',
        name: 'update',
        meta: {
            title: 'Actualización de contraseña',
            group: 'portal',
            section:'login'
        },
        component: PortalUpdatePasswordComponent,
    },
    {
        path: '/portal/register',
        name: 'recoverRegister',
        meta: {
            title: 'Registro con código',
            group: 'portal',
            section:'login'
        },
        component: PortalRegisterComponent,
    },
    {
        path: '/',
        name: 'dashboard',
        meta: {
            title: 'Escritorio',
            group: 'dashboard',
            section:'app'
        },
        component: DashBoardComponent,
    },
    {
        path: '/contracts',
        name: 'contracts', // ruta antigua, se mantiene por compatibilidad
        redirect: { name: 'orders' },
    },
    {
        path: '/orders',
        name: 'orders',
        meta: {
            title: 'Contratos',
            group: 'contracts',
            section:'app',
            middleware: hasPermission,
            permissions: ['contracts.view']
        },
        component: OrderListComponent,
    },
    {
        path: '/profile',
        name: 'profile',
        meta: {
            title: 'Perfil de usuario',
            group: 'profile',
            section:'app'
        },
        component: ProfileComponent,
    },
    {
        path: '/messages',
        name: 'messages',
        meta: {
            title: 'Mensajes',
            group: 'messages',
            section:'app'
        },
        component: MessagesComponent,
    },
    {
        path: '/contacts',
        name: 'contacts',
        meta: {
            title: 'Contactos',
            group: 'contacts',
            section:'app'
        },
        component: ContactListComponent,
    },
    {
        path: '/contacts/register',
        name: 'contactRegister',
        meta: {
            title: 'Registro contacto',
            group: 'contacts',
            section:'app'
        },
        component: ContactRegisterComponent,
    },
    {
        path: '/contacts/:id',
        name: 'contactDetails',
        meta: {
            title: 'Detalles contacto',
            group: 'contacts',
            section:'app'
        },
        component: ContactDetailsComponent,
    },
    {
        path: '/accounts',
        name: 'accounts',
        meta: {
            title: 'Cuentas',
            group: 'accounts',
            section:'app',
            middleware: hasPermission,
            permissions: ['accounts.view'],
        },
        component: AccountListComponent,
    },
    {
    path: '/orders/documents/:orderId',
    name: 'orderView',
    meta: {
        title: 'Subir documentación del contrato',
        group: 'orders',
    },
        component: () => import('../components/views/OrderDocumentsComponent.vue'),
    },

    {
        path: '/accounts/register',
        name: 'accountsRegister',
        meta: {
            title: 'Registro cuentas',
            group: 'accounts',
            section:'app',
            middleware: hasPermission,
            permissions: ['accounts.create']
        },
        component: AccountRegisterComponent,
        props: true, // Habilita el paso de params como props
    },
    {
        path: '/accounts/:id',
        name: 'accountsDetails',
        meta: {
            title: 'Detalles cuenta',
            group: 'accounts',
            section:'app',
            middleware: hasPermission,
            permissions: ['accounts.view']
        },
        component: AccountDetailsComponent,
    },
    {
        path: '/marketers',
        name: 'marketers',
        meta: {
            title: 'Productos',
            group: 'marketers',
            section:'app',
        },
        component: MarketerListComponent,
    },
    {
        path: '/marketers/register',
        name: 'marketers register',
        meta: {
            title: 'Registro comercializadora',
            group: 'marketers',
            section:'app',
            middleware: hasPermission,
            permissions: ['products.create']
        },
        component: MarketerRegisterComponent,
    },
    {
        path: '/marketers/:id',
        name: 'marketers details',
        meta: {
            title: 'Detalles de producto',
            group: 'marketers',
            section:'app',
            middleware: hasPermission,
            permissions: ['products.view']
        },
        component: MarketerDetailsComponent,
    },
    {
        path: '/orders/:id',
        name: 'ordersDetails',
        meta: {
            title: 'Detalles de pedido',
            group: 'orders',
            section:'app',
        },
        component: OrderDetailsComponent,
    },
    {
        path: '/opportunities',
        name: 'oportunities',
        meta: {
            title: 'Oportunidades',
            group: 'oportunities',
            section:'app'
        },
        component: OpportunityListComponent,
    },
    {
        path: '/opportunities/register',
        name: 'oportunitiesRegister',
        meta: {
            title: 'Registro oportunidades',
            group: 'oportunities',
            section:'app'
        },
        component: OpportunityRegisterComponent,
    },
    {
        path: '/opportunities/:id',
        name: 'oportunitiesDetails',
        meta: {
            title: 'Detalles de oportunidades',
            group: 'oportunities',
            section:'app'
        },
        component: OpportunityDetailsComponent,
    },

    {
        path: '/installersForm',
        name: 'installersForm',
        meta: {
            title: 'Formulario de consentimiento',
            group: 'installersForm',
        },
        component: InstallersFormComponent,
    },

    {
        path: '/tasks',
        name: 'tasks',
        meta: {
            title: 'Tareas',
            group: 'tasks',
            section:'app'
        },
        component: TaskListComponent,
    },
    {
        path: '/tasks/register',
        name: 'tasksRegister',
        meta: {
            title: 'Registro tareas',
            group: 'tasks',
            section:'app'
        },
        component: TaskRegisterComponent,
    },
    {
        path: '/tasks/:id',
        name: 'tasksDetails',
        meta: {
            title: 'Details tareas',
            group: 'tasks',
            section:'app'
        },
        component: TaskDetailsComponent,
    },
    {
        path: '/calendar',
        name: 'calendar',
        meta: {
            title: 'Calendario',
            group: 'calendar',
            section:'app'
        },
        component: CalendarComponent,
    },
    {
        path: '/calendar/register',
        name: 'calendarRegister',
        meta: {
            title: 'Registro eventos',
            group: 'calendar',
            section:'app'
        },
        component: EventRegisterComponent,
    },
    {
        path: '/calendar/:id',
        name: 'eventDetails',
        meta: {
            title: 'Detalles de eventos',
            group: 'calendar',
            section:'app'
        },
        component: EventDetailsComponent,
    },
    {
        path: '/liquidations',
        name: 'liquidations',
        meta: {
            title: 'Informes',
            group: 'liquidations',
            section:'app',
            middleware: hasPermission,
            permissions: ['liquidations.view'],
        },
        component: LiquidationsComponent,
    },
    {
        path: '/new-liquidations',
        name: 'new liquidations',
        meta: {
            title: 'Informes',
            group: 'liquidations',
            section:'app',
            middleware: hasPermission,
            permissions: ['liquidations.view'],
        },
        component: NewLiquidationsComponent,
    },
    {
        path: '/documents',
        name: 'documents',
        meta: {
            title: 'Documentos',
            group: 'documents',
            section:'app',
            middleware: hasPermission,
            permissions: ['documents.view'],
        },
        component: DocumentsComponent,
    },
    {
        path: '/users',
        name: 'users network',
        meta: {
            title: 'Mi red',
            group: 'users',
            section:'app',
            middleware: hasPermission,
            permissions: ['users.view']
        },
        component: UserNetworkComponent,
    },
    {
        path: '/users/:id',
        name: 'users details',
        meta: {
            title: 'Detalles de usuario',
            group: 'users',
            section:'app',
             middleware: hasPermission,
            permissions: ['users.view']
        },
        component: UserNetworkDetailsComponent,
    },
    {
        path: '/users/register',
        name: 'users register',
        meta: {
            title: 'Registro usuario',
            group: 'users',
            section:'app',
            middleware: hasPermission,
            permissions: ['users.create']
        },
        component: UserNetworkRegisterComponent,
    },
    {
        path: '/tools',
        name: 'tools',
        meta: {
            title: 'Herramientas',
            group: 'tools',
            section:'app',
            middleware: hasPermission,
            permissions: ['tools.view']
        },
        component: ToolsComponent,
    },
    {
        path: '/zoco-one',
        name: 'zoco-one',
        meta: {
            title: 'Zoco One',
            group: 'billing',
            section:'billing',
        },
        component: PricingComponent,
    },
    {
        path: '/zoco-one/success',
        name: 'zoco-one success',
        meta: {
            title: 'Zoco One',
            group: 'billing',
            section:'billing',
        },
        component: PricingSuccessComponent,
    },
    {
        path: '/monitoring/:id',
        name: 'monitoring',
        meta: {
            title: 'Monitorización',
            group: 'monitoring',
        },
        component: MonitoringComponent,
    },
    /*
    {
        path: '/comparadorluzygas',
        name: 'comparadorluzygas',
        meta: {
            title: 'Comparador de luz y gas',
            group: 'comparator',
        },
        component: ServicesPageComponent
    },

    {
        path: '/comparadorluzygas/comparadordeluzygas',
        name: 'comparadordeluzygas',
        meta: {
            title: 'Comparador de luz y gas',
            group: 'comparator',
        },
        component: OpenComparatorComponent
    },

    {
        path: '/comparadorluzygas/comparadortelefonia',
        name: 'comparadortelefonia',
        meta: {
            title: 'Comparador de telefonia',
            group: 'comparator',
        },
        component: TelecoComparatorComponent
    },

    {
        path: '/comparadorluzygas/comparadoralarma',
        name: 'comparadoralarma',
        meta: {
            title: 'Comparador de alarma',
            group: 'comparator',
        },
        component: AlarmComparatorComponent
    },
    */

    {
    path: '/pvpc',
    name: 'pvpc',
    component: PvpcChart,
    },

    {
        path: '/comparator',
        name: 'comparadordeluzygas',
        meta: {
            title: 'Comparador de luz y gas',
            group: 'comparator',
        },
        component: OpenComparatorComponent
    },

    {
        path: '/terminos',
        name: 'terminos',
        meta: {
            title: 'Condiciones de Uso',
            group: 'comparator',
        },
        component: Terminos
    },

    {
        path: '/autoconsumo',
        name: 'autoconsumo',
        meta: {
            title: 'Presupuesto de autoconsumo',
            group: 'comparator',
        },
        component: AutoconsumoComponent
    },

    {
        path: '/reclamaciones',
        name: 'reclamaciones',
        meta: {
            title: 'Reclamaciones',
            group: 'comparator',
        },
        component: ClaimsComponent
    },

    {
        path: '/cargadordecoche',
        name: 'cargadordecoche',
        meta: {
            title: 'Cargador de coche eléctrico',
            group: 'comparator',
        },
        component: CarChargerComponent
    },
    {
        path: '/cargadordecoche/success',
        name: 'Pago exitoso',
        meta: {
            title: 'Pago exitoso de presupuesto',
            group: 'comparator',
        },
        component: CarChargerComponent
    },

    {
        path: '/comparatortelefonia',
        name: 'telefonia',
        meta: {
            title: 'ComparadorTelefonia',
            group: 'comparator',
        },
        component: TelecoComparatorComponent
    },

    {
        path: '/comparatoralarma',
        name: 'alarma',
        meta: {
            title: 'ComparadorAlarma',
            group: 'comparator',
        },
        component: AlarmComparatorComponent
    },


    /*
    {
        path: '/telefonia',
        name: 'telefonia',
        meta: {
            title: 'ComparadorTelefonia',
            group: 'comparator',
        },
        component: TelefoniaComponent
    }
        */

    /*{
        path: '/test',
        name: 'Testeo',
        meta: {
            title: 'Testeo',
            group: 'users',
            section:'app',
        },
        component: TestComponent,
    },*/


]


//import ExampleComponent from "../components/ExampleComponent";

/*const routesApps = [
    {
        path: '/',
        name: 'example',
        meta: {
            title: 'Example',
            group: 'example'
        },
        component: ExampleComponent,
    },
]*/

// =====================================================================================================================



const router = createRouter({
    history: createWebHistory(),
    routes: routes
});

function nextFactory(context, middleware, index) {
    const subsequentMiddleware = middleware[index];
    if (!subsequentMiddleware) return context.next;

    return (...parameters) => {
        context.next(...parameters);
        const nextMiddleware = nextFactory(context, middleware, index + 1);
        subsequentMiddleware({...context, next: nextMiddleware});
    };
}

router.beforeEach(async (to, from, next) => {
    let parts = document.title.split(" | ");

    if (parts.length > 1) {
        document.title = `${parts[0]} | ${to.meta.title}`;
    } else {
        document.title = to.meta.title;
    }

    const allowedWithoutSubscription = [
        '/profile',
        '/zoco-one',
        '/zoco-one/success',
        '/portal',
        '/comparator',
    ];

    const isAllowedWithoutSubscription = allowedWithoutSubscription.some(path => {
        return to.path === path || to.path.startsWith(path + '/');
    });

    const isAppRoute = to.meta.section === 'app';

    if (isAppRoute && !isAllowedWithoutSubscription) {
        const subscriptionStatus = await checkSubscriptionStatus(true);

        if (!subscriptionStatus) {
            return next({
                path: '/profile',
                query: {
                    subscription: 'required'
                }
            });
        }
    }

    if (to.meta.middleware) {
        const middleware = Array.isArray(to.meta.middleware)
            ? to.meta.middleware
            : [to.meta.middleware];

        const context = {
            from,
            next,
            router,
            to,
        };

        const nextMiddleware = nextFactory(context, middleware, 1);

        return middleware[0]({
            ...context,
            next: nextMiddleware
        });
    }

    return next();
});


export default router;

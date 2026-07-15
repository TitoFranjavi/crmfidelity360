import './bootstrap';
import './fontawesome6';
import '../sass/app.scss';


import * as storage from './utils/storage';
import * as utilities from './utils/utilities';


import {createApp} from 'vue';
import VueCookies from 'vue-cookies';

const app = createApp({});


app.config.globalProperties.$storage = storage;
app.config.globalProperties.$utilities = utilities;


import router from "./router/routes";

window.axios.defaults.headers.common = {
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    'X-Requested-With': 'XMLHttpRequest'
};


//Componentes lucide
import { MailX, MailCheck } from 'lucide-vue-next';
app.component('MailX', MailX);
app.component('MailCheck', MailCheck);




// -- [PORTAL]
import Vue from "./components/Vue.vue";

app.component('Vue', Vue);




// -- [APP]
import NavInfoComponent from "./components/items/NavInfoComponent.vue";

app.component('nav-info-component', NavInfoComponent);


//escritorio
import OrderCardComponent from "./components/items/OrderCardComponent.vue";

app.component('order-card-component', OrderCardComponent);


//contactos
import ContactCardComponent from "./components/items/ContactCardComponent.vue";

app.component('contact-card-component', ContactCardComponent);


//cuentas
import AccountCardComponent from "./components/items/AccountCardComponent.vue";
import OrderItemComponent from "./components/items/OrderItemComponent.vue";
import OrderDetailsItemComponent from "./components/items/OrderDetailsItemComponent.vue";
import DocComponent from "./components/items/DocComponent.vue";
import UserListComponent from "./components/items/UserListComponent.vue";

app.component('account-card-component', AccountCardComponent);
app.component('order-item-component', OrderItemComponent);
app.component('order-details-item-component', OrderDetailsItemComponent);
app.component('doc-component', DocComponent);
app.component('user-list-component', UserListComponent);


//comercializadoras
import MarketerCardComponent from "./components/items/MarketerCardComponent.vue";
import MarketerProductCard from "./components/items/MarketerProductCard.vue";
import GeneralProductComponent from "./components/items/GeneralProductComponent.vue";
import DualProductComponent from "./components/items/DualProductComponent.vue";
import TelephonyProductComponent from "./components/items/TelephonyProductComponent.vue";
import AlarmProductComponent from "./components/items/AlarmProductComponent.vue";
import SelfSupplyProductComponent from "./components/items/SelfSupplyProductComponent.vue";

app.component('marketer-card-component', MarketerCardComponent);
app.component('marketer-product-card-component', MarketerProductCard);
app.component('general-product-component', GeneralProductComponent);
app.component('dual-product-component', DualProductComponent);
app.component('telephony-product-component', TelephonyProductComponent);
app.component('alarm-product-component', AlarmProductComponent);
app.component('self-supply-product-component', SelfSupplyProductComponent);


//Liquidaciones
import AgentLiquidationsComponent from "./components/items/AgentLiquidationsComponent.vue";
import MarketerLiquidationsComponent from "./components/items/MarketerLiquidationsComponent.vue";

app.component('agent-liquidations-component', AgentLiquidationsComponent);
app.component('marketer-liquidations-component', MarketerLiquidationsComponent);

//Productos
import NewProductComponent from './components/items/NewProductComponent.vue';
import NewCommissionsComponent from './components/items/NewCommissionsComponent.vue';
import ExtraProductComponent from './components/items/ExtraProductComponent.vue';
app.component('new-product', NewProductComponent);
app.component('new-commissions', NewCommissionsComponent);
app.component('extra-product-component', ExtraProductComponent);

//oportunidades
import OpportunityCardComponent from "./components/items/OpportunityCardComponent.vue";

app.component('opportunity-card-component', OpportunityCardComponent);

//tareas
import SubTaskComponent from "./components/items/SubTaskComponent.vue";
import CalendarItemComponent from "./components/items/CalendarItemComponent.vue";

app.component('sub-task-component', SubTaskComponent);
app.component('calendar-item-component', CalendarItemComponent);


//Calendario de eventos
import EventsCalendarItemComponent from "./components/items/EventsCalendarItemComponent.vue";

app.component('events-calendar-item-component', EventsCalendarItemComponent)


//Mi red
import UserCardComponent from "./components/items/UserCardComponent.vue";
import HierarchyItem from "./components/items/HierarchyItem.vue";

app.component('user-card-component', UserCardComponent)
app.component('hierarchy-item', HierarchyItem)


//Útiles
import CustomFieldsComponent from "./components/items/CustomFieldsComponent.vue";
import CustomSelectComponent from "./components/items/CustomSelectComponent.vue";
import MultipleSelectComponent from "./components/items/MultipleSelectComponent.vue";
import DragDropComponent from "./components/items/DragDropComponent.vue";
import TwilioCallComponent from "./components/items/TwilioCallComponent.vue";

app.component('custom-fields-component',CustomFieldsComponent);
app.component('custom-select-component',CustomSelectComponent);
app.component('multiple-select-component',MultipleSelectComponent);
app.component('drag-drop-component', DragDropComponent);
app.component('twilio-call-component', TwilioCallComponent);


//Herramientas
import LiquidationsToolComponent from "./components/items/LiquidationsToolComponent.vue";
import ComparatorComponent from "./components/items/ComparatorComponent.vue";
import ComparatorMultiPointComponent from "./components/items/ComparatorMultiPointComponent.vue";
import ComparatorGasComponent from "./components/items/ComparatorGasComponent.vue";
import ComparatorTelephonyComponent from "./components/items/ComparatorTelephonyComponent.vue";
import SearchCupsComponent from './components/items/SearchCupsComponent.vue';
import PowerOptimizerComponent from './components/items/PowerOptimizerComponent.vue';
import AdCreatorComponent from './components/items/AdCreatorComponent.vue';
import DatadisComponent from "./components/items/DatadisComponent.vue";
import SegenetComponent from "./components/items/SegenetComponent.vue";
import StatusesComponent from "./components/items/StatusesComponent.vue";
import MassiveEmailComponent from "./components/items/MassiveEmailComponent.vue";
import QuillComponent from "./components/items/QuillComponent.vue";
import EmailComponent from "./components/items/EmailCardComponent.vue";
import PdfPreviewComponent from "./components/items/PdfPreviewComponent.vue";
import Temporal from "./components/items/temporal.vue";
import StatesMassive from "./components/items/StatesComponent.vue";
import SigningsToolComponent from "./components/items/SigningsToolComponent.vue";
import DailySigningsComponent from "./components/items/DailySigningsComponent.vue";
import LogsToolComponent from "./components/items/LogsToolComponent.vue";
import LogCardComponent from "./components/items/LogCardComponent.vue";
import InvoiceComponent from "./components/items/InvoiceComponent.vue";
import PermissionsEditorComponent from './components/items/PermissionsEditorComponent.vue';

app.component('liquidations-tool-component',LiquidationsToolComponent);
app.component('comparator-component',ComparatorComponent);
app.component("comparator-multipoint-component", ComparatorMultiPointComponent);
app.component('comparator-gas-component',ComparatorGasComponent);
app.component('comparator-telephony-component',ComparatorTelephonyComponent);
app.component('search-cups-component', SearchCupsComponent);
app.component('power-optimizer-component', PowerOptimizerComponent);
app.component('ad-creator-component', AdCreatorComponent);
app.component("datadis-component", DatadisComponent);
app.component("segenet-component", SegenetComponent);
app.component("statuses-component", StatusesComponent);
app.component("massive-email-component", MassiveEmailComponent);
app.component("quill-component", QuillComponent);
app.component("email-card-component", EmailComponent);
app.component("pdf-preview-component", PdfPreviewComponent);
app.component("temporal-component", Temporal);
app.component("states-massive-component", StatesMassive);
app.component("signings-tool-component", SigningsToolComponent);
app.component('daily-signings-tool-component', DailySigningsComponent);
app.component('logs-tool-component', LogsToolComponent);
app.component('log-card-component', LogCardComponent);
app.component("invoice-component", InvoiceComponent);
app.component("permissions-component", PermissionsEditorComponent);


//Gráficas
import ChartStackedBarsComponent from './components/items/ChartStackedBars.vue';
import ChartLineCompareComponent from "./components/items/ChartLineCompare.vue";
import SimpleBarsChartComponent from './components/items/ChartSimpleBarsComponent.vue';
import ChartRadioComponent from './components/items/ChartRadioSimple.vue';
import ChartRadioSemi from './components/items/ChartRadioSemi.vue';
import ChartHeatMapComponent from "./components/items/ChartHeatMapComponent.vue";
import ChartDatadisDonut from "./components/items/ChartDatadisDonut.vue";
import ChartDatadisSummaryBars from "./components/items/ChartDatadisSummaryBars.vue";
import ChartDatadisStackedBars from "./components/items/ChartDatadisStackedBars.vue";
import ComparatorPDF from "./components/items/ComparatorPDF.vue";
import ComparatorGasPDF from "./components/items/ComparatorGasPDF.vue";

app.component("chart-stackedbars-component",ChartStackedBarsComponent);
app.component("chart-linecompare-component",ChartLineCompareComponent);
app.component("chart-simplebars-component", SimpleBarsChartComponent);
app.component("chart-radio-component", ChartRadioComponent);
app.component("chart-semi-radio-component", ChartRadioSemi);
app.component("chart-heatmap-component", ChartHeatMapComponent);
app.component("chart-datadis-donut-component", ChartDatadisDonut);
app.component("chart-datadis-summarybars-component", ChartDatadisSummaryBars);
app.component("chart-datadis-stackedbars-component", ChartDatadisStackedBars);
app.component("comparator-pdf", ComparatorPDF);
app.component("comparator-gas-pdf", ComparatorGasPDF);

app.use(router);
app.use(VueCookies, {expires: '7d'})

app.mount('#app');

import _ from 'lodash';
window._ = _;

/**
 * jQuery (GLOBAL - necesario para código legacy)
 */
import $ from 'jquery';
window.$ = $;
window.jQuery = $;

/**
 * Bootstrap
 */
import 'bootstrap';

/**
 * Axios
 */
import axios from 'axios';
window.axios = axios;

/**
 * SweetAlert2
 */
import Swal from 'sweetalert2';
window.Swal = Swal;

/**
 * Moment (con locale ES)
 */
import moment from 'moment';
import 'moment/locale/es';

moment.updateLocale('es', {
    months: ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],
    monthsShort: ["Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic"],
});

window.moment = moment;

/**
 * Fuse.js
 */
import Fuse from 'fuse.js';
window.Fuse = Fuse;


/**
 * CONFIG AXIOS (recomendado)
 */
window.axios.defaults.headers.common = {
    'X-Requested-With': 'XMLHttpRequest'
};

const token = document.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
}

/**
 * Echo (comentado, igual que tenía)
 */

// import Echo from 'laravel-echo';
// import Pusher from 'pusher-js';

// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     wsHost: import.meta.env.VITE_PUSHER_HOST ?? `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });

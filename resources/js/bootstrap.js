import _ from 'lodash';
import axios from 'axios';

// Ni Bootstrap ni jQuery ne sont importés ici : AdminLTE v4 charge déjà le bundle
// Bootstrap 5 (window.bootstrap) et config/adminlte.php le jQuery requis par
// Select2. Un second exemplaire doublerait les écouteurs data-bs-toggle (menus
// ouverts puis refermés) et écraserait window.jQuery par une copie sans Select2.

window._ = _;

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const csrfMeta = document.querySelector('meta[name="csrf-token"]');
if (csrfMeta) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfMeta.content;
}

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    encrypted: true,
    forceTLS: true,
});

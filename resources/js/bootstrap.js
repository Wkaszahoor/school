import axios from 'axios';
import { route } from 'ziggy-js';

window.axios = axios;
window.route = route;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

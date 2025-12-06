import axios from "axios";
import $ from 'jquery';

// Expose jQuery and Axios globally
window.axios = axios;
window.jQuery = window.$ = $;

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
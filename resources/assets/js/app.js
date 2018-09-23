
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

Vue.component('melting-point', require('./components/MeltingPoint.vue'));
Vue.component('hrms-data', require('./components/HRMSData.vue'));
Vue.component('rotation-data', require('./components/RotationData.vue'));

Vue.component('text-field', require('./components/clickable/TextField.vue'));
Vue.component('dropdown-field', require('./components/clickable/DropdownField.vue'));
Vue.component('checkbox-field', require('./components/clickable/CheckboxField.vue'));
Vue.component('dropdown-text-field', require('./components/clickable/DropdownTextField.vue'));

Vue.component('show-melting-point', require('./components/show/MeltingPoint.vue'));
Vue.component('show-hrms-data', require('./components/show/HRMSData.vue'));
Vue.component('show-rotation-data', require('./components/show/RotationData.vue'));

Vue.component('delete-compound-form', require('./components/DeleteCompoundForm.vue'));




const app = new Vue({
    el: '#app'
});

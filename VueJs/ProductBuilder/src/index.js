
const URL = "http://dev.allumandsidaway.co.uk/product-builder/app/";

import Vue from 'vue';
import App from './ProductBuilderApp.vue';
import VueResource from 'vue-resource';
import '@trevoreyre/autocomplete-vue/dist/style.css'
import './branding.css';
Vue.use(VueResource);
import VueRouter from 'vue-router'

Vue.use(VueRouter)
const router = new VueRouter();


new Vue({
	el: '#productBuilderApp',
	router,
	render: h => h(App),
});
console.log("LOADED APP");
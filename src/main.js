import '@mdi/font/css/materialdesignicons.css'
import Vue from 'vue'

import Vuetify from 'vuetify'
import 'vuetify/dist/vuetify.min.css'

import App from './App'

Vue.use(Vuetify)

const opts = {
	icons: {
		iconfont: 'mdi',
	},
}

const vuetify = new Vuetify(opts)

Vue.mixin({ methods: { t, n } })

export default new Vue({
	el: '#content',
	render: h => h(App),
	vuetify,
})

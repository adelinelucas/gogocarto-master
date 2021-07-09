import Vue from 'vue/dist/vue.esm'

// Ads global method "t"
let i18nPlugin = {}
i18nPlugin.install = function (Vue, options) {
    Vue.prototype.t = function (key, params = {}) {
      return window.t(key, params)
    }
  }
Vue.use(i18nPlugin)

export default Vue

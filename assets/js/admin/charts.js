import Highcharts from 'highcharts'

// Make it availabel globally for the highcharts-bundle
window.Highcharts = Highcharts

import Vue from '../vendor/vue-custom'
import MatomoVisits from './MatomoVisits'

document.addEventListener('DOMContentLoaded', function() {
    if ($('.matomo-visits-container').length > 0) {
        new Vue({
            el: '.matomo-visits-container',
            components: { MatomoVisits }
        })
    }
})
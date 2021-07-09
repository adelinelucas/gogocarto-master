import Vue from '../vendor/vue-custom'

document.addEventListener('DOMContentLoaded', function() {
    if ($('.element-data-fields').length > 0) {
        new Vue({
            el: ".element-data-fields",
            data: {
                newFields: [],
                existingProps: existingProps.map( prop => { return {id: prop, text: prop} })
            },
            methods: {
                addField() {
                    this.newFields.push('')               
                }
            },
        })
    }
})
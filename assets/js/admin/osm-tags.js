import Vue from '../vendor/vue-custom'
import OsmCondition from './element-import/OsmQueryBuilderCondition.vue'

document.addEventListener('DOMContentLoaded', function() {
    if ($('.osm-tags-field').length > 0) {
        new Vue({
            el: ".osm-tags-field",
            data: {
                formName: undefined,
                tags: []
            },
            computed: {
                stringifiedTagsHash() {
                    let result = {}
                    for(let tag of this.tags) {
                        if (tag.key && tag.value) result[tag.key] = tag.value
                    }
                    return JSON.stringify(result)
                }
            },
            components: { OsmCondition },
            mounted() {
                this.formName = formName
                console.log(importObject, importObject.osmTags)
                for(let key in importObject.osmTags) {
                    this.tags.push({key: key, value: importObject.osmTags[key]})
                }
            }
        })
    }
})

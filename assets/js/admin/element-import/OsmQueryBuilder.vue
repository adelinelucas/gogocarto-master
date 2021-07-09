<template>
     <div class="osm-query-builder">
        <label>{{ t('osm_query_builder.title') }}</label>
        
        <osm-tag-search></osm-tag-search>

        <button type="button" class="btn btn-default" 
                @click="queries.push([{key: '', operator: '=', value: ''}])">
            {{ t('osm_query_builder.or_add_manually') }}
        </button> 
        
        <div class="bs-callout" v-for="(query, queryIndex) in queries" :key="queryIndex">
            <button type="button" @click="queries.splice(queryIndex,1)" class="btn btn-default remove-query btn-icon">
                <i class="fa fa-trash"></i>
            </button>
            <div v-for="(condition, conditionIndex) in query" class="condition-container" :key="conditionIndex">
                <osm-condition :condition="condition" :key="'Query' + queryIndex"></osm-condition>
                <button type="button" @click="query.splice(conditionIndex,1)" class="btn btn-default btn-icon remove-condition">
                    <i class="fa fa-trash remove-condition"></i>
                </button>
            </div>
            <button type="button" class="btn btn-default btn-add-condition btn-sm" 
                    @click="query.push({key: '', operator: '=', value: ''})">{{ t('osm_query_builder.add_condition') }}</button>
        </div>

        <bounds-picker ref="boundsPicker" :osm-query-object="osmQueryObject" :tileLayer="tileLayer" :default-bounds="defaultBounds"></bounds-picker>

        <label v-show="overpassQuery">{{ t('osm_query_builder.auto_generated') }}</label>
        <pre v-show="overpassQuery">{{ overpassQuery }}</pre>
    </div>
</template>

<script>
import OsmCondition from "./OsmQueryBuilderCondition"
import OsmTagSearch from "./OsmQueryBuilderTagSearch"
import BoundsPicker from "./BoundsPicker"

// TODO translate .vue file

export default {
    props: [ 'osmQueryObject', 'tileLayer', 'defaultBounds' ],
    components: { OsmCondition, OsmTagSearch, BoundsPicker },
    data() {
        return {
            queries: []
        }
    },
    computed: {
        // Transform queries array into an Overpass query
        overpassQuery() {
            let result = ''
            for(let query of this.queries) {
                let queryString = ''
                for(let condition of query) {
                    if (condition.operator == "" || condition.operator == "!") {
                        queryString += `[${condition.operator}"${condition.key}"]`
                    } else if (condition.value) {
                        let value = condition.value.replace(/,/g, '|') // transform multi input into reg expr
                        queryString += `["${condition.key}"${condition.operator}"${value}"]`
                    }
                }
                queryString += this.$refs.boundsPicker.overpassQuery
                if (query != '') result += `nwr${queryString};`
            }
            return result
        },
        overpassApiUrl() {
            // out meta provide extra data, out center provide center of way or relation
            return `https://overpass-api.de/api/interpreter?data=[out:json][timeout:1000];(${this.overpassQuery});out%20meta%20center;`
        }
    },
    watch: {
        overpassApiUrl(url) {
            this.$emit('osm-url-changed', url)
            this.$emit('update:osmQueryObject', {
                queries: this.queries, 
                bounds: [this.$refs.boundsPicker.bounds.getSouthWest(), this.$refs.boundsPicker.bounds.getNorthEast()], 
                address: this.$refs.boundsPicker.address
            })
        }
    },
    mounted() {
        if (this.osmQueryObject && this.osmQueryObject.queries)
            this.queries = this.osmQueryObject.queries
    }
}
</script>

<style lang="scss" scoped>
    .condition-container {
        display: flex;
        align-items: center;
        .btn-icon { padding: 4px 10px;}
    }
    .condition {
        display: flex;
        align-items: center;
    }
    .btn.btn-icon i {
        margin: 0;
    }
    .remove-query {
        position: absolute;
        top: -10px;
        left: -10px;
        padding: 2px 5px;
    }
    .btn-add-condition {
        margin-top: 15px;
    }
</style>
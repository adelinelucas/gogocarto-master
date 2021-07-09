<template>
    <div class="condition">
        <osm-wiki-link :condition="condition" ref="wikiLink"></osm-wiki-link>

        <input type="text" class="form-control" ref="inputKey" v-model="condition.key"
                    :placeholder="t('osm_query_builder.condition.search')"/>
        
        <select data-sonata-select2="false" class="condition-operator form-control" ref="selectOperator" 
                v-model="condition.operator" :placeholder="t('osm_query_builder.condition.title')">
            <option value="">{{ t('osm_query_builder.condition.exists') }}</option>
            <option value="!">{{ t('osm_query_builder.condition.dont_exist') }}</option>
            <option value="=">{{ t('osm_query_builder.condition.equal_to') }}</option>
            <option value="!=">{{ t('osm_query_builder.condition.different_from') }}</option>
            <option value="~">{{ t('osm_query_builder.condition.one_of') }}</option>
            <option value="!~">{{ t('osm_query_builder.condition.none_of') }}</option>
        </select>

        <input class="form-control" v-model="condition.value" ref="inputValue"
            :disabled="['', '!'].includes(condition.operator)" />
    </div>
</template>

<script>
import OsmWikiLink from "./OsmqueryBuilderWikiLink"

export default {
    props: ['condition'],
    components: { OsmWikiLink },
    data() {
        return {
            prevalentValues: []
        }
    },
    computed: {
        operator() { return this.condition.operator },
        isMultipleCondition() { return this.operator && this.operator.includes('~')}
    },
    mounted() {
        if (this.condition.key) {
            // get prevalentValues using first result, i.e. perfect match
            $.getJSON(this.keyInfoUrl(this.condition.key), (response) => {
                if (response.data.length > 0) {
                    this.prevalentValues = response.data[0].prevalent_values
                    this.initInputValue()
                }
            })            
        } else {
            this.initSearchKeyInput()
        }        
    },
    watch: {
        operator(newVal, oldVal) {
            let newValIsArray = newVal && newVal.includes('~')
            let oldValIsArray = oldVal && oldVal.includes('~')
            if (newValIsArray != oldValIsArray) {
                if (!newValIsArray && this.condition.value) {
                    // transform array value to single value
                    this.condition.value = this.condition.value.split(',')[0]
                    $(this.$refs.inputValue).val(this.condition.value).trigger('change') // needed for select2 to be updated
                }
                this.initInputValue()
            }
        }
    },
    methods: {
        keyInfoUrl(key) { 
            return `https://taginfo.openstreetmap.org/api/4/keys/all?query=${key}&include=prevalent_values&sortname=count_all&sortorder=desc&page=1&rp=20&qtype=key&format=json_pretty`
        },
        initSearchKeyInput() {
            $(this.$refs.inputKey).select2({
                minimumInputLength: 2,
                ajax: {
                    url: (term) => this.keyInfoUrl(term),
                    dataType: 'json',   
                    processResults: function (response) { return { results: response.data }; } 
                },
                id: (item) => item.key, 
                formatResult: (item) => item.key,
                formatSelection: (item) => {
                    return this.onKeySelectedFromSearchResults(item)
                }
            })
        },
        onKeySelectedFromSearchResults(item) {
            this.prevalentValues = item.prevalent_values
            this.initInputValue()
            this.condition.key = item.key
            return item.key
        },
        initInputValue() {
            let data = this.prevalentValues
            if (this.condition.value) {
                if (this.isMultipleCondition) {
                    for (let value of this.condition.value.split(','))
                        data.unshift({value: value})
                } else {
                    data.unshift({value: this.condition.value})
                }
            }
            // Format to select2 style
            data = data.map((v) => { return {id: v.value, text: v.value.charAt(0).toUpperCase() + v.value.slice(1)} })
            // Init Value Input from prevalent values
            $(this.$refs.inputValue).select2({
                createSearchChoice:function(term, data) {
                    if ($(data).filter(function() {
                        return this.text.localeCompare(term)===0;
                    }).length===0)
                    {return {id:term, text:term};}
                }, 
                multiple: this.isMultipleCondition,
                data: data
            }).on('change', () => {
                this.condition.value = $(this.$refs.inputValue).val()
            })
        }
    }
}
</script>
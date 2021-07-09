<template>
    <p>
        <input type="text" class="form-control tag-search" 
               :placeholder="t('osm_query_builder.tagsearch.search_placeholder')"/>
    </p>
</template>

<script>

export default {
    mounted() {
        $('.tag-search').select2({
            minimumInputLength: 2,
            ajax: {
                url: (term) => `https://tagfinder.herokuapp.com/api/search?query=${term}`,
                dataType: 'json',
                processResults: function (data) { return { results: data }; } 
            },
            id: (item) => item.subject, 
            formatResult: (item) => `<b>${item.prefLabel}</b> : ${item.scopeNote.en}`,
            formatSelection: (item) => {
                let query = []
                if (item.isTag) query.push({key: item.prefLabel.split('=')[0], operator: '=', value: item.prefLabel.split('=')[1]})
                else query.push({key: item.prefLabel, operator: '=', value: ''})
                let combinesTags = {}
                for (let combine of item.combines) {
                    let combineKey = combine.label.split('=')[0]
                    let combineValue = combine.label.split('=')[1]
                    // Ignore some keys not used for filtering
                    if (['name', 'website', 'wikipedia', 'opening_hours'].includes(combineKey)) continue
                    
                    if (combinesTags[combineKey]) combinesTags[combineKey] += `,${combineValue}`
                    else combinesTags[combineKey] = combineValue
                }
                for(let key in combinesTags) {
                    let value = combinesTags[key]
                    if (value == '*') query.push({key: key, operator: '', value: ''})
                    else query.push({key: key, operator: value.includes(',') ? '~' : '=', value: value})
                }
                this.$parent.queries.push(query)
                return null
            },
        });
    }
}
</script>
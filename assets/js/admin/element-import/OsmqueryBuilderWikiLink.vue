<template>
    <a :href="url" v-if="condition.key" class="btn btn-default btn-icon" 
            target="_blank" :title="title">
            <i class="fa fa-external-link-square fas fa-external-link-square-alt"></i>
    </a>
</template>

<script>

export default {
    props: ['condition'],
    computed: {
        isTag() { 
            if (this.condition.key && (this.condition.operator == '=') && this.condition.value) 
                return true
            return false
        },
        url() {
            if (this.isTag) {
                return `https://wiki.openstreetmap.org/wiki/Tag:${this.tag}`
            } else {
                return `https://wiki.openstreetmap.org/wiki/Key:${this.condition.key}`
            }
        },
        tag() {
            return `${this.condition.key}=${this.condition.value}`
        },
        title() {
            return this.t('osm_query_builder.wikilink.link_to', { key: this.isTag ? this.tag : this.condition.key })
        }
    }
}
</script>

<style scoped>
    .btn.btn-icon i {
        margin: 0;
    }
    .btn-icon { padding: 4px 10px;}
</style>
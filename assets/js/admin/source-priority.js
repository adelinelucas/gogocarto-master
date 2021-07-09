import Vue from '../vendor/vue-custom'
import Sortable from 'sortablejs'

Vue.directive('sortable', {
  inserted: function (el, binding) {
    new Sortable(el, binding.value || {})
  }
})

document.addEventListener('DOMContentLoaded', function() {
  if ($('.source-priority-container').length > 0) {
    new Vue({
      el: ".source-priority-container",
      data: {
        list: undefined,
        value: undefined
      },
      mounted() {
        this.list = sourceList
        this.value = this.list.join(',')
      },
      methods: {
        onUpdate(event) {
          this.list.splice(event.newIndex, 0, this.list.splice(event.oldIndex, 1)[0])
          this.value = this.list.join(',');
        },
        textFrom(item) {
          return item ? item : t('js.import.source_this_map');
        }
      }
    })
  }
})

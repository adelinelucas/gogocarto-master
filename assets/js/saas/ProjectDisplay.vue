<template>
  <div class="project-container">
    <a class="project-item waves-effect gogo-border-primary waves-block waves-light" :href="project.homeUrl" target="_blank">
      <img v-if="project.imageUrl" class="project-logo" :src="project.imageUrl">
      <img v-else class="project-logo default" :src="defaultImageUrl">

      <div class="project-content gogo-bg-primary">
        <div class="name gogo-neutral" :title="project.name.length > 22 ? project.name : ''">{{ displayName }}</div>
        <div class="published-at">{{ project.date }}</div> <!-- |date('commons.date_format'|trans) -->
        <div class="tags">{{ project.tags }}</div>
        <div class="data-size gogo-bg-neutral">
          <span class="size">{{ project.dataSize }}</span>
          <span class="icon gogo-icon-marker-symbol"></span>
        </div>
      </div>
    </a>
  </div>
</template>

<script>
export default {
  props: ['project', 'loopIndex'],
  computed: {
    defaultImageUrl() {
      let image = this.loopIndex % 2 == 0 ? 'gogo-bot.png' : 'plane.png'
      return '/img/' + image
    },
    displayName() {
      let result = this.project.name.charAt(0).toUpperCase() + this.project.name.slice(1);
      if (result.length > 22) result = this.project.name.slice(0, 22) + '...'
      return result
    }
  }
}
</script>

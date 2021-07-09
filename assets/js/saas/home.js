import Vue from '../vendor/vue-custom'
import Dataset from 'vue-dataset/dist/es/Dataset.js'
import DatasetItem from 'vue-dataset/dist/es/DatasetItem.js'
import DatasetInfo from 'vue-dataset/dist/es/DatasetInfo.js'
import DatasetPager from 'vue-dataset/dist/es/DatasetPager.js'
import DatasetSearch from 'vue-dataset/dist/es/DatasetSearch.js'
import DatasetShow from 'vue-dataset/dist/es/DatasetShow.js'
import ProjectDisplay from './ProjectDisplay.vue'

document.addEventListener('DOMContentLoaded', function() {
  if ($('.projects-list').length > 0) {
    new Vue({
      el: ".projects-list",
      data: {
        projects: [],
        pinnedProjects: [],
        sortField: "date",
        sortAsc: false,
      },
      components: { Dataset, DatasetItem, DatasetShow, ProjectDisplay, DatasetPager, DatasetSearch },
      mounted() {        
        for(let project of projects) {
          let date = project.publishedAt ? new Date(project.publishedAt) : new Date(project.createdAt)
          if (date) project.date = date
        }
        this.projects = projects
        this.pinnedProjects = pinnedProjects
      },
      computed: {
        sortBy() {
          return `${this.sortAsc ? '' : '-'}${this.sortField}`
        }
      }
    })
  }
})

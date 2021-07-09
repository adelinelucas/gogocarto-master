<template>
  <div class="matomo-visits">
    <h4>{{ t('js.charts.title_visitors') }}</h4>
    <div class="form-inline text-center">
      <div class="form-group">
        <label>{{ t('js.charts.display') }}</label>
        <select class="form-control" v-model="field">
          <option v-for="type in ['nb_visits', 'avg_time_on_page', ]" :key="type" :value="type">
            {{ t(`js.charts.field.${type}`) }}
          </option>
        </select>
      </div>
      <div class="form-group">
        <label>{{ t('js.charts.last') }}</label>
        <input class="input-last form-control" type="number" v-model="lastCount"/>
        <select class="form-control" v-model="period">
          <option v-for="type in ['day', 'week', 'month', 'year']" :key="type" :value="type">
            {{ t(`js.charts.period.${type}`) }}
          </option>
        </select>
      </div>      
      <button type="button" class="btn btn-default" @click="loadData">{{ t('js.charts.reload') }}</button>
    </div>
    <div class="chart-container" ref="chartContainer">
      <div class="loader"><i class="fa fa-spienner fa-spin"></i></div>
    </div>
  </div>
</template>

<script>
import Highcharts from 'highcharts'
export default {
  props: ['baseUrl', 'siteId', 'token', 'projectUrl'],
  data() {
    return {
      period: 'month',
      lastCount: '12',
      field: 'nb_visits',
      data: []
    }
  },
  computed: {
    matomoUrl() {
      return `${this.baseUrl}/index.php?module=API`+
          `&method=Actions.getPageTitle`+
          `&idSite=${this.siteId}`+
          `&pageName=${this.projectUrl}`+ // we use the projectUrl as pageName in order to easily get stats for every project on saas instance
          `&period=${this.period}`+
          `&date=last${this.lastCount}`+
          `&format=JSON`+
          `&token_auth=${this.token}`
    }
  },
  watch: {
    field: function() {
      this.drawData()
    }
  },
  methods: {
    loadData() {
      $.getJSON(this.matomoUrl, (data) => {
        this.data = data
        this.drawData()
      })
    },
    drawData() {
      const chart = Highcharts.chart(this.$refs.chartContainer, {
        chart: { type: 'spline' },
        title: false,
        xAxis: {
          categories: Object.keys(this.data).map((el) => el)
        },
        yAxis: { title: false },
        series: [{
          name: t(`js.charts.field.${this.field}`),
          data: Object.values(this.data).map((el) => el.length ? el[0][this.field] : 0)
        }]
      });
    }
  },
  mounted() {
    this.loadData()
  }
}
</script>

<style lang="scss" scoped>
  .matomo-visits {
    background-color: white;
    padding-top: 1rem;
    margin-bottom: 2rem;
  }
  h4 { 
    text-align: center; 
    margin-bottom: 1.5rem;
    color: black;
  }
  .input-last { width: 60px; }
  .form-group {
    margin-right: 1rem;
  }
  label {
    font-weight: normal;
  }
  .loader {
    padding: 50px 0;
    text-align: center;
    i { font-size: 30px; }
  }

</style>
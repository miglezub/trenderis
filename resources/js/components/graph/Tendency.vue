<template>
    <div class="card">
        <filter-modal></filter-modal>
        <h5 class="card-header">Tendencijų grafikas</h5>
        <div class="card-body">
            <b-button class="btn btn-primary mb-3 float-right" @click="filterModal">Filtruoti grafiką</b-button>
            <div class="chart-container">
                <b-overlay
                    :show="!chartReady"
                    rounded
                    opacity="0.6"
                    spinner-variant="primary"
                    class="d-inline-block spinner-large"
                    >
                    <line-chart v-if="chartReady" :chart-data="datacollection" :options="chartOptions"></line-chart>
                    <div v-else></div>
                </b-overlay>
            </div>
        </div>
    </div>
</template>
<script>
  import LineChart from './LineChart.js'
  import FilterModal from './FilterModal.vue';

  export default {
    components: {
      LineChart,
      FilterModal
    },
    data () {
      return {
        datacollection: null,
        chartOptions: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                    id: 'freq',
                    type: 'linear',
                    position: 'left',
                }, {
                    id: 'tfidf',
                    type: 'linear',
                    position: 'right',
                }]
            },
            legend: {
                display: true,
                position: 'bottom'
            },
        },
        labels: [],
        freq: [],
        tfidf: [],
        chartReady: false,
      }
    },
    mounted () {
      this.fillData()
    },
    methods: {
        fillData () {
          this.chartReady = false;
          var that = this;
          this.axios
            .get('/api/dayResults')
            .then(response => {
                response.data.results.forEach(function(result) {
                    that.labels.push(result.w);
                    that.freq.push(result.freq);
                    if(result.tfidf) {
                        that.tfidf.push(result.tfidf);
                    } else {
                        that.tfidf.push(result.tf);
                    }
                });

                that.datacollection = {
                    labels: that.labels,
                    datasets: [
                    {
                        type: 'bar',
                        label: 'Dažnis',
                        yAxisID: 'freq',
                        data: that.freq,
                        backgroundColor: "#F26D3D98"
                    },
                    {
                        type: 'line',
                        label: 'TF-IDF',
                        yAxisID: 'tfidf',
                        data: that.tfidf,
                        backgroundColor: "#6CCED985",
                        color: "#1B91BF"
                    }
                ]
                };
                this.chartReady = true;
            });
        },
        filterModal() {
            $('#filterModal').modal('show');
        },
        filterGraph() {
            this.chartReady = false;
            var that = this;
            this.axios
                .get('/api/dayResults')
                .then(response => {
                    response.data.results.forEach(function(result) {
                        that.labels.push(result.w);
                        that.freq.push(result.freq);
                        if(result.tfidf) {
                            that.tfidf.push(result.tfidf);
                        } else {
                            that.tfidf.push(result.tf);
                        }
                    });

                    that.datacollection = {
                        labels: that.labels,
                        datasets: [
                        {
                            type: 'bar',
                            label: 'Dažnis',
                            yAxisID: 'freq',
                            data: that.freq,
                            backgroundColor: "#F26D3D98"
                        },
                        {
                            type: 'line',
                            label: 'TF-IDF',
                            yAxisID: 'tfidf',
                            data: that.tfidf,
                            backgroundColor: "#6CCED985",
                            color: "#1B91BF"
                        }
                    ]
                };
                this.chartReady = true;
            });
        }
    }
  }
</script>
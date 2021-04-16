<template>
    <div class="card">
        <filter-modal v-on:filterGraph="filterGraph"></filter-modal>
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
                    <line-chart v-if="chartReady && !chartEmpty" :chart-data="datacollection" :options="chartOptions"></line-chart>
                    <div v-else-if="chartEmpty">Nerasta duomenų atitinkančių filtro kriterijus</div>
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
        chartEmpty: false
      }
    },
    mounted () {
        var date = new Date();
        date.setDate(date.getDate() - 1);
        date.setHours(0);
        date.setMinutes(59);
        date.setSeconds(59);
        var filter = {dates: [date, ""]};
        this.filterGraph(filter);
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

                if((response.data.results).length < 1) {
                    that.chartEmpty = true;
                } else {
                    that.chartEmpty = false;
                }

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
        filterGraph(filter) {
            this.chartReady = false;
            var that = this;
            this.labels = [];
            this.freq = [];
            this.tfidf = [];
            var date1;
            var date2;
            if(!filter.dates[0]) {
                date1 = "";
            } else {
                date1 = new Date()
                date1.setTime(filter.dates[0].getTime() + 3600 * 1000 * 26 - 1);
            }
            if(!filter.dates[1]) {
                filter.dates[1] = "";
            } else {
                date2 = new Date()
                date2.setTime(filter.dates[1].getTime() + 3600 * 1000 * 26 - 1);
            }
            this.axios
                .get('/api/filterGraph', {
                    params: {
                        type: filter.type,
                        date1: date1,
                        date2: date2,
                        key: filter.key,
                        keyword: filter.keyword
                    }
                })
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

                    if((response.data.results).length < 1) {
                        that.chartEmpty = true;
                    } else {
                        that.chartEmpty = false;
                    }

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
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
            defaultFontFamily: '"Montserrat", sans-serif',
            scales: {
                yAxes: [{
                    id: 'freq',
                    type: 'linear',
                    position: 'left',
                    ticks: {
                        min: 0
                    }
                }, {
                    id: 'tfidf',
                    type: 'linear',
                    position: 'right',
                    ticks: {
                        min: 0
                    }
                }]
            },
            legend: {
                display: true,
                position: 'bottom'
            },
            title: {
                display: true,
                fontSize: 18,
                text: "",
            }
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
        date.setDate(date.getDate());
        date.setHours(0);
        date.setMinutes(0);
        date.setSeconds(0);
        var filter = {dates: [date, ""], type: 1, initial: 1};
        this.filterGraph(filter);
    },
    methods: {
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
                date1.setTime(filter.dates[0].getTime() + 3600 * 1000 * 3);
            }
            if(!filter.dates[1]) {
                filter.dates[1] = "";
            } else {
                date2 = new Date()
                date2.setTime(filter.dates[1].getTime() + 3600 * 1000 * 27 - 1);
            }
            this.axios
                .get('/api/filterGraph', {
                    params: {
                        type: filter.type,
                        date1: date1,
                        date2: date2,
                        api_key: filter.key,
                        keyword: filter.keyword,
                        initial: filter.initial
                    }
                })
                .then(response => {
                    that.chartOptions.title.text = response.data.title;
                    if(filter.type == 1) {
                        response.data.results.forEach(function(result) {
                            that.labels.push(result.w);
                            that.freq.push(parseFloat(result.tf).toFixed(4));
                            if(result.tfidf) {
                                that.tfidf.push(parseFloat(result.tfidf).toFixed(4));
                            } else {
                                that.tfidf.push(result.tf);
                            }
                        });
                    } else {
                        response.data.results.forEach(function(result) {
                            that.labels.push(result.date);
                            that.freq.push(result.total);
                            that.tfidf.push(parseFloat(result.tfidf).toFixed(4));
                        });
                    }

                    if((response.data.results).length < 1) {
                        that.chartEmpty = true;
                    } else {
                        that.chartEmpty = false;
                    }

                    if(filter.type == 1) {
                        that.datacollection = {
                            labels: that.labels,
                            datasets: [
                            {
                                type: 'bar',
                                label: 'TF',
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
                    } else {
                        that.datacollection = {
                            labels: that.labels,
                            datasets: [
                            {
                                type: 'line',
                                label: 'Straipsnių kiekis',
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
                    }
                this.chartReady = true;
            });
        }
    }
  }
</script>
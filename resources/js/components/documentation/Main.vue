<template>
    <div>
        <b-card no-body>
            <b-tabs card>
            <b-tab title="Analizės tekstų pridėjimas" active>
                <b-card-text><add-text></add-text></b-card-text>
            </b-tab>
            <b-tab title="Teksto analizės rezultatų gavimas">
                <b-card-text><text-info></text-info></b-card-text>
            </b-tab>
            <b-tab title="Grafikų filtravimas">
                <b-card-text><graph-data></graph-data></b-card-text>
            </b-tab>
            </b-tabs>
        </b-card>
    </div>
</template>
<script>
  import AddText from './AddText.vue';
  import TextInfo from './TextInfo.vue';
  import GraphData from './GraphData.vue';

  export default {
    components: { AddText, TextInfo, GraphData },
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
  }
</script>
<template>
    <div class="card">
        <h4 class="card-header">
            Teksto analizės rezultatai
            <router-link :to="{name: 'texts'}" class="btn btn-danger float-right">Atgal</router-link>
        </h4>
        <div class="card-body row">
            <div class="col-md-12">
                <b-button class="btn btn-primary float-right mb-2" @click="showModal()">Atnaujinti analizę</b-button>
            </div>
            <div class="col-lg-6 col-md-12 mb-3" id="jumbotron-container">
                <h5 class="jumbotron font-weight-bold" id="title-jumbotron" v-html="text.title" v-if="text.title"></h5>
                <div class="jumbotron" id="show-text" v-html="text.original_text">
                </div>
            </div>
            <div class="col-lg-6 col-md-12 mb-3">
                <b-table
                    id="analysis-table"
                    striped hover show-empty sort-icon-left
                    :items="results"
                    :fields="fields"
                    sticky-header='700px'
                    style="clear: both; height: 700px;"
                    :busy.sync="isBusy"
                    :sort-by.sync="sortBy"
                    :sort-desc.sync="sortDesc"
                    :tbody-tr-class="rowClass"
                    >
                    <template #empty="scope">
                        <div class="text-center">Analizė neatlikta</div>
                    </template>
                    <template v-slot:table-busy>
                        <div class="text-center text-orange my-5">
                                <b-spinner class="align-middle" type="grow"></b-spinner>
                                <strong>Kraunasi...</strong>
                        </div>
                    </template>
                    <template v-slot:cell(value.w)="data">
                        <span v-b-tooltip.hover.html="getTitle(data.item.value)" v-if="data.item.value.incl || data.item.value.lemma">
                            <b v-if="data.item.value.incl">{{ data.item.value.w }}</b>
                            <span v-else>{{ data.item.value.w }}</span>
                        </span>
                        <span v-else>{{ data.item.value.w }}</span>
                    </template>
                </b-table>
            </div>
        </div>
        <div class="modal" tabindex="-1" role="dialog" id="analyseModal">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Analizės nustatymai</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="language-select" class="font-weight-bold">Teksto kalba</label>
                        <select class="custom-select" id="language-select" v-model="text.language_id">
                            <option :value="language.id" v-for="language in languages" :key="language.id">{{ language.name }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="switch">
                            <input type="checkbox" v-model="text.use_idf">
                            <span class="slider round"></span>
                        </label>
                        <span class="pl-2">Ar naudoti TF-IDF?</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <b-overlay
                    :show="busyAnalysis"
                    rounded
                    opacity="0.6"
                    spinner-small
                    spinner-variant="success"
                    class="d-inline-block"
                    >
                        <button type="button" class="btn btn-success" :disabled="busyAnalysis" @click="analyse()">Analizuoti</button>
                    </b-overlay>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Uždaryti</button>
                </div>
                </div>
            </div>
        </div>
        <message id="errorMessage" type="error" title="Sistemos klaida" message="Atsiprašome, įvyko nenumatyta sistemos klaida."></message>
    </div>
</template>
<script>
    import Message from '../layout/Message.vue';
    export default {
        components: { Message },
        data() {
            return {
                text: {},
                results: [],
                languages: {},
                limit: 100,
                isBusy: true,
                sortBy: 'value.tf',
                sortDesc: true,
                busyAnalysis: false,
                fields: [
                    {
                        key: 'value.w',
                        label: 'Žodis',
                        sortable: true,
                    },
                    {
                        key: 'value.freq',
                        label: 'Pasikartojimai',
                        sortable: true,
                    },
                    {
                        key: 'value.tf',
                        label: 'TF',
                        sortable: true,
                        formatter: value => {
                            return parseFloat(value).toFixed(4);
                        }
                    },
                ],
            }
        },
        created() {
            this.fetchData();
        },
        methods: {
            fetchData() {
                this.isBusy = true;
                var that = this;
                if(!this.$route || !this.$route.params.id) {
                    this.$router.push({ name: 'texts' });
                } else {
                    this.axios
                        .get(`/api/texts/${this.$route.params.id}`)
                        .then((res) => {
                            this.text = res.data.text;
                            if(res.data.results) {
                                that.initializeIdfFields();
                                this.results = Object.entries(res.data.results).map(([key, value]) => ({key,value}));
                            }
                            // if(this.languages.length == 0) {
                                this.loadLanguages();
                            // }
                            this.isBusy = false;
                        })
                        .catch(function (error) {
                            if(error.response.status == 401) {
                                window.location = "/login";
                            } else {
                                $('#errorMessage').modal('show');
                            }
                        });
                }
            },
            loadLanguages() {
                this.axios
                    .get('/api/languages')
                    .catch(function (error) {
                        if(error.response.status == 401) {
                            window.location = "/login";
                        } else {
                            $('#errorMessage').modal('show');
                        }
                    })
                    .then(response => {
                        this.languages = response.data;
                    });
            },
            analyse() {
                this.busyAnalysis = true;
                this.sortBy = 'value.tf';
                if(this.fields.length > 3) {
                    this.fields.pop();
                    this.fields.pop();
                }
                var that = this;
                this.axios
                    .post(`/api/analyse/${this.$route.params.id}`, this.text)
                    .catch(function (error) {
                        if(error.response.status == 401) {
                            window.location = "/login";
                        } else {
                            $('#errorMessage').modal('show');
                        }
                    })
                    .then(function(response) {
                        that.busyAnalysis = false;
                        if(response.error) {
                            console.log(response.error);
                        } else {
                            that.fetchData();
                            $('#analyseModal').modal('hide');
                        }
                    });
            },
            showModal() {
                $('#analyseModal').modal('show');
            },
            initializeIdfFields() {
                if(this.text.use_idf) {
                    this.sortBy = 'value.tfidf';
                    var field = {
                        key: 'value.idf',
                        label: 'IDF',
                        sortable: true,
                        formatter: value => {
                            if(value) {
                                return value.toFixed(4);
                            } else {
                                return "";
                            }
                        }
                    };
                    this.fields.push(field);
                    var field = {
                        key: 'value.tfidf',
                        label: 'TF-IDF',
                        sortable: true,
                        formatter: value => {
                            if(value) {
                                return value.toFixed(4);
                            } else {
                                return "";
                            }
                        }
                    };
                    this.fields.push(field);
                }
            },
            getTitle(item) {
                var result = "";
                if(item.lemma) {
                    result += "Lematizuotas: " + item.lemma + "<br>";
                }
                if(item.incl) {
                    result += "Įtraukti žodžiai: " + item.incl.join(', ');
                }
                return result;
            },
            rowClass(item, type) {
                if (!item || type !== 'row') return;
                var index = parseInt(this.results.indexOf(item));
                if (index < 5) return 'table-success';
                if (index < 10) return 'table-primary';
                return;
            }
        }
    }
</script>
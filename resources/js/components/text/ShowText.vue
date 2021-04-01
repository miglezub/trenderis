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
            <div class="col-md-6">
                <div class="jumbotron" id="show-text" v-html="text.original_text">
                </div>
            </div>
            <div class="col-md-6">
                <b-table
                    id="analysis-table"
                    striped hover
                    :items="results"
                    :fields="fields"
                    sticky-header='700px'
                    style="clear: both; height: 700px;"
                    :busy.sync="isBusy"
                    :sort-by.sync="sortBy"
                    :sort-desc.sync="sortDesc"
                    >
                    <template v-slot:table-busy>
                        <div class="text-center text-orange my-5">
                                <b-spinner class="align-middle" type="grow"></b-spinner>
                                <strong>Kraunasi...</strong>
                        </div>
                    </template>
                    <template v-slot:cell(key)="data">
                        <span v-b-tooltip.hover v-if="data.item.value.incl" :title="data.item.value.incl.join(', ')">{{ data.item.key }}</span>
                        <span v-else>{{ data.item.key }}</span>
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
    </div>
</template>
<script>
    export default {
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
                        key: 'key',
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
                            this.loadLanguages();
                            this.isBusy = false;
                        });
                }
            },
            loadLanguages() {
                this.axios
                    .get('/api/languages')
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
                    this.sortBy = 'value.tf-idf';
                    var field = {
                        key: 'value.idf',
                        label: 'IDF',
                        sortable: true,
                        formatter: value => {
                            return value.toFixed(4);
                        }
                    };
                    this.fields.push(field);
                    var field = {
                        key: 'value.tf-idf',
                        label: 'TF-IDF',
                        sortable: true,
                        formatter: value => {
                            return value.toFixed(4);
                        }
                    };
                    this.fields.push(field);
                }
            }
        }
    }
</script>
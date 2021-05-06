<template>
    <div class="card">
        <h5 class="card-header">Tekstų sąrašas</h5>
        <div class="card-body">
            <div class="filter">
                <form @submit.prevent="submitFilter" class="d-inline">
                    <div class="form-group">
                        <label class="font-weight-bold">Datos</label>
                        <date-picker 
                            v-model="date" 
                            type="date" range 
                            placeholder="Nurodykite datas"
                            class="d-block"
                            input-class="form-control"
                            :disabled-date="disabledTomorrowAndLater">
                        </date-picker>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Raktažodis</label>
                        <input type="text" class="form-control" placeholder="Įveskite raktažodį" v-model="keyword">
                    </div>
                    <div v-if="keys.length > 0" class="form-group">
                        <label for="key-select" class="font-weight-bold">API raktas</label>
                        <select class="custom-select" id="key-select" v-model="key">
                            <option value="" disabled>Pasirinkite API raktą</option>
                            <option :value="key.id" v-for="key in keys" :key="key.id">{{ key.name }}</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary d-inline-block">Filtruoti</button>
                    <button type="button" class="btn btn-danger" title="Išvalyti filtrą" @click="clearFilter"><i class="fas fa-trash-alt"></i></button>
                </form>
                <router-link :to="{name: 'createText'}" class="btn btn-success float-right">Pridėti tekstą</router-link>
            </div>

            <b-table
                id="texts-table"
                striped hover show-empty sort-icon-left
                :items="texts"
                :fields="fields"
                :per-page="0"
                :current-page="currentPage"
                :busy.sync="isBusy"
                class="table-responsive"
                >
                <template #empty="scope">
                    <div class="text-center">Nėra pridėtų tekstų</div>
                </template>
                <template v-slot:cell(title)="data">
                    <div v-html="data.item.title"></div>
                </template>
                <template v-slot:cell(top_results)="data">
                    <div v-if="data.item.top_results" v-html="data.item.top_results.join(', ')"></div>
                </template>
                <template v-slot:cell(buttons)="data">
                    <b-button-group class="float-right">
                        <router-link :to="{name: 'showText', params: { id: data.item.id }}" class="btn btn-primary">Peržiūrėti</router-link>
                        <b-button class="btn btn-danger" @click="triggerDelete(data.item.id)">Ištrinti</b-button>
                    </b-button-group>
                </template>
                <template v-slot:table-busy>
                    <div class="text-center text-orange my-5">
                            <b-spinner class="align-middle" type="grow"></b-spinner>
                            <strong>Kraunasi...</strong>
                    </div>
                </template>
            </b-table>
            <b-pagination
                v-model="currentPage"
                :total-rows="totalTexts"
                :per-page="perPage"
                aria-controls="texts-table"
                align="right"
                v-if="totalTexts > 10"
                limit="7"
                ></b-pagination>
        </div>
        <delete-confirm title="Teksto ištrynimas" message="Ar tikrai norite ištrinti tekstą?<br>Pašalinus tekstą bus ištrinti ir jo analizės rezultatai." :id="deleteId" v-on:approvedDeletion="deleteText"></delete-confirm>
        <message id="filterMessage" type="error" title="Filtravimo klaida" message="Prašome suvesti bent vieną filtro lauką!"></message>
        <message id="errorMessage" type="error" title="Sistemos klaida" message="Atsiprašome, įvyko nenumatyta sistemos klaida."></message>
    </div>
</template>
 
<script>
    import DatePicker from 'vue2-datepicker';
    import 'vue2-datepicker/index.css';
    import 'vue2-datepicker/locale/lt';
    import DeleteConfirm from '../layout/DeleteConfirm.vue';
    import Message from '../layout/Message.vue';
    export default {
        components: { DatePicker, DeleteConfirm, Message },
        data() {
            return {
                texts: [],
                totalTexts: 0,
                date: [],
                keyword: "",
                key: "",
                keys: [],
                deleteId: 0,
                perPage: 10,
                currentPage: 1,
                languages: {},
                isBusy: true,
                fields: [
                    {
                        key: 'title',
                        label: 'Pavadinimas',
                        sortable: false
                    },
                    {
                        key: 'top_results',
                        label: 'Raktažodžiai',
                        sortable: false
                    },
                    {
                        key: 'language_id',
                        label: 'Kalba',
                        formatter: value => {
                            if(this.languages && this.languages[value - 1]) {
                                return this.languages[value - 1].name;
                            } else {
                                return "";
                            }
                        },
                        sortable: true,
                        tdClass: 'hideMobile',
                        thClass: 'hideMobile'
                    },
                    {
                        key: 'created_at',
                        label: 'Data',
                        formatter: value => {
                            return value.split("T")[0];
                        },
                        sortable: true,
                        tdClass: 'hideMobile',
                        thClass: 'hideMobile'
                    },
                    {
                        key: 'buttons',
                        label: '',
                        sortable: false
                    },
                ],
            }
        },
        mounted() {
            this.fetchData(true);
        },
        computed: {
            rows() {
                return this.texts.length
            }
        },
        watch: {
            currentPage: {
                handler: function(value) {
                    this.fetchData();
                }
            }
        },
        methods: {
            async fetchData(first = false) {
                this.isBusy = true;
                this.totalTexts = 0;
                var date1 = "";
                var date2 = "";
                if(this.date[0]) {
                    var date1 = new Date();
                    date1.setTime(this.date[0].getTime() + 3600 * 1000 * 3 - 1);
                }
                if(this.date[1]) {
                    var date2 = new Date();
                    date2.setTime(this.date[1].getTime() + 3600 * 1000 * 27 - 1);
                }
                this.axios
                .get('/api/texts/filter', {
                    params: {
                        date1: date1,
                        date2: date2,
                        keyword: this.keyword,
                        key: this.key,
                        page: this.currentPage,
                        limit: this.perPage
                    }
                })
                .catch(function (error) {
                    if(error.response.status == 401) {
                        window.location = "/login";
                    } else {
                        $('#errorMessage').modal('show');
                    }
                })
                .then(response => {
                    this.texts = response.data.results;
                    this.totalTexts = response.data.total;
                    if(first) {
                        this.loadLanguages();
                    } else {
                        this.isBusy = false;
                    }
                });
            },
            triggerDelete(id) {
                this.deleteId = id;
                $('#deleteModal').modal('show');
            },
            deleteText(id) { 
                this.axios
                    .delete(`/api/texts/${id}`)
                    .catch(function (error) {
                        if(error.response.status == 401) {
                            window.location = "/login";
                        } else {
                            $('#errorMessage').modal('show');
                        }
                    })
                    .then(response => {
                        this.filterList();
                        $('#deleteModal').modal('hide');
                        $('#textDeleted').modal('show');
                    });
            },
            submitFilter() {
                $('form input, form select').removeClass('is-invalid');
                if(this.keyword == "" && this.key == "" && (this.date.length < 2 || this.date[0] == "" || this.date[1] == "")) {
                    $('form input, form select').addClass('is-invalid');
                    $('#filterMessage').modal('show');
                    return;
                } else {
                    this.filterList();
                }
            },
            clearFilter() {
                this.isBusy = true;
                $('form input, form select').removeClass('is-invalid');
                this.date[0] = "";
                this.date[1] = "";
                this.keyword = "";
                this.key = "";
                this.page = 1;
                this.fetchData();
            },
            filterList() {
                this.isBusy = true;
                var date1 = "";
                var date2 = "";
                if(this.date[0]) {
                    var date1 = new Date();
                    date1.setTime(this.date[0].getTime() + 3600 * 1000 * 3 - 1);
                }
                if(this.date[1]) {
                    var date2 = new Date();
                    date2.setTime(this.date[1].getTime() + 3600 * 1000 * 27 - 1);
                }
                this.currentPage = 1;
                this.axios
                    .get('/api/texts/filter', {
                        params: {
                            date1: date1,
                            date2: date2,
                            keyword: this.keyword,
                            key: this.key,
                            page: this.currentPage,
                            limit: this.perPage
                        }
                    })
                    .catch(function (error) {
                        if(error.response.status == 401) {
                            window.location = "/login";
                        } else {
                            $('#errorMessage').modal('show');
                        }
                    })
                    .then(response => {
                        this.texts = response.data.results;
                        this.totalTexts = response.data.total;
                        this.isBusy = false;
                    });
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
                        this.fetchApiKeys();
                    });
            },
            disabledTomorrowAndLater(date) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                return date > today;
            },
            fetchApiKeys() {
                this.axios
                    .get('/api/keys')
                    .catch(function (error) {
                        if(error.response.status == 401) {
                            window.location = "/login";
                        } else {
                            $('#errorMessage').modal('show');
                        }
                    })
                    .then(response => {
                        this.keys = response.data;
                        this.isBusy = false;
                    });
            },
        }
    }
</script>
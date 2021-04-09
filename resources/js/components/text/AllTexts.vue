<template>
    <div class="card">
        <h5 class="card-header">Tekstų sąrašas</h5>
        <div class="card-body">
            <div class="filter">
                <form @submit.prevent="filterList" class="d-inline">
                    <div class="form-group d-inline-block">
                        <label>Datos</label>
                        <date-picker 
                            v-model="date" 
                            type="date" range 
                            placeholder="Nurodykite datas"
                            :disabled-date="disabledTomorrowAndLater">
                        </date-picker>
                    </div>
                    <button type="submit" class="btn btn-primary d-inline-block">Filtruoti</button>
                </form>
                <router-link :to="{name: 'createText'}" class="btn btn-success float-right">Pridėti tekstą</router-link>
            </div>

            <b-table
                id="texts-table"
                striped hover show-empty sort-icon-left
                :items="texts"
                :fields="fields"
                :per-page="perPage"
                :current-page="currentPage"
                :busy.sync="isBusy"
                >
                <template #empty="scope">
                    <div class="text-center">Nėra pridėtų tekstų</div>
                </template>
                <template v-slot:cell(original_text)="data">
                    <div v-html="data.item.original_text"></div>
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
                :total-rows="rows"
                :per-page="perPage"
                aria-controls="texts-table"
                align="right"
                v-if="texts.length > 10"
                ></b-pagination>
        </div>
        <delete-confirm title="Teksto ištrynimas" message="Ar tikrai norite ištrinti tekstą?<br>Pašalinus tekstą bus ištrinti ir jo analizės rezultatai." :id="deleteId" v-on:approvedDeletion="deleteText"></delete-confirm>
    </div>
</template>
 
<script>
    import DatePicker from 'vue2-datepicker';
    import 'vue2-datepicker/index.css';
    import 'vue2-datepicker/locale/lt';
    import DeleteConfirm from '../layout/DeleteConfirm.vue';
    export default {
        components: { DatePicker, DeleteConfirm },
        data() {
            return {
                texts: [],
                date: [],
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
                        key: 'language_id',
                        label: 'Kalba',
                        formatter: value => {
                            if(this.languages && this.languages[value - 1]) {
                                return this.languages[value - 1].name;
                            } else {
                                return "";
                            }
                        },
                        sortable: true
                    },
                    {
                        key: 'created_at',
                        label: 'Data',
                        formatter: value => {
                            return value.split("T")[0];
                        },
                        sortable: true
                    },
                    {
                        key: 'buttons',
                        label: '',
                        sortable: false
                    },
                ],
            }
        },
        created() {
            this.axios
                .get('/api/texts')
                .then(response => {
                    this.texts = response.data;
                    this.loadLanguages();
                });
        },
        computed: {
            rows() {
                return this.texts.length
            }
        },
        methods: {
            triggerDelete(id) {
                this.deleteId = id;
                $('#deleteModal').modal('show');
            },
            deleteText(id) { 
                this.axios
                    .delete(`/api/texts/${id}`)
                    .then(response => {
                        let i = this.texts.map(data => data.id).indexOf(id);
                        this.texts.splice(i, 1);
                        $('#deleteModal').modal('hide');
                    });
            },
            filterList() {
                this.isBusy = true;
                const date1 = new Date();
                date1.setTime(this.date[0].getTime() + 3600 * 1000 * 26 - 1);
                const date2 = new Date();
                date2.setTime(this.date[1].getTime() + 3600 * 1000 * 26 - 1);
                this.axios
                    .get('/api/texts/filter', {
                        params: {
                            date1: date1,
                            date2: date2
                        }
                    })
                    .then(response => {
                        this.texts = response.data;
                        this.isBusy = false;
                    });
            },
            loadLanguages() {
                this.axios
                    .get('/api/languages')
                    .then(response => {
                        this.languages = response.data;
                        this.isBusy = false;
                    });
            },
            disabledTomorrowAndLater(date) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                return date > today;
            },
        }
    }
</script>
<template>
    <div class="card">
        <h5 class="card-header">API raktų sąrašas</h5>
        <div class="card-body">
            <b-overlay
                :show="generateBusy"
                rounded
                opacity="0.6"
                spinner-small
                spinner-variant="success"
                class="float-right"
                >
                    <b-button class="btn btn-success mb-3" @click="generateKey" :disabled="generateBusy">Generuoti API raktą</b-button>
            </b-overlay>
            <b-alert
                :show="dismissCountDown"
                dismissible
                fade
                variant="success"
                style="clear: both;"
                @dismiss-count-down="countDownChanged"
                >
                {{ msg }}
            </b-alert>
            <b-table
                id="api-keys-table"
                striped hover show-empty
                :items="apiKeys"
                :fields="fields"
                :per-page="perPage"
                :current-page="currentPage"
                :busy.sync="isBusy"
                sort-icon-left
                >
                <template #empty="scope">
                    <div class="text-center">Nėra pridėtų API raktų</div>
                </template>
                <template v-slot:cell(buttons)="data">
                    <b-button-group class="float-right">
                        <b-button class="btn btn-success" v-clipboard:copy="data.item.key" v-clipboard:success="onCopy">Kopijuoti</b-button>
                        <b-button class="btn btn-primary" @click="triggerEdit(data.item.id)">Redaguoti</b-button>
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
                v-if="showPagination"
                :total-rows="rows"
                :per-page="perPage"
                aria-controls="api-keys-table"
                align="right"
                ></b-pagination>
        </div>
        <delete-confirm title="API rakto ištrynimas" message="Ar tikrai norite ištrinti API raktą?<br>Pašalinus API raktą, išorinės sistemos naudojusios šį API raktą praras prieigą prie šios sistemos.<br>Tekstai pridėti iš šio API rakto nebus ištrinti." :id="deleteId" v-on:approvedDeletion="deleteApiKey"></delete-confirm>
        <edit-key ref="editComponent" v-on:editSuccess="editSuccessMsg"></edit-key>
        <message type="success" id="successModal" title="API rakto generavimas" message="API raktas sugeneruotas.<br>Naujo rakto duomenys pateikiami API raktų sąraše"></message>
    </div>
</template>
 
<script>
    import DeleteConfirm from '../layout/DeleteConfirm.vue';
    import Message from '../layout/Message.vue';
    import EditKey from './EditKey.vue';

    export default {
        components: { DeleteConfirm, Message, EditKey },
        data() {
            return {
                key: {},
                apiKeys: [],
                date: [],
                deleteId: 0,
                editId: 0,
                perPage: 8,
                currentPage: 1,
                isBusy: true,
                generateBusy: false,
                showPagination: false,
                msg: "",
                fields: [
                    {
                        key: 'name',
                        label: 'Pavadinimas',
                        sortable: true
                    },
                    {
                        key: 'key',
                        label: 'API raktas',
                        sortable: false
                    },
                    {
                        key: 'textCount',
                        label: 'Pridėta tekstų',
                        sortable: true
                    },
                    {
                        key: 'buttons',
                        label: '',
                        sortable: false
                    },
                ],
                dismissSecs: 5,
                dismissCountDown: 0,
            }
        },
        created() {
            this.fetchData();
        },
        computed: {
            rows() {
                return this.apiKeys.length
            }
        },
        methods: {
            fetchData() {
                this.isBusy = true;
                this.axios
                    .get('/api/keys')
                    .then(response => {
                        this.apiKeys = response.data;
                        this.isBusy = false;
                        if(this.apiKeys.length > this.perPage) {
                            this.showPagination = true;
                        }
                    });
            },
            generateKey() {
                this.generateBusy = true;
                var that = this;
                this.axios
                    .post('/api/keys', this.key)
                    .then(function(response) {
                        that.generateBusy = false;
                        if(response.data.error) {
                            console.log(response.error);
                        } else {
                            $('#successModal').modal('show');
                            that.fetchData();
                        }
                    })
                    .catch(err => console.log(err))
                    .finally(() => this.loading = false)
            },
            triggerDelete(id) {
                this.deleteId = id;
                $('#deleteModal').modal('show');
            },
            deleteApiKey(id) { 
                this.axios
                    .delete(`/api/keys/${id}`)
                    .then(response => {
                        let i = this.apiKeys.map(data => data.id).indexOf(id);
                        this.apiKeys.splice(i, 1);
                        $('#deleteModal').modal('hide');
                    });
            },
            triggerEdit(id) {
                this.editId = id;
                this.$refs.editComponent.fetchData(id);
                $('#editKeyModal').modal('show');
            },
            editSuccessMsg(text) {
                this.msg = text;
                this.showAlert();
                this.fetchData();
            },
            countDownChanged(dismissCountDown) {
                this.dismissCountDown = dismissCountDown
            },
            showAlert() {
                this.dismissCountDown = this.dismissSecs
            },
            onCopy: function (e) {
                console.log(e.trigger);
                e.trigger.textContent = 'Nukopijuota!';
                setTimeout(() => {
                    e.trigger.textContent = 'Kopijuoti';
                }, 3000); 
            },
        }
    }
</script>
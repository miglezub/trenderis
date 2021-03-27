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
                            placeholder="Nurodykite datas">
                        </date-picker>
                    </div>
                    <button type="submit" class="btn btn-primary d-inline-block">Filtruoti</button>
                </form>
                <router-link :to="{name: 'createText'}" class="btn btn-default float-right">Pridėti tekstą</router-link>
            </div>
    
            <table class="table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Tekstas</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="text in texts" :key="text.id">
                    <td>{{ text.id }}</td>
                    <td v-html="text.original_text"></td>
                    <td>
                        <div class="btn-group" role="group">
                            <router-link :to="{name: 'edit', params: { id: text.id }}" class="btn btn-default">Redaguoti</router-link>
                            <button class="btn btn-danger" @click="triggerDelete(text.id)">Ištrinti</button>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <delete-confirm title="Teksto ištrynimas" message="Ar tikrai norite ištrinti tekstą?<br>Pašalinus tekstą bus ištrinti ir jo analizės rezultatai." :id="deleteId" v-on:approvedDeletion="deleteText"></delete-confirm>
    </div>
</template>
 
<script>
    import DatePicker from 'vue2-datepicker';
    import 'vue2-datepicker/index.css';
    import DeleteConfirm from '../layout/DeleteConfirm.vue';
    export default {
        components: { DatePicker, DeleteConfirm },
        data() {
            return {
                texts: [],
                date: [],
                deleteId: 0
            }
        },
        created() {
            this.axios
                .get('/api/texts')
                .then(response => {
                    this.texts = response.data;
                });
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
                this.axios
                    .get('/api/texts')
                    .then(response => {
                        this.texts = response.data;
                    });
            }
        }
    }
</script>
<template>
    <div class="card">
        <h5 class="card-header">Tekstų sąrašas</h5>
        <div class="card-body">
            <div class="filter">
                <form @submit.prevent="filterList">
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
                    <td>{{ text.original_text }}</td>
                    <td>
                        <div class="btn-group" role="group">
                            <router-link :to="{name: 'edit', params: { id: text.id }}" class="btn btn-default">Redaguoti</router-link>
                            <button class="btn btn-danger" @click="deleteProduct(text.id)">Ištrinti</button>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
 
<script>
    import DatePicker from 'vue2-datepicker';
    import 'vue2-datepicker/index.css';
    export default {
        components: { DatePicker },
        data() {
            return {
                texts: [],
                date: []
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
            deleteText(id) { 
                this.axios
                    .delete(`/api/texts/${id}`)
                    .then(response => {
                        let i = this.texts.map(data => data.id).indexOf(id);
                        this.texts.splice(i, 1)
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
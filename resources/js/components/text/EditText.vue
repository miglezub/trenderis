<template>
    <div class="card">
        <h5 class="card-header">Redaguoti tekstą</h5>
        <div class="card-body row">
            <div class="col-md-6">
                <form @submit.prevent="updateProduct">
                    <div class="form-group">
                        <label>Id</label>
                        <input type="text" class="form-control" v-model="text.id">
                    </div>
                    <div class="form-group">
                        <label>Text</label>
                        <input type="text" class="form-control" v-model="text.original_text">
                    </div>
                    <button type="submit" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>
</template>
 
<script>
    export default {
        data() {
            return {
                text: {}
            }
        },
        created() {
            this.axios
                .get(`/api/texts/${this.$route.params.id}`)
                .then((res) => {
                    this.text = res.data;
                });
        },
        methods: {
            updateProduct() {
                this.axios
                    .patch(`/api/texts/${this.$route.params.id}`, this.text)
                    .then((res) => {
                        this.$router.push({ name: 'home' });
                    });
            }
        }
    }
</script>
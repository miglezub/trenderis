<template>
    <div class="card">
        <h5 class="card-header">Pridėti tekstą</h5>
        <div class="card-body">
            <form @submit.prevent="handleSubmit" class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label class="font-weight-bold">Tekstas</label>
                        <textarea type="text" class="form-control" v-bind:class="{ 'is-invalid': submitted && !$v.text.original_text.required }" rows="20" v-model="text.original_text"></textarea>
                        <div v-if="submitted && !$v.text.original_text.required" class="invalid-feedback">Privaloma suvesti tekstą</div>
                    </div>
                </div>
                <div class="col-md-4 pt-5">
                    <div class="form-group">
                        <label for="language-select" class="font-weight-bold">Teksto kalba</label>
                        <select class="custom-select" id="language-select" v-model="text.language_id" v-bind:class="{ 'is-invalid': submitted && (!$v.text.language_id.required || !$v.text.language_id.minValue) }">
                            <option value="" disabled>Pasirinkite kalbą</option>
                            <option :value="language.id" v-for="language in languages" :key="language.id">{{ language.name }}</option>
                        </select>
                        <div v-if="submitted && (!$v.text.language_id.required || !$v.text.language_id.minValue)" class="invalid-feedback">Privaloma nurodyti kalbą</div>
                    </div>
                    <div class="form-group">
                        <label class="switch">
                            <input type="checkbox" v-model="text.use_idf">
                            <span class="slider round"></span>
                        </label>
                        <span class="pl-2">Ar naudoti TF-IDF?</span>
                    </div>
                    <button type="submit" class="btn btn-primary mt-5">Analizuoti</button>
                </div>
            </form>
        </div>
    </div>
</template>
 
<script>
import { required, minValue } from "vuelidate/lib/validators";
    export default {
        data() {
            return {
                text: {
                    original_text: "",
                    language_id: "",
                    use_idf: false,
                    use_word2vec: false
                },
                languages: {},
                submitted: false,
            }
        },
        created() {
            this.axios
                .get('/api/languages')
                .then(response => {
                    this.languages = response.data;
                });
        },
        validations: {
            text: {
                original_text: { required },
                language_id: { required, minValue: minValue(1) }
            }
        },
        methods: {
            handleSubmit(e) {
                this.submitted = true;

                this.$v.$touch();
                if (this.$v.$invalid) {
                    return;
                }

                this.axios
                    .post('/api/texts', this.text)
                    .then(response => (
                        this.$router.push({ name: 'texts' })
                    ))
                    .catch(err => console.log(err))
                    .finally(() => this.loading = false)
            },
        }
    }
</script>
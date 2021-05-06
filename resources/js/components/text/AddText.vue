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
                        <label class="font-weight-bold">Pavadinimas</label>
                        <textarea class="form-control" rows="2" v-model="text.title"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="language-select" class="font-weight-bold">Teksto kalba</label>
                        <select class="custom-select" id="language-select" v-model="text.language_id" v-bind:class="{ 'is-invalid': submitted && (!$v.text.language_id.required || !$v.text.language_id.minValue) }">
                            <option value="" disabled>Pasirinkite kalbą</option>
                            <option :value="language.id" v-for="language in languages" :key="language.id">{{ language.name }}</option>
                        </select>
                        <div v-if="submitted && (!$v.text.language_id.required || !$v.text.language_id.minValue)" class="invalid-feedback">Privaloma nurodyti kalbą</div>
                    </div>
                    <div class="form-group mb-5">
                        <label class="switch">
                            <input type="checkbox" v-model="text.use_idf">
                            <span class="slider round"></span>
                        </label>
                        <span class="pl-2">Ar naudoti TF-IDF?</span>
                    </div>
                    <b-overlay
                    :show="busy"
                    rounded
                    opacity="0.6"
                    spinner-small
                    spinner-variant="primary"
                    class="d-inline-block"
                    >
                    <button type="submit" class="btn btn-primary" :disabled="busy">Pridėti tekstą</button>
                    </b-overlay>
                </div>
            </form>
        </div>
        <message id="errorMessage" type="error" title="Sistemos klaida" message="Atsiprašome, įvyko nenumatyta sistemos klaida."></message>S
    </div>
</template>
 
<script>
import Message from '../layout/Message.vue';
import { required, minValue } from "vuelidate/lib/validators";
    export default {
        data() {
            return {
                text: {
                    title: "",
                    original_text: "",
                    language_id: "",
                    use_idf: false,
                    use_word2vec: false
                },
                languages: {},
                submitted: false,
                busy: false,
            }
        },
        created() {
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
        validations: {
            text: {
                original_text: { required },
                language_id: { required, minValue: minValue(1) }
            }
        },
        methods: {
            handleSubmit(e) {
                this.busy = true;
                this.submitted = true;
                var that = this;

                this.$v.$touch();
                if (this.$v.$invalid) {
                    this.busy = false;
                    return;
                }

                this.axios
                    .post('/api/texts', this.text)
                    .then(function(response) {
                        that.busy = false;
                        if(response.data.error) {
                            console.log(response.error);
                        } else {
                            if(response.data.id) {
                                that.$router.push({ name: 'showText', params: { id: response.data.id } })
                            } else {
                                that.$router.push({ name: 'texts' })
                            }
                        }
                    })
                    .catch(function (error) {
                        if(error.response.status == 401) {
                            window.location = "/login";
                        } else {
                            $('#errorMessage').modal('show');
                        }
                    })
                    .finally(() => this.loading = false)
            },
        }
    }
</script>
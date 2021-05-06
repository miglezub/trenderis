<template>
    <div class="modal" tabindex="-1" role="dialog" id="editKeyModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">API rakto redagavimas</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form @submit.prevent="handleSubmit">
                    <div class="modal-body">
                            <div class="form-group">
                                <label for="language-select" class="font-weight-bold">API rakto pavadinimas</label>
                                <input type="text" class="form-control" name="name" v-model="key.name" placeholder="API rakto pavadinimas" v-bind:class="{ 'is-invalid': submitted && (!$v.key.name.required || !$v.key.name.minLength || !$v.key.name.maxLength) }">
                                <div v-if="submitted && !$v.key.name.required" class="invalid-feedback">Privaloma suvesti API rakto pavadinimą</div>
                                <div v-else-if="submitted && !$v.key.name.minLength" class="invalid-feedback">API rakto pavadinimas turi būti ilgesnis nei 4 simboliai</div>
                                <div v-else-if="submitted && !$v.key.name.maxLength" class="invalid-feedback">API rakto pavadinimas turi būti trumpesnis nei 255 simboliai</div>
                            </div>
                    </div>
                    <div class="modal-footer">
                        <b-overlay
                        :show="busy"
                        rounded
                        opacity="0.6"
                        spinner-small
                        spinner-variant="primary"
                        class="d-inline-block"
                        >
                            <button type="submit" class="btn btn-primary" :disabled="busy">Išsaugoti</button>
                        </b-overlay>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
<script>
import { required, minLength, maxLength } from "vuelidate/lib/validators";
    export default {
        data() {
            return {
                id: 0,
                key: {},
                submitted: false,
                busy: false,
            }
        },
        validations: {
            key: {
                name: { required, minLength: minLength(4), maxLength: maxLength(255) },
            }
        },
        methods: {
            fetchData(id) {
                this.id = id;
                if(!this.id) {
                    $('#editKeyModal').modal('hide');
                } else {
                    this.axios
                        .get(`/api/keys/${this.id}`)
                        .catch(function (error) {
                            if(error.response.status == 401) {
                                window.location = "/login";
                            } else {
                                $('#errorMessage').modal('show');
                            }
                        })
                        .then((res) => {
                            this.key = res.data;
                        });
                }
            },
            handleSubmit(e) {
                this.busy = true;
                this.submitted = true;
                var that = this;

                this.$v.$touch();
                if (this.$v.$invalid) {
                    this.busy = false;
                    return;
                }
                this.submitted = false;
                this.axios
                    .patch(`/api/keys/${this.id}`, this.key)
                    .then(function(response) {
                        that.busy = false;
                        if(response.data.error) {
                        } else {
                            $('#editKeyModal').modal('hide');
                            that.$emit('editSuccess', response.data.success);
                        }
                    })
                    .catch(function (error) {
                        if(error.response.status == 401) {
                            window.location = "/login";
                        } else {
                            $('#editKeyModal').modal('hide');
                            that.busy = false;
                            that.$emit('editError');
                        }
                    })
                    .finally(() => this.loading = false)
            },
        }
    }
</script>
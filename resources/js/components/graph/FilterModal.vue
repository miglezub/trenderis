<template>
    <div class="modal" tabindex="-1" role="dialog" id="filterModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filtruoti grafiką</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form @submit.prevent="filterGraph" class="d-inline">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="type-select" class="font-weight-bold">Grafiko tipas</label>
                        <select class="custom-select" id="type-select" v-model="filter.type" v-bind:class="{ 'is-invalid': submitted && (!$v.filter.type.required || !$v.filter.type.minValue) }">
                            <option value="" disabled>Pasirinkite grafiko tipą</option>
                            <option :value="type.id" v-for="type in types" :key="type.id">{{ type.name }}</option>
                        </select>
                        <div v-if="submitted && (!$v.filter.type.required || !$v.filter.type.minValue)" class="invalid-feedback">Privaloma nurodyti grafiko tipą</div>
                    </div>
                    <div class="form-group">
                        <label for="date-select" class="font-weight-bold">Datos</label>
                        <date-picker 
                            v-model="filter.dates" 
                            type="date" range 
                            placeholder="Nurodykite datas"
                            :disabled-date="disabledTomorrowAndLater"
                            @pick="handlePick"
                            class="d-block w-100"
                            id="date-select"
                            v-bind:class="{ 'is-invalid': submitted && !$v.filter.dates.required  }">
                        </date-picker>
                        <div v-if="submitted && (!$v.filter.dates.required)" class="invalid-feedback">Privaloma nurodyti datas arba API raktą</div>
                    </div>
                    <div class="form-group">
                        <label for="key-select" class="font-weight-bold">API raktas (neprivalomas)</label>
                        <select class="custom-select" id="key-select" v-model="filter.key" v-bind:class="{ 'is-invalid': submitted && !$v.filter.key.required }">
                            <option value="" disabled>Pasirinkite API raktą</option>
                            <option :value="key.id" v-for="key in keys" :key="key.id">{{ key.name }}</option>
                        </select>
                        <div v-if="submitted && (!$v.filter.key.required)" class="invalid-feedback">Privaloma nurodyti datas arba API raktą</div>
                    </div>
                    <div class="form-group" v-if="filter.type == 2">
                        <label class="font-weight-bold">Raktažodis</label>
                        <input type="text" class="form-control" placeholder="Įveskite raktažodį" v-model="filter.keyword" 
                        v-bind:class="{ 'is-invalid': submitted && (!$v.filter.keyword.required) }">
                        <div v-if="submitted && (!$v.filter.keyword.required)" class="invalid-feedback">Privaloma nurodyti raktažodį</div>
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
                        <button type="submit" class="btn btn-primary" :disabled="busy">Filtruoti</button>
                    </b-overlay>
                </div>
            </form>
            </div>
        </div>
    </div>
</template>
<script>
import { required, minValue, requiredIf } from "vuelidate/lib/validators";
import DatePicker from 'vue2-datepicker';
import 'vue2-datepicker/index.css';
import 'vue2-datepicker/locale/lt';
export default {
    components: { DatePicker },
    data() {
        return {
            filter: {
                type: "",
                dates: [],
                key: "",
                keyword: "",
            },
            keys: {},
            types: {
                1: { id: 1, name: "Populiariausių raktažodžių grafikas" },
                2: { id: 2, name: "Raktažodžio istorinis grafikas" }

            },
            submitted: false,
            newFilter: {},
            busy: false,
        }
    },
    validations() {
        return {
            filter: {
                type: { required, minValue: minValue(1) },
                keyword: {
                    required: requiredIf(function (filter) {
                        return filter.type == 2 && filter.keyword == "";
                    })
                },
                dates: {
                    required: requiredIf(function (filter) {
                        return filter.type == 1 && (filter.key == "" || filter.dates[0] == null);
                    })
                },
                key: {
                    required: requiredIf(function (filter) {
                        return filter.type == 1 && ((filter.dates).length < 1 || filter.dates[0] == null);
                    })
                }
            }
        };
    },
    created() {
        this.fetchApiKeys();
    },
    methods: {
        fetchApiKeys() {
            this.axios
                .get('/api/keys')
                .then(response => {
                    this.keys = response.data;
                });
        },
        disabledTomorrowAndLater(date) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            return date > today;
        },
        filterGraph() {
            this.busy = true;
            this.submitted = true;

            this.$v.$touch();
            if (this.$v.$invalid) {
                this.busy = false;
                return;
            }

            this.$emit('filterGraph', this.filter);
            $('#filterModal').modal('hide');
            this.busy = false;
        },
        handlePick(newDate) {
            if(!this.filter.dates[0]) {
                this.filter.dates[0] = new Date(newDate.getTime() + 30 * 24 * 3600 * 1000);
                this.filter.dates[1] = new Date(newDate.getTime() + 30 * 24 * 3600 * 1000);
            }
        }
    }
}
</script>

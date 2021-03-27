<template>
    <div class="modal" tabindex="-1" role="dialog" id="deleteModal">
        {{ id }}
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" v-html="title"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p v-html="message"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" @click="approved()">Ištrinti</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Uždaryti</button>
            </div>
            </div>
        </div>
        </div>
</template>
<script>
export default {
    props: {
        id: Number,
        title: String,
        message: String
    },
    methods: {
        approved() {
            this.$emit('approvedDeletion', this.id);
        }
    }
}
</script>

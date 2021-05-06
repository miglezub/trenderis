<template>
    <div class="modal" tabindex="-1" role="dialog" :id="id">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" v-html="title"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <sweetalert-icon :icon="type" />
                <p class="text-center" v-html="message"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Gerai</button>
            </div>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    props: {
        id: String,
        type: String,
        title: String,
        message: String
    },
}
</script>

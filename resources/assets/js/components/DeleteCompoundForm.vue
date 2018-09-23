<template>
    <form method="POST" :action="postRoute" autocomplete="off">
        <input type="hidden" name="_token" :value="csrf_token">
        <input type="hidden" name="_method" value="delete">

        <p>Type in the label of this compound to confirm: </p>

        <input @keyup="checkIfLabelMatches" style="width:260px; text-align: center;" class="form-control" type="text" v-model="label" name="label" :placeholder="placeHolder" autofocus> 

        <br>
        
        <button type="submit" class="btn btn-danger" :disabled="!matched">Delete this compound</button>

    </form>
</template>

<script>
    export default {
        props: ['csrf_token', 'compoundId', 'compoundLabel'],

        data() {
            return {
                label: '',
                matched: false,
            }
        },

        computed: {
            postRoute() {
                return '/compounds/' + this.compoundId;
            },

            placeHolder() {
                return 'Type ' + this.compoundLabel + ' to confirm';
            }
        },

        methods: {
            checkIfLabelMatches() {
                if (this.label == this.compoundLabel) {
                    this.matched = true;
                } else {
                    this.matched = false;
                }
            }
        },


    }
</script>

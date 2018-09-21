<template>
    <div>
        <span @click="toggleEdit" v-if="!showEdit">{{ data }}</span>
        <div class="form-group" v-if="showEdit">
            <input ref="IR" @blur="submit" @keyup.enter="submit" class="form-control" type="text" v-model="data">
        </div>
    </div>
</template>

<script>
    export default {
        props: ['data'],

        data() {
            return {
                showEdit: false,
            }
        },

        methods: {
            submit() {
                this.showEdit = false;
            },

            toggleEdit() {
                this.showEdit = true;
                
                setTimeout(() => {
                    this.$refs.IR.focus();
                }, 1);

            }
        }
    }
</script>

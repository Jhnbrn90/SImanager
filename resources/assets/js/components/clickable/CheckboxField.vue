<template>
    <div :class="classes" style="display:inline-block; width: 100%; height: 100%; padding: 10px; text-align: center;">    
        <input type="checkbox" v-model="fieldData" @click.prevent="toggleAndSubmit">
    </div>
</template>

<script>
    export default {
        props: ['id', 'data', 'column'],

        data() {
            return {
                fieldData: !!+this.data,
                showEdit: false,
            }
        },

        computed: {
            patchPath() {
                return '/compounds/' + this.id;
            },

            patchData() {
                return { column: this.column, value: this.fieldData };
            },

            classes() {
                return this.fieldData ? 'bg-success' : 'bg-danger';
            }
        },

        methods: {
            toggleAndSubmit() {
                this.fieldData = !this.fieldData;

                axios.patch(this.patchPath, this.patchData)
                    .then((response) => {
                    console.log(response.data);
                });
            },

        }
    }
</script>

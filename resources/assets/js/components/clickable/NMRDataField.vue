<template>
    <div
        :class="classes"
        style="display:inline-block; width: 100%; height: 100%; padding: 10px;"
    >
        
        <div style="position:relative; top: 50%; transform: translateY(-50%);">
            <input type="checkbox" @click.prevent="toggleEdit" v-if="!fieldData && !showEdit">

            <span @click="toggleEdit" v-if="!showEdit && fieldData">
                <input class="btn btn-primary" type="checkbox" checked>
            </span>
            
            <div class="form-group" v-if="showEdit">
                <input 
                    ref="inputField" 
                    @blur="submitData" 
                    @keyup.enter="unsetFocus"
                    @keyup.esc="unsetFocus" 
                    class="form-control" 
                    type="text" 
                    v-model="fieldData"
                >
            </div>
        </div>

    </div>
</template>

<script>
    export default {
        props: ['id', 'data', 'column'],

        data() {
            return {
                fieldData: this.data,
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
                if (this.column == 'notes') {
                    return '';
                }
                return this.fieldData ? 'bg-success' : 'bg-danger';
            }
        },

        methods: {
            setFocus() {
                setTimeout(() => {
                    this.$refs.inputField.focus();
                }, 2);
            },

            unsetFocus() {
                this.$refs.inputField.blur();
            },

            submitData() {
                this.toggleEdit();

                axios.patch(this.patchPath, this.patchData)
                    .then((response) => {
                    console.log(response.data);
                });
            },

            toggleEdit() {
                this.showEdit = !this.showEdit;

                if (this.showEdit) {
                    this.setFocus();
                }

            }
        }
    }
</script>

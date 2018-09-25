<template>
    <div
        :class="classes"
        style="display:inline-block; width: 100%; height: 100%; padding: 10px; text-align: center;"
    >
        
        <div style="position:relative; top: 50%; transform: translateY(-50%);">
            <button @click.prevent="toggleEdit" v-if="!fieldData && !showEdit" class="btn btn-link" style="color: grey;">&plus; add</button>

            <span @click="toggleEdit" v-if="!showEdit && fieldData">
                {{ fieldData.substring(0,30) + '...' }}
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

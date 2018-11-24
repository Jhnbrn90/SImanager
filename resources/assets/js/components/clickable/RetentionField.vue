<template>
    <div
        :class="classes"
        style="display:inline-block; width: 100%; height: 100%; padding: 10px;"
    >
        
        <div style="position:relative; top: 50%; transform: translateY(-50%); text-align: center;">
            <button @click.prevent="toggleEdit" v-if="!fieldData && !showEdit" class="btn btn-link" style="color: grey;">&plus; add</button>

            <span @click="toggleEdit" v-if="!showEdit && fieldData">
                {{ trimmedRetentionValue }}
                <small style="display:block; font-size: 10px;">
                    {{ trimmedRetentionSolvent }}
                </small>
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
            },

            trimmedRetentionValue() {
                const regex = /\d+\.\d+/g;
                let match = regex.exec(this.fieldData);
                return match[0];
            },
            trimmedRetentionSolvent() {
                const regex = /\(.*\)/g;
                let match = regex.exec(this.fieldData);
                return match[0];
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

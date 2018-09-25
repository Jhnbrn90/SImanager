<template>
    <div :class="classes" style="display:inline-block; width: 100%; height: 100%; padding: 10px;">    
        <div style="position:relative; top: 50%; transform: translateY(-50%);">

            <button @click.prevent="toggleEdit" v-if="!fieldData && !showEdit" class="btn btn-link" style="color: grey;">
                &plus; add
            </button>

            <span @click="toggleEdit" v-if="!showEdit">{{ fieldData }}</span>

            <div class="form-group" v-if="showEdit">
                <select 
                    ref="inputField" 
                    class="form-control"
                    @blur="submitData" 
                    @keyup.enter="unsetFocus"
                    @keyup.esc="unsetFocus"   
                    v-model="fieldData"
                >       
                    <option value="H+">H+</option>
                    <option value="Na+">Na+</option>
                    <option value="H-">Negative mode (H-)</option>
                </select>

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

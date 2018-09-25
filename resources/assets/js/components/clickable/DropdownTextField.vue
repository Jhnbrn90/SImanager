<template>
    <div :class="classes" style="display:inline-block; width: 100%; height: 100%; padding: 10px;">
        <div style="position:relative; top: 50%; transform: translateY(-50%);">
             <button @click.prevent="toggleEdit" v-if="!textData && !showEdit" class="btn btn-link" style="color: grey;">
                &plus; add
            </button>

            <span @click="toggleEdit" v-if="!showEdit">{{ dropdownData }} {{ textData }}</span>
            
            <div class="form-group" v-if="showEdit">
                <div class="col-sm-5" style="margin-right: -20px;">
                    <select 
                        class="form-control"
                        ref="dropdownField" 
                        v-model="dropdownData"
                        @keyup.esc="closeEdit"  
                    >
                        <option value="+">&plus;</option>
                        <option value="-">&minus;</option>
                    </select>
                </div>
                <div class="col-sm-6">
                    <input 
                        type="text" 
                        ref="textField"
                        @blur="submitData" 
                        @keyup.enter="unsetFocus"
                        @keyup.esc="closeEdit"                    
                        class="form-control" 
                        placeholder="19.65"
                        v-model="textData"
                    >
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: [
            'id', 
            'dropdown_data', 
            'dropdown_column', 
            'text_data',
            'text_column'
        ],

        data() {
            return {
                dropdownData: this.dropdown_data,
                textData: this.text_data,
                showEdit: false,
            }
        },

        computed: {
            patchPath() {
                return '/compounds/' + this.id;
            },

            classes() {
                return this.textData ? 'bg-success' : 'bg-danger';
            }
        },

        methods: {
            setFocus() {
                setTimeout(() => {
                    this.$refs.dropdownField.focus();
                });
            },

            patchData(column, data) {
              return { column: column, value: data };
            },

            unsetFocus() {
                this.$refs.textField.blur();
            },

            closeEdit() {
                this.showEdit = false;
            },

            toggleEdit() {
                this.showEdit = true;
                this.setFocus();
            },

            submitData() {
                this.showEdit = false;

                axios.patch(this.patchPath, this.patchData(this.dropdown_column, this.dropdownData))
                .then((response) => {
                    console.log(response.data);
                });

                axios.patch(this.patchPath, this.patchData(this.text_column, this.textData))
                .then((response) => {
                    console.log(response.data);
                });
            }
        }
    }
</script>

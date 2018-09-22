<template>
    <div class="form-group">
        <label for="MP" class="col-sm-2 control-label">Melting Point (&deg; C.)</label>
        <div class="col-sm-10">
                
                <span v-if="markedUnobtainable">
                    Marked as unobtainable. 
                    <button class="btn btn-link" @click.prevent="meltingPoint = null"> Undo? </button>
                </span>
                
                <div v-if="!markedUnobtainable">
                    <input type="text" class="form-control" id="MP" name="MP" placeholder="102 - 108" v-model="meltingPoint">
                    <button class="btn btn-link" @click.prevent="meltingPoint = '@'"> Mark as unobtainable </button>
                </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['data'],

        data() {
            return {
                meltingPoint: this.data,
            }
        },

        computed: {
            markedUnobtainable() {
                return this.meltingPoint == "@";
            }
        },
    }
</script>

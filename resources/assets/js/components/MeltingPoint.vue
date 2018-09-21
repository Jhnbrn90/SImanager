<template>
    
    <div class="form-group">
      <label for="MP" class="col-sm-2 control-label">Melting Point (&deg; C.)</label>
      <div class="col-sm-10">
        <div v-if="showMP">
          <input
            ref="MP" 
            type="text" 
            class="form-control" 
            id="MP" 
            name="MP" 
            placeholder="102 - 108"  
            >
            <a href="#" @click.prevent="showMP = false">undo</a>
        </div>

            <div v-if="!showMP && !markUnobtainable">
                <button class="btn btn-primary btn-sm" @click.prevent="toggleShowMP" tabindex="6">
                    &plus; Add data
                </button>
                <button class="btn btn-info btn-sm" @click.prevent="markUnobtainable = true" tabindex="7">
                    &times; Unobtainable
                </button>
            </div>

            <div v-if="markUnobtainable">
                <input type="hidden" id="MP" name="MP" value="@">
                <input type="text" class="form-control" placeholder="Unobtainable" disabled>
                <a href="#" @click.prevent="markUnobtainable = false">undo</a>
            </div>
            
      </div>
    </div>

</template>

<script>
    export default {
        data() {
            return {
                showMP: false,
                markUnobtainable: false,
            }
        },

        methods: {
            toggleShowMP() {
                this.showMP = true;
                setTimeout(() => {
                    this.$refs.MP.focus();    
                }, 1);
            }
        }
    }
</script>

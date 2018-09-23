<template>
    <div>

        <div class="form-group" v-if="markedUnobtainable">
          <label for="rotation_sign" class="col-sm-2 control-label">Specific Rotation</label>
          <div class="col-sm-8">
            Marked as unobtainable.
            <button class="btn btn-link" @click.prevent="alphaValue = null">Undo ?</button>
          </div>
        </div>

        <div v-if="markedUnobtainable">
          <input type="hidden" name="rotation_sign" value="@" >
          <input type="hidden" name="rotation_value" value="@">
          <input type="hidden" name="rotation_solvent" value="@">
          <input type="hidden" name="rotation_concentration" value="@">
        </div>
        
        <div class="form-group" v-if="!markedUnobtainable">
          <label for="rotation_sign" class="col-sm-2 control-label">Specific Rotation</label>
          <div class="col-sm-1">
              <select name="rotation_sign" v-model="alphaSign" id="rotation_sign" class="form-control">
                <option value="+">&plus;</option>
                <option value="-">&minus;</option>
              </select>
          </div>
          
          <div class="col-sm-2">
              <div class="input-group">
                  <div class="input-group-addon">[&alpha;]<sup>20</sup><sub>D</sub> = </div>
                  <input type="text" class="form-control" name="rotation_value" v-model="alphaValue" placeholder="19.65">
              </div>
          </div>

          <div class="col-sm-2">
              <div class="input-group">
                  <div class="input-group-addon">conc. = </div>
                  <input type="text" class="form-control" name="rotation_concentration" v-model="alphaConcentration" placeholder="1.05">
              </div>
          </div>

          <div class="col-sm-3">
              <div class="input-group">
                  <div class="input-group-addon">solvent</div>
                  <input type="text" class="form-control" id="rotation_solvent" name="rotation_solvent" v-model="alphaSolvent" placeholder="CHCl3">
              </div>
          </div>
          <button class="btn btn-link" @click.prevent="alphaValue = '@'"> Mark as unobtainable </button>
        </div>

    </div>
</template>

<script>
    export default {
        props: [
            'alpha_sign', 
            'alpha_value', 
            'alpha_solvent', 
            'alpha_concentration'
        ],

        data() {
            return {
                alphaSign: this.alpha_sign,
                alphaValue: this.alpha_value,
                alphaSolvent: this.alpha_solvent,
                alphaConcentration: this.alpha_concentration,
            }
        },

        computed: {
            markedUnobtainable() {
                return this.alphaValue == "@";
            }
        }

    }
</script>

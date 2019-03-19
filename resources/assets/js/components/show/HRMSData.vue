<template>
    <div>
        <div class="form-group">
          <label for="mass_adduct" class="col-sm-2 control-label">HRMS Data</label>

          <span class="col-sm-8" v-if="markedUnobtainable">
            Marked as unobtainable.
            <button class="btn btn-link" @click.prevent="massAdduct = null">Undo ?</button>
          </span>
          
          <div v-if="markedUnobtainable">
            <input type="hidden" name="mass_adduct" value="@" >
            <input type="hidden" name="mass_calculated" value="@">
            <input type="hidden" name="mass_measured" value="@">
          </div>

          <div v-if="!markedUnobtainable">
              <div class="col-sm-1">
                  <select name="mass_adduct" ref="ion" id="mass_adduct" v-model="massAdduct" class="form-control">
                      <option value="H+">H+</option>
                      <option value="Na+">Na+</option>
                      <option value="H-">Negative mode (H-)</option>
                    </select>
              </div>
              
              <div class="col-sm-4">
                  <div class="input-group">
                      <div class="input-group-addon">calculated:</div>
                      <input type="text" class="form-control" id="mass_calculated" name="mass_calculated" v-model="massCalculated" placeholder="221.0290">
                  </div>
              </div>

              <div class="col-sm-3">
                  <div class="input-group">
                      <div class="input-group-addon">found:</div>
                      <input type="text" class="form-control" id="mass_measured" name="mass_measured" v-model="massMeasured" placeholder="221.0291">
                  </div>
              </div>
              <button class="btn btn-link" @click.prevent="massAdduct = '@'"> Mark as unobtainable </button>
          </div>
          
        </div>

    </div>
</template>

<script>
    export default {
        props: ['mass_adduct', 'mass_calculated', 'mass_measured'],

        data() {
            return {
                massAdduct: this.mass_adduct,
                massCalculated: this.mass_calculated,
                massMeasured: this.mass_measured,
            }
        },

        computed: {
            markedUnobtainable() {
                return this.massAdduct == "@";
            }
        }
    }
</script>

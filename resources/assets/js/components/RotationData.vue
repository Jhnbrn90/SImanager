<template>
    <div>
        <div v-if="showAlphaD">
            <div class="form-group">
              <label for="alpha_sign" class="col-sm-2 control-label">Specific Rotation</label>
              <div class="col-sm-1">
                  <select name="alpha_sign" id="alpha_sign" class="form-control">
                    <option value="+">&plus;</option>
                    <option value="-">&minus;</option>
                  </select>
              </div>
              
              <div class="col-sm-2">
                  <div class="input-group">
                      <div class="input-group-addon">[&alpha;]<sup>20</sup><sub>D</sub> = </div>
                      <input type="text" class="form-control" name="alpha_value" placeholder="19.65">
                  </div>
              </div>

              <div class="col-sm-2">
                  <div class="input-group">
                      <div class="input-group-addon">conc. = </div>
                      <input type="text" class="form-control" name="alpha_concentration" placeholder="1.05">
                  </div>
              </div>

              <div class="col-sm-3">
                  <div class="input-group">
                      <div class="input-group-addon">solvent</div>
                      <input type="text" class="form-control" id="rotation_solvent" name="alpha_solvent" placeholder="CHCl3">
                  </div>
              </div>
            </div>

            <div class="form-group">
                  <div class="col-sm-2"></div>
                  <div class="col-sm-10">
                      <a href="#" @click.prevent="showAlphaD = false">undo</a>
                  </div>
              </div>
        </div>
        
        <div v-if="!showAlphaD && !markUnobtainable">
            <div class="form-group">
                <label class="col-sm-2 control-label">Specific Rotation</label>
                <div class="col-sm-10">
                    <button @click.prevent="showAlphaD = true" class="btn btn-sm btn-primary">&plus; Add data</button>
                    <button @click.prevent="markUnobtainable = true" class="btn btn-sm btn-info">&times; Unobtainable</button>
                </div>
            </div>
        </div>
        
        <div v-if="markUnobtainable">
          <div class="form-group">
                <label class="col-sm-2 control-label">Specific Rotation</label>
                <div class="col-sm-10">
                    <input type="hidden" id="alpha_sign" name="alpha_sign" value="@">
                    <input type="hidden" id="alpha_value" name="alpha_value" value="@">
                    <input type="hidden" id="alpha_concentration" name="alpha_concentration" value="@">
                    <input type="hidden" id="alpha_solvent" name="alpha_solvent" value="@">
                    <input type="text" class="form-control" placeholder="Unobtainable" disabled>
                    <a href="#" @click.prevent="markUnobtainable = false">undo</a>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
    export default {
        data() {
            return {
                showAlphaD: false,
                markUnobtainable: false,
            }
        }
    }
</script>

<template>
    <div>
        <div v-if="showAlphaD">
            <div class="form-group">
              <label for="rotation_sign" class="col-sm-2 control-label">Specific Rotation</label>
              <div class="col-sm-1">
                  <select name="rotation_sign" id="rotation_sign" class="form-control">
                    <option value="+">&plus;</option>
                    <option value="-">&minus;</option>
                  </select>
              </div>
              
              <div class="col-sm-2">
                  <div class="input-group">
                      <div class="input-group-addon">[&alpha;]<sup>20</sup><sub>D</sub> = </div>
                      <input type="text" class="form-control" name="rotation_value" placeholder="19.65">
                  </div>
              </div>

              <div class="col-sm-2">
                  <div class="input-group">
                      <div class="input-group-addon">conc. = </div>
                      <input type="text" class="form-control" name="rotation_concentration" placeholder="1.05">
                  </div>
              </div>

              <div class="col-sm-3">
                  <div class="input-group">
                      <div class="input-group-addon">solvent</div>
                      <input type="text" class="form-control" id="rotation_solvent" name="rotation_solvent" placeholder="CHCl3">
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
                    <input type="hidden" id="rotation_sign" name="rotation_sign" value="@">
                    <input type="hidden" id="rotation_value" name="rotation_value" value="@">
                    <input type="hidden" id="rotation_concentration" name="rotation_concentration" value="@">
                    <input type="hidden" id="rotation_solvent" name="rotation_solvent" value="@">
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

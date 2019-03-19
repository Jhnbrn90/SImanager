<template>
    <div>
        <div v-if="showHRMS" tabindex="7">

          <div class="form-group">
            <label for="mass_adduct" class="col-sm-2 control-label">Mass Ion</label>
            <div class="col-sm-1">
                <select name="mass_adduct" ref="ion" id="mass_adduct" class="form-control">
                    <option value="H+">H+</option>
                    <option value="Na+">Na+</option>
                    <option value="H-">Negative mode (H-)</option>
                  </select>
            </div>
            
            <div class="col-sm-4">
                <div class="input-group">
                    <div class="input-group-addon">calculated:</div>
                    <input type="text" class="form-control" id="mass_calculated" name="mass_calculated" placeholder="221.0290">
                </div>
            </div>

            <div class="col-sm-3">
                <div class="input-group">
                    <div class="input-group-addon">found:</div>
                    <input type="text" class="form-control" id="mass_measured" name="mass_measured" placeholder="221.0291">
                </div>
            </div>
          </div>
            
        <div class="form-group">
            <div class="col-sm-2"></div>
            <div class="col-sm-10">
                <a href="#" @click.prevent="showHRMS = false">undo</a>
            </div>
        </div>

        </div>
        <div v-if="!showHRMS && !markUnobtainable">
            <div class="form-group">
                <label for="mass_calculated" class="col-sm-2 control-label">HRMS data</label>
                <div class="col-sm-10">
                    <button @click.prevent="toggleShowHRMS" class="btn btn-sm btn-primary" tabindex="8">&plus; Add data</button>
                    <button @click.prevent="markUnobtainable = true" class="btn btn-sm btn-info" tabindex="9">&times; Unobtainable</button>
                </div>
            </div>
        </div>

        <div v-if="markUnobtainable">
            <div class="form-group">
                <label for="mass_calculated" class="col-sm-2 control-label">HRMS data</label>
                <div class="col-sm-10">
                    <input type="hidden" id="mass_ion" name="mass_adduct" value="@">
                    <input type="hidden" id="mass_found" name="mass_measured" value="@">
                    <input type="hidden" id="mass_calculated" name="mass_calculated" value="@">
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
                showHRMS: false,
                markUnobtainable: false,
            }
        },

        methods: {
            toggleShowHRMS() {
                this.showHRMS = true;
                setTimeout(() => {
                    this.$refs.ion.focus();    
                }, 1);
            }
        }
    }
</script>

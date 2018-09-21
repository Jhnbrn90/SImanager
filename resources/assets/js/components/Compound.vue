<template>
    <tr>
        <td><img :src="svgPath" height="70" style="margin-top:-10px;"></td>
        <td><a href="#">{{ label }}</a></td>
        <td><input type="checkbox" v-model="H_NMR" disabled></td>
        <td><input type="checkbox" v-model="C_NMR" disabled></td>
        <td>{{ Rf }}</td>
        <td><input type="text" :value="IR" disabled></td>
        <td>100</td>
        <td>Na<sup>+</sup></td>
        <td>102.204</td>
        <td><input type="checkbox" checked disabled></td>
        <td>+ 14.6</td>
        <td> 1.30 </td>
        <td>CHCl<sub>3</sub></td>
        <td>NMR on 600 MHz</td>
    </tr>
</template>

<script>
export default {
    props: [
        'id',
        'label',
        'H_NMR',
        'C_NMR',
        'Rf',
        'IR',
        'MP',
        'MassIon',
        'MassFound',
        'RotationValue',
        'RotationConcentration',
        'RotationSolvent',
        'Notes'
    ],

    data() {
        return {
            H_NMR: true,
        }
    },

    computed: {
        svgPath() {
            return "/svg/" + this.id + ".svg";
        }
    }
}
</script>

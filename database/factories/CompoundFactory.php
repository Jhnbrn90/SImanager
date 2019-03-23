<?php

use App\Project;
use App\Compound;
use Faker\Generator as Faker;

$factory->define(Compound::class, function (Faker $faker) {
    return [
        'project_id'            => function () {
            return factory(Project::class);
        },
        'label'                 => $faker->word,
        'formula'               => 'C6H12O6',
        'molweight'             => 180.16,
        'exact_mass'            => 180.0634,
        'H_NMR_data'            => '1H NMR ...',
        'C_NMR_data'            => '13C NMR ...',
        'retention'             => '0.18 (EtOAc/cHex = 2:3)',
        'melting_point'         => '@',
        'infrared'              => '1735, 1639, 1244, 1224, 1035, 904',
        'mass_measured'         => 180.0708,
        'mass_adduct'           => 'Na',
        'alpha_sign'            => '+',
        'alpha_value'           => 52,
        'alpha_solvent'         => 'H2O',
        'alpha_concentration'   => 10,
        'notes'                 => 'White crystals',
    ];
});

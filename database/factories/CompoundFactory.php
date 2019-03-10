<?php

use Faker\Generator as Faker;

$factory->define(App\Compound::class, function (Faker $faker) {
    $user = auth()->user() ?? factory('App\User')->create();

    return [
        'user_id'               => $user->id, 
        'project_id'            => function() use ($user) {
            return factory('App\Project')->create(['user_id' => $user->id])->id;
        },
        'label'                 => 'jbn478',
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
        'notes'                 => 'White crystals'
    ];
});

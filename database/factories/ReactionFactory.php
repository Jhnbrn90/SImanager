<?php

use Faker\Generator as Faker;

$factory->define(App\Reaction::class, function (Faker $faker) {
    return [
        'solvent_id'    => function () {
            return factory('App\Solvent');
        },
        'project_id'    => function () {
            return factory('App\Project');
        },
        'label'     => $faker->word,
    ];
});

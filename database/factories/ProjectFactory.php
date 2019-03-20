<?php

use App\Project;
use Faker\Generator as Faker;

$factory->define(Project::class, function (Faker $faker) {
    return [
        'name'          => $faker->word,
        'description'   => $faker->sentence,
        'bundle_id'     => function() {
            return factory('App\Bundle')->create()->id;
        },
    ];
});

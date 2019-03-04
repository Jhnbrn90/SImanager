<?php

use Faker\Generator as Faker;

$factory->define(App\Project::class, function (Faker $faker) {
    return [
        'name'          => $faker->word,
        'description'   => $faker->sentence,
        'user_id'       => function() {
            return auth()->id() ?: factory('App\User')->create()->id;
        },
    ];
});

<?php

use Faker\Generator as Faker;

$factory->define(App\Bundle::class, function (Faker $faker) {
    return [
        'name'      => $faker->word,
        'user_id'   => function () {
            return auth()->id() ?? factory('App\User')->create()->id;
        },
    ];
});

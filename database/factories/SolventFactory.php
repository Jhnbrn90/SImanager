<?php

use Faker\Generator as Faker;

$factory->define(App\Solvent::class, function (Faker $faker) {
    return [
        'trivial_name'  => $faker->word,
    ];
});

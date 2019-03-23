<?php

use Faker\Generator as Faker;

$factory->define(App\Chemical::class, function (Faker $faker) {
    return [
        'name'          => $faker->sentence(3),
        'structure_id'  => function () {
            return factory('App\Structure');
        },
        'cas'           => $faker->numberBetween(10, 50).'-'.$faker->numberBetween(10, 50).'-'.$faker->numberBetween(10, 50),
        'molweight'     => $faker->numberBetween(100, 1000).'.'.$faker->numberBetween(1000, 2000),
        'density'       => $faker->randomFloat(0, 1.2),
        'quantity'      => $faker->randomElement(['100 g', '500 g', '10 mL', '500 mL', '50 mL', '1 g']),
        'location'      => $faker->randomElement(['4W35', '4W19']),
        'cabinet'       => $faker->numberBetween(1, 12),
        'number'        => $faker->numberBetween(1, 100),
        'remarks'       => 'fake chemical',
    ];
});

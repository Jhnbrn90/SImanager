<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BundleTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function a_new_user_has_a_default_bundle()
    {
        $user = factory('App\User')->create();

        $this->assertCount(1, $user->bundles); // 1 + default bundle
        $this->assertEquals('Default bundle', $user->bundles->first()->name);
    }
}

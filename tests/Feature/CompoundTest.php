<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompoundTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
   public function authenticated_users_can_list_their_compounds()
   {
        $this->withoutExceptionHandling();

        $this->signIn();
        $compound = create('App\Compound');

        $this->get('/')->assertSee($compound->label);
   }

   /** @test **/
   public function unauthenticated_users_are_can_not_list_compounds_but_are_redirected_to_auth_page()
   {
        $this->withExceptionHandling();

        $this->get('/')->assertRedirect('/login');
   }

   /** @test **/
   public function authenticated_users_can_view_a_single_compound()
   {
      $this->signIn();
      $compound = create('App\Compound');

      $this->get($compound->path())
        ->assertSee($compound->label);
   }

   /** @test **/
   public function a_compound_returns_the_number_of_protons_in_its_formula_and_NMR_data()
   {  
      $compound = create('App\Compound', [
        'formula'     => 'C18H21NO3',
        'H_NMR_data'  => '1H NMR (600 MHz, CDCl3) δ 7.17 – 7.13 (m, 2H), 6.89 – 6.84 (m, 2H), 6.02 – 5.92 (m, 1H), 5.26 – 5.11 (m, 2H), 4.34 (dd, J = 98.0, 14.5 Hz, 2H), 3.81 (s, 3H), 3.25 – 3.15 (m, 2H), 2.55 (s, 3H), 2.01 (d, J = 9.6 Hz, 1H), 1.40 (s, 3H).'
      ]);

      $this->assertEquals(21, $compound->formulaProtons);
      $this->assertEquals(21, $compound->nmrProtons);
   }

   /** @test **/
   public function a_compound_can_check_the_integrity_of_its_proton_NMR_data()
   {
      $compound = create('App\Compound', [
        'formula'     => 'C18H21NO3',
        'H_NMR_data'  => '1H NMR (600 MHz, CDCl3) δ 7.17 – 7.13 (m, 2H), 6.89 – 6.84 (m, 2H), 6.02 – 5.92 (m, 1H), 5.26 – 5.11 (m, 2H), 4.34 (dd, J = 98.0, 14.5 Hz, 2H), 3.81 (s, 3H), 3.25 – 3.15 (m, 2H), 2.55 (s, 3H), 2.01 (d, J = 9.6 Hz, 1H), 1.40 (s, 3H).'
      ]);

      $incorrectCompound = create('App\Compound', [
        'formula'     => 'C18H20NO3',
        'H_NMR_data'  => '1H NMR (600 MHz, CDCl3) δ 7.17 – 7.13 (m, 2H), 6.89 – 6.84 (m, 2H), 6.02 – 5.92 (m, 1H), 5.26 – 5.11 (m, 2H), 4.34 (dd, J = 98.0, 14.5 Hz, 2H), 3.81 (s, 3H), 3.25 – 3.15 (m, 2H), 2.55 (s, 3H), 2.01 (d, J = 9.6 Hz, 1H), 1.40 (s, 3H).'
      ]);

      $this->assertTrue($compound->checkProtonNMR());   
      $this->assertFalse($incorrectCompound->checkProtonNMR());   
   }

   /** @test **/
   public function a_compound_returns_the_number_of_carbons_in_its_formula_and_NMR_data()
   {  
      $compound = create('App\Compound', [
        'formula'     => 'C12H19BrO2',
        'C_NMR_data'  => '13C NMR (126 MHz, CDCl3) δ 140.21, 137.13, 128.69, 110.30, 72.69, 68.99, 40.08, 36.65, 35.77, 25.44, 24.29, 24.22, 15.66.'
      ]);

      $this->assertEquals(12, $compound->formulaCarbons);
      $this->assertEquals(12, $compound->nmrCarbons);
   }

      /** @test **/
   public function a_compound_can_check_the_integrity_of_its_carbon_NMR_data()
   {
      $compound = create('App\Compound', [
        'formula'     => 'C12H19BrO2',
        'C_NMR_data'  => '13C NMR (126 MHz, CDCl3) δ 140.21, 137.13, 128.69, 110.30, 72.69, 68.99, 40.08, 36.65, 35.77, 25.44, 24.29, 24.22, 15.66.'
      ]);
      
      $this->assertTrue($compound->checkCarbonNMR());   

   }

}

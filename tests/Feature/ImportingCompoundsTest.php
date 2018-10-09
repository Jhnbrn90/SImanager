<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\DataImporter;
use Tests\utilities\ExperimentBuilder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ImportingCompoundsTest extends TestCase
{
    /** @test **/
    public function it_returns_null_if_no_matching_proton_nmr_data_is_found()
    {
        $protonNMR = "";

        $importer = new DataImporter(
            ExperimentBuilder::build(['protonNMR' => $protonNMR])
        );

        $this->assertEquals(null, $importer->getProtonNMR());
    }

    /** @test **/
    public function it_can_extract_proton_nmr_data_from_an_experiment()
    {
        $protonNMR = '1H NMR (600 MHz, CDCl3) δ 5.34 (d, J = 8.1 Hz, 1H), 4.86 (q, J = 7.5 Hz, 1H), 4.17 (dd, J = 68.7, 13.7 Hz, 2H), 4.06 (t, J = 7.0 Hz, 1H), 3.53 (t, J = 8.0 Hz, 1H), 1.84 (s, 3H), 1.67 – 1.33 (m, 10H)';

        $edgeCase = '1H-NMR (400 MHz, DMSO-d6): 7.19 (t, J = 8.0 Hz,
                2H), 6.90 (t, J = 8.0 Hz, 1H), 6.72 (d, J = 8.0 Hz, 2H), 5.70 (br, 1H), 2.19 (s, 3H), 1.39 (s, 9H) ppm.';

        $importer = new DataImporter(
            ExperimentBuilder::build(['protonNMR' => $protonNMR])
        );

        $importerEdgeCase = new DataImporter(
            ExperimentBuilder::build(['protonNMR' => $edgeCase])
        );

        $this->assertEquals($protonNMR, $importer->getProtonNMR());
        $this->assertEquals($edgeCase, $importerEdgeCase->getProtonNMR());
    }

    /** @test **/
    public function it_can_extract_carbon_nmr_data_from_an_experiment()
    {
        $carbonNMR = '13C NMR (151 MHz, CDCl3) δ 141.38, 125.49, 109.94, 71.84, 69.52, 62.18, 36.54, 35.62, 25.24, 24.12, 24.05, 21.88';

        $edgeCase = '13CNMR
(100 MHz, DMSO-d6):  150.2, 149.7, 128.5, 121.9, 121.4, 52.5, 28.4, 14.4 ppm.';

        $importer = new DataImporter(
            ExperimentBuilder::build(['carbonNMR' => $carbonNMR])
        );

        $importerEdgeCase = new DataImporter(
            ExperimentBuilder::build(['carbonNMR' => $edgeCase])
        );

        $this->assertEquals($carbonNMR, $importer->getCarbonNMR());

        $this->assertEquals($edgeCase, $importerEdgeCase->getCarbonNMR());
    }

    /** @test **/
    public function it_can_extract_the_rf_value_from_an_experiment()
    {
        $rfValueText = "Rf = 0.3 (EtOAc/cHex = 3:7)";
        $rfValueTextEdgeCase = "Rf= 0.40 in Heptane / EtOAc (66:33)";

        $importer = new DataImporter(
            ExperimentBuilder::build(['rf' => $rfValueText])
        );

        $importerEdgeCase = new DataImporter(
            ExperimentBuilder::build(['rf' => $rfValueTextEdgeCase])
        );

        $this->assertEquals("0.3 (EtOAc/cHex = 3:7)", $importer->getRfValue());
        $this->assertEquals("0.40 in Heptane / EtOAc (66:33)", $importerEdgeCase->getRfValue());
    }

    /** @test **/
    public function it_can_extract_the_ir_data_from_an_experiment()
    {
        $irDataText = "IR (neat): νmax (cm-1): 2931, 2862, 1672, 1448, 1363, 1332, 1278, 1249, 1230, 1141, 1163, 1099, 1068, 1039, 1020";

        $irDataTextEdgeCase = "IR = 2931,2862, 1672, 1448, 1363, 1332, 1278, 1249, 1230, 1141, 1163, 1099, 1068, 1039, 1020";

        $importer = new DataImporter(
            ExperimentBuilder::build(['ir' => $irDataText])
        );

        $importerEdgeCase = new DataImporter(
            ExperimentBuilder::build(['ir' => $irDataTextEdgeCase])
        );

        $this->assertEquals(
            "2931, 2862, 1672, 1448, 1363, 1332, 1278, 1249, 1230, 1141, 1163, 1099, 1068, 1039, 1020", 
            $importer->getIrData()
        );

        $this->assertEquals(
            "2931,2862, 1672, 1448, 1363, 1332, 1278, 1249, 1230, 1141, 1163, 1099, 1068, 1039, 1020", 
            $importerEdgeCase->getIrData()
        );
    }

    /** @test **/
    public function it_can_extract_the_melting_point_from_an_experiment()
    {
        $meltingPointText = "M.p. = 88 °C";
        $meltingPointTextEdgeCase = "m.p.: 119-120 °C";

        $importer = new DataImporter(
            ExperimentBuilder::build(['meltingPoint' => $meltingPointText])
        );

        $importerEdgeCase = new DataImporter(
            ExperimentBuilder::build(['meltingPoint' => $meltingPointTextEdgeCase])
        );

        $this->assertEquals("88", $importer->getMeltingPoint());
        $this->assertEquals("119-120", $importerEdgeCase->getMeltingPoint());
    }

    /** @test **/
    public function it_can_extract_the_HRMS_data_from_an_experiment()
    {
        $hrmsText = "HRMS (ESI): calculated for C12H19BrNaO2 ([M+Na]+) = 297.0461, found = 297.0462";

        $hrmsTextEdgeCase = "HRMS (ESI): m/z calculated for C13H17ClNO3 [M + H]+
270.0891,
found 270.0886.";

        $importer = new DataImporter(
            ExperimentBuilder::build(['hrms' => $hrmsText])
        );

        $importerEdgeCase = new DataImporter(
            ExperimentBuilder::build(['hrms' => $hrmsTextEdgeCase])
        );

        $this->assertEquals('C12H19BrNaO2', $importer->getHRMS('formula'));
        $this->assertEquals('297.0461', $importer->getHRMS('calculated'));
        $this->assertEquals('297.0462', $importer->getHRMS('found'));

        $this->assertEquals('C13H17ClNO3', $importerEdgeCase->getHRMS('formula'));
        $this->assertEquals('270.0891', $importerEdgeCase->getHRMS('calculated'));
        $this->assertEquals('270.0886', $importerEdgeCase->getHRMS('found'));
    }

    /** @test **/
    public function it_can_extract_the_HRMS_ion()
    {
        $hrmsText = "HRMS (ESI): calculated for C12H19BrNaO2 ([M+Na]+) = 297.0461, found = 297.0462";

        $importer = new DataImporter(
            ExperimentBuilder::build(['hrms' => $hrmsText])
        );

        $this->assertEquals('Na+', $importer->getHRMS('ion'));
    }

    /** @test **/
    public function it_can_extract_the_optical_rotation_data_from_an_experiment()
    {
        $rotationText = "[α]D20 = + 32.29 (c = 1.20, CHCl3).";
        $rotationTextEdgeCase = "alpha-D[20] = - 10.19 (c=1.20, PhMe)";

        $importer = new DataImporter(
            ExperimentBuilder::build(['rotation' => $rotationText])
        );

        $importerEdgeCase = new DataImporter(
            ExperimentBuilder::build(['rotation' => $rotationTextEdgeCase])
        );

        $this->assertEquals('+', $importer->getRotation('sign'));
        $this->assertEquals('32.29', $importer->getRotation('value'));
        $this->assertEquals('1.20', $importer->getRotation('concentration'));
        $this->assertEquals('CHCl3', $importer->getRotation('solvent'));

        $this->assertEquals('-', $importerEdgeCase->getRotation('sign'));
        $this->assertEquals('10.19', $importerEdgeCase->getRotation('value'));
        $this->assertEquals('1.20', $importerEdgeCase->getRotation('concentration'));
        $this->assertEquals('PhMe', $importerEdgeCase->getRotation('solvent'));  
    }

    /** @test **/
    public function it_can_returns_null_if_no_optical_rotation_data_was_found()
    {
        $rotationText = "";

        $importer = new DataImporter(
            ExperimentBuilder::build(['rotation' => $rotationText])
        );

        $this->assertEquals(null, $importer->getRotation('sign'));
        $this->assertEquals(null, $importer->getRotation('value'));
        $this->assertEquals(null, $importer->getRotation('concentration'));
        $this->assertEquals(null, $importer->getRotation('solvent'));
    }
}

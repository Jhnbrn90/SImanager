<?php

namespace Tests\utilities;

class ExperimentBuilder
{
    protected $options;

    protected $data = [
            'rf'            => 'RF = 0.2 (EtOAc/cHex = 3:97)',

            'protonNMR'     => '1H NMR (500 MHz, CDCl3) δ 5.61 (d, J = 8.3 Hz, 1H), 4.75 (td, J = 8.0, 6.1 Hz, 1H), 4.09 (dd, J = 8.1, 6.1 Hz, 1H), 3.96 – 3.91 (m, 2H), 3.54 (t, J = 7.9 Hz, 1H), 1.85 (d, J = 1.3 Hz, 3H), 1.66 – 1.59 (m, 10H)',

            'carbonNMR'     => '13C NMR (126 MHz, CDCl3) δ 137.13, 128.69, 110.30, 72.69, 68.99, 40.08, 36.65, 35.77, 25.44, 24.29, 24.22, 15.66',

            'ir'            => 'IR (neat): max (cm-1): 2933, 2858, 2358, 2345, 2327, 1448, 1382, 1365, 1330, 1280, 1249, 1230, 1209, 1163, 1103, 1068, 1041, 1004, 929',

            'hrms'          => 'HRMS (ESI): calculated for C12H19BrNaO2 ([M+Na]+) = 297.0461, found = 297.0462',

            'rotation'      => '[α]D20  = + 46.0 (c = 1.00, CHCl3)',

            'meltingPoint'  => 'M.p. = 80 °C',
    ];

    public function __construct(array $options = [])
    {
        $this->options = $options;

        foreach ($options as $key => $value) {
            if (array_key_exists($key, $this->data)) {
                $this->data[$key] = $value;
            }
        }
    }

    public static function build(array $options = [])
    {
        $instance = new self($options);

        $experiment = "Allylic ester S01 (590 mg, 2.32 mmol, 1.0 equiv) was dissolved in anhydrous THF (0.3 M) and the reaction mixture was cooled to 0 °C after which a solution of DIBAL-H (1.0 M in heptane, 5.1 mL, 5.1 mmol 2.2 equiv) was added dropwise. The reaction mixture was allowed to warm to room temperature and was stirred overnight. The reaction mixture was cooled to 0 °C, quenched by the dropwise addition of MeOH (20 mL) and stirred for 3 h at room temperature. The white suspension was filtered over Celite® and the residue was washed with MeOH. The filtrate was concentrated under reduced pressure after which the title compound was obtained as a colorless liquid in 97% yield (480 mg, 2.26 mmol). The product was directly used in the next reaction without purification. {$instance->data['rf']}. {$instance->data['protonNMR']}. {$instance->data['carbonNMR']}. {$instance->data['ir']}. {$instance->data['hrms']}. {$instance->data['rotation']}. {$instance->data['meltingPoint']}.";

        return $experiment;
    }
}

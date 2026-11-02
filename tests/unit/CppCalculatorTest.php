<?php

use App\Libraries\CppCalculator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class CppCalculatorTest extends CIUnitTestCase
{
	private array $settings = [
		'basic_exemption'       => 3500,
		'contribution_rate'     => 5.95,
		'max_contribution'      => 4034.10,
		'ympe'                  => 71300,
		'yampe'                 => 81200,
		'cpp2_rate'             => 4,
		'cpp2_max_contribution' => 396,
	];

	public function testFormulaDelPeriodo(): void
	{
		// =ROUND(MAX(0, (3231 - (3500 / 24)) * 0.0595), 2)
		$result = CppCalculator::calculate($this->settings, 3231);

		$this->assertSame(183.57, $result['ee_cpp']);
		$this->assertSame($result['ee_cpp'], $result['er_cpp']);
		$this->assertSame(0.0, $result['ee_cpp2']);
	}

	public function testIngresoMenorALaExencionNoAporta(): void
	{
		$result = CppCalculator::calculate($this->settings, 100);

		$this->assertSame(0.0, $result['ee_cpp']);
	}

	public function testTopeMaximoAnual(): void
	{
		$result = CppCalculator::calculate($this->settings, 3231, 60000, 4000);

		$this->assertSame(34.10, $result['ee_cpp']);

		$result = CppCalculator::calculate($this->settings, 3231, 65000, 4034.10);

		$this->assertSame(0.0, $result['ee_cpp']);
	}

	public function testCpp2SoloSobreLaParteQuePasaElYmpe(): void
	{
		// acumulado 70,000 + 3,000 = 73,000 -> 1,700 sobre el YMPE * 4% = 68
		$result = CppCalculator::calculate($this->settings, 3000, 70000, 3800);

		$this->assertSame(68.0, $result['ee_cpp2']);
		$this->assertSame($result['ee_cpp2'], $result['er_cpp2']);
	}

	public function testCpp2TopeEnYampeYMaximo(): void
	{
		// todo el periodo sobre el YAMPE: no aporta
		$this->assertSame(0.0, CppCalculator::calculate($this->settings, 3000, 82000, 4034.10, 396)['ee_cpp2']);

		// cruza el YAMPE: solo hasta 81,200 -> 1,200 * 4% = 48, limitado por lo que falta del maximo (396 - 380 = 16)
		$this->assertSame(16.0, CppCalculator::calculate($this->settings, 3000, 80000, 4034.10, 380)['ee_cpp2']);
	}
}

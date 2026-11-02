<?php

use App\Libraries\EiCalculator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class EiCalculatorTest extends CIUnitTestCase
{
	private array $settings = [
		'max_insurable_earnings' => 70800,
		'rate'                   => 1.64,
	];

	public function testFormulaDelPeriodo(): void
	{
		// =ROUND(3231 * 0.0164, 2)
		$result = EiCalculator::calculate($this->settings, 3231);

		$this->assertSame(52.99, $result['ee_ei']);
		$this->assertSame(74.19, $result['er_ei']);
	}

	public function testTopeMaximoAnual(): void
	{
		// prima maxima 70,800 * 1.64% = 1,161.12
		$result = EiCalculator::calculate($this->settings, 3231, 1140);

		$this->assertSame(21.12, $result['ee_ei']);
		$this->assertSame(29.57, $result['er_ei']);

		$result = EiCalculator::calculate($this->settings, 3231, 1161.12);

		$this->assertSame(0.0, $result['ee_ei']);
		$this->assertSame(0.0, $result['er_ei']);
	}

	public function testGrossCeroNoAporta(): void
	{
		$result = EiCalculator::calculate($this->settings, 0);

		$this->assertSame(0.0, $result['ee_ei']);
	}
}

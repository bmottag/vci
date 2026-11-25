<?php

use App\Libraries\BankTimeCalculator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class BankTimeCalculatorTest extends CIUnitTestCase
{
	public function testExcesoVaAlBanco(): void
	{
		$result = BankTimeCalculator::calculate(95.5, 10);

		$this->assertSame(88.0, $result['paid_hours']);
		$this->assertSame(7.5, $result['to_bank']);
		$this->assertSame(0.0, $result['from_bank']);
		$this->assertSame(17.5, $result['new_balance']);
		$this->assertSame(BankTimeCalculator::MOVEMENT_ADD, $result['movement']);
	}

	public function testExactamente88NoMueveElBanco(): void
	{
		$result = BankTimeCalculator::calculate(88, 10);

		$this->assertSame(88.0, $result['paid_hours']);
		$this->assertSame(10.0, $result['new_balance']);
		$this->assertSame('', $result['movement']);
	}

	public function testFaltanHorasYElSaldoAlcanza(): void
	{
		$result = BankTimeCalculator::calculate(80, 20);

		$this->assertSame(88.0, $result['paid_hours']);
		$this->assertSame(8.0, $result['from_bank']);
		$this->assertSame(12.0, $result['new_balance']);
		$this->assertSame(BankTimeCalculator::MOVEMENT_SUBTRACT, $result['movement']);
	}

	public function testFaltanHorasYElSaldoNoAlcanza(): void
	{
		$result = BankTimeCalculator::calculate(70, 5.25);

		$this->assertSame(75.25, $result['paid_hours']);
		$this->assertSame(5.25, $result['from_bank']);
		$this->assertSame(0.0, $result['new_balance']);
	}

	public function testSinSaldoSePaganLasTrabajadas(): void
	{
		$result = BankTimeCalculator::calculate(60.333, 0);

		$this->assertSame(60.333, $result['paid_hours']);
		$this->assertSame(0.0, $result['from_bank']);
		$this->assertSame('', $result['movement']);
	}
}

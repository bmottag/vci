<?php

use App\Libraries\TaxTableCalculator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class TaxTableCalculatorTest extends CIUnitTestCase
{
	private string $csv = "From,Less than,CC 0,CC 1,CC 2,CC 3,CC 4,CC 5,CC 6,CC 7,CC 8,CC 9,CC 10\n"
		. "0,801,*,.00,,,,,,,,,\n"
		. "801,805,96.35,.40,,,,,,,,,\n"
		. "1005,1009,122.75,26.80,18.50,2.00,,,,,,,\n";

	public function testLeeElCsv(): void
	{
		$rows = TaxTableCalculator::parseCsv("From,Less than,CC 0,CC 1,CC 2,CC 3,CC 4,CC 5,CC 6,CC 7,CC 8,CC 9,CC 10\n,801,* (1),.00,,,,,,,,,\n801,805,96.35,.40,,,,,,,,,\n");

		$this->assertCount(2, $rows);
		$this->assertSame(0.0, $rows[0]['pay_from']);
		$this->assertNull($rows[0]['cc0']);
		$this->assertSame(0.0, $rows[0]['cc1']);
		$this->assertSame(96.35, $rows[1]['cc0']);
		$this->assertSame(0.4, $rows[1]['cc1']);
		$this->assertSame(0.0, $rows[1]['cc10']);
	}

	public function testRechazaRangosConHueco(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Line 3');

		TaxTableCalculator::parseCsv("From,Less than,CC 0\n0,801,*,.00\n805,809,96.90,.90\n");
	}

	public function testRechazaColumnasCorridas(): void
	{
		// CC 2 mayor que CC 1: las columnas no estan alineadas
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('CC 2');

		TaxTableCalculator::parseCsv("0,801,*,.00\n801,805,96.35,.40,5.00\n");
	}

	public function testFederalMasProvincial(): void
	{
		$federal    = ['pay_from' => 1005, 'pay_less_than' => 1009, 'cc0' => 122.75, 'cc1' => 26.80, 'cc2' => 18.50] + array_fill_keys(['cc3', 'cc4', 'cc5', 'cc6', 'cc7', 'cc8', 'cc9', 'cc10'], 0.0);
		$provincial = ['pay_from' => 0, 'pay_less_than' => 1018, 'cc0' => null, 'cc1' => 0.0] + array_fill_keys(['cc2', 'cc3', 'cc4', 'cc5', 'cc6', 'cc7', 'cc8', 'cc9', 'cc10'], 0.0);

		$result = TaxTableCalculator::calculate($federal, $provincial, 1005.80, 1);

		$this->assertSame(26.80, $result['federal_tax']);
		$this->assertSame(0.0, $result['provincial_tax']);
		$this->assertSame(26.80, $result['tax']);
		$this->assertNull($result['error']);
	}

	public function testErroresFueraDeTablaYAsterisco(): void
	{
		$provincial = ['pay_from' => 0, 'pay_less_than' => 1018, 'cc0' => null, 'cc1' => 0.0];

		$result = TaxTableCalculator::calculate(null, $provincial, 20000, 1);
		$this->assertNull($result['tax']);
		$this->assertStringContainsString('outside the federal', $result['error']);

		$federal = ['pay_from' => 0, 'pay_less_than' => 801, 'cc0' => null, 'cc1' => 0.0];
		$result  = TaxTableCalculator::calculate($federal, $provincial, 500, 0);
		$this->assertNull($result['tax']);
		$this->assertStringContainsString('Claim code 0 does not apply', $result['error']);
	}
}

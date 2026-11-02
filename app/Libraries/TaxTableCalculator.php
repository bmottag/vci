<?php

namespace App\Libraries;

use InvalidArgumentException;

/**
 * Tablas de retencion de la CRA (Federal y Provincial, semi-monthly 24 periodos).
 *
 * CSV: From, Less than, CC 0 ... CC 10. Una fila por rango de pago (From <= gross < Less than).
 * Celda vacia = 0 (no se retiene); "*" = no aplica (la CRA pide el calculo paso a paso).
 *
 * TAX = Federal (fila del gross, columna del claim code) + Provincial (igual en la tabla provincial)
 * @since 27/10/2026
 */
class TaxTableCalculator
{
	public const MAX_CLAIM_CODE = 10;
	public const DEFAULT_CLAIM_CODE = 1;

	/**
	 * Lee y valida el CSV; cualquier error lanza InvalidArgumentException con la linea
	 * @return array<int, array{pay_from: float, pay_less_than: float, cc0: ?float, cc1: ?float, cc2: ?float, cc3: ?float, cc4: ?float, cc5: ?float, cc6: ?float, cc7: ?float, cc8: ?float, cc9: ?float, cc10: ?float}>
	 */
	public static function parseCsv(string $content): array
	{
		$content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
		$lines   = preg_split('/\r\n|\r|\n/', $content);
		$rows    = [];
		$prevTo  = null;

		foreach ($lines as $i => $line) {
			$lineNumber = $i + 1;
			$cells      = array_map(fn($cell) => trim((string)$cell), str_getcsv($line));
			if (implode('', $cells) === '') {
				continue;
			}
			// encabezado
			if (stripos($cells[0], 'from') === 0 || stripos($cells[0], 'pay') === 0) {
				continue;
			}

			// columnas extra al final solo si estan vacias
			if (count($cells) > 13) {
				if (implode('', array_slice($cells, 13)) !== '') {
					throw new InvalidArgumentException("Line {$lineNumber}: expected 13 columns (From, Less than, CC 0 ... CC 10).");
				}
				$cells = array_slice($cells, 0, 13);
			}
			$cells = array_pad($cells, 13, '');

			$from = $cells[0] === '' ? 0.0 : self::number($cells[0], $lineNumber, 'From');
			$to   = self::number($cells[1], $lineNumber, 'Less than');
			if ($from === null || $to === null || $to <= $from) {
				throw new InvalidArgumentException("Line {$lineNumber}: invalid pay range.");
			}
			if ($prevTo === null && $from != 0) {
				throw new InvalidArgumentException("Line {$lineNumber}: the first range must start at 0 (or be empty).");
			}
			if ($prevTo !== null && $from != $prevTo) {
				throw new InvalidArgumentException("Line {$lineNumber}: the range must start at {$prevTo} (the previous Less than).");
			}
			$prevTo = $to;

			$row = ['pay_from' => $from, 'pay_less_than' => $to];
			$previousValue = null;
			for ($cc = 0; $cc <= self::MAX_CLAIM_CODE; $cc++) {
				$cell  = $cells[$cc + 2];
				$value = str_starts_with($cell, '*') ? null : ($cell === '' ? 0.0 : self::number($cell, $lineNumber, 'CC ' . $cc));
				// el impuesto baja (o se mantiene) al subir el claim code; si no, las columnas estan corridas
				if ($value !== null && $previousValue !== null && $value > $previousValue) {
					throw new InvalidArgumentException("Line {$lineNumber}: CC {$cc} is greater than the previous claim code, check that the columns are aligned.");
				}
				$previousValue = $value ?? $previousValue;
				$row['cc' . $cc] = $value;
			}
			$rows[] = $row;
		}

		if (!$rows) {
			throw new InvalidArgumentException('The file has no rows.');
		}
		return $rows;
	}

	/**
	 * Federal y Provincial para el gross y claim code. $federalRow / $provincialRow: fila del rango (null = fuera de la tabla)
	 * @return array{federal_tax: ?float, provincial_tax: ?float, tax: ?float, error: ?string}
	 */
	public static function calculate(?array $federalRow, ?array $provincialRow, float $gross, int $claimCode): array
	{
		$federal    = self::value($federalRow, $claimCode);
		$provincial = self::value($provincialRow, $claimCode);

		$error = self::error('federal', $federalRow, $federal, $gross, $claimCode)
			?? self::error('provincial', $provincialRow, $provincial, $gross, $claimCode);

		return [
			'federal_tax'    => $federal,
			'provincial_tax' => $provincial,
			'tax'            => $error ? null : round($federal + $provincial, 2),
			'error'          => $error,
		];
	}

	/**
	 * Formula con los valores del periodo, para mostrarla debajo del campo
	 */
	public static function explain(?array $row, float $gross, int $claimCode): string
	{
		if (!$row) {
			return '';
		}
		return sprintf('Pay %s in %s – %s · CC %d', number_format($gross, 2), number_format((float)$row['pay_from'], 0), number_format((float)$row['pay_less_than'], 0), $claimCode);
	}

	private static function value(?array $row, int $claimCode): ?float
	{
		if (!$row || $claimCode < 0 || $claimCode > self::MAX_CLAIM_CODE || $row['cc' . $claimCode] === null) {
			return null;
		}
		return round((float)$row['cc' . $claimCode], 2);
	}

	private static function error(string $type, ?array $row, ?float $value, float $gross, int $claimCode): ?string
	{
		if ($claimCode < 0 || $claimCode > self::MAX_CLAIM_CODE) {
			return 'Invalid claim code.';
		}
		if (!$row) {
			return 'The pay $ ' . number_format($gross, 2) . " is outside the {$type} tax table; calculate the tax with the CRA PDOC.";
		}
		if ($value === null) {
			return "Claim code {$claimCode} does not apply for this pay in the {$type} tax table; use the CRA step-by-step calculation.";
		}
		return null;
	}

	private static function number(string $cell, int $lineNumber, string $column): ?float
	{
		$cell = str_replace([',', '$', ' '], '', $cell);
		if (!is_numeric($cell) || (float)$cell < 0) {
			throw new InvalidArgumentException("Line {$lineNumber}: {$column} must be a positive number.");
		}
		return (float)$cell;
	}
}

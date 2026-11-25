<?php

namespace App\Libraries;

/**
 * Bank Time por periodo de pago.
 *
 * El empleado con bank time no cobra overtime: se le pagan como horas regulares
 * hasta PAID_HOURS por periodo.
 *  - Si trabaja mas, el exceso va al banco.
 *  - Si trabaja menos, se toma del banco lo que falta (hasta donde alcance el saldo).
 * @since 25/11/2026
 */
class BankTimeCalculator
{
	public const PAID_HOURS = 88;

	public const MOVEMENT_ADD      = 'add';
	public const MOVEMENT_SUBTRACT = 'subtract';

	/**
	 * @param float $workedHours  horas trabajadas en el periodo
	 * @param float $balance      saldo del banco antes del periodo
	 * @return array{paid_hours: float, to_bank: float, from_bank: float, balance: float, new_balance: float, movement: string}
	 */
	public static function calculate(float $workedHours, float $balance): array
	{
		$workedHours = round(max(0, $workedHours), 3);
		$balance     = round(max(0, $balance), 3);
		$toBank      = 0.0;
		$fromBank    = 0.0;

		if ($workedHours >= self::PAID_HOURS) {
			$paidHours = (float)self::PAID_HOURS;
			$toBank    = round($workedHours - self::PAID_HOURS, 3);
		} else {
			$fromBank  = round(min($balance, self::PAID_HOURS - $workedHours), 3);
			$paidHours = round($workedHours + $fromBank, 3);
		}

		$movement = '';
		if ($toBank > 0) {
			$movement = self::MOVEMENT_ADD;
		} elseif ($fromBank > 0) {
			$movement = self::MOVEMENT_SUBTRACT;
		}

		return [
			'paid_hours'  => $paidHours,
			'to_bank'     => $toBank,
			'from_bank'   => $fromBank,
			'balance'     => $balance,
			'new_balance' => round($balance + $toBank - $fromBank, 3),
			'movement'    => $movement,
		];
	}
}

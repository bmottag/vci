<?php

namespace App\Libraries;

/**
 * Calculo de EI por periodo de pago.
 *
 * EE EI = ROUND( MIN( gross * tasa, prima_maxima - EI_acumulado ), 2 )
 * prima_maxima = ROUND( maximo_asegurable * tasa, 2 )
 *
 * El empleador paga 1.4 veces la prima del empleado.
 * @since 27/10/2026
 */
class EiCalculator
{
	public const EMPLOYER_FACTOR = 1.4;

	/**
	 * @param array $settings  fila de payroll_ei_settings (tasa en %)
	 * @param float $gross     ingreso asegurable del periodo (gross salary)
	 * @param float $ytdEi     EE EI acumulado del ano antes de este periodo
	 * @return array{ee_ei: float, er_ei: float}
	 */
	public static function calculate(array $settings, float $gross, float $ytdEi = 0): array
	{
		$rate       = (float)$settings['rate'] / 100;
		$maxPremium = round((float)$settings['max_insurable_earnings'] * $rate, 2);

		$ei = round(max(0, $gross) * $rate, 2);
		$ei = round(min($ei, max(0, $maxPremium - $ytdEi)), 2);

		return [
			'ee_ei' => $ei,
			'er_ei' => round($ei * self::EMPLOYER_FACTOR, 2),
		];
	}

	/**
	 * Formula con los valores del periodo, para mostrarla debajo del campo del paystub
	 */
	public static function explain(array $settings, float $gross, float $ytdEi = 0): string
	{
		$rate       = (float)$settings['rate'];
		$maxPremium = round((float)$settings['max_insurable_earnings'] * $rate / 100, 2);

		return sprintf('%s × %s%%', number_format(max(0, $gross), 2), $rate)
			. CppCalculator::cap(max(0, $gross) * $rate / 100, $maxPremium, $ytdEi);
	}
}

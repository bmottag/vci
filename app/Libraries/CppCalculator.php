<?php

namespace App\Libraries;

/**
 * Calculo de CPP y CPP2 por periodo de pago.
 *
 * CPP  = ROUND( MIN( MAX(0, (gross - exencion / periodos) * tasa), maximo_anual - CPP_acumulado ), 2 )
 * CPP2 = ROUND( MIN( MAX(0, MIN(acumulado + gross, YAMPE) - MAX(acumulado, YMPE)) * tasa_cpp2, maximo_cpp2 - CPP2_acumulado ), 2 )
 *
 * El empleador aporta lo mismo que el empleado (ER = EE) en ambos.
 * @since 26/10/2026
 */
class CppCalculator
{
	public const PERIODS_PER_YEAR = 24;

	/**
	 * @param array $settings  fila de payroll_cpp_settings (tasas en %)
	 * @param float $gross     ingreso pensionable del periodo (gross salary)
	 * @param float $ytdGross  ingreso acumulado del ano antes de este periodo
	 * @param float $ytdCpp    EE CPP acumulado del ano antes de este periodo
	 * @param float $ytdCpp2   EE CPP2 acumulado del ano antes de este periodo
	 * @return array{ee_cpp: float, er_cpp: float, ee_cpp2: float, er_cpp2: float}
	 */
	public static function calculate(array $settings, float $gross, float $ytdGross = 0, float $ytdCpp = 0, float $ytdCpp2 = 0): array
	{
		$rate     = (float)$settings['contribution_rate'] / 100;
		$cpp2Rate = (float)$settings['cpp2_rate'] / 100;

		$cpp = max(0, ($gross - (float)$settings['basic_exemption'] / self::PERIODS_PER_YEAR) * $rate);
		$cpp = min($cpp, max(0, (float)$settings['max_contribution'] - $ytdCpp));
		$cpp = round($cpp, 2);

		$cpp2Earnings = max(0, min($ytdGross + $gross, (float)$settings['yampe']) - max($ytdGross, (float)$settings['ympe']));
		$cpp2 = min($cpp2Earnings * $cpp2Rate, max(0, (float)$settings['cpp2_max_contribution'] - $ytdCpp2));
		$cpp2 = round($cpp2, 2);

		return [
			'ee_cpp'  => $cpp,
			'er_cpp'  => $cpp,
			'ee_cpp2' => $cpp2,
			'er_cpp2' => $cpp2,
		];
	}

	/**
	 * Formulas con los valores del periodo, para mostrarlas debajo de cada campo del paystub
	 * @return array{ee_cpp: string, ee_cpp2: string}
	 */
	public static function explain(array $settings, float $gross, float $ytdGross = 0, float $ytdCpp = 0, float $ytdCpp2 = 0): array
	{
		$rate     = (float)$settings['contribution_rate'];
		$cpp2Rate = (float)$settings['cpp2_rate'];

		$cpp = sprintf('(%s − %s / %d) × %s%%', number_format($gross, 2), number_format((float)$settings['basic_exemption'], 2), self::PERIODS_PER_YEAR, $rate);
		$cppRaw = max(0, ($gross - (float)$settings['basic_exemption'] / self::PERIODS_PER_YEAR) * $rate / 100);
		$cpp .= self::cap($cppRaw, (float)$settings['max_contribution'], $ytdCpp);

		$cpp2Earnings = max(0, min($ytdGross + $gross, (float)$settings['yampe']) - max($ytdGross, (float)$settings['ympe']));
		if ($cpp2Earnings == 0) {
			$cpp2 = 'No earnings between YMPE and YAMPE';
		} else {
			$cpp2 = sprintf('%s between YMPE and YAMPE × %s%%', number_format($cpp2Earnings, 2), $cpp2Rate);
			$cpp2 .= self::cap($cpp2Earnings * $cpp2Rate / 100, (float)$settings['cpp2_max_contribution'], $ytdCpp2);
		}

		return ['ee_cpp' => $cpp, 'ee_cpp2' => $cpp2];
	}

	/**
	 * Nota del tope anual cuando el valor del periodo lo supera
	 */
	public static function cap(float $raw, float $max, float $ytd): string
	{
		$remaining = max(0, $max - $ytd);
		if (round($raw, 2) <= round($remaining, 2)) {
			return '';
		}
		return sprintf('<br>Capped: max %s − YTD %s', number_format($max, 2), number_format($ytd, 2));
	}
}

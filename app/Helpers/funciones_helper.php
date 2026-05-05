<?php

if (!function_exists('pr')) {

    function pr($objVar)
    {
        echo "<div style='text-align:left'>";
        
        if (is_array($objVar) || is_object($objVar)) {
            echo "<pre>";
            print_r($objVar);
            echo "</pre>";
        } else {
            echo nl2br($objVar);
        }

        echo "</div><hr>";
    }

}

/**
 * Formatear el numero del celular
 * @author bmottag
 * @param	String	$mobile	Numero de celular
 * @return	formatea valor del numero de celular
 */
if (!function_exists("mobile_adjustment")) {
    function mobile_adjustment($mobile = '') {
        $count = strlen($mobile); 
        $num_tlf1 = substr($mobile, 0, 3); 
        $num_tlf2 = substr($mobile, 3, 3); 
        $num_tlf3 = substr($mobile, 6, 2); 
        $num_tlf4 = substr($mobile, -2); 
        return $count == 10?"$num_tlf1 $num_tlf2 $num_tlf3 $num_tlf4":chunk_split($mobile,3," ");
    }
}


if (!function_exists('base64url_encode')) {
    function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

if (!function_exists('base64url_decode')) {
    function base64url_decode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

if (!function_exists('calculate_time_difference_in_hours')) {
    function calculate_time_difference_in_hours(string $start, string $end): float
    {
        $minutes = abs(strtotime($end) - strtotime($start)) / 60;
        return round($minutes / 60, 2);
    }
}

if (!function_exists('formatear_fecha')) {
    function formatear_fecha(string $fecha): string
    {
        $date = date_create($fecha);
        return $date ? date_format($date, 'Y-m-d') : $fecha;
    }
}

/**
 * convert_hours_minutes
 * @author bmottag
 * @param $horasDecimal
 */
if (!function_exists("convert_hours_minutes")) {
    function convert_hours_minutes($horasDecimal) {
        if (!is_numeric($horasDecimal) || $horasDecimal == 0) {
            return "-";
        }

        $horasDecimal = (float)$horasDecimal;

        $horas = floor($horasDecimal);
        $minutos = round(($horasDecimal - $horas) * 60);

        return sprintf("%d hrs %02d min", $horas, $minutos);
    }
}
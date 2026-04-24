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
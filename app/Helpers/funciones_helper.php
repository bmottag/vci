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
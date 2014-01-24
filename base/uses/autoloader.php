<?php

define('USE_AUTOLOADER', 'sofi');

function sofi_autoloader($class){
    if (!Sofi::load($class)) Sofi::import($class);
}

spl_autoload_register('sofi_autoloader');

?>

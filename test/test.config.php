<?php

header('Content-type: text/plain; charset=UTF-8');

include dirname(__FILE__).'/../src/config.php';

Xily\Config::load('./data/config.ini');
echo Xily\Config::get('general.debug', 'bool', true) ? 'TRUE' : 'FALSE'; // Returns FALSE
echo "\n";
echo Xily\Config::get('numeric.taxrate', 'float', 1.19); // Returns the default value 1.19
echo "\n";
echo Xily\Config::get('numeric.discount', 'float', 0); // Returns 0.6

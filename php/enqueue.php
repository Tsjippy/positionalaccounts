<?php
namespace SIM\POSITIONALACCOUNTS;
use SIM;

add_action( 'wp_enqueue_scripts', __NAMESPACE__.'\loadAssets');
function loadAssets(){
    wp_register_script('sim_positional_script', SIM\pathToUrl(MODULE_PATH.'js/positional.min.js'), [], MODULE_VERSION, true);
}
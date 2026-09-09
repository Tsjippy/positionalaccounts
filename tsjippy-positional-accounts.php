<?php

namespace TSJIPPY\POSITIONALACCOUNTS;

/**
 * Plugin Name:          Tsjippy Positional Account
 * Description:          This plugin adds the folowing benefits: * Roles and permissions can be added to a function and not to a person * Content can be published by a person. * When another user takes on the function you link his account to the positional account so they immideate access to all content and permissions of the old person.
 * Version:              10.5.8
 * Author:               Ewald Harmsen
 * AuthorURI:            harmseninnigeria.nl
 * Requires at least:    6.3
 * Requires PHP:         8.3
 * Tested up to:         7.1
 * Plugin URI:            https://github.com/Tsjippy/positionalaccounts
 * Tested:               7.1
 * TextDomain:            tsjippy
 * Requires Plugins:    , tsjippy-forms
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @author Ewald Harmsen
 */
if (! defined('ABSPATH')) {
    exit;
}

// Load shared code
if(file_exists(__DIR__  . '/shared-functionality/loader.php')){
    require_once(__DIR__  . '/shared-functionality/loader.php');
}

// Define constants
define(__NAMESPACE__ . '\PLUGIN', plugin_basename(__FILE__));
define(__NAMESPACE__ . '\PLUGINPATH', __DIR__ . '/');
define(__NAMESPACE__ . '\PLUGINVERSION', get_plugin_data(__FILE__, false, false)['Version']);
define(__NAMESPACE__ . '\PLUGINSLUG', str_replace('tsjippy-', '', basename(__FILE__, '.php')));
define(__NAMESPACE__ . '\SETTINGS', get_option('tsjippy_positional-accounts_settings', []));

// run right before activation
register_activation_hook(__FILE__, function () {

    // Load shared code
    if(file_exists(__DIR__  . '/shared-functionality/loader.php')){
        require_once(__DIR__  . '/shared-functionality/loader.php');
    }

    $settings    = SETTINGS;
    
    // Import the forms
    $formBuilder    = new \TSJIPPY\FORMS\FormExport();

    $files = glob(PLUGINPATH  . "imports/*.sform");
    foreach ($files as $file) {
        $formName            = basename($file, '.sform');
        $settings[$formName] = $formBuilder->importForm($file);
    }

    update_option('tsjippy_positional-accounts_settings', $settings);

    if(function_exists('TSJIPPY\activate')){
        \TSJIPPY\activate();
    }
});

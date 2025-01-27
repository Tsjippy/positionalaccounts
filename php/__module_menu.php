<?php
namespace SIM\FORMS;
use SIM;

const MODULE_VERSION		= '7.0.0';
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

add_filter('sim_module_data', __NAMESPACE__.'\moduleData', 10, 3);
function moduleData($dataHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $dataHtml;
	}

	return $dataHtml;
}

add_action('sim_module_activated', __NAMESPACE__.'\moduleActivated');
function moduleActivated($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{
		return;
	}

	// Enable forms module
	if(!SIM\getModuleOption('forms', 'enable')){
		SIM\ADMIN\enableModule('forms');
	}

	// Import the forms
	$formBuilder	= new SIM\FORMS\FormBuilderForm();

	$files = glob(MODULE_PATH  . "imports/*.sform");
	foreach ($files as $file) {
		$formBuilder->importForm($file);
	}
}
<?php
namespace SIM\POSTIONALACCOUNT;
use SIM;

const MODULE_VERSION		= '1.0.0';
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

add_filter('sim_module_data', __NAMESPACE__.'\moduleData', 10, 3);
function moduleData($dataHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $dataHtml;
	}

	$args = array(
		'meta_query' => array(
			array(
				'key' 		=> 'account-type',
				'value' 	=> 'positional',
				'compare' 	=> '='
			)
		)
	);

	$url		= SIM\ADMIN\getDefaultPageLink('usermanagement', 'user_edit_page')."?userid=";

	$dataHtml	.= "<table class='sim'>";
		$dataHtml	.= "<tr><th>Name</th><th>Linked to</th></tr>";

	foreach(get_users($args) as $user){
		$linkedUserId 	= get_user_meta($user->ID, 'linked-account', true);

		$name			= "No user linked to this account <a href='$url$user->ID&main_tab=login_info'>Link now</a>";

		if(is_numeric($linkedUserId)){
			$linkedUser		= get_user($linkedUserId);

			if($linkedUser){
				$name		= $linkedUser->display_name;
			}
		}

		$dataHtml	.= "<tr><td><a href='$url$user->ID&main_tab=login_info'>$user->display_name</a></td><td>$name</td></tr>";
	}
	$dataHtml	.= "</table>";
	

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
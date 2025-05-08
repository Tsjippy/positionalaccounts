<?php
namespace SIM\POSITIONALACCOUNTS;
use SIM;

const MODULE_VERSION		= '1.0.9';
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

add_filter('sim_module_positionalaccounts_data', __NAMESPACE__.'\moduleData');
function moduleData($dataHtml){
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

	// SHow a table with one positional account per row and all the accounts linked to it.
	$dataHtml	.= "<table class='sim'>";
		$dataHtml	.= "<tr><th>Name</th><th>Linked to</th></tr>";


		foreach(get_users($args) as $user){
			$linkedUserIds 	= get_user_meta($user->ID, 'linked-accounts', true);

			$name			= "No user linked to this account <a href='$url$user->ID&main_tab=login_info'>Link now</a>";

			if(is_array($linkedUserIds)){
				$names	= [];
				foreach($linkedUserIds as $linkedUserId){
					$linkedUser		= get_user($linkedUserId);

					if($linkedUser){
						$names[]		= $linkedUser->display_name;
					}
				}

				if(!empty($names)){
					$name	= implode("\n", $names);
				}

				$dataHtml	.= "<tr><td><a href='$url$user->ID&main_tab=login_info'>$user->display_name</a></td><td>$name</td></tr>";
			}	
		}
	$dataHtml	.= "</table>";
	

	return $dataHtml;
}

add_action('sim_module_positionalaccounts_activated', __NAMESPACE__.'\moduleActivated');
function moduleActivated(){
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
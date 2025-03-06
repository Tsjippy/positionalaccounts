<?php
namespace SIM\POSITIONALACCOUNTS;
use SIM;

add_action('sim_positionalaccounts_module_update', __NAMESPACE__.'\pluginUpdate');
function pluginUpdate($oldVersion){

    SIM\printArray($oldVersion);

    if($oldVersion < '1.0.7'){
        $args = array(
            'meta_query' => array(
                array(
                    'key' 		=> 'account-type',
                    'value' 	=> 'positional',
                    'compare' 	=> '='
                )
            )
        );
        
        foreach(get_users($args) as $user){
            $linkedUserId 	= get_user_meta($user->ID, 'linked-account', true);

            if(!empty($linkedUserId)){
                update_user_meta($user->ID, 'linked-accounts', [$linkedUserId]);
            }

            delete_user_meta($user->ID, 'linked-account');
        }
    }
}


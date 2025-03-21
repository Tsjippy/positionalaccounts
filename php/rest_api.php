<?php
namespace SIM\POSITIONALACCOUNTS;
use SIM;

add_action( 'rest_api_init', __NAMESPACE__.'\bioRestApi');
function bioRestApi() {
	register_rest_route(
        RESTAPIPREFIX.'/positional',
        '/switch_account',
        array(
            'methods'               => 'POST',
            'callback'              => __NAMESPACE__.'\switchAccount',
            'permission_callback'   => '__return_true',
            'args'					=> array(
				'switch-account'		=> array(
					'required'	=> true
				),
                'nonce'		=> array(
					'required'	=> true
				),
			)
		)
	);
}


function switchAccount(){
    // Check if valid request
    if(!isset($_POST['switch-account']) || !is_numeric($_POST['switch-account']) || !wp_verify_nonce($_POST['nonce'], 'sim_switch_account')){
        return;
    }

    // Get the linked accounts for the current user
    $user               = wp_get_current_user();
    $linkedAccountIds   = get_user_meta($user->ID, 'linked-accounts', true);

    // check if the current user has permission to switch to this account
    if(empty($linkedAccountIds) || !is_array($linkedAccountIds) || !in_array($_POST['switch-account'], $linkedAccountIds)){
        echo "<div class='error'>This account is not linked to your account!</div>";
    }

    if(!isset($_SESSION)){
        session_start();
    }
    $_SESSION['orgaccount']   = $user->ID;

    session_write_close();

    // Logout the current user
    wp_destroy_current_session();
	wp_clear_auth_cookie();
    do_action( 'wp_logout', $user->ID );

    // Login the new user
    wp_set_current_user($_POST['switch-account']);  

    // add a filter to allow passwordless sign in
    add_filter( 'authenticate', __NAMESPACE__.'\allowPasswordlessLogin', 999, 3 );

    // Add action to store the login cookie in $_COOKIE
    add_action( 'set_logged_in_cookie', __NAMESPACE__.'\storeInCookieVar', 10, 6 );

    // perform the login
    $user = wp_signon(['remember'=>true]);

    // Remove action to store the login cookie in $_COOKIE
    remove_action( 'set_logged_in_cookie', __NAMESPACE__.'\storeInCookieVar' );

    // remove the filter to allow passwordless sign in
    remove_filter( 'authenticate', __NAMESPACE__.'\allowPasswordlessLogin', 999, 3 );

    if ( is_wp_error( $user ) ) {
        return new \WP_Error('Login error', $user->get_error_message());
    }

    return 'Succesfully switched, the page will refresh now.';
}

/**
  * An 'authenticate' filter callback that authenticates the user using only the username.
  *
  * To avoid potential security vulnerabilities, this should only be used in the context of a programmatic login,
  * and unhooked immediately after it fires.
  * 
  * @param WP_User $user
  * @param string $username
  * @param string $password
  * @return bool|WP_User a WP_User object if the username matched an existing user, or false if it didn't
*/
function allowPasswordlessLogin( $user, $username, $password ) {
    session_start();

    if(isset($_POST['switch-account'])){
        $user   =  get_user_by( 'id', $_POST['switch-account'] );

        return $user;
    }

    return $user;
}

// function to update the $_COOKIE variable without refreshing the page
// Needed to create a nonce after ajax login
function storeInCookieVar($loggedInCookie, $expire, $expiration, $userId, $type, $token){
    // make sure we only write the right cookie
    if(get_current_user_id() == $userId){
        $_COOKIE[ LOGGED_IN_COOKIE ] = $loggedInCookie;
    }
}
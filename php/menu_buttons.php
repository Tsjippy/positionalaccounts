<?php
namespace SIM\POSITIONALACCOUNTS;
use SIM;

// switch accounts if needed. Do this as early as possible to make sure the website is rendered after the switch
add_action( 'plugins_loaded', __NAMESPACE__ . '\maybeSwitchAccount' );
function maybeSwitchAccount($args){
    if(isset($_POST['switch-account'])){
        switchAccount();
    }

    return $args;
}

//add switch account buttons
add_filter('wp_nav_menu_items', __NAMESPACE__.'\menuItems', 1, 2);
function menuItems($items, $args) {
    // We should add a switch menu item
    if(!is_user_logged_in() || $args->menu->slug != 'primary-menu'){
        return $items;
    }

    $user       = wp_get_current_user();
    $userId     = $user->ID;
    $nonce      = wp_create_nonce('sim_switch_account');

    $subItems   = '';

    $profilePicture = SIM\displayProfilePicture($userId, [20, 20], false, false, false);

    $baseMenuItem   = "<li class='menu-item switch-account'>";
        $baseMenuItem   .= "<form action='' method='post'>";
            $baseMenuItem   .= "<input type='hidden' name='switch-account' value='%d'>";
            $baseMenuItem   .= "<input type='hidden' name='nonce' value='$nonce'>";
            $baseMenuItem   .= "<button type='submit' class='account-switcher'>Switch to %s</button>";
        $baseMenuItem   .= "</form>";
    $baseMenuItem   .= "</li>";

    if(getAccountType($userId) == 'positional'){
        $linkedAccountId    = get_user_meta($userId, 'linked-account', true);
        if(empty($linkedAccountId)){
            return $items;
        }

        $linkedAccountName  = get_user($linkedAccountId)->display_name;
        if(!$linkedAccountName){
            return $items;
        }

        if(!$profilePicture){
            $profilePicture = SIM\displayProfilePicture($linkedAccountId, [20, 20], true, false, false);
        }

        // Add switch back to linked account
        $subItems   .= sprintf($baseMenuItem, $linkedAccountId, $linkedAccountName);
    }else{
        $linkedAccountIds   = get_user_meta($userId, 'linked-accounts', true);

        if(is_array($linkedAccountIds)){
            foreach($linkedAccountIds as $id){
                if(!is_numeric($id)){
                    continue;
                }

                $linkedAccountName  = get_user($id)->display_name;

                // Add switch button to positional account
                $subItems   .= sprintf($baseMenuItem, $id, $linkedAccountName);
            }
        }
    }

    if(empty($subItems)){
        return $items;
    }

    ob_start();
    ?>
    <style>
        .account-switcher, .account-switcher:hover{
            padding:            3px;
            font-weight:        600;
            border-radius:      7%;
            background-color:   #fff;
            color:              #515151;
            font-size:          14px;
            line-height:        40px;
        }

        .account-switcher:hover{
            text-decoration:                  underline;
            text-decoration-color:            currentcolor;
            -webkit-text-decoration-color:  #bd2919;
            text-decoration-color:          #bd2919;
                }
    </style>
    <li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-has-children">
        <a href="/my-profile/">
            <?php echo $profilePicture;?>
            <span role="presentation" class="dropdown-menu-toggle">
                <span class="gp-icon icon-arrow">
                    <svg viewBox="0 0 330 512" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em">
                        <path d="M305.913 197.085c0 2.266-1.133 4.815-2.833 6.514L171.087 335.593c-1.7 1.7-4.249 2.832-6.515 2.832s-4.815-1.133-6.515-2.832L26.064 203.599c-1.7-1.7-2.832-4.248-2.832-6.514s1.132-4.816 2.832-6.515l14.162-14.163c1.7-1.699 3.966-2.832 6.515-2.832 2.266 0 4.815 1.133 6.515 2.832l111.316 111.317 111.316-111.317c1.7-1.699 4.249-2.832 6.515-2.832s4.815 1.133 6.515 2.832l14.162 14.163c1.7 1.7 2.833 4.249 2.833 6.515z"></path>
                    </svg>
                </span>
            </span>
        </a>
        <ul class="sub-menu" style='width: min-content;'>
            <?php echo $subItems;?>
        </ul>
    </li>
    <?php
    $items  .= ob_get_clean();

    return $items;
}

function switchAccount(){
    // Check if valid request
    if(!isset($_POST['switch-account']) || !is_numeric($_POST['switch-account']) || !wp_verify_nonce($_POST['nonce'], 'sim_switch_account')){
        return;
    }

    // Get the linked accounts for the current user
    $user   = wp_get_current_user();
    if(getAccountType($user->ID) == 'positional'){
        $linkedAccountIds   = (array) get_user_meta($user->ID, 'linked-account', true);
    }else{
        $linkedAccountIds   = get_user_meta($user->ID, 'linked-accounts', true);
    }

    // check if the current user has permission to switch to this account
    if(empty($linkedAccountIds) || !in_array($_POST['switch-account'], $linkedAccountIds)){
        echo "<div class='error'>This account is not linked to your account!</div>";
    }

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
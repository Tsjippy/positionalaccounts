<?php
namespace SIM\POSTIONALACCOUNT;
use SIM;

//add switch account button
add_filter('wp_nav_menu_items', __NAMESPACE__.'\menuItems', 10, 2);
function menuItems($items, $args) {
    // We should add a switch menu item
    if(is_user_logged_in() && $args->menu->slug == 'primary-menu'){
        if(isset($_GET['switch-account'])){
            switchAccount();
        }

        $user       = wp_get_current_user();
        $userId     = $user->ID;
        $nonce      = wp_create_nonce('sim_switch_account');

        $subItems   = '';

        $profilePicture = SIM\displayProfilePicture($userId, [20, 20], false, false, false);

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

            $subItems   .= "<li class='menu-item switch-account'><a href='?switch-account=$linkedAccountId&nonce=$nonce' class='switch-accounts'>Switch to $linkedAccountName</a></li>";
        }else{
            $linkedAccountIds   = get_user_meta($userId, 'linked-accounts', true);

            if(is_array($linkedAccountIds)){
                foreach($linkedAccountIds as $id){
                    if(!is_numeric($id)){
                        continue;
                    }

                    $linkedAccountName  = get_user($id)->display_name;

                    $subItems   .= "<li class='menu-item switch-account'><a href='?switch-account=$id&nonce=$nonce' class='switch-accounts'>Switch to $linkedAccountName</a></li>";
                }
            }
        }

        if(empty($subItems)){
            return $items;
        }

        ob_start();
        ?>
        <li class="menu-item">
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
            <ul class="sub-menu">
                <?php echo $subItems;?>
            </ul>
        </li>
        <?php
        $items  .= ob_get_clean();
        
    }

    return $items;
}

function switchAccount(){
        if(!isset($_GET['switch-account']) || !wp_verify_nonce($_GET['nonce'], 'sim_switch_account')){
            return;
        }

        wp_set_current_user($_GET['switch-account']);  

        // add a filter to allow passwordless sign in
        add_filter( 'authenticate', __NAMESPACE__.'\allowPasswordlessLogin', 999, 3 );

        // Add action to store the login cookie in $_COOKIE
        add_action( 'set_logged_in_cookie', __NAMESPACE__.'\storeInCookieVar', 10, 6 );
    
        // perform the login
        $user = wp_signon();
    
        // Remove action to store the login cookie in $_COOKIE
        remove_action( 'set_logged_in_cookie', __NAMESPACE__.'\storeInCookieVar' );
    
        // remove the filter to allow passwordless sign in
        remove_filter( 'authenticate', __NAMESPACE__.'\allowPasswordlessLogin', 999, 3 );
    
        if ( is_wp_error( $user ) ) {
            return new \WP_Error('Login error', $user->get_error_message());
        }
    
        // make sure we set the current user to the just logged in user
        wp_set_current_user($user->ID);    
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
    if(!wp_verify_nonce($_GET['nonce'], 'sim_switch_account')){
        return;
    }

    session_start();

    if(isset($_GET['switch-account'])){
        $user   =  get_user_by( 'id', $_GET['switch-account'] );

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
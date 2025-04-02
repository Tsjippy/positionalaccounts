<?php
namespace SIM\POSITIONALACCOUNTS;
use SIM;

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

    $style      = '';
    if(wp_is_mobile()){
        $style  = "style='width:100%;'";
    }

    $baseMenuItem   = "<li class='menu-item switch-account'>";
        $baseMenuItem   .= "<button type='button' class='account-switcher' data-accountid='%d' data-nonce='$nonce' $style>Switch to %s</button>";
    $baseMenuItem   .= "</li>";

    $linkedAccountIds    = get_user_meta($userId, 'linked-accounts', true);
    if(empty($linkedAccountIds)){
        return $items;
    }

    wp_enqueue_script('sim_positional_script');

    foreach($linkedAccountIds as $linkedAccountId){
        if(!is_numeric($linkedAccountId)){
            continue;
        }

        $linkedAccountName  = get_user($linkedAccountId)->display_name;
        if(!$linkedAccountName){
            return $items;
        }

        if(!$profilePicture){
            $profilePicture = SIM\displayProfilePicture($linkedAccountId, [20, 20], true, false, false);
        }

        // Add switch back to the linked account
        $subItems   .= sprintf($baseMenuItem, $linkedAccountId, $linkedAccountName);
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
<?php
namespace SIM\POSITIONALACCOUNTS;
use SIM;

add_action('sim-after-login-settings', __NAMESPACE__.'\addConditionalAccountSettings', 10, 2);

function addConditionalAccountSettings($userId, $nonce){
    $type			= 'positional';
    if(get_user_meta($userId, 'account-type', true) == 'positional'){
        $type		= 'normal';
    }
    ?>
    <form method='post'>
        <input type='hidden' name='user_id' value='<?php echo $userId;?>'>
        <input type='hidden' name='wp_2fa_nonce' value='<?php echo $nonce;?>'>
        <input type='hidden' name='type' value='<?php echo $type;?>'>

        Use the button below to switch this account to a <?php echo $type;?> account<br>
        <input type='submit' name='action' value='Change account type' class='button small'>
    </form>
    <br>
    
    <form method='post'>
        <input type='hidden' name='user_id' value='<?php echo $userId;?>'>
        <input type='hidden' name='wp_2fa_nonce' value='<?php echo $nonce;?>'>

        <?php
        $linkedAccountId	= get_user_meta($userId, 'linked-account', true);
        if(empty($linkedAccountId)){
            $linkedAccountId	= -1;
        }

        echo SIM\userSelect("Link to an user account", true, false, '', 'linked_account', [], $linkedAccountId, [1]);
        ?>
        <input type='submit' name='action' value='Link now' class='button small'>
    </form>
    <?php
}

add_action('sim-login-settings-save', __NAMESPACE__.'\updateAccountType', 10, 2);
function updateAccountType($userId, $name){
    if($_REQUEST['action'] == 'Change account type'){
        update_user_meta($userId, 'account-type', $_REQUEST['type']);
        echo "<div class='success'>Succesfully changed the account type for $name to {$_REQUEST['type']}</div>";
    }elseif($_REQUEST['action'] == 'Link now'){
        if(!is_numeric($_REQUEST['linked_account'])){
            return;
        }

        $linkedAccountId    = $_REQUEST['linked_account'];

        // Remove old linked user if needed
        $oldLinkedUserId = get_user_meta($userId, 'linked-account', true);
        if(is_numeric($oldLinkedUserId)){
            // A non-positional account can have multiple positional account linked to it
            $oldLinkedAccountLinkedAccounts = get_user_meta($oldLinkedUserId, 'linked-accounts', true);

            if(is_array($oldLinkedAccountLinkedAccounts) && in_array($userId, $oldLinkedAccountLinkedAccounts)){
                unset($oldLinkedAccountLinkedAccounts[$userId]);

                update_user_meta($oldLinkedUserId, 'linked-accounts', $oldLinkedAccountLinkedAccounts);
            }
        }

        // Store the link in this account
        update_user_meta($userId, 'linked-account', $linkedAccountId);

        // Store the link in the target account
        // A non-positional account can have multiple positional account linked to it
        $linkedAccountLinkedAccounts    = (array)get_user_meta($linkedAccountId, 'linked-accounts', true);
        $linkedAccountLinkedAccounts[]  = $userId;
        update_user_meta($linkedAccountId, 'linked-accounts', $linkedAccountLinkedAccounts);

        $displayName	= get_user($_REQUEST['linked_account'])->display_name;
        echo "<div class='success'>Succesfully linked the account for $name to the account of $displayName</div>";
    }
}

add_filter('sim-generics-form', __NAMESPACE__.'\showPositionalForm', 10, 2);
function showPositionalForm($html, $userId){
    if(checkIfNormal('', $userId)){
        return $html;
    }

    $linkedAccountId	= get_user_meta($userId, 'linked-account', true);
    $inkedUser			= get_user($linkedAccountId);
    if(empty($linkedAccountId) || !$inkedUser){
        $linkedAccountId	= -1;
        $html			   .= "<div class='warning'>This account is an positional account and should be linked to a normal user account.<br>Please do so on the 'Login Info' tab</div>";
    }else{
        $nameHtml			= $inkedUser->display_name;
        if(function_exists('SIM\USERPAGES\getUserPageUrl')){
            $url = SIM\USERPAGES\getUserPageUrl($inkedUser->ID);
            if($url){
                $nameHtml	= "<a href='$url' target='_blank'>$nameHtml</a>";
            }
        }
        
        $html			   .= "<div class='warning'>This account is a positional account and is linked to $nameHtml</div>";
    }

    $html	.= do_shortcode("[formbuilder formname=positional_generic userid=$userId]");

    return $html;
}

// Most forms do not apply to positional accounts
add_filter('sim-should-show-family-form',__NAMESPACE__.'\checkIfNormal', 10, 2);
add_filter('sim-should-show-location-form',__NAMESPACE__.'\checkIfNormal', 10, 2);
add_filter('sim-should-show-picture-form',__NAMESPACE__.'\checkIfNormal', 10, 2);
add_filter('sim-should-show-security-form',__NAMESPACE__.'\checkIfNormal', 10, 2);
add_filter('sim-should-show-vaccination-form',__NAMESPACE__.'\checkIfNormal', 10, 2);

// no mandatory documents for positional accounts
add_filter('sim-must-read',__NAMESPACE__.'\checkIfNormal', 10, 2);

function checkIfNormal( $isNormal, $userId=''){
    return getAccountType($userId) != 'positional';
}

// No recommended fields for positional user accounts
add_filter("sim_recommended_html_filter", __NAMESPACE__.'\filterPositionalAccount', 10, 2);
add_filter("sim_mandatory_html_filter", __NAMESPACE__.'\filterPositionalAccount', 10, 2);
function filterPositionalAccount($html, $userId){
	if(getAccountType($userId) == 'positional'){
		return '';
	}

	return $html;
}

function getAccountType($userId=''){
    if(!is_numeric($userId)){
        $user       = wp_get_current_user();
        $userId     = $user->ID;
    }
    
    return get_user_meta($userId, 'account-type', true);
}

// Show the details of the person linked to a positional account and not the positional account details
add_filter('sim-user-description-user-id', __NAMESPACE__.'\userDescriptionId');
function userDescriptionId($userId){
    $linkedAccountId    = get_user_meta($userId, 'linked-account', true);

    // account is linked and the account still exists
    if(is_numeric($linkedAccountId) && get_user($linkedAccountId)){
        return $linkedAccountId;
    }

    return $userId;
}
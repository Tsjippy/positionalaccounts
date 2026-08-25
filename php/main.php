<?php

namespace TSJIPPY\POSITIONALACCOUNTS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('tsjippy-user-management-after-login-settings', __NAMESPACE__ . '\addConditionalAccountSettings', 10, 2);

/**
 * Prints the forms to change an account type and to link a positional account to a personal account
 * 
 * @param int $userId The user id of the account
 * @param string $nonce The nonce for the form
 */
function addConditionalAccountSettings($userId, $nonce)
{
    $type            = 'positional';
    if (isPositionalAccount($userId)) {
        $type        = 'normal';
    }
    ?>
    <form method='post'>
        <input type='hidden' class='no-reset' name='user-id' value='<?php echo esc_attr($userId); ?>'>
        <input type='hidden' class='no-reset' name='wp-2fa-nonce' value='<?php echo esc_attr($nonce); ?>'>
        <input type='hidden' class='no-reset' name='type' value='<?php echo esc_attr($type); ?>'>

        Use the button below to switch this account to a <?php echo esc_attr($type); ?> account<br>
        <input type='submit' name='action' value='Change account type' class='button small'>
    </form>
    <br>

    <form method='post'>
        <input type='hidden' class='no-reset' name='user-id' value='<?php echo esc_attr($userId); ?>'>
        <input type='hidden' class='no-reset' name='wp-2fa-nonce' value='<?php echo esc_attr($nonce); ?>'>

        <?php
        $linkedAccountIds    = get_user_meta($userId, 'tsjippy_linked_accounts');
        if (empty($linkedAccountIds)) {
            $linkedAccountIds    = [];
        }

        TSJIPPY\userSelect(title: "Link to an user account", onlyAdults: true, id: 'linked_accounts', userId: $linkedAccountIds, excludeIds: [1], multiple: true, echo: true);
        ?>
        <input type='submit' name='action' value='Link now' class='button small'>
    </form>
<?php
}

add_action('tsjippy-user-management-login-settings-save', __NAMESPACE__ . '\updateAccountType', 10, 2);
/**
 * Updates the account type and linked accounts for a user
 *
 * @param int    $userId The user id of the account
 * @param string $name   The name of the account
 */
function updateAccountType($userId, $name)
{
    // phpcs:ignore
    if ($_REQUEST['action'] == 'Change account type') {
        // phpcs:ignore
        $type   = TSJIPPY\sanitize($_REQUEST['type']);

        if($type == 'positional'){
            update_user_meta($userId, 'tsjippy_account-type', $type);
        }else{
            delete_user_meta($userId, 'tsjippy_account-type');
        }
        
        ?>
        <div class='success'>
            Succesfully changed the account type for <?php echo esc_html($name);?> to <?php echo esc_html($type);?>
        </div>
        <?php
    } 
    // phpcs:ignore
    elseif ($_REQUEST['action'] == 'Link now') {
        // phpcs:ignore
        $linkedAccountIds = TSJIPPY\sanitize($_REQUEST['linked_accounts'] ?? []);

        // Remove old linked user if needed
        $oldLinkedUserIds = get_user_meta($userId, 'tsjippy_linked_accounts');
        $removed          = array_diff($oldLinkedUserIds, $linkedAccountIds);
        $displayName      = '';

        foreach ($removed as $oldLinkedUserId) {
            // Delete from old target account
            delete_user_meta($oldLinkedUserId, 'tsjippy_linked_accounts', $userId);

            // Delete from current user account
            delete_user_meta($userId, 'tsjippy_linked_accounts', $oldLinkedUserId);

            if (!empty($displayName)) {
                $displayName    .= ' & ';
            }
            $displayName    .= get_user($oldLinkedUserId)->display_name;

            ?>
            <div class='success'>
                Succesfully unlinked the account for <?php echo esc_html($name);?> from the account of <?php echo esc_html($displayName);?>
            </div>
            <?php
        }

        // Store the link
        $added          = array_diff($linkedAccountIds, $oldLinkedUserIds);

        $displayName    = '';

        foreach ($added as $newlyLinkedId) {
            // add to target account
            add_user_meta($newlyLinkedId, 'tsjippy_linked_accounts', $userId);
            
            // add to own account
            add_user_meta($userId, 'tsjippy_linked_accounts', $newlyLinkedId);

            if (!empty($displayName)) {
                $displayName    .= ' & ';
            }
            $displayName    .= get_user($newlyLinkedId)->display_name;

            ?>
            <div class='success'>
                Succesfully linked the account for <?php echo esc_html($name);?> to the account of <?php echo esc_html($displayName);?>
            </div>
            <?php
        }
    }
}

add_filter('tsjippy-user-management-generics-form', __NAMESPACE__ . '\showPositionalForm', 10, 2);
/**
 * Shows the generic form for positional accounts
 *
 * @param string $html The html of the form
 * @param int $userId The user id of the account
 *
 * @return string The html of the form
 */
function showPositionalForm($html, $userId)
{
    if (checkIfNormal('', $userId)) {
        return $html;
    }

    $linkedAccountIds    = get_user_meta($userId, 'tsjippy_linked_accounts');
    if (empty($linkedAccountIds)) {
        $linkedAccountIds = [];
    }

    $userNames  = [];
    foreach ($linkedAccountIds as $linkedAccountId) {
        $inkedUser            = get_user($linkedAccountId);
        if (!empty($linkedAccountId) && $inkedUser) {
            $nameHtml         = $inkedUser->display_name;
            if (function_exists('TSJIPPY\USERPAGES\getUserPageUrl')) {
                $url          = get_author_posts_url($inkedUser->ID);
                if ($url) {
                    $nameHtml = "<a href='$url' target='_blank'>$nameHtml</a>";
                }
            }

            $userNames[]   = $nameHtml;
        }
    }

    if (empty($userNames)) {
        $html               .= "<div class='warning'>This account is an positional account and should be linked to a normal user account.<br>Please do so on the 'Login Info' tab</div>";
    } else {
        $userNames          = implode(' & ', $userNames);
        $html               .= "<div class='warning'>This account is a positional account and is linked to $userNames</div>";
    }

    $forms  = new TSJIPPY\FORMS\Forms( postId: SETTINGS['positional_generic'] ?? -1, userId: $userId);
    $html    .= $forms->showForm();

    return $html;
}

// Most forms do not apply to positional accounts
add_filter('tsjippy-user-management-should-show-family-form', __NAMESPACE__ . '\checkIfNormal', 10, 2);
add_filter('tsjippy-user-management-should-show-location-form', __NAMESPACE__ . '\checkIfNormal', 10, 2);
add_filter('tsjippy-user-management-should-show-picture-form', __NAMESPACE__ . '\checkIfNormal', 10, 2);
add_filter('tsjippy-user-management-should-show-security-form', __NAMESPACE__ . '\checkIfNormal', 10, 2);

// no mandatory documents for positional accounts
add_filter('tsjippy-mandatory-must-read', __NAMESPACE__ . '\checkIfNormal', 10, 2);
/**
 * Checks if the account is a normal account
 *
 * @param bool $isNormal Whether the account is normal
 * @param int $userId The user id of the account
 *
 * @return bool Whether the account is normal
 */
function checkIfNormal($isNormal, $userId = '')
{
    return !isPositionalAccount($userId);
}

// No recommended fields for positional user accounts
add_filter("tsjippy-forms-manadatory-html-filter", __NAMESPACE__ . '\filterPositionalAccount', 10, 2);
/**
 * Filters the html for positional accounts
 *
 * @param string $html The html of the form
 * @param int $userId The user id of the account
 *
 * @return string The html of the form
 */
function filterPositionalAccount($html, $userId)
{
    if (isPositionalAccount($userId)) {
        return '';
    }

    return $html;
}

/**
 * Checks if positional account
 *
 * @param int $userId The user id of the account
 *
 * @return bool true if account is a positional account
 */
function isPositionalAccount($userId = '')
{
    if (!is_numeric($userId)) {
        $user       = wp_get_current_user();
        $userId     = $user->ID;
    }

    return get_user_meta($userId, 'tsjippy_account-type', true) == 'positional';
}

// Show the details of the person linked to a positional account and not the positional account details
add_filter('tsjippy-user-pages-description-user-id', __NAMESPACE__ . '\userDescriptionId');
/**
 * Gets the user id of the account to show in the description
 *
 * @param int $userId The user id of the account
 *
 * @return int The user id of the account to show in the description
 */
function userDescriptionId($userId)
{
    $linkedAccountIds    = get_user_meta($userId, 'tsjippy_linked_accounts');

    // account is linked and the account still exists
    if (is_array($linkedAccountIds) && get_user($linkedAccountIds[0])) {
        return $linkedAccountIds[0];
    }

    return $userId;
}

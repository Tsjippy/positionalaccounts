import {webAuthVerification} from './../../login/js/partials/webauth.js';

async function verifyAccountSwitch(target){
    let targetAccountId = target.dataset.accountid;
    let nonce           = target.dataset.nonce;

    let loader          = Main.showLoader(target);

    if(await webAuthVerification(targetAccountId)){
        let formData    = new FormData();

        formData.append('switch-account', targetAccountId);

        formData.append('nonce', nonce);

        let response	= await FormSubmit.fetchRestApi('positional/switch_account', formData);

	    if(response){
            Main.displayMessage(response);

            window.location.href   = window.location.href;

            return;
        }
    }

    Main.displayMessage("Passkey login for the account failed.\nLogging out...", 'error');

    // If the passkey login failed, we log out the user
    // This is to ensure that the user can try logging in with a different method
    document.querySelectorAll(`.logout`).forEach((el) => el.click());
    
    let menu     = loader.closest('.menu-item-has-children');
    loader.remove();
    if(menu.querySelectorAll(`button`).length == 0){
        menu.remove();
    }
}

console.log('Positional accounts script loaded');

document.addEventListener("click", function(event) {
    if(event.target.matches(`.account-switcher`)){
        event.stopImmediatePropagation();
        verifyAccountSwitch(event.target);
    }
});
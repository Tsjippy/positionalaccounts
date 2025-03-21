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
        
    Main.displayMessage("Please logout and login as the other account.\nMake sure you than setup passkey login for that account.", 'error');
    
    let menu     = loader.closest('.menu-item-has-children');
    loader.remove();
    if(menu.querySelectorAll(`button`).length == 0){
        menu.remove();
    }
}

console.log('Positional accounts script loaded');

document.addEventListener("click", function(event) {
    if(event.target.matches(`.account-switcher`)){
        verifyAccountSwitch(event.target);
    }
});
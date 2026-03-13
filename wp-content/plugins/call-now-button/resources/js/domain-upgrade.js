function cnb_domain_upgrade_hide_notice() {
    const cnb_notice = jQuery('.cnb-message');
    const message = jQuery('.cnb-error-message').text();
    if (!message) {
        cnb_notice.hide();
    }
}

function showMessage(type='success', message='') {
    const cnb_notice = jQuery('.cnb-message');

    cnb_notice.hide();
    jQuery('.cnb-error-message').text(message);
    cnb_notice.removeClass('notice-error notice-warning notice-success');
    cnb_notice.addClass('notice notice-' + type);
    cnb_notice.show();
}

/**
 * function for the currency selector on the Upgrade page
 */
function cnb_domain_upgrade_currency() {
    jQuery(".cnb-currency-select").on('click', function(){
        jQuery(".cnb-currency-select").removeClass('nav-tab-active');
        jQuery(".currency-box").removeClass('currency-box-active');
        jQuery(this).addClass("nav-tab-active");
        const currencyType = jQuery(this).attr("data-cnb-currency");
        const currencySelector = 'currency-box-' + currencyType;
        jQuery('.' + currencySelector).addClass('currency-box-active');
    });
}

/**
 * Request a Stripe Checkout Session ID for a given domain and a selected plan
 *
 * Used on the Domain upgrade page.
 * @param planId
 */
function cnb_get_checkout(planId) {
    showMessage('warning', 'Processing your request, please wait...')

    const data = {
        'action': 'cnb_get_checkout',
        'planId': planId,
        'domainId': jQuery('#cnb_domain_id').val()
    };

    jQuery.post(ajaxurl, data, function(response) {
        cnb_goto_checkout(response)
    });
}

/**
 * The callback function once an API response for a Stripe Checkout Session ID is received.
 * @param response
 */
function cnb_goto_checkout(response) {
    if (typeof stripe === 'undefined') {
        showMessage('warning', 'Using alternate provider...');
        location.href = 'https://www.callnowbutton.com/stripe.html?s=' + response.message;
    }else if (response.status === 'success') {
        showMessage('success', 'Redirecting you...')
        stripe.redirectToCheckout({sessionId: response.message});
    }else if (response.status === 'error') {
        showMessage('warning', response.message)
    }
}

function cnb_domain_upgrade_notice_profile_saved() {
    showMessage('success', 'Your profile has been saved, click Upgrade to finish your order.')

    // Also go straight to checkout
    if (cnb_btn) {
        const className = '.' + cnb_btn;
        const ele = jQuery(className)
        ele.trigger('click');
    }
}

function do_cnb_ajax_submit_profile() {
    const form = jQuery('.cnb-settings-profile');

    // Prep data
    const formData = form.serialize();
    const data = {
        action: 'cnb_settings_profile_save',
        data: formData
    };

    const valid = form[0].checkValidity();
    if (valid) {
        try {
            jQuery.post({
                url: ajaxurl,
                data: data,
                success: function () {
                    // Hide "Next", show "Upgrade"
                    jQuery('.open-profile-details-modal').hide();
                    jQuery('.button-upgrade').show();

                    // Close box
                    cnb_domain_upgrade_notice_profile_saved();
                    jQuery('#TB_closeWindowButton').trigger('click');
                }
            });
        } catch (e) {
            showMessage('error', e.message);
        }
    } else {
        // Noop, show errors in the form itself
        form[0].reportValidity()
    }
    return false;
}

/**
 * This calls the admin-ajax action called 'cnb_settings_profile_save'
 *
 * This is used on the modal on domain-upgrade
 */
function cnb_ajax_submit_profile() {
    jQuery('.cnb-settings-profile')
        .on('submit', function(e) {
        e.preventDefault();
        return do_cnb_ajax_submit_profile();
    });
    jQuery('.cnb-settings-profile #submit')
        .on('click', function(e){
            e.preventDefault();
            return do_cnb_ajax_submit_profile();

        });
}

jQuery(function () {
    cnb_domain_upgrade_hide_notice();
    cnb_domain_upgrade_currency();
    cnb_ajax_submit_profile();
});
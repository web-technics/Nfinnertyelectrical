function cnb_email_activation_reenable_fields(showSomethingWentWrong = true) {
    let errorMessage = ''
    if (showSomethingWentWrong) {
        errorMessage = '<h3 class="title">Something went wrong!</h3>' +
            '<p>Something has gone wrong and we do not know why...</p>' +
            '<p>As unlikely as it is, our service might be experiencing issues (check <a href="https://status.callnowbutton.com">our status page</a>).</p>' +
            '<p>If you think you\'ve found a bug, please report it at our <a href="https://callnowbutton.com/support/" target="_blank">Help Center</a>.' +
            '<p>Technical details:</p>';
    }
    const errorDetails = '<p style="color:red"><span id="cnb_email_activation_details"></span></p>';

    const submitButton = jQuery('#cnb_email_activation_alternate')
    jQuery('#cnb_email_activation_alternate_address').removeAttr("disabled")
    submitButton.removeAttr("disabled")
    submitButton.val("Activate Premium")
    jQuery('#cnb_email_activation').html(errorMessage + errorDetails);
}

function cnb_email_activation_taking_too_long() {
    const errorMessage = '<h3 class="title">Hmm, that\'s taking a while...</h3>' +
        '<p>This call should not take this long. Please try again in a minute or so.</p>' +
        '<p>As unlikely as it is, our service might be experiencing issues (check <a href="https://status.callnowbutton.com">our status page</a>).</p>' +
        '<p>If you think you\'ve found a bug, please report it at our <a href="https://callnowbutton.com/support/" target="_blank">Help Center</a>.';
    const errorDetails = '<p>Technical details:</p><p style="color:red"><span id="cnb_email_activation_details"></span></p>';

    const submitButton = jQuery('#cnb_email_activation_alternate')
    jQuery('#cnb_email_activation_alternate_address').removeAttr("disabled")
    submitButton.removeAttr("disabled")
    submitButton.val("Activate Premium")
    jQuery('#cnb_email_activation').html(errorMessage + errorDetails);
}

/**
 * This calls the admin-ajax action called 'cnb_email_activation' (function cnb_admin_cnb_email_activation)
 */
function cnb_email_activation(admin_email) {
    // Prep data
    const data = {
        'action': 'cnb_email_activation',
        'admin_email': admin_email
    };

    // Disable the Email and Button fields (reactivate in case of errors)
    const submitButton = jQuery('#cnb_email_activation_alternate')
    jQuery('#cnb_email_activation_alternate_address').attr("disabled", "disabled")
    submitButton.attr("disabled", "disabled")
    submitButton.val("Check your e-mail")

    // Clear the error fields
    jQuery('#cnb_email_activation').empty()
    jQuery('#cnb_email_activation_email').empty()

    const statusTimeout = 5000
    const takingTooLongTimer = setTimeout(cnb_email_activation_taking_too_long, statusTimeout)

    // Send remove request
    jQuery.post(ajaxurl, data)
        .done((result) => {
            if (result && result.email) {
                clearTimeout(takingTooLongTimer)
                jQuery('#cnb_email_activation').html('<span class="cnb_check_email_message">Check your inbox for an activation email sent to <strong><span id="cnb_email_activation_email"></span></strong>.</span>')
                jQuery('#cnb_email_activation_email').text(result.email)
            }

            if (result && result.errors) {
                clearTimeout(takingTooLongTimer)
                const keys = Object.keys(result.errors)

                let showSomethingWentWrong = true
                if (keys.length === 1 && (keys[0] === 'CNB_EMAIL_INVALID'|| keys[0] === 'CNB_EMAIL_EMPTY')) {
                    // Skip showing the big block with links, since we know exactly what's going on
                    showSomethingWentWrong = false
                }
                cnb_email_activation_reenable_fields(showSomethingWentWrong)

                keys.forEach((key) => {
                    // Create Text Nodes to ensure escaping of the content
                    const codeMsg = document.createTextNode(key)
                    const errorMsg = document.createTextNode(result.errors[key])
                    const code = jQuery('<code>').append(codeMsg)
                    jQuery('#cnb_email_activation_details').append('<br />', code, ': ', errorMsg);
                })
            }
        })
        .fail((result) => {
            clearTimeout(takingTooLongTimer)
            cnb_email_activation_reenable_fields()

            // Create Text Nodes to ensure escaping of the content
            const codeMsg = document.createTextNode(result.status + ' ' + result.statusText)
            const errorMsg = document.createTextNode(result.responseText)
            const code = jQuery('<code>').append(codeMsg)
            jQuery('#cnb_email_activation_details').append('<br />', code, ': ', errorMsg);
        });
    return false;
}

function cnb_email_activation_submit() {
    const alternate_admin_email = jQuery('#cnb_email_activation_alternate_address').val()
    return cnb_email_activation(alternate_admin_email);
}

// Note: IDE marks this as unused, but it is used by settings-edit.php ("Delete API key")
function cnb_delete_apikey() {
    const apiKeyField = jQuery(".call-now-button-plugin #cnb_api_key")
    apiKeyField.prop("type", "hidden");
    apiKeyField.prop("value", "delete_me");
    apiKeyField.removeAttr("disabled");

    // Ensure we use the exact verbiage of the Submit button
    const saveVal = apiKeyField.parents('.cnb-container').find('#submit').val();
    jQuery('.call-now-button-plugin #cnb_api_key_delete').replaceWith("<p>Click <strong>"+saveVal+"</strong> to disconnect your account.</p>")

    // Present the default behavior of this submit button (since it needs to be actioned on by the *actual* submit button
    return false;
}

/**
 * Disable the Cloud inputs when it is disabled (but only on the settings screen,
 * where that checkbox is actually visible)
 */
function cnb_disable_api_key_when_cloud_hosting_is_disabled() {
    const ele = jQuery('#cnb_cloud_enabled');
    if (ele.length) {
        jQuery('.when-cloud-enabled :input').prop('disabled', !ele.is(':checked'));
    }
}

function init_settings() {
    jQuery("#cnb_email_activation_alternate_form").hide()
}

jQuery(() => {
    init_settings();
    cnb_disable_api_key_when_cloud_hosting_is_disabled();
})

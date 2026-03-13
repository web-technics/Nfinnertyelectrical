/**
 * Admin code for dismissing notifications.
 *
 */
function cnb_dismissables_listener() {
    jQuery( '.notice-call-now-button').on( 'click', '.notice-dismiss',
        function() {
        const $notice = jQuery(this).parent('.is-dismissible');
        const dismiss_url = $notice.attr('data-dismiss-url');
        if ( dismiss_url ) {
            jQuery.get( dismiss_url );
        }
    });
}

function cnb_set_sidenav_counter(value) {
    const counter = jQuery('#cnb-nav-counter');
    counter.text(value)
    if (value) {
        counter.parent().textNodes().first().replaceWith('Call Now Bu...')
        counter.show();
    } else {
        counter.parent().textNodes().first().replaceWith('Call Now Button')
        counter.hide()
    }
}

function cnb_decrease_sidenav_counter() {
    const counter = jQuery('#cnb-nav-counter');
    const counterValue = parseInt(counter.text())
    const newValue = counterValue - 1;
    cnb_set_sidenav_counter(newValue)
}

function cnb_upgrade_notice_dismiss_listener() {
    jQuery('#cnb_is_updated').closest('.notice-call-now-button').on('click', '.notice-dismiss',
        () => {
        cnb_decrease_sidenav_counter();
    })
}

function cnb_welcome_banner_dismiss_listener() {
    jQuery('#welcome-banner').on('click', '.notice-dismiss', () => {
        cnb_decrease_sidenav_counter();
        jQuery('#welcome-banner').remove();
    })
}

/**
 * Add a jQuery extension so it can be used on any jQuery object
 * Copied from https://stackoverflow.com/a/4106957
 */
function cnb_add_jquery_textnodes() {
    jQuery.fn.textNodes = function () {
        return this.contents().filter(function () {
            return (this.nodeType === Node.TEXT_NODE && this.nodeValue.trim() !== "");
        });
    }
}

jQuery(() => {
    cnb_add_jquery_textnodes();
    cnb_dismissables_listener();
    cnb_upgrade_notice_dismiss_listener();
    cnb_welcome_banner_dismiss_listener();
})

// If the modal is enabled, we need to clear and disable the "Default message"

/**
 * This sets the various WhatsApp Modal fields to appear only when needed.
 *
 * When the
 */
function cnb_set_action_modal_fields() {
    const ele = jQuery('#cnb-action-modal')

    // messageRow contains just the message (the non-modal message)
    const messageRow = jQuery("#action-properties-message-row")
    const messageEle = jQuery('#action-properties-message')
    const modalElements = jQuery('.cnb-action-properties-whatsapp-modal')

    const isVisible = ele.is(":visible")
    const isChecked = ele.prop('checked')
    if (!isVisible) {
        messageRow.hide()
        modalElements.hide()
    } else if (isChecked) {
        messageEle.attr('disabled', 'disabled')
        messageRow.hide()
        modalElements.show()
    } else {
        messageEle.removeAttr('disabled')
        messageRow.show()
        modalElements.hide()
    }
}

function cnb_clear_default_on_modal() {
    const ele = jQuery('#cnb-action-modal')
    ele.on('click', () => {
        cnb_set_action_modal_fields()
    })
}

function cnb_refresh_on_action_change() {
    const ele = jQuery('#cnb_action_type')
    ele.on('change', () => {
        cnb_set_action_modal_fields()
    })
}

function cnb_add_sortable_to_action_table() {
    // Only add sortable if the table exists (otherwise ".sortable()" might not even exist
    jQuery('table.cnb_list_actions #the-list').each(function(){
        const ele = jQuery(this)

        // Only set sortable if >1 item in the table
        // And if there are 0 or 1 item, hide the draggable item, to avoid confusion
        const childCount = jQuery('tr', ele).length

        if (childCount > 1) {
            ele.sortable({
                stop: function () {
                    livePreview()
                },
                placeholder: 'ui-state-highlight'
            })
        } else {
            jQuery('.column-draggable', ele.parentElement).hide()
        }
    })

}

jQuery(() => {
    // These 2 set up action handler to process changes on the form
    cnb_clear_default_on_modal()
    cnb_refresh_on_action_change()
    // This ensures that the default state matches the state of the page when it is loaded
    cnb_set_action_modal_fields()
    cnb_add_sortable_to_action_table()
})

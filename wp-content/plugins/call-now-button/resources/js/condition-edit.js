function cnb_show_condition_placeholder_action() {
    const optionSelected = jQuery('#cnb_condition_match_type').val();
    let placeholderText;
    if(optionSelected === 'SIMPLE') {
        placeholderText = '/blog/';
    } else if(optionSelected === 'EXACT') {
        placeholderText = 'https://www.example.com/sample-page/';
    } else if(optionSelected === 'SUBSTRING') {
        placeholderText = 'category/';
    } else if(optionSelected === 'REGEX') {
        placeholderText = '/(index|about)(\?id=[0-9]+)?$';
    }
    jQuery('#cnb_condition_match_value').attr('placeholder', placeholderText);
}
/**
 * Show an example condition in the form field for each of the match types
 */
function cnb_show_condition_placeholder() {
    cnb_show_condition_placeholder_action();
    jQuery('#cnb_condition_match_type').on('change', function () {
        cnb_show_condition_placeholder_action();
    });
}

/**
 * This calls the admin-ajax action called 'cnb_delete_condition'
 */
function cnb_delete_condition() {
    jQuery('tbody[data-wp-lists="list:cnb_list_condition"]#the-list span.delete a[data-ajax="true"]')
        .on('click', function(){
            // Prep data
            const id = jQuery(this).data('id');
            const bid = jQuery(this).data('bid');
            const data = {
                'action': 'cnb_delete_condition',
                'id': id,
                'bid': bid,
                '_ajax_nonce': jQuery(this).data('wpnonce'),
            };

            // Send remove request
            jQuery.post(ajaxurl, data)
                .done(() => {
                    // Remove container
                    const action_row = jQuery(this).closest('tr');
                    jQuery(action_row).css("background-color", "#ff726f");
                    jQuery(action_row).fadeOut(function() {
                        jQuery(action_row).css("background-color", "");
                        jQuery(action_row).remove();

                        // Special case: if this is the last item, show a "no items" row
                        const remaining_items = jQuery('table.cnb_list_conditions #the-list tr').length;
                        if (!remaining_items) {
                            // Add row
                            jQuery('table.cnb_list_conditions #the-list').html('<tr class="no-items"><td class="colspanchange" colspan="5"<p class="cnb_paragraph">You have no page visibility rules set up. This means that your button will show on all pages.</p>' +
                                '<p class="cnb_paragraph">Click the <code>Add page rule</code> button above to limit the appearance. You can freely mix and match rules to meet your requirements.</p></td></tr>');
                        }
                    });
                });

            // Remove ID from Button array
            jQuery('input[name^="conditions['+id+']"').remove();
            return false;
        });
}


jQuery( function() {
    cnb_delete_condition();
    cnb_show_condition_placeholder();
})

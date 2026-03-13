/**
 * This is used in the legacyEdit and in ButtonEdit
 */
function cnb_setup_colors() {
	// This "change" options ensures that if a color is changed and a livePreview is available,
	// we update it. Cannot not be done via an ".on('change')", since the wpColorPicker cannot
	// respond to those events
	const options = {
		change: () => {
			jQuery(() => {
				if (typeof livePreview !== 'undefined') {
					livePreview();
				}
			});

		}
	}

	// Add color picker
	jQuery('.cnb-color-field').wpColorPicker(options);
	jQuery('.cnb-iconcolor-field').wpColorPicker(options);
}

function cnb_setup_placements() {
	// Reveal additional button placements when clicking "more"
	jQuery("#button-more-placements").on('click', function (e) {
		e.preventDefault();
		jQuery(".cnb-extra-placement").css("display", "block");
		jQuery("#button-more-placements").remove();
	});
}

function cnb_setup_sliders() {
	jQuery('#cnb_slider').on("input change", function() {
		cnb_update_sliders();
	});
	jQuery('#cnb_order_slider').on("input change", function() {
		cnb_update_sliders();
	});
	cnb_update_sliders();
}

function cnb_update_sliders() {
	// Zoom slider - show percentage
	const cnb_slider = document.getElementById("cnb_slider");
	if (cnb_slider && cnb_slider.value) {
		const cnb_slider_value = document.getElementById("cnb_slider_value");
		cnb_slider_value.innerHTML = '(' + Math.round(cnb_slider.value * 100) + '%)';
	}

	// Z-index slider - show steps
	const cnb_order_slider = document.getElementById("cnb_order_slider");
	if (cnb_order_slider && cnb_order_slider.value) {
		const cnb_order_value = document.getElementById("cnb_order_value");
		cnb_order_value.innerHTML = cnb_order_slider.value;
	}
}

function cnb_hide_on_show_always() {
	let show_always_checkbox = document.getElementById('actions_schedule_show_always');
	if (show_always_checkbox) {
		if (show_always_checkbox.checked) {
			// Hide all items specific for Scheduler
			jQuery('.cnb_hide_on_show_always').hide();

			// Hide Domain Timezone notice
			jQuery('#cnb-notice-domain-timezone-unsupported').parent('.notice').hide();
		} else {
			// Show all items specific for Scheduler
			jQuery('.cnb_hide_on_show_always').show();

			// Show Domain Timezone notice (and move to the correct place)
			const domainTimezoneNotice = jQuery('#cnb-notice-domain-timezone-unsupported').parent('.notice');
			domainTimezoneNotice.show();
			const domainTimezoneNoticePlaceholder = jQuery('#domain-timezone-notice-placeholder');
			if (domainTimezoneNoticePlaceholder.length !== 0) {
				domainTimezoneNotice.insertAfter(domainTimezoneNoticePlaceholder);
			}
		}
	}
	cnb_clean_up_advanced_view();
	return false;
}

function cnb_animate_saving() {
	jQuery('.call-now-button-plugin form.cnb-validation #submit').on('click', function (event) {
		// if value is saving, skip...
		if (jQuery(this).prop('value') === 'Saving...') {
			event.preventDefault();
			return;
		}
		// Check if the form will actually subbmit...
		const form = jQuery(this).closest('form');
		const valid = form[0].checkValidity();
		if (valid) {
			jQuery(this).addClass('is-busy');
			jQuery(this).prop('value', 'Saving...');
			jQuery(this).prop('aria-disabled', 'true');
		} else {
			// Clear old notices
			jQuery('.cnb-form-validation-notice').remove();

			const invalidFields = form.find(':invalid')
			// Find tab with error and switch to it if found
			const tabName = invalidFields.first().closest('[data-tab-name]').data('tabName')
			if (tabName) {
				cnb_switch_tab(tabName);
			}
			// Collect all errors and create notification
			invalidFields.each( function(index,node) {
				const inner = jQuery('<p/>');
				const notification = jQuery('<div />', {class: "cnb-form-validation-notice notice notice-warning"}).append(inner);
				const label = node.labels.length > 0 ? node.labels[0].innerText + ': ' : '';
				inner.text(label + node.validationMessage);
				notification.insertBefore(form.find('#submit'));
			})
		}
	})
}
function cnb_setup_toggle_label_clicks() {
	jQuery('.cnb_toggle_state').on( "click", function() {
		const stateLabel = jQuery(this).data('cnb_toggle_state_label');
		jQuery('#' + stateLabel).trigger('click');
	});
}

function cnb_action_appearance() {
	jQuery('#cnb_action_type').on('change', function (obj) {
		cnb_action_update_appearance(obj.target.value);
	});

	// Setup WHATSAPP integration
	const input = document.querySelector("#cnb_action_value_input_whatsapp");
	if (!input || !window.intlTelInput) {
		return
	}

	const iti = window.intlTelInput(input, {
		utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.12/js/utils.min.js',
		nationalMode: false,
		separateDialCode: true,
		hiddenInput: 'actionValueWhatsappHidden'
	});

	// here, the index maps to the error code returned from getValidationError - see readme
	const errorMap = [
		'Invalid number',
		'Invalid country code',
		'Too short',
		'Too long',
		'Invalid number'];

	const errorMsg = jQuery('#cnb-error-msg');
	const validMsg = jQuery('#cnb-valid-msg');

	const reset = function() {
		input.classList.remove('error');
		errorMsg.html('');
		errorMsg.hide();
		validMsg.hide();
	};

	const onBlur = function() {
		reset();
		if (input.value.trim()) {
			if (iti.isValidNumber()) {
				validMsg.show();
			} else {
				const errorCode = iti.getValidationError();
				if (errorCode < 0) {
					// Unknown error, ignore for now
					return
				}
				input.classList.add('error');
				errorMsg.text(errorMap[errorCode]);
				errorMsg.show();
			}
		} else {
			// Empty
			reset();
		}
	}

	// on blur: validate
	input.addEventListener('blur', onBlur);

	// on keyup / change flag: reset
	input.addEventListener('change', onBlur);
	input.addEventListener('keyup', onBlur);

	// init
	onBlur();
}

function cnb_action_update_appearance(value) {
	const emailEle = jQuery('.cnb-action-properties-email');
	const linkEle = jQuery('.cnb-action-properties-link');
	const emailExtraEle = jQuery('.cnb-action-properties-email-extra');
	const whatsappEle = jQuery('.cnb-action-properties-whatsapp');
	const signalEle = jQuery('.cnb-action-properties-signal');
	const whatsappExtraEle = jQuery('.cnb-action-properties-whatsapp-extra');
	const smsEle = jQuery('.cnb-action-properties-sms');
	const smsExtraEle = jQuery('.cnb-action-properties-sms-extra');

	const propertiesEle = jQuery('.cnb-action-properties-map');
	const valueEle = jQuery('.cnb-action-value');
	const valueTextEle = jQuery('#cnb_action_value_input');
	const valuelabelEle = jQuery('#cnb_action_value');
	const whatsappValueEle = jQuery('#cnb_action_value_input_whatsapp')
	const intlInputLabel = jQuery('#cnb_action_value_input_intl_input')

	emailEle.hide();
	emailExtraEle.hide();
	whatsappEle.hide();
	signalEle.hide();
	whatsappExtraEle.hide();
	smsEle.hide();
	smsExtraEle.hide();
	propertiesEle.hide();
	linkEle.hide()

	valueEle.show();
	valueTextEle.prop( 'disabled', false );
	whatsappValueEle.prop( 'disabled', true );

	valueTextEle.removeAttr("required")
	whatsappValueEle.removeAttr("required")

	switch (value) {
		case 'ANCHOR':
			valuelabelEle.text('On-page anchor');
			valueTextEle.attr("required", "required");
			break
		case 'EMAIL':
			valuelabelEle.text('E-mail address');
			valueTextEle.attr("required", "required");
			emailEle.show()
			break
		case 'LINK':
			valuelabelEle.text('Full URL');
			valueTextEle.attr("required", "required");
			linkEle.show()
			break
		case 'MAP':
			valuelabelEle.text('Address');
			valueTextEle.attr("required", "required");
			propertiesEle.show();
			break
		case 'PHONE':
			valuelabelEle.text('Phone number');
			valueTextEle.attr("required", "required");
			break
		case 'SMS':
			valuelabelEle.text('Phone number');
			valueTextEle.attr("required", "required");
			smsEle.show();
			break
		case 'WHATSAPP':
			valuelabelEle.text('WhatsApp number');
			intlInputLabel.text('WhatsApp number')
			valueEle.hide();
			valueTextEle.prop( 'disabled', true );
			whatsappValueEle.prop( 'disabled', false );
			whatsappValueEle.attr("required", "required");
			whatsappEle.show();
			break
		case 'FACEBOOK':
		case 'TELEGRAM':
			valuelabelEle.text('Username');
			valueTextEle.attr("required", "required");
			break
		case 'SIGNAL':
			valuelabelEle.text('Signal number');
			intlInputLabel.text('Signal number')
			valueEle.hide();
			valueTextEle.prop( 'disabled', true );
			whatsappValueEle.prop( 'disabled', false );
			whatsappValueEle.attr("required", "required");
			signalEle.show();
			break;
		default:
			valuelabelEle.text('Action value');
			valueTextEle.attr("required", "required");
	}
	cnb_clean_up_advanced_view();
}

function cnb_action_update_map_link(element) {
	jQuery(element).prop("href", "https://maps.google.com?q=" + jQuery('#cnb_action_value_input').val())
}

function cnb_hide_edit_action_if_advanced() {
	const element = jQuery('#toplevel_page_call-now-button li.current a');
	if (element.text() === 'Edit action') {
		element.removeAttr('href');
		element.css('cursor', 'default');
	}
}

function cnb_hide_edit_domain_upgrade_if_advanced() {
	const element = jQuery('#toplevel_page_call-now-button li.current a');
	if (element.text() === 'Upgrade domain') {
		element.removeAttr('href');
		element.css('cursor', 'default');
	}
}

function cnb_hide_on_modal() {
	jQuery('.cnb_hide_on_modal').hide();
	jQuery('.cnb_hide_on_modal input').removeAttr('required');
}

/**
 * Used in admin-header.php
 *
 * @param ele HTMLElement
 * @returns {boolean}
 */
function cnb_enable_advanced_view(ele) {
	window.cnb_show_advanced_view_only_set=1;
	cnb_clean_up_advanced_view();
	jQuery(ele.parentElement.parentElement).remove();
	return false;
}

function cnb_is_advanced_view() {
	return typeof window.cnb_show_advanced_view_only_set !== 'undefined' &&
		window.cnb_show_advanced_view_only_set &&
		window.cnb_show_advanced_view_only_set === 1;
}

function show_advanced_view_only() {
	jQuery('.cnb_advanced_view').show();
}

function cnb_clean_up_advanced_view() {
	const advanced_views = jQuery('.cnb_advanced_view')
	advanced_views.hide()
	if(cnb_is_advanced_view()) {
		show_advanced_view_only()
	}
}

function cnb_strip_beta_from_referrer() {
	const referer = jQuery('input[name="_wp_http_referer"]');
	if (referer && referer.val()) {
		referer.val(referer.val().replace(/[?&]beta/, ''))
		referer.val(referer.val().replace(/[?&]api_key=[0-9a-z-]+/, ''))
		referer.val(referer.val().replace(/[?&]api_key_ott=[0-9a-z-]+/, ''))
		referer.val(referer.val().replace(/[?&]cloud_enabled=[0-9]/, ''))
	}
}

/**
 * This calls the admin-ajax action called 'cnb_delete_action'
 */
function cnb_delete_action() {
	jQuery('.cnb-button-edit-action-table tbody[data-wp-lists="list:cnb_list_action"]#the-list span.delete a[data-ajax="true"]')
		.on('click', function(){
		// Prep data
		const id = jQuery(this).data('id');
		const bid = jQuery(this).data('bid');
		const data = {
			'action': 'cnb_delete_action',
			'id': id,
			'bid': bid,
			'_ajax_nonce': jQuery(this).data('wpnonce'),
		};

		// Send remove request
		jQuery.post(ajaxurl, data)
			.done((result) => {
				// Update the global "cnb_actions" variable
				if (result && result.button && result.button.actions) {
					cnb_actions = result.button.actions
					// livePreview is also called again below in case the Ajax call comes back before the fadeOut is done.
					if (typeof livePreview !== 'undefined') {
						livePreview();
					}
				}
			});

		// Remove container
		const action_row = jQuery(this).closest('tr');
		jQuery(action_row).css("background-color", "#ff726f");
		jQuery(action_row).fadeOut(function() {
			jQuery(action_row).css("background-color", "");
			jQuery(action_row).remove();

			// Special case: if this is the last item, show a "no items" row
			const remaining_items = jQuery('.cnb-button-edit-action-table tbody[data-wp-lists="list:cnb_list_action"]#the-list tr').length;
			if (!remaining_items) {
				// Add row
				jQuery('.cnb-button-edit-action-table tbody[data-wp-lists="list:cnb_list_action"]#the-list').html('<tr class="no-items"><td class="colspanchange" colspan="4">This button has no actions yet. Let\'s add one!</td></tr>');
			}

			// We call livePreview /again/ (in case the Ajax call comes back before the fadeOut is done).
			if (typeof livePreview !== 'undefined') {
				livePreview();
			}
		});

		// Remove ID from Button array
		jQuery('input[name^="actions['+id+']"').remove();
		return false;
	});
}

/**
 * function for the button type selection in the New button modal
 */
function cnb_button_overview_modal() {
	jQuery(".cnb_type_selector_item").on('click', function(){
		jQuery(".cnb_type_selector_item").removeClass('cnb_type_selector_active');
		jQuery(this).addClass("cnb_type_selector_active");
		const cnbType = jQuery(this).attr("data-cnb-selection");
		jQuery('#button_type').val(cnbType);
	});

	jQuery("#cnb-button-overview-modal-add-new").on("click", function() {
		setTimeout(function () {
			jQuery("input[name='button[name]']").trigger("focus");
		});
	});
}

function cnb_button_overview_add_new_click() {
	jQuery("#cnb-button-overview-modal-add-new").trigger("click");
	return false;
}

function cnb_init_tabs() {
	jQuery('a.nav-tab').on('click', (e) => {
		e.preventDefault();
		return cnb_switch_tab(jQuery( e.target ).data('tabName'))
	});
}

function cnb_switch_tab(tabName, addToHistory = true) {
	const tab = jQuery('a.nav-tab[data-tab-name][data-tab-name="' + tabName + '"]');
	const tabContent = jQuery('table[data-tab-name][data-tab-name="' + tabName + '"], div[data-tab-name][data-tab-name="' + tabName + '"]');

	// Does tab name exist (if not, don't do anything)
	if (tab.length === 0) return false;

	// Hide all tabs
	const otherTabs = jQuery('a.nav-tab[data-tab-name][data-tab-name!="' + tabName + '"]');
	const otherTabsContent = jQuery('table[data-tab-name][data-tab-name!="' + tabName + '"], div[data-tab-name][data-tab-name!="' + tabName + '"]');
	otherTabs.removeClass('nav-tab-active')
	otherTabsContent.hide();

	// Display passed in tab
	tab.addClass('nav-tab-active')
	tabContent.show();

	// If there is an element keeping track of the tab, update it
	jQuery('input[name="tab"]').val(tabName)

	// Push this to URL
	if (addToHistory) {
		const url = new URL(window.location);
		const data = {
			cnb_switch_tab_event: true,
			tab_name: tabName
		}

		url.searchParams.set('tab', tabName);
		window.history.pushState(data, '', url);
	}

	return false;
}

function cnb_switch_tab_from_history_listener() {
	window.addEventListener('popstate', (event) => {
		if (event && event.state && event.state.cnb_switch_tab_event && event.state.tab_name) {
			// Switch back but do NOT add this action to the history again to prevent loops
			cnb_switch_tab(event.state.tab_name, false)
		}
	});
}

function cnb_hide_add_new_on_error() {
	// Find an error box - if that exists, remove the "Add new" macro
	if (jQuery('.cnb-remove-add-new').length) {
		jQuery("li.toplevel_page_call-now-button li:contains('Add New') a").hide();
	}
}

function cnb_setup_pricing() {
	// Find the elements
	const elements = jQuery('.eur-per-month, .usd-per-month');

	// If there are elements, find the pricing (ajax call)
	if (elements.length) {
		const data = {
			'action': 'cnb_get_plans',
		};
		jQuery.post(ajaxurl, data)
			.done((result) => {
				// Fix the elements
				jQuery('.eur-per-month').text(result['eur_per_month'])
				jQuery('.usd-per-month').text(result['usd_per_month'])
			})
	}
}

jQuery( function() {
	// Generic
	cnb_setup_colors();
	cnb_setup_placements();
	cnb_setup_sliders();
	cnb_hide_on_show_always();
	cnb_action_appearance();
	cnb_action_update_appearance(jQuery('#cnb_action_type').val());
	cnb_hide_edit_action_if_advanced();
	cnb_hide_edit_domain_upgrade_if_advanced();
	cnb_strip_beta_from_referrer();
	cnb_animate_saving();
	cnb_setup_toggle_label_clicks();
	cnb_switch_tab_from_history_listener();

	// Allow for tab switching to be dynamic
	cnb_init_tabs();

	cnb_clean_up_advanced_view();

	// This needs to go AFTER the "advanced_view" check so a modal does not get additional (unneeded) "advanced" items
	if (typeof cnb_hide_on_modal_set !== 'undefined' && cnb_hide_on_modal_set === 1) {
		cnb_hide_on_modal();
	}

	// page: button-edit (conditions tabs)

	cnb_delete_action();
	cnb_button_overview_modal();

	cnb_hide_add_new_on_error();

	cnb_setup_pricing();
});

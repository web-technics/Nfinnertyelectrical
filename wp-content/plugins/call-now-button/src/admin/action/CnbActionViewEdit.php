<?php

namespace cnb\admin\action;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

use cnb\admin\api\CnbAppRemote;
use cnb\admin\button\CnbButton;
use cnb\admin\domain\CnbDomain;
use cnb\utils\CnbAdminFunctions;
use cnb\CnbHeaderNotices;
use cnb\notices\CnbAdminNotices;
use cnb\utils\CnbUtils;
use stdClass;
use WP_Locale;

class CnbActionViewEdit {
    /**
     * @param $action CnbAction
     *
     * @return void
     */
    function add_header( $action ) {
        if ( is_wp_error( $action ) ) {
            esc_html_e( 'An error occurred' );

            return;
        }
        if ( $action->id !== 'new' ) {
            $actionTypes = ( new CnbAdminFunctions() )->cnb_get_action_types();
            $name        = $actionTypes[ $action->actionType ];
            if ( $action->actionValue ) {
                $name = $action->actionValue;
            }
            echo esc_html__( 'Editing action' ) . ' <span class="cnb_button_name">' . esc_html( $name ) . '</span>';
        } else {
            echo esc_html__( 'Add action' );
        }
    }

    /**
     * @param $button CnbButton
     * @param $tab string
     *
     * @return string
     */
    private function create_tab_url( $button, $tab ) {
        $url = admin_url( 'admin.php' );

        return add_query_arg(
            array(
                'page'   => CNB_SLUG,
                'action' => 'edit',
                'type'   => strtolower( $button->type ),
                'id'     => $button->id,
                'tab'    => $tab
            ),
            $url );
    }

    /**
     *
     * WP_Locale considers "0" to be Sunday, whereas the CallNowButton APi considers "0" to be Monday. See the below table:
     *
     * +-----------+-----------+------------+
     * | Day       | WP_Locale | API Server |
     * +-----------+-----------+------------+
     * | Monday    | 1         | 0          |
     * +-----------+-----------+------------+
     * | Tuesday   | 2         | 1          |
     * +-----------+-----------+------------+
     * | Wednesday | 3         | 2          |
     * +-----------+-----------+------------+
     * | Thursday  | 4         | 3          |
     * +-----------+-----------+------------+
     * | Friday    | 5         | 4          |
     * +-----------+-----------+------------+
     * | Saturday  | 6         | 5          |
     * +-----------+-----------+------------+
     * | Sunday    | 0         | 6          |
     * +-----------+-----------+------------+
     *
     * So, we need to translate.
     *
     * @param int $wp_locale_day
     *
     * @return int The index for the CNB API Server
     */
    function wp_locale_day_to_daysofweek_array_index( $wp_locale_day ) {
        if ( $wp_locale_day == 0 ) {
            return 6;
        }

        return $wp_locale_day - 1;
    }

    /**
     * CNB week starts on Monday (0), WP_Local starts on Sunday (0)
     * See wp_locale_day_to_daysofweek_array_index()
     *
     * This array only signifies the order to DISPLAY the days in the UI according to WP_Locale
     * So, in this case, we make the UI render the week starting on Monday (1) and end on Sunday (0).
     */
    function get_order_of_days() {
        return array( 1, 2, 3, 4, 5, 6, 0 );
    }

    /**
     * previously cnb_render_form_action
     *
     * @param $action CnbAction
     * @param $button CnbButton
     * @param $domain CnbDomain
     */
    private function render_table( $action, $button = null, $domain = null ) {
        /**
         * @global WP_Locale $wp_locale WordPress date and time locale object.
         */
        global $wp_locale;

        $cnb_utils = new CnbUtils();

        // In case a domain is not passed, we take it from the button
        $domain = isset( $domain ) ? $domain : ( isset( $button ) ? $button->domain : null );

        $cnb_days_of_week_order = $this->get_order_of_days();

        if ( empty( $action->actionType ) ) {
            $action->actionType = 'PHONE';
        }
        if ( empty( $action->iconText ) ) {
            $action->iconText = ( new CnbUtils() )->cnb_actiontype_to_icontext( $action->actionType );
        }
        if ( empty( $action->iconType ) ) {
            $action->iconType = 'DEFAULT';
        }

        $adminFunctions = new CnbAdminFunctions();

        wp_enqueue_style( CNB_SLUG . '-jquery-ui' );
        wp_enqueue_script( CNB_SLUG . '-timezone-picker-fix' );

        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-slider' );
        wp_enqueue_script( CNB_SLUG . '-action-edit-scheduler' );

        // Uses domain timezone if no timezone can be found
        $timezone                        = ( isset( $action->schedule ) && ! empty( $action->schedule->timezone ) ) ? $action->schedule->timezone : ( isset( $domain ) ? $domain->timezone : null );
        $action_tz_different_from_domain = isset( $domain ) && ! empty( $domain->timezone ) && $domain->timezone !== $timezone;

        $timezone_set_correctly = ( new CnbHeaderNotices() )->is_timezone_valid( $domain );

        ?>
        <input type="hidden" name="actions[<?php echo esc_attr( $action->id ) ?>][id]"
               value="<?php if ( $action->id !== null && $action->id !== 'new' ) {
                   echo esc_attr( $action->id );
               } ?>"/>
        <input type="hidden" name="actions[<?php echo esc_attr( $action->id ) ?>][delete]"
               id="cnb_action_<?php echo esc_attr( $action->id ) ?>_delete" value=""/>
        <table data-tab-name="basic_options"
               class="form-table <?php echo esc_attr( $adminFunctions->is_active_tab( 'basic_options' ) ) ?>">
            <?php if ( ! $button ) { ?>
                <tr>
                    <th colspan="2"><h2>Action Settings</h2>
                    </th>
                </tr>
            <?php } ?>
            <tr class="cnb_hide_on_modal">
                <th></th>
                <td></td>
            </tr>
            <tr class="cnb_hide_on_modal">
                <th scope="row"><label for="cnb_action_type">Button type</label></th>
                <td>
                    <select id="cnb_action_type" name="actions[<?php echo esc_attr( $action->id ) ?>][actionType]">
                        <?php foreach ( ( new CnbAdminFunctions() )->cnb_get_action_types() as $action_type_key => $action_type_value ) { ?>
                            <option value="<?php echo esc_attr( $action_type_key ) ?>"<?php selected( $action_type_key, $action->actionType ) ?>>
                                <?php echo esc_html( $action_type_value ) ?>
                            </option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr class="cnb-action-value cnb_hide_on_modal">
                <th scope="row">
                    <label for="cnb_action_value_input">
                        <span id="cnb_action_value">Action value</span>
                    </label>

                </th>
                <td>
                    <input type="text" id="cnb_action_value_input"
                           name="actions[<?php echo esc_attr( $action->id ) ?>][actionValue]"
                           value="<?php echo esc_attr( $action->actionValue ) ?>"/>
                    <p class="description cnb-action-properties-map">Preview via <a href="#"
                                                                                    onclick="cnb_action_update_map_link(this)"
                                                                                    target="_blank">Google Maps</a></p>

                </td>
            </tr>
            <tr class="cnb-action-properties-whatsapp cnb-action-properties-signal">
                <th scope="row"><label id="cnb_action_value_input_intl_input" for="cnb_action_value_input_whatsapp">WhatsApp
                        Number</label></th>
                <td>
                    <input type="tel" id="cnb_action_value_input_whatsapp"
                           name="actions[<?php echo esc_attr( $action->id ) ?>][actionValueWhatsapp]"
                           value="<?php echo esc_attr( $action->actionValue ) ?>"/>
                    <p class="description" id="cnb-valid-msg">✓ Valid</p>
                    <p class="description" id="cnb-error-msg"></p>
                </td>
            </tr>
            <tr class="button-text cnb_hide_on_modal">
                <th scope="row"><label for="buttonTextField">Button label text <a
                                href="<?php echo esc_url( $cnb_utils->get_support_url( 'wordpress/buttons/button-label/', 'question-mark', 'button-label' ) ) ?>"
                                target="_blank" class="cnb-nounderscore">
                            <span class="dashicons dashicons-editor-help"></span>
                        </a></label></th>
                <td>
                    <input id="buttonTextField" type="text"
                           name="actions[<?php echo esc_attr( $action->id ) ?>][labelText]"
                           value="<?php echo esc_attr( $action->labelText ) ?>" maxlength="30" placeholder="Optional"/>
                </td>
            </tr>

            <tr class="cnb_hide_on_modal">
                <th scope="row"><label for="actions-<?php echo esc_attr( $action->id ) ?>-iconText">Icon</label></th>
                <td data-icon-text-target="cnb_action_icon_text" data-icon-type-target="cnb_action_icon_type">
                    <div class="icon-text-options" id="icon-text-ANCHOR">
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="anchor">anchor</i>
                        </div>
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="close_down">close_down</i>
                        </div>
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="anchor_up">anchor_up</i>
                        </div>
                    </div>
                    <div class="icon-text-options" id="icon-text-EMAIL">
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="email">email</i>
                        </div>
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="mail2">mail2</i>
                        </div>
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="mail3">mail3</i>
                        </div>
                    </div>
                    <div class="icon-text-options" id="icon-text-HOURS">
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon family-material" data-icon-type="FONT_MATERIAL"
                               data-icon-text="access_time">access_time</i>
                        </div>
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon family-material" data-icon-type="FONT_MATERIAL"
                               data-icon-text="access_time_filled">access_time_filled</i>
                        </div>
                    </div>
                    <div class="icon-text-options" id="icon-text-LINK">
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="link">link</i>
                        </div>
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="link2">link2</i>
                        </div>
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="link3">link3</i>
                        </div>
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="link4">link4</i>
                        </div>
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="link5">link5</i>
                        </div>
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="calendar">calendar</i>
                        </div>
                    </div>
                    <div class="icon-text-options" id="icon-text-MAP">
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="directions">directions</i>
                        </div>
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="directions2">directions2</i>
                        </div>
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="directions3">directions3</i>
                        </div>
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="directions4">directions4</i>
                        </div>
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="directions5">directions5</i>
                        </div>
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="directions6">directions6</i>
                        </div>
                    </div>
                    <div class="icon-text-options" id="icon-text-PHONE">
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="call">call</i>
                        </div>
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="call2">call2</i>
                        </div>
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="call3">call3</i>
                        </div>
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="call4">call4</i>
                        </div>
                    </div>
                    <div class="icon-text-options" id="icon-text-SMS">
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="chat">chat</i>
                        </div>
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="sms">sms</i>
                        </div>
                    </div>
                    <div class="icon-text-options" id="icon-text-WHATSAPP">
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="whatsapp">whatsapp</i>
                        </div>
                    </div>
                    <div class="icon-text-options" id="icon-text-FACEBOOK">
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="facebook_messenger">facebook_messenger</i>
                        </div>
                    </div>
                    <div class="icon-text-options" id="icon-text-TELEGRAM">
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="telegram">telegram</i>
                        </div>
                    </div>
                    <div class="icon-text-options" id="icon-text-SIGNAL">
                        <div class="cnb-button-icon">
                            <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="signal">signal</i>
                        </div>
                    </div>

                    <a
                            href="#"
                            onclick="return cnb_show_icon_text_advanced(this)"
                            data-icon-text="cnb_action_icon_text"
                            data-icon-type="cnb_action_icon_type"
                            data-description="cnb_action_icon_text_description"
                            class="cnb_advanced_view">Use a custom icon</a>
                    <input
                            type="hidden"
                            name="actions[<?php echo esc_attr( $action->id ) ?>][iconText]"
                            value="<?php if ( isset( $action->iconText ) ) {
                                echo esc_attr( $action->iconText );
                            } ?>"
                            id="cnb_action_icon_text"/>
                    <input
                            type="hidden"
                            readonly="readonly"
                            name="actions[<?php echo esc_attr( $action->id ) ?>][iconType]"
                            value="<?php if ( isset( $action->iconType ) ) {
                                echo esc_attr( $action->iconType );
                            } ?>"
                            id="cnb_action_icon_type"/>
                    <p class="description" id="cnb_action_icon_text_description" style="display: none">
                        You can enter a custom Material Design font code here. Search the full library at <a
                                href="https://fonts.google.com/icons" target="_blank">Google Fonts</a>.<br/>
                        The Call Now Button uses the <code>filled</code> version of icons.</p>
                </td>
            </tr>

            <?php if ( $button && $button->type === 'SINGLE' ) { ?>
                <tr class="cnb_hide_on_modal cnb_advanced_view">
                    <th colspan="2">
                        <h3>Colors for a Single button are defined on the Presentation tab.</h3>
                    </th>
                </tr>
            <?php } else { ?>

                <tr>
                    <th scope="row"><label for="actions[<?php echo esc_attr( $action->id ) ?>][backgroundColor]">Button
                            color</label></th>
                    <td>
                        <input name="actions[<?php echo esc_attr( $action->id ) ?>][backgroundColor]"
                               id="actions[<?php echo esc_attr( $action->id ) ?>][backgroundColor]" type="text"
                               value="<?php echo esc_attr( $action->backgroundColor ) ?>"
                               class="cnb-color-field" data-default-color="#009900"/>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="actions[<?php echo esc_attr( $action->id ) ?>][iconColor]">Icon
                            color</label></th>
                    <td>
                        <input name="actions[<?php echo esc_attr( $action->id ) ?>][iconColor]"
                               id="actions[<?php echo esc_attr( $action->id ) ?>][iconColor]" type="text"
                               value="<?php echo esc_attr( $action->iconColor ) ?>"
                               class="cnb-iconcolor-field" data-default-color="#FFFFFF"/>
                    </td>
                </tr>
                <?php if ( $button && $button->type === 'MULTI' ) { ?>
                    <input name="actions[<?php echo esc_attr( $action->id ) ?>][iconEnabled]" type="hidden" value="1"/>
                <?php } else { ?>
                    <tr>
                        <th scope="row"></th>
                        <td>
                            <input type="hidden" name="actions[<?php echo esc_attr( $action->id ) ?>][iconEnabled]"
                                   id="actions[<?php echo esc_attr( $action->id ) ?>][iconEnabled]" value="0"/>
                            <input id="cnb-action-icon-enabled" class="cnb_toggle_checkbox" type="checkbox"
                                   name="actions[<?php echo esc_attr( $action->id ) ?>][iconEnabled]"
                                   id="actions[<?php echo esc_attr( $action->id ) ?>][iconEnabled]"
                                   value="true" <?php checked( true, $action->iconEnabled ); ?>>
                            <label for="cnb-action-icon-enabled" class="cnb_toggle_label">Toggle</label>
                            <span data-cnb_toggle_state_label="cnb-action-icon-enabled"
                                  class="cnb_toggle_state cnb_toggle_false">Hide icon</span>
                            <span data-cnb_toggle_state_label="cnb-action-icon-enabled"
                                  class="cnb_toggle_state cnb_toggle_true">Show icon</span>
                        </td>
                    </tr>
                <?php } // End Multi/Buttonbar ?>
            <?php } ?>

            <tr class="cnb-action-properties-whatsapp">
                <th colspan="2">
                    <hr/>
                </th>
            </tr>

            <tr class="cnb-action-properties-whatsapp">
                <th scope="row">Show WhatsApp modal <a
                            href="<?php echo esc_url( $cnb_utils->get_support_url( 'wordpress/buttons/whatsapp-modal/', 'question-mark', 'whatsapp-modal' ) ) ?>"
                            target="_blank" class="cnb-nounderscore">
                        <span class="dashicons dashicons-editor-help"></span>
                    </a></th>
                <td class="appearance">
                    <input type="hidden"
                           name="actions[<?php echo esc_attr( $action->id ) ?>][properties][whatsapp-dialog-type]"
                           value=""/>
                    <input id="cnb-action-modal" class="cnb_toggle_checkbox" type="checkbox"
                           name="actions[<?php echo esc_attr( $action->id ) ?>][properties][whatsapp-dialog-type]"
                           value="popout"
                        <?php checked( true, isset( $action->properties ) && isset( $action->properties->{'whatsapp-dialog-type'} ) && $action->properties->{'whatsapp-dialog-type'} ); ?> />
                    <label for="cnb-action-modal" class="cnb_toggle_label">Toggle</label>
                    <span data-cnb_toggle_state_label="cnb-action-modal"
                          class="cnb_toggle_state cnb_toggle_false">(Off)</span>
                    <span data-cnb_toggle_state_label="cnb-action-modal"
                          class="cnb_toggle_state cnb_toggle_true">Yes</span>
                </td>
            </tr>
            <tr id="action-properties-message-row" class="cnb-action-properties-sms">
                <th scope="row"><label for="action-properties-message">Message template <a
                                href="<?php echo esc_url( $cnb_utils->get_support_url( 'wordpress/buttons/message-template/', 'question-mark', 'message-template' ) ) ?>"
                                target="_blank" class="cnb-nounderscore">
                            <span class="dashicons dashicons-editor-help"></span>
                        </a></label></th>
                <td>
                    <textarea id="action-properties-message"
                              name="actions[<?php echo esc_attr( $action->id ) ?>][properties][message]" class="code"
                              rows="3"
                              placeholder="Optional"><?php if ( isset( $action->properties ) && isset( $action->properties->message ) ) {
                            echo esc_textarea( $action->properties->message );
                        } ?></textarea>
                </td>
            </tr>

            <tr class="cnb-action-properties-whatsapp-modal">
                <th scope="row"><label for="actionWhatsappTitle">Title</label></th>
                <td>
                    <input id="actionWhatsappTitle" type="text"
                           name="actions[<?php echo esc_attr( $action->id ) ?>][properties][whatsapp-title]"
                           value="<?php if ( isset( $action->properties ) && isset( $action->properties->{'whatsapp-title'} ) ) {
                               echo esc_attr( $action->properties->{'whatsapp-title'} );
                           } ?>" maxlength="30" placeholder="Optional"/>
                    <p class="description">When left empty, the "Button label text" entered above will be used here.</p>
                </td>
            </tr>
            <tr class="cnb-action-properties-whatsapp-modal">
                <th scope="row"><label for="actionWhatsappWelcomeMessage">Welcome message</label></th>
                <td>
                    <textarea id="actionWhatsappWelcomeMessage" rows="3"
                              name="actions[<?php echo esc_attr( $action->id ) ?>][properties][whatsapp-welcomeMessage]"
                              placeholder="How can we help?"><?php if ( isset( $action->properties ) && isset( $action->properties->{'whatsapp-welcomeMessage'} ) ) {
                            echo esc_textarea( $action->properties->{'whatsapp-welcomeMessage'} );
                        } ?></textarea>
                    <p class="description">Press [Enter] to start a new speech bubble in the chat modal. Speech bubbles
                        will appear in a sequence with a short pause between them.</p>
                </td>
            </tr>
            <tr class="cnb-action-properties-whatsapp">
                <th scope="row"><label for="cnb-action-show-notification-count">Show notification count</label></th>
                <td class="appearance">
                    <input type="hidden"
                           name="actions[<?php echo esc_attr( $action->id ) ?>][properties][show-notification-count]"
                           value=""/>
                    <input id="cnb-action-show-notification-count" class="cnb_toggle_checkbox" type="checkbox"
                           name="actions[<?php echo esc_attr( $action->id ) ?>][properties][show-notification-count]"
                           value="true"
                        <?php checked( true, isset( $action->properties ) && isset( $action->properties->{'show-notification-count'} ) && $action->properties->{'show-notification-count'} ); ?> />
                    <label for="cnb-action-show-notification-count" class="cnb_toggle_label">Toggle</label>
                    <span data-cnb_toggle_state_label="cnb-action-show-notification-count"
                          class="cnb_toggle_state cnb_toggle_false">(Off)</span>
                    <span data-cnb_toggle_state_label="cnb-action-show-notification-count"
                          class="cnb_toggle_state cnb_toggle_true">Yes</span>
                    <p class="description">Show a small red notification badge on WhatsApp the button to draw the
                        attention.</p>
                </td>
            </tr>
            <tr class="cnb-action-properties-whatsapp-modal">
                <th scope="row"><label for="actionWhatsappPlaceholderMessage">Placeholder message</label></th>
                <td>
                    <input id="actionWhatsappPlaceholderMessage" type="text"
                           name="actions[<?php echo esc_attr( $action->id ) ?>][properties][whatsapp-placeholderMessage]"
                           value="<?php if ( isset( $action->properties ) && isset( $action->properties->{'whatsapp-placeholderMessage'} ) ) {
                               echo esc_attr( $action->properties->{'whatsapp-placeholderMessage'} );
                           } ?>" placeholder="Type your message"/>
                </td>
            </tr>
            <tr class="cnb-action-properties-email">
                <th colspan="2">
                    <hr/>
                </th>
            </tr>
            <tr class="cnb-action-properties-email">
                <th scope="row"><label for="action-properties-subject">Subject</label></th>
                <td><input placeholder="Optional" id="action-properties-subject"
                           name="actions[<?php echo esc_attr( $action->id ) ?>][properties][subject]" type="text"
                           value="<?php if ( isset( $action->properties ) && isset( $action->properties->subject ) ) {
                               echo esc_attr( $action->properties->subject );
                           } ?>"/></td>
            </tr>
            <tr class="cnb-action-properties-email">
                <th scope="row"><label for="action-properties-body">Message template <a
                                href="<?php echo esc_url( $cnb_utils->get_support_url( 'wordpress/buttons/message-template/', 'question-mark', 'message-template' ) ) ?>"
                                target="_blank" class="cnb-nounderscore">
                            <span class="dashicons dashicons-editor-help"></span>
                        </a></label></th>
                <td><textarea placeholder="Optional" id="action-properties-body"
                              name="actions[<?php echo esc_attr( $action->id ) ?>][properties][body]"
                              class="large-text code"
                              rows="3"><?php if ( isset( $action->properties ) && isset( $action->properties->body ) ) {
                            echo esc_textarea( $action->properties->body );
                        } ?></textarea></td>

            </tr>
            <tr class="cnb-action-properties-email">
                <th scope="row"><label for="action-properties-cc">CC</label></th>
                <td><input placeholder="Optional" id="action-properties-cc"
                           name="actions[<?php echo esc_attr( $action->id ) ?>][properties][cc]" type="text"
                           value="<?php if ( isset( $action->properties ) && isset( $action->properties->cc ) ) {
                               echo esc_attr( $action->properties->cc );
                           } ?>"/></td>
            </tr>
            <tr class="cnb-action-properties-email">
                <th scope="row"><label for="action-properties-bcc">BCC</label></th>
                <td><input placeholder="Optional" id="action-properties-bcc"
                           name="actions[<?php echo esc_attr( $action->id ) ?>][properties][bcc]" type="text"
                           value="<?php if ( isset( $action->properties ) && isset( $action->properties->bcc ) ) {
                               echo esc_attr( $action->properties->bcc );
                           } ?>"/></td>
            </tr>
            <tr class="cnb-action-properties-link">
                <th colspan="2">
                    <hr/>
                </th>
            </tr>
            <tr class="cnb-action-properties-link cnb_hide_on_modal">
                <th scope="row"><label for="actionLinkTargetSelect">Open link in</label></th>
                <td>
                    <?php $action_link_target = isset( $action->properties ) && isset( $action->properties->{'link-target'} ) ? $action->properties->{'link-target'} : null; ?>
                    <select id="actionLinkTargetSelect"
                            name="actions[<?php echo esc_attr( $action->id ) ?>][properties][link-target]">
                        <option value="_blank" <?php selected( '_blank', $action_link_target ) ?>>New window</option>
                        <option value="_self" <?php selected( '_self', $action_link_target ) ?>>Current window</option>
                    </select>
                </td>
            </tr>
            <tr class="cnb-action-properties-link cnb_advanced_view cnb_hide_on_modal">
                <th scope="row"><label for="actionLinkDownload">Download</label></th>
                <td>
                    <?php
                    $action_download_enabled = isset( $action->properties ) && isset( $action->properties->{'link-download-enabled'} ) ? $action->properties->{'link-download-enabled'} : false;
                    $action_download_value   = isset( $action->properties ) && isset( $action->properties->{'link-download'} ) ? $action->properties->{'link-download'} : null;
                    ?>
                    <p><input type="hidden"
                              name="actions[<?php echo esc_attr( $action->id ) ?>][properties][link-download-enabled]"
                              value="0"/>
                        <input id="cnb-action-link-download-enabled" class="cnb_toggle_checkbox" type="checkbox"
                               name="actions[<?php echo esc_attr( $action->id ) ?>][properties][link-download-enabled]"
                               value="true" <?php checked( true, $action_download_enabled ); ?>>
                        <label for="cnb-action-link-download-enabled" class="cnb_toggle_label">Toggle</label>
                        <span data-cnb_toggle_state_label="cnb-action-link-download-enabled"
                              class="cnb_toggle_state cnb_toggle_false">(No)</span>
                        <span data-cnb_toggle_state_label="cnb-action-link-download-enabled"
                              class="cnb_toggle_state cnb_toggle_true">Yes</span></p>
                    <p><input id="actionLinkDownload" type="text"
                              name="actions[<?php echo esc_attr( $action->id ) ?>][properties][link-download]"
                              value="<?php echo esc_attr( $action_download_value ) ?>" placeholder="Download filename"/>
                    </p>
                </td>
            </tr>
            <tr class="cnb-action-properties-map">
                <th colspan="2">
                    <hr/>
                </th>
            </tr>
            <tr class="cnb-action-properties-map">
                <th scope="row"><label for="actionMapQueryTypeSelect">Maps display</label></th>
                <td>
                    <?php $action_map_query_type = isset( $action->properties ) && isset( $action->properties->{'map-query-type'} ) ? $action->properties->{'map-query-type'} : null; ?>
                    <select id="actionMapQueryTypeSelect"
                            name="actions[<?php echo esc_attr( $action->id ) ?>][properties][map-query-type]">
                        <option value="q" <?php selected( 'q', $action_map_query_type ) ?>>Show location</option>
                        <option value="daddr" <?php selected( 'daddr', $action_map_query_type ) ?>>Show travel
                            directions
                        </option>
                    </select>
                </td>
            </tr>

        </table>
        <table data-tab-name="scheduler"
               class="form-table <?php echo esc_attr( $adminFunctions->is_active_tab( 'scheduler' ) ) ?>">
            <tr class="cnb_hide_on_modal">
                <th></th>
                <td></td>
            </tr>
            <tr class="cnb_hide_on_modal">
                <th scope="row">Show at all times</th>
                <td>
                    <?php
                    $showAlwaysValue = $action->id === 'new' || ( isset( $action->schedule ) && $action->schedule->showAlways ); ?>
                    <?php if ( $timezone_set_correctly ) { ?>
                        <input name="actions[<?php echo esc_attr( $action->id ) ?>][schedule][showAlways]" type="hidden"
                               value="false"/>
                        <input id="actions_schedule_show_always" class="cnb_toggle_checkbox"
                               onchange="return cnb_hide_on_show_always();"
                               name="actions[<?php echo esc_attr( $action->id ) ?>][schedule][showAlways]"
                               type="checkbox"
                               value="true" <?php checked( true, $showAlwaysValue ); ?>
                        />
                        <label for="actions_schedule_show_always" class="cnb_toggle_label">Toggle</label>
                        <span data-cnb_toggle_state_label="actions_schedule_show_always"
                              class="cnb_toggle_state cnb_toggle_true">Yes</span>
                        <span data-cnb_toggle_state_label="actions_schedule_show_always"
                              class="cnb_toggle_state cnb_toggle_false">(No)</span>
                    <?php } else if ( $showAlwaysValue ) { ?>
                        <p class="description"><span class="dashicons dashicons-warning"></span>The scheduler is
                            disabled because your timezone is not set correctly yet.</p>
                        <input id="actions_schedule_show_always" class="cnb_toggle_checkbox"
                               name="actions[<?php echo esc_attr( $action->id ) ?>][schedule][showAlways]"
                               type="checkbox" value="true" checked="checked"/>
                    <?php } else { ?>
                        <input name="actions[<?php echo esc_attr( $action->id ) ?>][schedule][showAlways]" type="hidden"
                               value="false"/>
                        <input id="actions_schedule_show_always" class="cnb_toggle_checkbox"
                               onchange="return cnb_hide_on_show_always();"
                               name="actions[<?php echo esc_attr( $action->id ) ?>][schedule][showAlways]"
                               type="checkbox"
                               value="true"/>
                        <label for="actions_schedule_show_always" class="cnb_toggle_label">Toggle</label>
                        <span data-cnb_toggle_state_label="actions_schedule_show_always"
                              class="cnb_toggle_state cnb_toggle_true">Yes</span>
                        <span data-cnb_toggle_state_label="actions_schedule_show_always"
                              class="cnb_toggle_state cnb_toggle_false">(No)</span>
                        <p class="description"><span class="dashicons dashicons-warning"></span>Please set your timezone
                            before making any more changes. See the notice at the top of the page for more information.
                        </p>
                    <?php } ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="cnb_padding_0">
                    <span id="domain-timezone-notice-placeholder"></span>
                </td>
            </tr>
            <tr class="cnb_hide_on_show_always">
                <th>Set days</th>
                <td>
                    <?php
                    foreach ( $cnb_days_of_week_order as $cnb_day_of_week ) {
                        $api_server_index = $this->wp_locale_day_to_daysofweek_array_index( $cnb_day_of_week );
                        echo '
                <input class="cnb_day_selector" id="cnb_weekday_' . esc_attr( $api_server_index ) . '" type="checkbox" name="actions[' . esc_attr( $action->id ) . '][schedule][daysOfWeek][' . esc_attr( $api_server_index ) . ']" value="true" ' . checked( isset( $action->schedule ) && isset( $action->schedule->daysOfWeek ) && isset( $action->schedule->daysOfWeek[ $api_server_index ] ) && $action->schedule->daysOfWeek[ $api_server_index ], true, false ) . '>
            	  <label title="' . esc_attr( $wp_locale->get_weekday( $cnb_day_of_week ) ) . '" class="cnb_day_selector" for="cnb_weekday_' . esc_attr( $api_server_index ) . '">' . esc_attr( $wp_locale->get_weekday_abbrev( $wp_locale->get_weekday( $cnb_day_of_week ) ) ) . '</label>
                ';
                    }

                    ?>
                </td>
            </tr>
            <tr class="cnb_hide_on_show_always">
                <th><label for="actions_schedule_outside_hours">After hours</label></th>
                <td>
                    <input id="actions_schedule_outside_hours" class="cnb_toggle_checkbox"
                           name="actions[<?php echo esc_attr( $action->id ) ?>][schedule][outsideHours]" type="checkbox"
                           value="true" <?php checked( true, isset( $action->schedule ) && $action->schedule->outsideHours ); ?> />
                    <label for="actions_schedule_outside_hours" class="cnb_toggle_label">Toggle</label>
                    <span data-cnb_toggle_state_label="actions_schedule_outside_hours"
                          class="cnb_toggle_state cnb_toggle_true">Active</span>
                    <span data-cnb_toggle_state_label="actions_schedule_outside_hours"
                          class="cnb_toggle_state cnb_toggle_false">(Off)</span>
                </td>
            </tr>
            <tr class="cnb_hide_on_show_always">
                <th>Set times</th>
                <td class="cnb-scheduler-slider">
                    <p id="cnb-schedule-range-text"></p>
                    <div id="cnb-schedule-range" style="max-width: 300px"></div>
                </td>
            </tr>
            <tr class="cnb_hide_on_show_always cnb_advanced_view">
                <th><label for="actions-schedule-start">Start time</label></th>
                <td><input type="time" name="actions[<?php echo esc_attr( $action->id ) ?>][schedule][start]"
                           id="actions-schedule-start" value="<?php if ( isset( $action->schedule ) ) {
                        echo esc_attr( $action->schedule->start );
                    } ?>"></td>
            </tr>
            <tr class="cnb_hide_on_show_always cnb_advanced_view">
                <th><label for="actions-schedule-stop">End time</label></th>
                <td><input type="time" name="actions[<?php echo esc_attr( $action->id ) ?>][schedule][stop]"
                           id="actions-schedule-stop" value="<?php if ( isset( $action->schedule ) ) {
                        echo esc_attr( $action->schedule->stop );
                    } ?>"></td>
            </tr>
            <tr class="cnb_hide_on_show_always<?php if ( ! $action_tz_different_from_domain ) { ?> cnb_advanced_view<?php } ?>">
                <th><label for="actions[<?php echo esc_attr( $action->id ) ?>][schedule][timezone]">Timezone</label>
                </th>
                <td>
                    <select name="actions[<?php echo esc_attr( $action->id ) ?>][schedule][timezone]"
                            id="actions[<?php echo esc_attr( $action->id ) ?>][schedule][timezone]"
                            class="cnb_timezone_picker">
                        <?php
                        // phpcs:ignore WordPress.Security
                        echo wp_timezone_choice( $timezone );
                        ?>
                    </select>
                    <p class="description" id="domain_timezone-description">
                        <?php if ( empty( $timezone ) ) { ?>
                            Please select your timezone.
                        <?php } else { ?>
                            Set to <code><?php echo esc_html( $timezone ) ?></code>.
                        <?php } ?>
                    </p>
                    <?php if ( $action_tz_different_from_domain ) { ?>
                        <div class="notice notice-warning inline">
                            <p>Be aware that the timezone for this action
                                (<code><?php echo esc_html( $timezone ) ?></code>) is different from the timezone for
                                your domain (<code><?php echo esc_html( $domain->timezone ) ?></code>).</p>
                        </div>
                    <?php } ?>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * previously cnb_admin_page_action_edit_render_main
     * used by button-edit
     *
     * @param $action CnbAction
     * @param $button CnbButton
     * @param $domain CnbDomain
     */
    public function render_main( $action, $button, $domain = null ) {
        wp_enqueue_style( CNB_SLUG . '-intl-tel-input' );
        wp_enqueue_script( CNB_SLUG . '-intl-tel-input' );
        $bid = ( new CnbUtils() )->get_query_val( 'bid', null );
        // Set some sane defaults
        $action->backgroundColor = ! empty( $action->backgroundColor )
            ? $action->backgroundColor
            : '#009900';
        $action->iconColor       = ! empty( $action->iconColor )
            ? $action->iconColor
            : '#FFFFFF';
        /** @noinspection PhpTernaryExpressionCanBeReplacedWithConditionInspection */
        $action->iconEnabled = isset( $action->iconEnabled )
            // phpcs:ignore PHPCompatibility.FunctionUse
            ? boolval( $action->iconEnabled )
            : true;
        ?>
        <input type="hidden" name="bid" value="<?php echo esc_attr( $bid ) ?>"/>
        <input type="hidden" name="action_id" value="<?php echo esc_attr( $action->id ) ?>"/>
        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'cnb-action-edit' ) ) ?>"/>
        <?php
        $this->render_table( $action, $button, $domain );
    }

    public function render() {
        $action_id           = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_STRING );
        $action              = new CnbAction();
        $action->id          = 'new';
        $action->actionType  = 'PHONE';
        $action->actionValue = null;
        $action->labelText   = null;

        if ( strlen( $action_id ) > 0 && $action_id !== 'new' ) {
            $action = CnbAppRemote::cnb_remote_get_action( $action_id );
        }

        add_action( 'cnb_header_name', function () use ( $action ) {
            $this->add_header( $action );
        } );

        $button = null;
        $bid    = ( new CnbUtils() )->get_query_val( 'bid', null );
        if ( $bid !== null && ! is_wp_error( $action ) ) {
            $button = CnbAppRemote::cnb_remote_get_button_full( $bid );

            // Create back link
            $url                 = admin_url( 'admin.php' );
            $back_to_button_link = add_query_arg(
                array(
                    'page'   => 'call-now-button',
                    'action' => 'edit',
                    'id'     => $bid
                ),
                $url );

            $action_verb = $action->id === 'new' ? 'adding' : 'editing';
            $message     = '<p><strong>You are ' . $action_verb . ' an Action</strong>.
                        Click <a href="' . esc_url( $back_to_button_link ) . '">here</a> to go back to continue configuring the Button.</p>';
            CnbAdminNotices::get_instance()->renderInfo( $message );
        }

        $url           = admin_url( 'admin-post.php' );
        $form_action   = esc_url( $url );
        $redirect_link = add_query_arg(
            array(
                'bid' => $bid
            ),
            $form_action
        );

        $adminFunctions = new CnbAdminFunctions();

        wp_enqueue_script( CNB_SLUG . '-action-type-to-icon-text' );
        wp_enqueue_script( CNB_SLUG . '-form-to-json' );
        wp_enqueue_script( CNB_SLUG . '-preview' );
        wp_enqueue_script( CNB_SLUG . '-client' );
        wp_enqueue_script( CNB_SLUG . '-action-edit' );

        do_action( 'cnb_header' );

        if ( is_wp_error( $action ) ) {
            return;
        }
        ?>
        <div class="cnb-two-column-section-preview">
            <div class="cnb-body-column">
                <div class="cnb-body-content">

                    <?php if ( $bid !== null ) { ?>
                        <h2 class="nav-tab-wrapper">
                            <a href="<?php echo esc_url( $back_to_button_link ); ?>" class="cnb-nav-tab"><span
                                        class="dashicons dashicons-arrow-left-alt"></span></a>
                            <a data-tab-name="basic_options"
                               href="<?php echo esc_url( $this->create_tab_url( $button, 'basic_options' ) ) ?>"
                               class="nav-tab <?php echo esc_attr( $adminFunctions->is_active_tab( 'basic_options' ) ) ?>">Basics</a>
                            <a data-tab-name="scheduler"
                               href="<?php echo esc_url( $this->create_tab_url( $button, 'scheduler' ) ) ?>"
                               class="nav-tab <?php echo esc_attr( $adminFunctions->is_active_tab( 'scheduler' ) ) ?>">Scheduling</a>
                        </h2>
                    <?php } ?>
                    <?php if ( $button ) { ?>
                        <script>
                            let cnb_button = <?php echo wp_json_encode( $button ); ?>;
                            let cnb_actions = <?php echo wp_json_encode( $button->actions ); ?>;
                            let cnb_domain = <?php echo wp_json_encode( $button->domain ) ?>;
                            let cnb_css_root = '<?php echo esc_js( CnbAppRemote::cnb_get_static_base() ) ?>';
                            let cnb_options = <?php echo wp_json_encode( new stdClass() ) ?>;
                            // disable scheduler for the action-edit screen
                            let cnb_ignore_schedule = true
                        </script>
                    <?php } ?>

                    <form class="cnb-container cnb-validation" action="<?php echo esc_url( $redirect_link ); ?>"
                          method="post">
                        <input type="hidden" name="page" value="call-now-button-actions"/>
                        <input type="hidden" name="action"
                               value="<?php echo $action->id === 'new' ? 'cnb_create_action' : 'cnb_update_action' ?>"/>
                        <?php
                        $this->render_main( $action, $button );
                        submit_button();
                        ?>
                    </form>
                </div>
            </div>
            <div class="cnb-side-column">
                <div id="phone-preview">
                    <div class="phone-outside double">
                        <div class="speaker single"></div>
                        <div class="phone-inside single">
                            <div id="cnb-button-preview"></div>
                        </div>
                        <div class="mic double"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php do_action( 'cnb_footer' );
    }
}

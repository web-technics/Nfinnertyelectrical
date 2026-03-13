<?php

namespace cnb\admin\button;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

use cnb\admin\action\CnbAction;
use cnb\admin\action\CnbActionProperties;
use cnb\admin\action\CnbActionView;
use cnb\admin\action\CnbActionViewEdit;
use cnb\admin\api\CnbAppRemote;
use cnb\admin\condition\CnbConditionView;
use cnb\admin\domain\CnbDomain;
use cnb\utils\CnbAdminFunctions;
use cnb\utils\CnbUtils;
use stdClass;
use WP_Error;

class CnbButtonViewEdit {
    /**
     * Renders the "Edit <type>" header
     *
     * @param $button CnbButton Used to determine type if available
     */
    function header( $button ) {
        $type = strtoupper( filter_input( INPUT_GET, 'type', FILTER_SANITIZE_STRING ) );
        $name = 'New Button';
        if ( $button && ! is_wp_error( $button ) ) {
            $type = $button->type;
            $name = $button->name;
        }
        $adminFunctions = new CnbAdminFunctions();
        $buttonTypes    = $adminFunctions->cnb_get_button_types();
        $typeName       = $buttonTypes[ $type ];
        echo esc_html__( 'Editing ' ) . esc_html( $typeName ) . ' <span class="cnb_button_name">' . esc_html( $name ) . '</span>';
    }

    /**
     * @param $button CnbButton
     * @param $tab string
     *
     * @return string
     */
    private function get_tab_url( $button, $tab ) {
        $url = admin_url( 'admin.php' );

        return add_query_arg(
            array(
                'page'   => 'call-now-button',
                'action' => 'edit',
                'type'   => strtolower( $button->type ),
                'id'     => $button->id,
                'tab'    => $tab
            ),
            $url );
    }

    /**
     *
     * This renders JUST the form (no tabs, preview phone, etc.) and is also used in button-overview for the "Add new" modal.
     *
     * @param $button_id int
     * @param $button CnbButton
     * @param $default_domain CnbDomain|WP_Error
     * @param $options array (modal_view (boolean), submit_button_text (string), advanced_view (boolean)
     *
     * @return void
     */
    public function render_form( $button_id, $button, $default_domain, $options = array() ) {
        $adminFunctions = new CnbAdminFunctions();
        $cnb_utils      = new CnbUtils();
        $domains        = CnbAppRemote::cnb_remote_get_domains();

        $cnb_single_image_url = plugins_url( '../../../resources/images/button-new-single.png', __FILE__ );
        $cnb_multi_image_url  = plugins_url( '../../../resources/images/button-new-multi.png', __FILE__ );
        $cnb_full_image_url   = plugins_url( '../../../resources/images/button-new-full.png', __FILE__ );

        $submit_button_text = array_key_exists( 'submit_button_text', $options ) ? $options['submit_button_text'] : '';
        $hide_on_modal      = array_key_exists( 'modal_view', $options ) && $options['modal_view'] === true;
        if ( $hide_on_modal ) {
            echo '<script type="text/javascript">cnb_hide_on_modal_set=1</script>';
        }

        // Create "add Action" link WITH Button association
        $url             = admin_url( 'admin.php' );
        $new_action_link =
            add_query_arg(
                array(
                    'page'   => 'call-now-button-actions',
                    'action' => 'new',
                    'id'     => 'new',
                    'tab'    => 'basic_options',
                    'bid'    => $button->id
                ),
                $url );

        $new_condition_link =
            add_query_arg(
                array(
                    'page'   => 'call-now-button-conditions',
                    'action' => 'new',
                    'id'     => 'new',
                    'bid'    => $button->id
                ),
                $url );

        // In case the API isn't working properly
        if ( $default_domain instanceof WP_Error ) {
            $default_domain     = new CnbDomain();
            $default_domain->id = 0;
        }

        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( CNB_SLUG . '-jquery-ui-touch-punch' );
        wp_enqueue_script( CNB_SLUG . '-action-type-to-icon-text' );
        wp_enqueue_script( CNB_SLUG . '-form-to-json' );
        wp_enqueue_script( CNB_SLUG . '-preview' );
        wp_enqueue_script( CNB_SLUG . '-client' );
        wp_enqueue_script( CNB_SLUG . '-action-edit' );
        wp_enqueue_script( CNB_SLUG . '-condition-edit' );
        wp_enqueue_style( CNB_SLUG . '-client' );
        ?>
        <script>
            let cnb_css_root = '<?php echo esc_js( CnbAppRemote::cnb_get_static_base() ) ?>';
            let cnb_options = <?php echo wp_json_encode( new stdClass() ) ?>;
        </script>
        <form class="cnb-container <?php if ( ! $hide_on_modal ) { ?>cnb-validation<?php } ?>"
              action="<?php echo esc_url( admin_url( 'admin-post.php' ) ) ?>" method="post">
            <input type="hidden" name="page" value="call-now-button"/>
            <input type="hidden" name="action"
                   value="<?php echo $button_id === 'new' ? 'cnb_create_' . esc_attr( strtolower( $button->type ) ) . '_button' : 'cnb_update_' . esc_attr( strtolower( $button->type ) ) . '_button' ?>"/>
            <input type="hidden" name="_wpnonce_button"
                   value="<?php echo esc_attr( wp_create_nonce( 'cnb-button-edit' ) ) ?>"/>
            <input type="hidden" name="tab" value="<?php echo esc_attr( $adminFunctions->get_active_tab_name() ) ?>"/>

            <input type="hidden" name="button[id]" value="<?php echo esc_attr( $button->id ) ?>"/>
            <input type="hidden" name="button[type]" value="<?php echo esc_attr( $button->type ) ?>" id="button_type"/>
            <input type="hidden" name="button[active]" value="<?php echo esc_attr( $button->active ) ?>"/>
            <input type="hidden" name="button[domain]" value="<?php echo esc_attr( $default_domain->id ) ?>"/>

            <table class="form-table <?php if ( ! $hide_on_modal ) {
                echo esc_attr( $adminFunctions->is_active_tab( 'basic_options' ) );
            } else {
                echo 'nav-tab-only';
            } ?>" data-tab-name="basic_options">
                <tr class="cnb_hide_on_modal">
                    <th></th>
                    <td></td>
                </tr>
                <tr>
                    <th scope="row"><label for="button_name">Button name</label></th>

                    <td class="activated">
                        <input type="text" name="button[name]" id="button_name" required="required"
                               value="<?php echo esc_attr( $button->name ); ?>"/>
                    </td>
                </tr>
                <tr class="cnb_hide_on_modal">
                    <th scope="row"><label for="cnb-enable">Button status</label></th>

                    <td class="activated">
                        <input type="hidden" name="button[active]" value="0"/>
                        <input id="cnb-enable" class="cnb_toggle_checkbox" type="checkbox" name="button[active]"
                               value="1" <?php checked( true, $button->active ); ?> />
                        <label for="cnb-enable" class="cnb_toggle_label">Toggle</label>
                        <span data-cnb_toggle_state_label="cnb-enable" class="cnb_toggle_state cnb_toggle_false">(Inactive)</span>
                        <span data-cnb_toggle_state_label="cnb-enable"
                              class="cnb_toggle_state cnb_toggle_true">Active</span>
                    </td>
                </tr>
                <tr class="cnb_hide_on_modal cnb_advanced_view">
                    <th scope="row"><label for="button_domain">Domain</label></th>
                    <td>
                        <select name="button[domain]" id="button_domain">
                            <?php
                            foreach ( $domains as $domain ) { ?>
                                <option
                                    <?php selected( $domain->id, $button->domain->id ) ?>
                                        value="<?php echo esc_attr( $domain->id ) ?>">
                                    <?php echo esc_html( $domain->name ) ?>
                                    <?php if ( $domain->id == $default_domain->id ) {
                                        echo ' (current WordPress domain)';
                                    } ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <?php if ( $button->type !== 'SINGLE' ) { ?>
                    <tr class="cnb_hide_on_modal">
                        <th colspan="2" class="cnb_padding_0">
                            <h2>
                                Actions <?php echo '<a href="' . esc_url( $new_action_link ) . '" class="page-title-action">Add Action</a>'; ?></h2>
                        </th>
                    </tr>
                <?php }
                if ( $button->type === 'SINGLE' ) {
                    $action = new CnbAction();

                    // If there is a real one, use that one
                    if ( sizeof( $button->actions ) > 0 ) {
                        $action = $button->actions[0];
                    } else {
                        // Create a dummy Action
                        $action->id          = 'new';
                        $action->actionType  = '';
                        $action->actionValue = '';
                        $action->labelText   = '';
                        $action->properties  = new CnbActionProperties();
                    }
                    // Start workaround: This table below (<tr>...</tr>) needs to be there for the modal to work!
                    if ( $hide_on_modal ) { ?>
                        <tr class="cnb_hide_on_modal">
                        <th></th>
                        <td>
                        <input type="hidden" name="actions[<?php echo esc_attr( $action->id ) ?>][id]"
                               value="<?php echo esc_attr( $action->id ) ?>"/>
                    <?php }
                    ( new CnbActionViewEdit() )->render_main( $action, $button, $default_domain );
                    if ( $hide_on_modal ) { ?>
                        </td>
                        </tr>
                    <?php } // End workaround
                } else { ?>
            </table>

            <!-- This div exists to allow rendering the Action table outside the existing table -->
            <div data-tab-name="basic_options" class="cnb-button-edit-action-table <?php if ( $hide_on_modal ) {
                echo esc_attr( $adminFunctions->is_active_tab( 'basic_options' ) );
            } else {
                echo 'nav-tab-only';
            } ?>" <?php if ( ! $adminFunctions->is_active_tab( 'basic_options' ) ) {
                echo 'style="display:none"';
            } ?>>
                <?php ( new CnbActionView() )->renderTable( $button ); ?>
            </div>

            <table class="form-table <?php if ( ! $hide_on_modal ) {
                echo esc_attr( $adminFunctions->is_active_tab( 'basic_options' ) );
            } else {
                echo 'nav-tab-only';
            } ?>"><?php
                } ?>
                <script>
                    let cnb_actions = <?php echo wp_json_encode( $button->actions ) ?>;
                    let cnb_domain = <?php echo wp_json_encode( $button->domain ) ?>;
                </script>

                <?php if ( $button_id === 'new' ) { ?>
                    <tr>
                        <th scope="row">Select button type</th>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div class="cnb-flexbox cnb_type_selector">
                                <div class="cnb_type_selector_item cnb_type_selector_single cnb_type_selector_active"
                                     data-cnb-selection="single">
                                    <img style="max-width:100%;" alt="Choose a Single button type"
                                         src="<?php echo esc_url( $cnb_single_image_url ) ?>">
                                    <div style="text-align:center">Single button</div>
                                </div>
                                <div class="cnb_type_selector_item cnb_type_selector_multi" data-cnb-selection="multi">
                                    <img style="max-width:100%;" alt="Choose a Multibutton type"
                                         src="<?php echo esc_url( $cnb_multi_image_url ) ?>">
                                    <div style="text-align:center">Multibutton</div>
                                </div>
                                <div class="cnb_type_selector_item cnb_type_selector_full" data-cnb-selection="full">
                                    <img style="max-width:100%;" alt="Choose a Full button type"
                                         src="<?php echo esc_url( $cnb_full_image_url ) ?>">
                                    <div style="text-align:center">Buttonbar</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </table>
            <table class="form-table <?php echo esc_attr( $adminFunctions->is_active_tab( 'extra_options' ) ) ?>"
                   data-tab-name="extra_options">
                <?php if ( $button->type === 'FULL' ) { ?>
                    <tr>
                        <th colspan="2">
                            <h3>Colors for the Buttonbar are defined via the individual Action(s).</h3>
                            <input name="button[options][iconBackgroundColor]" type="hidden"
                                   value="<?php echo esc_attr( $button->options->iconBackgroundColor ); ?>"/>
                            <input name="button[options][iconColor]" type="hidden"
                                   value="<?php echo esc_attr( $button->options->iconColor ); ?>"/>
                        </th>
                    </tr>
                <?php } else if ( $button->type === 'SINGLE' ) {
                    // Migration note:
                    //- we move from button.options.iconBackgroundColor to action.backgroundColor
                    //- we move from button.options.iconColor to action.iconColor
                    // So for now, "button" take priority, but once the new value is saved, we blank the button options
                    $backgroundColor = ( $button && $button->options && $button->options->iconBackgroundColor ) ? $button->options->iconBackgroundColor : ( $action->backgroundColor ?: '#009900' );
                    $iconColor       = ( $button && $button->options && $button->options->iconColor ) ? $button->options->iconColor : ( $action->iconColor ?: '#FFFFFF' );
                    ?>
                    <tr class="cnb_hide_on_modal">
                        <th></th>
                        <td>
                            <input name="button[options][iconBackgroundColor]" type="hidden" value=""/>
                            <input name="button[options][iconColor]" type="hidden" value=""/>
                            <!-- We always enable the icon when the type if SINGLE, original value is "<?php echo esc_attr( $action->iconEnabled ) ?>" -->
                            <input name="actions[<?php echo esc_attr( $action->id ) ?>][iconEnabled]" type="hidden"
                                   value="1"/>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="actions-options-iconBackgroundColor">Button color</label></th>
                        <td>
                            <input name="actions[<?php echo esc_attr( $action->id ) ?>][backgroundColor]"
                                   id="actions-options-iconBackgroundColor" type="text"
                                   value="<?php echo esc_attr( $backgroundColor ); ?>" class="cnb-iconcolor-field"
                                   data-default-color="#009900"/>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="actions-options-iconColor">Icon color</label></th>
                        <td>
                            <input name="actions[<?php echo esc_attr( $action->id ) ?>][iconColor]"
                                   id="actions-options-iconColor" type="text"
                                   value="<?php echo esc_attr( $iconColor ); ?>" class="cnb-iconcolor-field"
                                   data-default-color="#FFFFFF"/>
                        </td>
                    </tr>

                <?php } else if ( $button->type === 'MULTI' ) {
                    $backgroundColor      = ( $button->options && $button->options->iconBackgroundColor ) ? $button->options->iconBackgroundColor : ( $button->multiButtonOptions->iconBackgroundColor ?: '#009900' );
                    $iconColor            = ( $button->options && $button->options->iconColor ) ? $button->options->iconColor : ( $button->multiButtonOptions->iconColor ?: '#FFFFFF' );
                    $iconTextOpen         = ( $button->multiButtonOptions && $button->multiButtonOptions->iconTextOpen ) ? $button->multiButtonOptions->iconTextOpen : 'more_vert';
                    $iconTypeOpen         = ( $button->multiButtonOptions && $button->multiButtonOptions->iconTypeOpen ) ? $button->multiButtonOptions->iconTypeOpen : 'FONT';
                    $iconTextClose        = ( $button->multiButtonOptions && $button->multiButtonOptions->iconTextClose ) ? $button->multiButtonOptions->iconTextClose : 'close';
                    $iconTypeClose        = ( $button->multiButtonOptions && $button->multiButtonOptions->iconTypeClose ) ? $button->multiButtonOptions->iconTypeClose : 'FONT';
                    $labelText            = ( $button->multiButtonOptions && $button->multiButtonOptions->iconTypeClose ) ? $button->multiButtonOptions->labelText : null;
                    $labelBackgroundColor = ( $button->multiButtonOptions && $button->multiButtonOptions->iconTypeClose ) ? $button->multiButtonOptions->labelBackgroundColor : null;
                    ?>
                    <tr class="cnb_hide_on_modal">
                        <th></th>
                        <td></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="button-multiButtonOptions-iconBackgroundColor">Main button
                                color</label></th>
                        <td>
                            <input name="button[multiButtonOptions][id]" type="hidden"
                                   value="<?php echo esc_attr( $button->multiButtonOptions->id ); ?>"/>
                            <input name="button[multiButtonOptions][iconBackgroundColor]"
                                   id="button-multiButtonOptions-iconBackgroundColor" type="text"
                                   value="<?php echo esc_attr( $backgroundColor ); ?>"
                                   class="cnb-iconcolor-field" data-default-color="#009900"/>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="button-multiButtonOptions-iconColor">Main icon color</label></th>
                        <td>
                            <input name="button[multiButtonOptions][iconColor]" id="button-multiButtonOptions-iconColor"
                                   type="text" value="<?php echo esc_attr( $iconColor ); ?>"
                                   class="cnb-iconcolor-field" data-default-color="#FFFFFF"/>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="button-multiButtonOptions-iconTextOpen">Main icon</label></th>
                        <td>
                            <div class="icon-text-options" id="icon-text-open"
                                 data-icon-text-target="button-multiButtonOptions-iconTextOpen"
                                 data-icon-type-target="button-multiButtonOptions-iconTypeOpen">
                                <div class="cnb-button-icon">
                                    <i class="cnb-font-icon" data-icon-type="FONT"
                                       data-icon-text="more_vert">more_vert</i>
                                </div>
                                <div class="cnb-button-icon">
                                    <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="menu">menu</i>
                                </div>
                                <div class="cnb-button-icon">
                                    <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="support">support</i>
                                </div>
                                <div class="cnb-button-icon">
                                    <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="communicate">communicate</i>
                                </div>
                                <div class="cnb-button-icon">
                                    <i class="cnb-font-icon" data-icon-type="FONT"
                                       data-icon-text="more_info">more_info</i>
                                </div>
                                <div class="cnb-button-icon">
                                    <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="conversation">conversation</i>
                                </div>
                            </div>
                            <div class="cnb_advanced_view">
                                <a
                                        href="#"
                                        onclick="return cnb_show_icon_text_advanced(this)"
                                        data-icon-text="button-multiButtonOptions-iconTextOpen"
                                        data-icon-type="button-multiButtonOptions-iconTypeOpen"
                                        data-description="button-multiButtonOptions-iconTextOpen-description"
                                        class="cnb_advanced_view">Use a custom icon</a>
                                <input name="button[multiButtonOptions][iconTextOpen]"
                                       id="button-multiButtonOptions-iconTextOpen" type="hidden"
                                       value="<?php echo esc_attr( $iconTextOpen ); ?>"/>
                                <input name="button[multiButtonOptions][iconTypeOpen]"
                                       id="button-multiButtonOptions-iconTypeOpen" type="hidden"
                                       value="<?php echo esc_attr( $iconTypeOpen ); ?>"/>
                                <p class="description" id="button-multiButtonOptions-iconTextOpen-description"
                                   style="display: none">
                                    You can enter a custom Material Design font code here. Search the full library at <a
                                            href="https://fonts.google.com/icons" target="_blank">Google Fonts</a>.<br/>
                                    The Call Now Button uses the <code>filled</code> version of icons.</p>
                            </div>
                        </td>
                    </tr>
                    <tr class="cnb_advanced_view">
                        <th scope="row"><label for="button-multiButtonOptions-iconTextClose">Close Icon</label></th>
                        <td>
                            <div class="icon-text-options" id="icon-text-close"
                                 data-icon-text-target="button-multiButtonOptions-iconTextClose"
                                 data-icon-type-target="button-multiButtonOptions-iconTypeClose">
                                <div class="cnb-button-icon">
                                    <i class="cnb-font-icon" data-icon-type="FONT" data-icon-text="close">close</i>
                                </div>
                                <div class="cnb-button-icon">
                                    <i class="cnb-font-icon family-material" data-icon-type="FONT_MATERIAL"
                                       data-icon-text="cancel">cancel</i>
                                </div>
                                <div class="cnb-button-icon">
                                    <i class="cnb-font-icon family-material" data-icon-type="FONT_MATERIAL"
                                       data-icon-text="close">close</i>
                                </div>
                                <div class="cnb-button-icon">
                                    <i class="cnb-font-icon family-material" data-icon-type="FONT_MATERIAL"
                                       data-icon-text="zoom_in_map">zoom_in_map</i>
                                </div>
                            </div>
                            <a
                                    href="#"
                                    onclick="return cnb_show_icon_text_advanced(this)"
                                    data-icon-text="button-multiButtonOptions-iconTextClose"
                                    data-icon-type="button-multiButtonOptions-iconTypeClose"
                                    data-description="button-multiButtonOptions-iconTextClose-description"
                                    class="cnb_advanced_view">Use a custom icon</a>
                            <input name="button[multiButtonOptions][iconTextClose]"
                                   id="button-multiButtonOptions-iconTextClose" type="hidden"
                                   value="<?php echo esc_attr( $iconTextClose ); ?>"/>
                            <input name="button[multiButtonOptions][iconTypeClose]"
                                   id="button-multiButtonOptions-iconTypeClose" type="hidden"
                                   value="<?php echo esc_attr( $iconTypeClose ); ?>"/>
                            <p class="description" id="button-multiButtonOptions-iconTextClose-description"
                               style="display: none">
                                You can enter a custom Material Design font code here. Search the full library at <a
                                        href="https://fonts.google.com/icons" target="_blank">Google Fonts</a>.<br/>
                                The Call Now Button uses the <code>filled</code> version of icons.</p>
                        </td>
                    </tr>
                    <tr class="cnb_advanced_view">
                        <th scope="row"><label for="button-multiButtonOptions-labelText">Label text (when open)</label>
                        </th>
                        <td>
                            <input name="button[multiButtonOptions][labelText]" id="button-multiButtonOptions-labelText"
                                   type="text" value="<?php echo esc_attr( $labelText ); ?>"/>
                        </td>
                    </tr>
                    <tr class="cnb_advanced_view">
                        <th scope="row"><label for="button-multiButtonOptions-labelBackgroundColor">Label background
                                color</label></th>
                        <td>
                            <input name="button[multiButtonOptions][labelBackgroundColor]"
                                   id="button-multiButtonOptions-labelBackgroundColor" type="text"
                                   value="<?php echo esc_attr( $labelBackgroundColor ); ?>"
                                   class="cnb-iconcolor-field" data-default-color="#3c434a"/>
                        </td>
                    </tr>
                <?php } ?>
                <tr>
                    <th scope="row">Position <a
                                href="<?php echo esc_url( $cnb_utils->get_support_url( 'button-position/', 'question-mark', 'button-position' ) ) ?>"
                                target="_blank" class="cnb-nounderscore">
                            <span class="dashicons dashicons-editor-help"></span>
                        </a></th>
                    <td class="appearance">
                        <div class="appearance-options">
                            <?php if ( $button->type === 'FULL' ) { ?>
                                <div class="cnb-radio-item">
                                    <input type="radio" id="appearance1" name="button[options][placement]"
                                           value="TOP_CENTER" <?php checked( 'TOP_CENTER', $button->options->placement ); ?>>
                                    <label title="top-center" for="appearance1">Top</label>
                                </div>
                                <div class="cnb-radio-item">
                                    <input type="radio" id="appearance2" name="button[options][placement]"
                                           value="BOTTOM_CENTER" <?php checked( 'BOTTOM_CENTER', $button->options->placement ); ?>>
                                    <label title="bottom-center" for="appearance2">Bottom</label>
                                </div>
                            <?php } else { ?>
                                <div class="cnb-radio-item">
                                    <input type="radio" id="appearance1" name="button[options][placement]"
                                           value="BOTTOM_RIGHT" <?php checked( 'BOTTOM_RIGHT', $button->options->placement ); ?>>
                                    <label title="bottom-right" for="appearance1">Right corner</label>
                                </div>
                                <div class="cnb-radio-item">
                                    <input type="radio" id="appearance2" name="button[options][placement]"
                                           value="BOTTOM_LEFT" <?php checked( 'BOTTOM_LEFT', $button->options->placement ); ?>>
                                    <label title="bottom-left" for="appearance2">Left corner</label>
                                </div>
                                <div class="cnb-radio-item">
                                    <input type="radio" id="appearance3" name="button[options][placement]"
                                           value="BOTTOM_CENTER" <?php checked( 'BOTTOM_CENTER', $button->options->placement ); ?>>
                                    <label title="bottom-center" for="appearance3">Center</label>
                                </div>

                                <!-- Extra placement options -->
                                <br class="cnb-extra-placement">
                                <div class="cnb-radio-item cnb-extra-placement <?php echo $button->options->placement == 'MIDDLE_RIGHT' ? 'cnb-extra-active' : ''; ?>">
                                    <input type="radio" id="appearance5" name="button[options][placement]"
                                           value="MIDDLE_RIGHT" <?php checked( 'MIDDLE_RIGHT', $button->options->placement ); ?>>
                                    <label title="middle-right" for="appearance5">Middle right</label>
                                </div>
                                <div class="cnb-radio-item cnb-extra-placement <?php echo $button->options->placement == 'MIDDLE_LEFT' ? 'cnb-extra-active' : ''; ?>">
                                    <input type="radio" id="appearance6" name="button[options][placement]"
                                           value="MIDDLE_LEFT" <?php checked( 'MIDDLE_LEFT', $button->options->placement ); ?>>
                                    <label title="middle-left" for="appearance6">Middle left </label>
                                </div>
                                <br class="cnb-extra-placement">
                                <div class="cnb-radio-item cnb-extra-placement <?php echo $button->options->placement == 'TOP_RIGHT' ? 'cnb-extra-active' : ''; ?>">
                                    <input type="radio" id="appearance7" name="button[options][placement]"
                                           value="TOP_RIGHT" <?php checked( 'TOP_RIGHT', $button->options->placement ); ?>>
                                    <label title="top-right" for="appearance7">Top right corner</label>
                                </div>
                                <div class="cnb-radio-item cnb-extra-placement <?php echo $button->options->placement == 'TOP_LEFT' ? 'cnb-extra-active' : ''; ?>">
                                    <input type="radio" id="appearance8" name="button[options][placement]"
                                           value="TOP_LEFT" <?php checked( 'TOP_LEFT', $button->options->placement ); ?>>
                                    <label title="top-left" for="appearance8">Top left corner</label>
                                </div>
                                <div class="cnb-radio-item cnb-extra-placement <?php echo $button->options->placement == 'TOP_CENTER' ? 'cnb-extra-active' : ''; ?>">
                                    <input type="radio" id="appearance9" name="button[options][placement]"
                                           value="TOP_CENTER" <?php checked( 'TOP_CENTER', $button->options->placement ); ?>>
                                    <label title="top-center" for="appearance9">Center top</label>
                                </div>
                                <a href="#" id="button-more-placements">More placement options...</a>
                                <!-- END extra placement options -->
                            <?php } ?>
                        </div>
                    </td>
                </tr>
                <?php if ( $button->type !== 'FULL' ) { ?>
                    <tr>
                        <th scope="row"><label for="button_options_animation">Button animation <a
                                        href="<?php echo esc_url( $cnb_utils->get_support_url( 'wordpress/buttons/button-animation/', 'question-mark', 'button-animation' ) ) ?>"
                                        target="_blank" class="cnb-nounderscore">
                                    <span class="dashicons dashicons-editor-help"></span>
                                </a></label></th>
                        <td>
                            <select name="button[options][animation]" id="button_options_animation">
                                <?php foreach ( CnbButtonOptions::getAnimationTypes() as $animation_type_key => $animation_type_value ) { ?>
                                    <option value="<?php echo esc_attr( $animation_type_key ) ?>"<?php selected( $animation_type_key, $button->options->animation ) ?>><?php echo esc_html( $animation_type_value ) ?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                <?php } ?>
            </table>
            <table class="form-table <?php echo esc_attr( $adminFunctions->is_active_tab( 'visibility' ) ) ?>"
                   data-tab-name="visibility">
                <tbody id="cnb_form_table_visibility">
                <tr>
                    <th></th>
                    <td></td>
                </tr>
                <tr>
                    <th scope="row"><label for="button_options_displaymode">Display on </label></th>
                    <td class="appearance">
                        <select name="button[options][displayMode]" id="button_options_displaymode">
                            <option value="MOBILE_ONLY"<?php selected( 'MOBILE_ONLY', $button->options->displayMode ) ?>>
                                Mobile only
                            </option>
                            <option value="DESKTOP_ONLY"<?php selected( 'DESKTOP_ONLY', $button->options->displayMode ) ?>>
                                Desktop only
                            </option>
                            <option value="ALWAYS"<?php selected( 'ALWAYS', $button->options->displayMode ) ?>>All
                                screens
                            </option>
                        </select>
                    </td>
                </tr>
                <tr class="cnb_hide_on_modal">
                    <th class="cnb_padding_0">
                        <h2>Page rules</h2>
                    </th>
                    <td>
                        <?php echo '<a href="' . esc_url( $new_condition_link ) . '" class="button">Add page rule</a>'; ?>
                    </td>
                </tr>
                </tbody>
            </table>

            <!-- This div exists to allow rendering the Conditions' table outside the existing table -->
            <div data-tab-name="visibility" class="cnb-button-edit-conditions-table <?php if ( $hide_on_modal ) {
                echo esc_attr( $adminFunctions->is_active_tab( 'visibility' ) );
            } else {
                echo 'nav-tab-only';
            } ?>" <?php if ( ! $adminFunctions->is_active_tab( 'visibility' ) ) {
                echo 'style="display:none"';
            } ?>>
                <?php
                $view = new CnbConditionView();
                $view->renderTable( $button );
                ?>
            </div>

            <?php submit_button( $submit_button_text ); ?>
        </form>
        <?php
    }

    function render() {
        global $wp_locale;
        $cnb_options = get_option( 'cnb' );

        $button_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_STRING );
        $button    = new CnbButton();

        // Get the various supported domains
        $domain = CnbAppRemote::cnb_remote_get_wp_domain();

        if ( strlen( $button_id ) > 0 && $button_id !== 'new' ) {
            $button = CnbAppRemote::cnb_remote_get_button_full( $button_id );
        } elseif ( $button_id === 'new' ) {
            $button->type   = strtoupper( filter_input( INPUT_GET, 'type', FILTER_SANITIZE_STRING ) );
            $button->domain = $domain;
        }
        if ( is_wp_error( $button ) || $button->actions === null ) {
            $button->actions = array();
        }

        // Create options
        $options                  = array();
        $options['advanced_view'] = $cnb_options['advanced_view'];

        add_action( 'cnb_header_name', function () use ( $button ) {
            $this->header( $button );
        } );

        $adminFunctions = new CnbAdminFunctions();

        do_action( 'cnb_header' );

        if ( is_wp_error( $button ) ) {
            return;
        }

        // Preview date picker details
        // "w": 0 (for Sunday) through 6 (for Saturday)
        $currentDayOfWeek    = current_time( 'w' );
        $currentHourOfDay    = current_time( 'H' );
        $currentMinuteOfHour = current_time( 'i' );

        // Round to the nearest 15 in an extremely lazy way
        $currentMinuteOfHour = ( $currentMinuteOfHour < 45 ) ? '30' : '45';
        $currentMinuteOfHour = ( $currentMinuteOfHour < 30 ) ? '15' : $currentMinuteOfHour;
        $currentMinuteOfHour = ( $currentMinuteOfHour < 15 ) ? '00' : $currentMinuteOfHour;
        // END Preview date picker details
        ?>

        <div class="cnb-two-column-section-preview">
            <div class="cnb-body-column">
                <div class="cnb-body-content">
                    <h2 class="nav-tab-wrapper">
                        <a href="<?php echo esc_url( $this->get_tab_url( $button, 'basic_options' ) ) ?>"
                           class="nav-tab <?php echo esc_attr( $adminFunctions->is_active_tab( 'basic_options' ) ) ?>"
                           data-tab-name="basic_options">Basics</a>
                        <?php if ( $button_id !== 'new' ) { ?>
                            <a href="<?php echo esc_url( $this->get_tab_url( $button, 'extra_options' ) ) ?>"
                               class="nav-tab <?php echo esc_attr( $adminFunctions->is_active_tab( 'extra_options' ) ) ?>"
                               data-tab-name="extra_options">Presentation</a>
                            <a href="<?php echo esc_url( $this->get_tab_url( $button, 'visibility' ) ) ?>"
                               class="nav-tab <?php echo esc_attr( $adminFunctions->is_active_tab( 'visibility' ) ) ?>"
                               data-tab-name="visibility">Visibility</a>
                            <?php if ( $button->type === 'SINGLE' ) { ?>
                                <a href="<?php echo esc_url( $this->get_tab_url( $button, 'scheduler' ) ) ?>"
                                   class="nav-tab <?php echo esc_attr( $adminFunctions->is_active_tab( 'scheduler' ) ) ?>"
                                   data-tab-name="scheduler">Schedule</a>
                            <?php } ?>
                        <?php } else { ?>
                            <a class="nav-tab"><i>Additional options available after saving</i></a>
                        <?php } ?>
                    </h2>
                    <?php $this->render_form( $button_id, $button, $domain, $options ); ?>
                </div> <!-- /cnb-body-content -->
            </div> <!-- /cnb-body-column -->
            <div class="cnb-side-column">
                <div id="phone-preview">
                    <div class="phone-outside double">
                        <div class="speaker single"></div>
                        <div class="phone-inside single">
                            <div class="cnb-preview-moment">
                                <label>
                                    <select class="call-now-button-preview-selector"
                                            id="call-now-button-preview-selector-day">
                                        <?php $days = array( 1, 2, 3, 4, 5, 6, 0 );
                                        foreach ( $days as $day ) {
                                            echo '<option value="' . esc_attr( $day ) . '" ' . selected( $currentDayOfWeek, $day ) . '>' . esc_attr( $wp_locale->get_weekday( $day ) ) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </label>

                                <label>
                                    <select class="call-now-button-preview-selector"
                                            id="call-now-button-preview-selector-hour">
                                        <?php
                                        foreach ( range( 0, 23 ) as $number ) {
                                            $number = $number < 10 ? '0' . $number : $number;
                                            echo '<option ' . selected( $currentHourOfDay, $number ) . '>' . esc_html( $number ) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </label>
                                :
                                <label>
                                    <select class="call-now-button-preview-selector"
                                            id="call-now-button-preview-selector-minute">
                                        <?php
                                        foreach ( range( 0, 45, 15 ) as $number ) {
                                            $number = $number < 10 ? '0' . $number : $number;
                                            echo '<option ' . selected( $currentMinuteOfHour, $number ) . '>' . esc_html( $number ) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </label>
                            </div>
                            <div id="cnb-button-preview"></div>
                        </div>
                        <div class="mic double"></div>
                    </div>
                </div>
            </div>
        </div>

        <?php
        do_action( 'cnb_footer' );
    }
}

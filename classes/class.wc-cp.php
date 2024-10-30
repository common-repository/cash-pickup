<?php
/**
 * Payment Cash Pickup
 * Copyright (C) 2016-2017 Ilario Tresoldi. All rights reserved.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Contact the author at ilario.tresoldi@gmail.com
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( !class_exists( 'WC_Gateway_Cash_pickup' ) ):

/**
 * Main plugin class
 *
 * @usedby WC_Payment_Gateway
 */
class WC_Gateway_Cash_pickup extends WC_Payment_Gateway {

    /**
     * Init gateway settigns
     */
    public function __construct() {
        $this->id                = 'cp';
        $this->icon              = apply_filters('woocommerce_cp_icon', '');
        $this->has_fields        = false;
        $this->method_title      = __( 'Payment Cash pickup', 'payment-cash-pickup' );
        $this->order_button_text = apply_filters( 'woocommerce_order_button_text', __( 'Place order', 'woocommerce' ) );

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        // Get settings
        $this->title              = $this->get_option( 'title' );
        $this->description        = $this->get_option( 'description' );
        $this->instructions       = $this->get_option( 'instructions' );
		$this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_thankyou_cp', array( $this, 'thankyou' ) );
    }

    /**
     * Admin Panel Options
     *
     * @access public
     * @return void
     */
    function admin_options() {
        ?>
        <h3><?php _e('Cash Pickup', 'payment-cash-pickup'); ?></h3>
        <p><?php __('Customers pay with cash.', 'payment-cash-pickup' ); ?></p>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
    <?php
    }

    /**
     * Create form fields for the payment gateway
     *
     * @return void
     */
    public function init_form_fields() {
        $shipping_methods = array();
		$payment_methods = array();
        if ( is_admin() ) {
            foreach ( WC()->shipping->load_shipping_methods() as $method ) {
				$shipping_methods[ $method->id ] = $method->method_title;
            }
        }

        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'payment-cash-pickup' ),
                'type' => 'checkbox',
                'label' => __( 'Enable Cash Pickup', 'payment-cash-pickup' ),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __( 'Title', 'payment-cash-pickup' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout', 'payment-cash-pickup' ),
                'default' => __( 'Cash pickup', 'payment-cash-pickup' ),
                'desc_tip'      => true,
            ),
            'description' => array(
                'title' => __( 'Customer Message', 'payment-cash-pickup' ),
                'type' => 'textarea',
                'default' => __( 'Pay your order with cash at our store.', 'payment-cash-pickup' )
            ),
            'instructions' => array(
                'title' => __( 'Instructions', 'payment-cash-pickup' ),
                'type' => 'textarea',
                'description' => __( 'Instructions that will be added to the thank you page.', 'payment-cash-pickup' ),
                'default' => __( 'Pay with cash at [Store address].', 'payment-cash-pickup' )
            ),
			'enable_for_methods' => array(
                'title'         => __( 'Enable for shipping methods', 'payment-cash-pickup' ),
                'type'          => 'multiselect',
                'class'         => 'chosen_select',
                'css'           => 'width: 450px;',
                'default'       => '',
                'description'   => __( 'If Cash Pickup is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'payment-cash-pickup' ),
                'options'       => $shipping_methods,
                'desc_tip'      => true,
            )
        );
    }

	/**
     * Check If The Gateway Is Available For Use
     *
     * @return bool
     */
    public function is_available() {
		if (!is_admin()) {
			if (!empty( $this->enable_for_methods)) {
				$chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );

				if (isset( $chosen_shipping_methods_session)) {
					$chosen_shipping_methods = array_unique( $chosen_shipping_methods_session );
				} else {
					$chosen_shipping_methods = array();
				}

				$check_method = false;

				if (is_page( wc_get_page_id('checkout')) && !empty($wp->query_vars['order-pay'])) {
					$order_id = absint($wp->query_vars['order-pay']);
					$order    = new WC_Order($order_id);

					if ($order->shipping_method)
						$check_method = $order->shipping_method;
				} elseif (empty($chosen_shipping_methods) || sizeof($chosen_shipping_methods) > 1) {
					$check_method = false;
				} elseif (sizeof($chosen_shipping_methods) == 1) {
					$check_method = $chosen_shipping_methods[0];
				}

				if (!$check_method)
					return false;

				$found = false;

				foreach ($this->enable_for_methods as $method_id) {
					if (strpos($check_method, $method_id) === 0) {
						$found = true;
						break;
					}
				}

				if (!$found)
					return false;
			}
		}
        return parent::is_available();
    }

    /**
     * Process the order payment status
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment( $order_id ) {
        $order = new WC_Order($order_id);

        // Mark as on-hold (we're awaiting the cheque)
        //$order->update_status( apply_filters( 'wc-cash-pickup_default_order_status', 'pending' ), __( 'Awaiting payment', 'payment-cash-pickup' ) );
		$order->update_status(apply_filters('wc-cash-pickup_default_order_status', 'processing'), __('Awaiting payment', 'payment-cash-pickup'));

        // Reduce stock levels
        $order->reduce_order_stock();

        // Remove cart
        WC()->cart->empty_cart();

        // Return thankyou redirect
        return array(
            'result'    => 'success',
            'redirect'  => $this->get_return_url($order)
        );
    }

    /**
     * Output for the order received page.
     *
     * @return void
     */
    public function thankyou() {
        echo $this->instructions != '' ? wpautop(wptexturize(wp_kses_post($this->instructions))) : '';
    }
}
endif;

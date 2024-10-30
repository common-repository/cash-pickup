<?php
/*
Plugin Name:       Payment Cash Pickup
Plugin URI:        https://wordpress.org/plugins/payment-cash-pickup/
Description:       A WooCommerce Extension that adds the payment gateway "Cash Pickup"
Version:           1.0.5
Author:            Ilario Tresoldi
Author URI:        http://www.webcreates.eu
Textdomain:        wc_cp
Domain Path:       /language
License:           GPL-2.0+
License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
*/

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

/**
 * Start the plugin
 */
function wc_cp_init() {
    global $woocommerce;

    if( !isset( $woocommerce ) ) { return; }

	$domain = 'payment-cash-pickup';
	$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
	$path   = plugins_url('payment-cash-pickup/language/'.$domain.'-'.$locale.'.mo');
	$loaded = load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/language/' );
	if ( !$loaded )
	{
		$path   = plugins_url('payment-cash-pickup/language/'.$domain.'-en_US.mo');
		$loaded = load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/language/' );
	}

    require_once( 'classes/class.wc-cp.php' );
}
add_action( 'plugins_loaded', 'wc_cp_init' );

/**
 * Add in WooCommerce payment gateways
 * @param $methods
 * @return array
 */
function add_cash_pickup( $methods ) {
    $methods[] = 'WC_Gateway_Cash_pickup';
    return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'add_cash_pickup' );

/**
 * Add "Donate" link in plugins list page
 *
 * @param $links
 * @param $file
 * @return mixed
 */
function wc_cp_add_donate_link( $links, $file ) {
    if( $file == plugin_basename( __FILE__ ) ) {
        //Settings link
        array_unshift( $links, '<a href="' . site_url() . '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=wc_gateway_cash_pickup" title="' . __( 'Settings', 'wc_cp' ) . '">' . __( 'Settings', 'wc_cp' ) . '</a>' );
        //Donate link
        array_unshift( $links, '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=ilario.tresoldi@gmail.com&currency_code=EUR&item_name=Donation+for+Cash+Pickup" title="' . __( 'Donate', 'wc_pgec' ) . '" target="_blank">' . __( 'Donate', 'wc_cp' ) . '</a>' );
    }

    return $links;
}
add_filter( 'plugin_action_links', 'wc_cp_add_donate_link', 10, 4 );

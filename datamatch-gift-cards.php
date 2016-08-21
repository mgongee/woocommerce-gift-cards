<?php 
/**
 *
 * Plugin Name:       Datamatch Gift Cards
 * Description:       Custom plugin to utilize Datamatch gift API to give discounts on Woocommerce purchases
 * Version:           0.9
 * Author:            mgongee
 * Author URI:        http://jatwps.net
 * Text Domain:       woo-gift-cards
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

/*  This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
*/

require_once 'includes.php';


if ( ! defined( 'WPINC' ) ) {
		die;
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    $pluginRoot = __FILE__;
	$dgc = new DatamatchGiftCardsAdmin($pluginRoot);
} else {
	function datamatch_admin_notice__error() {
		$class = 'notice notice-error';
		$message = __( 'WooCommerce is not active or not installed. Datamatch Gift Cards plugin requires WooCommerce to be active', 'datamatch-text-domain' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); 
	}
	add_action( 'admin_notices', 'datamatch_admin_notice__error' );
}

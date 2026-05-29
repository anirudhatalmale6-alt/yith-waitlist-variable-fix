<?php
/**
 * Plugin Name: YITH Waiting List - Variable Product Fix
 * Plugin URI:  https://github.com/anirudhatalmale6-alt/yith-waitlist-variable-fix
 * Description: Muestra el formulario de lista de espera en productos variables cuando todas las variaciones estan sin stock, sin necesidad de seleccionar ninguna variacion.
 * Version:     1.0.0
 * Author:      Anirudha Talmale
 * Requires PHP: 7.4
 * Requires at least: 5.8
 * WC requires at least: 6.0
 * License:     GPL-2.0-or-later
 *
 * --- THE PROBLEM ---
 *
 * When a variable product has ALL variations out of stock, WooCommerce renders
 * a plain "out of stock" message using woocommerce_out_of_stock_message filter
 * inside a <p> tag with esc_html(). It does NOT call wc_get_stock_html(), which
 * is the hook the YITH Waiting List plugin uses to inject its form.
 *
 * Result: the waiting list form never appears for fully out-of-stock variable
 * products. Users must select a specific variation first, but WooCommerce hides
 * the variation selector when no variations are available.
 *
 * --- THE FIX ---
 *
 * This plugin hooks into woocommerce_single_product_summary (right after the
 * add-to-cart section) and outputs the YITH waiting list form for variable
 * products where all variations are out of stock.
 *
 * The form is displayed automatically on page load without needing to select
 * any variation. The YITH plugin's own can_have_waitlist() check still applies,
 * so excluded products are respected.
 *
 * --- FUTURE UPDATES ---
 *
 * Uses standard WooCommerce hooks and the YITH [ywcwtl_form] shortcode.
 * Survives updates to both WooCommerce and YITH Waiting List.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_Waitlist_Variable_Fix' ) ) {

	final class YITH_Waitlist_Variable_Fix {

		private static $instance = null;

		public static function init() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		private function __construct() {
			// Priority 31 = right after woocommerce_template_single_add_to_cart (priority 30).
			add_action( 'woocommerce_single_product_summary', array( $this, 'show_waitlist_for_variable' ), 31 );
		}

		/**
		 * Show the waiting list form for variable products where all variations
		 * are out of stock.
		 */
		public function show_waitlist_for_variable() {
			global $product;

			if ( ! $product || ! $product->is_type( 'variable' ) ) {
				return;
			}

			if ( $product->is_in_stock() ) {
				return;
			}

			if ( ! defined( 'YITH_WCWTL' ) ) {
				return;
			}

			echo do_shortcode( '[ywcwtl_form product_id="' . $product->get_id() . '"]' );
		}
	}

	add_action( 'plugins_loaded', array( 'YITH_Waitlist_Variable_Fix', 'init' ), 20 );
}

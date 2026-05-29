<?php
/**
 * Plugin Name: YITH Waiting List - Variable Product Fix
 * Plugin URI:  https://github.com/anirudhatalmale6-alt/yith-waitlist-variable-fix
 * Description: Muestra el formulario de lista de espera en productos variables cuando todas las variaciones estan sin stock, sin necesidad de seleccionar ninguna variacion.
 * Version:     1.1.0
 * Author:      Anirudha Talmale
 * Requires PHP: 7.4
 * Requires at least: 5.8
 * WC requires at least: 6.0
 * License:     GPL-2.0-or-later
 *
 * --- THE PROBLEM ---
 *
 * When a variable product has ALL variations out of stock, WooCommerce does not
 * call wc_get_stock_html() for the parent product, which is the hook the YITH
 * Waiting List plugin uses to inject its form. The form only appears when a
 * specific variation is selected.
 *
 * --- THE FIX ---
 *
 * This plugin:
 * 1. Injects the waiting list form inside the variation area (same position as
 *    when a variation is selected) so it appears immediately on page load.
 * 2. Hides the quantity selector and add-to-cart button.
 * 3. Automatically hides the form when a variation IS selected (to avoid
 *    duplicate forms), and shows it again when the selection is cleared.
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
			add_action( 'woocommerce_before_single_variation', array( $this, 'show_waitlist_for_variable' ), 5 );
		}

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

			$form = do_shortcode( '[ywcwtl_form product_id="' . $product->get_id() . '"]' );
			if ( empty( $form ) ) {
				return;
			}

			// CSS: hide qty + add-to-cart since all variations are out of stock.
			echo '<style>
				form.variations_form .woocommerce-variation-add-to-cart {
					display: none !important;
				}
			</style>';

			// The form wrapper with a specific ID so JS can toggle it.
			echo '<div id="yith-wcwtl-variable-waitlist">' . $form . '</div>';

			// JS: hide this form when a variation is selected (YITH handles per-variation),
			// show it again when the selection is cleared.
			echo '<script>
				jQuery(function($) {
					var $form = $("form.variations_form");
					var $waitlist = $("#yith-wcwtl-variable-waitlist");
					var $addToCart = $form.find(".woocommerce-variation-add-to-cart");

					$form.on("show_variation", function() {
						$waitlist.hide();
						$addToCart.css("display", "");
					});

					$form.on("hide_variation reset_data", function() {
						$waitlist.show();
						$addToCart.hide();
					});
				});
			</script>';
		}
	}

	add_action( 'plugins_loaded', array( 'YITH_Waitlist_Variable_Fix', 'init' ), 20 );
}

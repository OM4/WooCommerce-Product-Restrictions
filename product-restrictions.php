<?php
/*
Plugin Name: WooCommerce Product Restrictions
Plugin URI: https://github.com/OM4/woocommerce-product-restrictions/
Description: Implement mixed dozens using WooCommerce
Version: 1.0-dev
Author: OM4
Author URI: https://om4.com.au/plugins/
Git URI: https://github.com/OM4/woocommerce-product-restrictions
Git Branch: release
*/

/*
Copyright 2012-2015 OM4 (email: info@om4.com.au    web: https://om4.com.au/)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

load_plugin_textdomain( 'woocommerce-product-restrictions', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) );

function init_woocommerce_variation_restrictions() {

	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	class WC_Variation_Restrictions {

		private $version = '0.1';

		private $db_version = '1';

		private $admin;

		public $default_message;

		private $multiple_ofs = array();

		private $product_variation_quantities = array();

		private $cart_must_be_multiple_of;

		private $can_checkout = true;


		/**
		 * Constructor
		 */
		public function __construct() {

			if ( is_admin() ) {
				require_once( 'includes/WC_Variation_Restrictions_Admin.php' );
				$this->admin = new WC_Variation_Restrictions_Admin( $this );
			}

			add_action( 'woocommerce_check_cart_items', array( $this, 'CheckCart' ) );

			$this->default_message = __( 'The following product(s) must be ordered in groups of %mod%. Please add another %gap% eligible product(s) to continue. %productlist%', 'woocommerce-product-restrictions' );

		}

		/**
		 * Load the restrictions
		 */
		private function LoadRestrictions() {

			global $wpdb;

			$restrictions = $wpdb->get_results( "SELECT * FROM {$wpdb->woocommerce_termmeta} WHERE meta_key='multiple_of'");

			if ( is_array($restrictions) ) {
				foreach ( $restrictions as $restriction ) {

					$this->multiple_ofs[ $restriction->woocommerce_term_id ] = intval( $restriction->meta_value );

					if ( !$this->multiple_ofs[ $restriction->woocommerce_term_id ] )
						unset( $this->multiple_ofs[ $restriction->woocommerce_term_id ] );

				}

			}

			$this->cart_must_be_multiple_of = intval ( get_option( 'woocommerce_cart_multiple_of', 0 ) );

		}

		/**
		 * Get the "multiple of" setting for the specified attribute term
		 *
		 * @param int $term_id
		 *
		 * @return int|null
		 */
		public function GetMultipleOf( $term_id ) {
			global $wpdb;

			$multiple = intval( $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->woocommerce_termmeta} WHERE woocommerce_term_id=%d AND meta_key='multiple_of'", $term_id ) ) );

			return $multiple > 0 ? $multiple : null;
		}

		/**
		 * Set the "multiple of" setting for the specified attribute term.
		 *
		 * @param $term_id
		 * @param int|null $multiple_of For no restriction, specify null.
		 */
		public function SetMultipleOf( $term_id, $multiple_of ) {
			global $wpdb;

			$term_id = absint($term_id);

			if ( is_null($multiple_of) ) {
				$wpdb->delete( $wpdb->woocommerce_termmeta, array( 'meta_key' => 'multiple_of', 'woocommerce_term_id' => $term_id ) );
			} else {
				$multiple_of = intval($multiple_of);
				$result = $wpdb->update( $wpdb->woocommerce_termmeta, array( 'meta_value' => $multiple_of	), array( 'woocommerce_term_id' => $term_id, 'meta_key' => 'multiple_of' ) );
				if ( !$result ) {
					$wpdb->insert( $wpdb->woocommerce_termmeta, array( 'meta_key' => 'multiple_of', 'meta_value' => $multiple_of, 'woocommerce_term_id' => $term_id ) );
				}
			}
		}

		/**
		 * Get the "checkout message" setting, which is displayed during checkout.
		 *
		 * @return mixed|void
		 */
		public function GetMultipleOfMessage() {
			return get_option( 'woocommerce_multiple_of_message', $this->default_message );
		}


		/**
		 * Check the customer's cart, and notify them if their cart contents don't match the product groups rules.
		 *
		 * Executed during the 'woocommerce_check_cart_items' hook
		 */
		public function CheckCart() {

			$this->LoadRestrictions();

			if ( $this->cart_must_be_multiple_of > 0 ) {
				// Entire cart contents must be a multiple of x. This takes precedence over all other product group rules
				$modulus = WC()->cart->cart_contents_count % $this->cart_must_be_multiple_of;

				if ( $modulus != 0 ) {

					$message = $this->GetMultipleOfMessage();

					$message = str_replace('%gap%', $this->cart_must_be_multiple_of - $modulus, $message);
					$message = str_replace('%mod%', $this->cart_must_be_multiple_of, $message);
					$message = str_replace('%productlist%', '', $message);
					wc_add_notice( $message, 'error' );
					$this->can_checkout = false;
					return;

				}
			}

			foreach ( WC()->cart->get_cart() as $cart_item_id => $cart_item ) {

				if ( isset($cart_item['data']) && $cart_item['data'] instanceof WC_Product_Variation && is_array( $cart_item['variation'] ) ) {

					foreach ( $cart_item['variation'] as $variation_name => $variation_value ) {
						if ( ! $variation_value ) continue;
						if ( taxonomy_exists( esc_attr( str_replace( 'attribute_', '', $variation_name ) ) ) ) {
							$term = get_term_by( 'slug', $variation_value, esc_attr( str_replace( 'attribute_', '', $variation_name ) ) );
							if ( !$term )
								continue;
							if ( isset($this->multiple_ofs[ $term->term_id ]) ) {
//								echo $term->term_id . ' restricted to ' . $this->multiple_ofs[ $term->term_id ];
//								echo '<br />';
								if ( isset( $this->product_variation_quantities[ $term->term_id ]['qty'] ) ) {
									$this->product_variation_quantities[ $term->term_id ]['qty'] += $cart_item['quantity'];
								} else {
									$this->product_variation_quantities[ $term->term_id ]['qty'] = $cart_item['quantity'];
								}
								$this->product_variation_quantities[ $term->term_id ]['products'][ $cart_item['product_id'] ] = true;
							}
						}
					}
				}
			}

			foreach ( $this->product_variation_quantities as $term_id => $data ) {

				$quantity = $data['qty'];

				$groupof = $this->multiple_ofs[ $term_id ];
				$modulus = $quantity % $groupof;
				if ( $modulus != 0 ) {

					$message = $this->GetMultipleOfMessage();

					$message = str_replace('%gap%', $groupof - $modulus, $message);
					$message = str_replace('%mod%', $groupof, $message);

					$productlist = '<ul>';

					foreach ( array_keys($data['products']) as $product_id ) {

						// get_product() exists in WooCommerce 2.0+ only
						$product = wc_get_product( $product_id );

						$product_name = '';
						$variation_info = '';

						foreach ( WC()->cart->get_cart() as $cart_item ) {
							if ( $cart_item['product_id'] == $product_id ) {
								$product_name = $product->get_title();
								$variation_info = WC()->cart->get_item_data( $cart_item, true );
								break;
							}
						}

						$productlist .= '<li>' . sprintf( __( '<a href="%1$s">%2$s</a> <small>%3$s</small>', 'woocommerce-product-restrictions' ), get_permalink($product_id), $product_name, $variation_info ) . '</li>';

					}

					$productlist .= '</ul>';

					$message = str_replace('%productlist%', $productlist, $message);

					wc_add_notice( $message, 'error' );

					$this->can_checkout = false;

				}
			}

		}

	}

	global $WC_Variation_Restrictions;
	$WC_Variation_Restrictions = new WC_Variation_Restrictions();


}
add_action( 'plugins_loaded', 'init_woocommerce_variation_restrictions', 0 );
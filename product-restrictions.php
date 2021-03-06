<?php
/*
Plugin Name: WooCommerce Product Restrictions
Plugin URI: https://github.com/OM4/woocommerce-product-restrictions/
Description: Implement mixed dozens using WooCommerce.
Version: 2.3
Author: OM4
Author URI: https://om4.com.au/plugins/
Git URI: https://github.com/OM4/woocommerce-product-restrictions
Git Branch: release
WC requires at least: 2.6.0
WC tested up to: 3.6.4
*/

/*
Copyright 2012-2017 OM4 (email: plugins@om4.com.au    web: https://om4.com.au/plugins/)

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

//load_plugin_textdomain( 'woocommerce-product-restrictions', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) );

function init_woocommerce_product_restrictions() {

	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	class WC_Product_Restrictions {

		private $db_version = '1';

		private $admin;

		public $default_message;

		private $multiple_ofs_attributes = array();

		private $multiple_ofs_categories = array();

		private $product_variation_quantities = array();

		private $product_category_quantities = array();

		private $cart_must_be_multiple_of = 0;

		private $can_checkout = true;

		private $restrictions_loaded = false;

		/**
		 * @var WC_Product_Restrictions The single instance of the class.
		 * @since 2.0
		 */
		protected static $_instance = null;

		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Constructor
		 */
		public function __construct() {

			if ( is_admin() ) {
				require_once( 'includes/WC_Product_Restrictions_Admin.php' );
				$this->admin = new WC_Product_Restrictions_Admin();
			}

			add_action( 'woocommerce_check_cart_items', array( $this, 'CheckCart' ) );

			$this->default_message = __( 'The following product(s) must be ordered in groups of %mod%. Please add another %gap% eligible product(s) to continue. %productlist%', 'woocommerce-product-restrictions' );

		}

		/**
		 * Load the restrictions from the database
		 */
		private function LoadRestrictions() {

			if ( $this->restrictions_loaded ) {
				return;
			}

			// Attribute/Variation Restrictions
			$attribute_taxonomies = wc_get_attribute_taxonomies();
			$taxonomies           = array();
			foreach ( $attribute_taxonomies as $taxonomy ) {
				$taxonomies[] = wc_attribute_taxonomy_name( $taxonomy->attribute_name );
			}
			$restrictions = get_terms(
				array(
					'taxonomy'   => $taxonomies,
					'meta_query' => array(
						array(
							'key'     => 'multiple_of',
							'compare' => 'EXISTS',
						),
					),
					'hide_empty' => false,
				)
			);

			if ( is_array( $restrictions ) ) {
				foreach ( $restrictions as $restriction ) {
					$this->multiple_ofs_attributes[ $restriction->term_id ] = $this->GetMultipleOfForAttribute( $restriction->term_id );
					if ( ! $this->multiple_ofs_attributes[ $restriction->term_id ] ) {
						unset( $this->multiple_ofs_attributes[ $restriction->term_id ] );
					}
				}
			}

			// Category Restrictions
			$restrictions = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'meta_query' => array(
						array(
							'key'     => 'cat_multiple_of',
							'compare' => 'EXISTS',
						),
					),
					'hide_empty' => false,
				)
			);

			if ( is_array( $restrictions ) ) {
				foreach ( $restrictions as $restriction ) {
					$this->multiple_ofs_categories[ $restriction->term_id ] = $this->GetMultipleOfForCategory( $restriction->term_id );
					if ( ! $this->multiple_ofs_categories[ $restriction->term_id ] ) {
						unset( $this->multiple_ofs_categories[ $restriction->term_id ] );
					}
				}
			}

			$this->cart_must_be_multiple_of = intval( get_option( 'woocommerce_cart_multiple_of', 0 ) );

			$this->restrictions_loaded = true;

		}

		/**
		 * Mitigate deprecated warning in WC 3.3 and above
		 * wc_get_formatted_cart_item_data() is introduced in WC 3.3
		 *
		 * @param array $cart_item Cart item object.
		 * @param bool  $flat      Should the data be returned flat or in a list.
		 * @return string
		 */
		private function get_cart_item_data( $cart_item, $flat = false ) {
			if ( function_exists( 'wc_get_formatted_cart_item_data' ) ) {
				return wc_get_formatted_cart_item_data( $cart_item, $flat );
			} else {
				return WC()->cart->get_item_data( $cart_item, $flat );
			}
		}

		/**
		 * Get the "multiple of" setting for the specified attribute term
		 *
		 * @param int $term_id
		 *
		 * @return int|null
		 */
		public function GetMultipleOfForAttribute( $term_id ) {

			$multiple = intval( get_term_meta( $term_id, 'multiple_of', true ) );

			return $multiple > 0 ? $multiple : null;
		}

		/**
		 * Get the "multiple of" setting for the specified product category
		 *
		 * @param int $category_id
		 *
		 * @return bool|int The multiple of setting, or false.
		 */
		public function GetMultipleOfForCategory( $category_id ) {
			$restriction = get_term_meta( $category_id, 'cat_multiple_of', true );

			if ( false == $restriction ) {
				return false;
			} else {
				return intval( $restriction );
			}
		}

		/**
		 * Get the Store-wide "multiple of" setting.
		 *
		 * @return int
		 */
		public function GetStorewideMultipleOf() {
			$this->LoadRestrictions();
			return $this->cart_must_be_multiple_of;
		}

		/**
		 * Set the "multiple of" setting for the specified attribute term.
		 *
		 * @param $term_id
		 * @param int|null $multiple_of For no restriction, specify null.
		 */
		public function SetMultipleOfForAttributeTerm( $term_id, $multiple_of ) {

			$term_id = absint( $term_id );

			if ( is_null( $multiple_of ) ) {
				delete_term_meta( $term_id, 'multiple_of' );
			} else {
				$multiple_of = intval( $multiple_of );

				$existing_value = get_term_meta( $term_id, 'multiple_of', true );

				update_term_meta( $term_id, 'multiple_of', $multiple_of, $existing_value );

			}
		}

		public function SetMultipleOfForCategory( $category_id, $multiple_of ) {
			$multiple_of = intval( $multiple_of );
			if ( $multiple_of ) {
				update_term_meta( $category_id, 'cat_multiple_of', $multiple_of );
			} else {
				delete_term_meta( $category_id, 'cat_multiple_of' );
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
		 * Check the customer's cart, and notify them if their cart contents don't match the restrictions.
		 *
		 * Executed during the 'woocommerce_check_cart_items' hook
		 */
		public function CheckCart() {

			$this->LoadRestrictions();

			if ( $this->GetStorewideMultipleOf() > 0 ) {
				// Entire cart contents must be a multiple of x. This takes precedence over all other product group rules

				$modulus = WC()->cart->cart_contents_count % $this->cart_must_be_multiple_of;

				if ( $modulus != 0 ) {

					$message = $this->GetMultipleOfMessage();

					$message = str_replace( '%gap%', $this->cart_must_be_multiple_of - $modulus, $message );
					$message = str_replace( '%mod%', $this->cart_must_be_multiple_of, $message );
					$message = str_replace( '%productlist%', '', $message );
					wc_add_notice( $message, 'error' );
				}
			}

			foreach ( WC()->cart->get_cart() as $cart_item_id => $cart_item ) {

				// Product Attribute/Variation restrictions
				if ( isset( $cart_item['data'] ) && $cart_item['data'] instanceof WC_Product_Variation && is_array( $cart_item['variation'] ) ) {

					foreach ( $cart_item['variation'] as $variation_name => $variation_value ) {
						if ( ! $variation_value ) {
							continue;
						}
						if ( taxonomy_exists( esc_attr( str_replace( 'attribute_', '', $variation_name ) ) ) ) {
							$term = get_term_by( 'slug', $variation_value, esc_attr( str_replace( 'attribute_', '', $variation_name ) ) );
							if ( ! $term ) {
								continue;
							}
							if ( isset( $this->multiple_ofs_attributes[ $term->term_id ] ) ) {
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

				// Product Category restrictions
				$categories = wc_get_product_terms( $cart_item['product_id'], 'product_cat' );

				foreach ( $categories as $category ) {
					$restriction = $this->GetMultipleOfForCategory( $category->term_id );
					if ( $restriction ) {
						if ( isset( $this->product_category_quantities[ $restriction ] ) ) {
							// This product could be assigned to multiple categories (each of which could have matching restrictions)
							// Only count the first one
							if ( ! array_key_exists( $cart_item['product_id'], $this->product_category_quantities[ $restriction ]['products'] ) ) {
								$this->product_category_quantities[ $restriction ]['qty'] += $cart_item['quantity'];
							}
						} else {
							$this->product_category_quantities[ $restriction ]['qty'] = $cart_item['quantity'];
						}
						$this->product_category_quantities[ $restriction ]['products'][ $cart_item['product_id'] ] = true;
					}
				}
			}

			// Check the Attribute/Variation Restrictions
			foreach ( $this->product_variation_quantities as $term_id => $data ) {

				$quantity = $data['qty'];

				$groupof = $this->multiple_ofs_attributes[ $term_id ];
				$modulus = $quantity % $groupof;
				if ( $modulus != 0 ) {

					$message = $this->GetMultipleOfMessage();

					$message = str_replace( '%gap%', $groupof - $modulus, $message );
					$message = str_replace( '%mod%', $groupof, $message );

					$productlist = '<ul>';

					foreach ( array_keys( $data['products'] ) as $product_id ) {

						$product = wc_get_product( $product_id );

						$product_name   = '';
						$variation_info = '';

						foreach ( WC()->cart->get_cart() as $cart_item ) {
							if ( $cart_item['product_id'] == $product_id ) {
								$product_name   = $product->get_title();
								$variation_info = $this->get_cart_item_data( $cart_item, true );
								break;
							}
						}

						$productlist .= '<li>' . sprintf( __( '<a href="%1$s">%2$s</a> <small>%3$s</small>', 'woocommerce-product-restrictions' ), get_permalink( $product_id ), $product_name, $variation_info ) . '</li>';

					}

					$productlist .= '</ul>';

					$message = str_replace( '%productlist%', $productlist, $message );

					wc_add_notice( $message, 'error' );

				}
			}

			// Check the Product Category restrictions
			foreach ( $this->product_category_quantities as $groupof => $data ) {

				$quantity = $data['qty'];

				$modulus = $quantity % $groupof;
				if ( $modulus != 0 ) {

					$message = $this->GetMultipleOfMessage();

					$message = str_replace( '%gap%', $groupof - $modulus, $message );
					$message = str_replace( '%mod%', $groupof, $message );

					$productlist = '<ul>';

					foreach ( array_keys( $data['products'] ) as $product_id ) {

						// get_product() exists in WooCommerce 2.0+ only
						$product = wc_get_product( $product_id );

						$product_name   = '';
						$variation_info = '';

						foreach ( WC()->cart->get_cart() as $cart_item ) {
							if ( $cart_item['product_id'] == $product_id ) {
								$product_name   = $product->get_title();
								$variation_info = $this->get_cart_item_data( $cart_item, true );
								break;
							}
						}

						$productlist .= '<li>' . sprintf( __( '<a href="%1$s">%2$s</a> <small>%3$s</small>', 'woocommerce-product-restrictions' ), get_permalink( $product_id ), $product_name, $variation_info ) . '</li>';

					}

					$productlist .= '</ul>';

					$message = str_replace( '%productlist%', $productlist, $message );

					wc_add_notice( $message, 'error' );

				}
			}

		}
	}

	function WC_Product_Restrictions() {
		return WC_Product_Restrictions::instance();
	}


	$GLOBALS['woocommerce_product_restrictions'] = WC_Product_Restrictions();

}
add_action( 'plugins_loaded', 'init_woocommerce_product_restrictions', 0 );

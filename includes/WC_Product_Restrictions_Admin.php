<?php

/**
 * Dashboard/Admin interface.
 *
 */
class WC_Product_Restrictions_Admin {


	/**
	 * Constructor
	 *
	 * @param WC_Variation_Restrictions $instance
	 */
	public function __construct() {

		add_filter( 'woocommerce_products_general_settings', array( $this, 'Settings' ) );

		// Category restriction settings
		add_action( 'product_cat_edit_form_fields', array( $this, 'EditProductCategory' ) );
		add_action( 'edit_product_cat', array( $this, 'SaveProductCategory' ) );

		// Product attribute/variation restriction setings
		$attribute_taxonomies = wc_get_attribute_taxonomies();
		if ( $attribute_taxonomies ) {
			foreach ( $attribute_taxonomies as $tax ) {
				// For each of WooCommerce's custom product attributes

				$attribute_name = wc_attribute_taxonomy_name( $tax->attribute_name );

				add_action( "{$attribute_name}_edit_form_fields", array( $this, 'EditAttributeTerm' ) );
				add_action( "edit_{$attribute_name}", array( $this, 'SaveAttributeTerm' ), 10, 2 );
			}
		}

	}

	/**
	 * Setting(s)/option(s) to add the to Dashboard, WooCommerce, Settings, Products, General
	 *
	 * @param $inventory_settings
	 *
	 * @return array
	 */
	public function Settings( $inventory_settings ) {

		$inventory_settings[] = array(
			'name' => __( 'Product Group(s)', 'woocommerce-product-restrictions' ),
			'type' => 'title',
			'desc' => __( 'You can use the product groups feature to force customers to purchase products in groups. This is typically used for mixed dozen wine cases.', 'woocommerce-product-restrictions' ),
			'id'   => 'product_group_options',
		);
		$inventory_settings[] = array(
			'name' => __( 'Checkout Message', 'woocommerce-product-restrictions' ),
			'desc' => '<p>The message below will be displayed during checkout if the customer\'s cart contents don\'t match the product group rules.<br /><small>Notes:<br />
				            - The <code>%mod%</code> variable can be used to indicate the "multiple of" number (as specified by the <code>groupof</code> parameter).<br />
				            - The <code>%gap%</code> variable can be used to indicate the number of products that need to be added before the customer can check out.<br />
				            - The <code>%productlist%</code> variable can be used to list the eligibile products</small><br />
				            <small>Example:<br /><code>' . esc_attr( WC_Product_Restrictions()->default_message ) . '</code></small></p>',
			'id'   => 'woocommerce_multiple_of_message',
			'css'  => 'width:100%;',
			'type' => 'textarea',
			'std'  => WC_Product_Restrictions()->default_message,
		);
		$inventory_settings[] = array(
			'title'             => __( 'Store-wide Product Quantity Restriction', 'woocommerce-product-restrictions' ),
			'desc'              => __( 'Use this setting to ensure that the total quantity of all items in an order must be a multiple of this number. For example, enter "12" here if you\'d like to ensure all orders must be a multiple of 12 bottles. Enter Zero (or empty) for no store-wide restriction.', 'woocommerce-product-restrictions' ),
			'id'                => 'woocommerce_cart_multiple_of',
			'css'               => 'width:50px;',
			'default'           => '',
			'type'              => 'number',
			'custom_attributes' => array(
				'min'  => 0,
				'step' => 1,
			),
		);

		$inventory_settings[] = array(
			'type' => 'sectionend',
			'id'   => 'product_group_options',
		);

		return $inventory_settings;

	}


	/**
	 * The Edit Attribute Term Dashboard Screen
	 *
	 * @param $taxonomy
	 */
	public function EditAttributeTerm( $term ) {
		$field = '<input name="multiple_of" id="multiple_of" type="number" min="0" step="1" style="width: 5em;" value="' . esc_attr( WC_Product_Restrictions()->GetMultipleOfForAttribute( $term->term_id ) ) . '" class="small-text" />';
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><?php _e( 'Product Groups', 'woocommerce-product-restrictions' ); ?></th>
			<td>
					<?php printf( __( '<label for="multiple_of">Products variations using this attribute term must be ordered in groups of</label> %s <small>(Leave blank for no restriction)</small>', 'woocommerce-product-restrictions' ), $field ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * When an existing term is saved/updated
	 *
	 * @param $term_id
	 * @param $tt_id
	 */
	public function SaveAttributeTerm( $term_id, $tt_id ) {
		$multiple_of = null;
		if ( isset( $_POST['multiple_of'] ) ) {
			$multiple_of = intval( $_POST['multiple_of'] );
		}

		if ( $multiple_of <= 0 ) {
			$multiple_of = null;
		}

		WC_Product_Restrictions()->SetMultipleOfForAttributeTerm( $term_id, $multiple_of );
	}

	/**
	 * The Product Category Dashboard Screen
	 *
	 * @param $category
	 */
	public function EditProductCategory( $category ) {
		$field = '<input name="multiple_of" id="multiple_of" type="number" min="0" step="1" style="width: 5em;" value="' . esc_attr( WC_Product_Restrictions()->GetMultipleOfForCategory( $category->term_id ) ) . '" class="small-text" />';
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><?php _e( 'Product Groups', 'woocommerce-product-restrictions' ); ?></th>
			<td>
				<?php printf( __( '<label for="multiple_of">Products in this category must be ordered in groups of</label> %s <small>(Leave blank for no restriction)</small>', 'woocommerce-product-restrictions' ), $field ); ?>
			</td>
		</tr>
		<?php
	}


	/**
	 * When an existing product category is saved/updated
	 *
	 * @param $term_id
	 * @param $tt_id
	 */
	public function SaveProductCategory( $term_id ) {
		$multiple_of = false;
		if ( isset( $_POST['multiple_of'] ) ) {
			$multiple_of = intval( $_POST['multiple_of'] );
		}

		if ( $multiple_of <= 0 ) {
			$multiple_of = false;
		}

		WC_Product_Restrictions()->SetMultipleOfForCategory( $term_id, $multiple_of );
	}

	public function pre_update_option_woocommerce_cart_multiple_of_exclusions( $value ) {
		if ( strlen( $value ) ) {
			$values = explode( ',', $value );
			$values = array_map( 'intval', $values );
			// filter our zero/false values
			$values = array_filter( $values );
			$value  = implode( ',', $values );
		}
		return $value;
	}

}

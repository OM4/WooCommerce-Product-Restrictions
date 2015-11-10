<?php

/**
 * Dashboard/Admin interface.
 *
 */
class WC_Variation_Restrictions_Admin {

	/**
	 * @var WC_Variation_Restrictions
	 */
	private $instance;

	/**
	 * Constructor
	 *
	 * @param WC_Variation_Restrictions $instance
	 */
	public function __construct( WC_Variation_Restrictions $instance ) {

		$this->instance = $instance;
		add_filter( 'woocommerce_product_settings', array( $this, 'Settings') );

		$attribute_taxonomies = wc_get_attribute_taxonomies();
		if ( $attribute_taxonomies ) {
			foreach ($attribute_taxonomies as $tax) {
				// For each of WooCommerce's custom product attributes

				$attribute_name = wc_attribute_taxonomy_name( $tax->attribute_name );

				add_action( "{$attribute_name}_edit_form_fields", array( $this, 'EditAttributeTerm') );
				add_action( "edit_{$attribute_name}", array( $this, 'SaveAttributeTerm'), 10, 2 );
			}
		}

	}

	/**
	 * Setting(s)/option(s) to add the to Dashboard, WooCommerce, Settings, Catalog screen
	 *
	 * @param $inventory_settings
	 *
	 * @return array
	 */
	public function Settings( $inventory_settings ) {

		// TODO: add setting for entire cart contents multiple of setting

		$inventory_settings[] = array(
				'name' => __( 'Product Group(s)', 'woocommerce-product-restrictions' ), 'type' => 'title','desc' => __('You can use the product groups feature to force customers to purchase products in groups. This is typically used for mixed dozen wine cases.', 'woocommerce-product-restrictions'), 'id' => 'product_group_options'
		);
		$inventory_settings[] = array(
				'name' => __( 'Checkout Message', 'woocommerce-product-restrictions' ),
				'desc' 		=> '<p>The message above is displayed during checkout if the customer\'s cart contents don\'t match the product group rules.<br /><small>Notes:<br />
				            - The <code>%mod%</code> variable can be used to indicate the "multiple of" number (as specified by the <code>groupof</code> parameter).<br />
				            - The <code>%gap%</code> variable can be used to indicate the number of products that need to be added before the customer can check out.<br />
				            - The <code>%productlist%</code> variable can be used to list the eligibile products</small><br />
				            <small>Example:<br /><code>' . esc_attr($this->instance->default_message) . '</code></small></p>',
				'id' 		=> 'woocommerce_multiple_of_message',
				'css' 		=> 'width:100%;',
				'type' 		=> 'textarea',
				'std' 		=> $this->instance->default_message
			);
		$inventory_settings[] = array(
			'type' => 'sectionend', 'id' => 'product_group_options'
		);

		return $inventory_settings;

	}


	/**
	 * The Edit Attribute Term Dashboard Screen
	 *
	 * @param $taxonomy
	 */
	public function EditAttributeTerm( $term ) {
		$field = '<input name="multiple_of" id="multiple_of" type="number" min="0" step="1" style="width: 5em;" value="' . esc_attr( $this->instance->GetMultipleOf( $term->term_id ) ) . '" class="small-text" />';
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><?php _e('Product Groups', 'woocommerce-product-restrictions'); ?></th>
			<td>
					<?php printf( __('<label for="multiple_of">Products variations using this attribute term must be ordered in groups of</label> %s <small>(Leave blank for no restriction)</small>', 'woocommerce-product-restrictions'), $field); ?>
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
		if ( isset($_POST['multiple_of']) ) {
			$multiple_of = intval($_POST['multiple_of']);
		}

		if ( $multiple_of <= 0 )
			$multiple_of = null;

		$this->instance->SetMultipleOf( $term_id, $multiple_of );
	}

}
=== WooCommerce Product Restrictions ===

Implement mixed dozens using WooCommerce.
Contributors: jamescollins, OM4
Donate link: https://om4.com.au/plugins/#donate
Tags: woocommerce, products, mixed dozen, mixed dozens
Requires at least: 4.0
Tested up to: 4.4
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


== Description ==

Implement mixed dozens using WooCommerce.

- Adds new "Product Groups" setting to Dashboard, WooCommerce, Settings, Products.
- Adds new "Product Groups" setting to Dashboard, Products, Attributes, Configure Terms, Edit.

== Installation ==

1. Install & activate plugin.

1. Dashboard, WooCommerce, Settings, Products, Product Groups. Configure checkout message.

1. Dashboard, Products, Attributes:
    - Add new attribute called "Pricing"

1. Dashboard, Products, Attributes, Pricing, Configure Terms:
    - Add new "Per bottle in mixed case of 12" term
        - Edit term and set Product Groups restriction to 12
        - Add new "Per bottle in mixed case of 6" term
        - Edit term and set Product Groups restriction to 12
    - Add new "Per whole case of 6" term
    - Add new "Per whole case of 12" term

1. Dashboard Products Add Product, and create a new product:
    - Product Type: Variable Product
    - Attributes: Add existing "Pricing" attribute, with "Per bottle in mixed case of 12" and "Per whole case of 12" values. Tick "Used for variations checkbox" and "Visible on the product page" checkbox.
    - Save the Product as a draft. Re-edit the product:
    - Variations:
        - Add Variation button
        - Choose "Per bottle in mixed case of 12" from dropdown box. Enter per bottle price
        - Add Variation button
        - Choose "Per whole case of 12" from dropdown box. Enter whole case price.
        - Make sure "Default selections" is set to "No default Pricing".
    - Save and publish the product.
    - This will create a product that can be ordered in whole cases of 12, or part of a mixed dozen.
Repeat the above steps for remaining products.

== Frequently Asked Questions ==

= How can I get support? =

No support is currently provided.

== Changelog ==

= 1.0 =
* Update code to be compatible with WooCommerce 2.2.x.

= 0.1 =
* Initial Release.

== Upgrade Notice ==

= 1.0 =
WooCommerce 2.2 compatibility.
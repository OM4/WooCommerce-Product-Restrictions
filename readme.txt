=== WooCommerce Product Restrictions ===

Implement mixed dozens using WooCommerce.
Contributors: jamescollins, OM4
Donate link: https://om4.com.au/plugins/#donate
Tags: woocommerce, product, products, mixed dozen, mixed dozens, wine, checkout, variations
Requires at least: 4.5
Tested up to: 5.2.1
Stable tag: 2.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 2.6.0
WC tested up to: 3.6.4


== Description ==

> **This package is no longer supported or maintained.**

Implement mixed dozens using WooCommerce.

- Adds new "Product Groups" settings to Dashboard, WooCommerce, Settings, Products, General.
- Adds new "Product Groups" setting to Dashboard, Products, Attributes, Configure Terms, Edit.
- Adds new "Product Groups" setting to Dashboard, Products, Categories, Edit.

== Installation ==

1. Install & activate plugin.

1. Go to Dashboard, WooCommerce, Settings, Products, General, Product Groups. Configure checkout message that is displayed if any restrictions aren't met.

1. To use a store-wide product quantity restriction:
    1. Configure the restriction using Dashboard, WooCommerce, Settings, Products, General, Product Groups.

1. To use Per-category restrictions:
    1. Go to Dashboard, Products, Categories.
    1. Edit an existing product category and complete the Product Groups setting.

1. To use Product Attribute/Variation restrictions:

    1. Go to Dashboard, Products, Attributes.
    1. Add a new attribute called "Pricing"

    1. Dashboard, Products, Attributes, Pricing, Configure Terms:
        - Add new "Per bottle in mixed case of 12" term
            - Edit term and set Product Groups restriction to 12
            - Add new "Per bottle in mixed case of 6" term
            - Edit term and set Product Groups restriction to 6
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

= 2.3 =
* WordPress 5.2 and WooCommerce 3.6 compatibility.

= 2.2 =
* WooCommerce 3.0 and 3.1 compatibility.

= 2.1 =
* WooCommerce 2.5 and 2.6 compatibility.

= 2.0 =
* Add support for per-category product quantity restrictions.
* Add support for a store-wide quantity restriction.
* On the cart page, display more than one validation message if applicable.

= 1.1 =
* Fix PHP warning
* Update code to be compatible with WooCommerce 2.4.x and WordPress 4.4.x

= 1.0 =
* Update code to be compatible with WooCommerce 2.2.x.

= 0.1 =
* Initial Release.

== Upgrade Notice ==

= 2.0 =
Adds support for site-wide and per-category product restrictions.

= 1.0 =
WooCommerce 2.4 compatibility. WordPress 4.4 compatibility.

= 1.0 =
WooCommerce 2.2 compatibility.

=== WooCommerce - Product Restrictions ===
Contributors: jamescollins
Requires at least: 3.4
Tested up to: 3.5
Stable tag: 1.0

Implement mixed dozens using WooCommerce

== Description ==

Implement mixed dozens using WooCommerce.

﻿- Adds new "Product Groups" setting to Dashboard, WooCommerce, Settings, Catalog.

- Adds new "Product Groups" setting to Dashboard, Products, Attributes, Configure Terms, Edit.

== Configuration ==

﻿Example usage:

Install & activate plugin.

Dashboard, Products, Attributes:
- Add new attribute called "Pricing"

Dashboard, Products, Attributes, Pricing, Configure Terms:
- Add new "Per bottle in mixed case of 12" term
    - Edit term and set Product Groups restriction to 12
- Add new "Per bottle in mixed case of 6" term
    - Edit term and set Product Groups restriction to 12
- Add new "Per whole case of 6" term
- Add new "Per whole case of 12" term

Dashboard Products Add Product, and create a new product:
    - Product Type: Variable Product
    - Attributes: Add existing "Pricing" attribute, with "Per bottle in mixed case of 12" and "Per whole case of 12" values. Tick "Used for variations checkbox" and "Visible on the product page" checkbox.
    - Save the Product as a draft. Re-edit the product:
    - Variations:
        - Add Variation button
        - Choose "Per bottle in mixed case of 12" from dropdown box. Enter per bottle price
        - Add Variation button
        - Choose "Per whole case of 12" from dropdown box. Enter whole case price.
        - Make sure "Default selections" is set to "No default Pricing".
    - Save and publish the product
This will create a product that can be ordered in whole cases of 12, or part of a mixed dozen.
Repeat the above steps for remaining products.
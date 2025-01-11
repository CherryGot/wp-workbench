# Changelog

### 1.7.0

> 11 January 2025

- Feature: sync articles along with sets

### 1.6.0

> 11 January 2025

- Feature: adds mechanism to sync faulty set import
- Fix: generate slugs after decoding title/name
- Fix: sync set after all variants are imported

### 1.5.2

> 28 December 2024

- Fix: fixes logic to assign price fields

### 1.5.1

> 08 December 2024

- Fix: added Spanish translations

### 1.5.0

> 08 December 2024

- Feature: weekly run imports on a chosen day
- Feature: field to choose weekday to run import on
- Fix: reducing batch size to improve site speed

### 1.4.0

> 11 November 2024

- Feature: checkbox to overwrite category relations
- Fix: stops the import if can't find catalog
- Fix: forces explicit transient timeout check
- Feature: replace PadelPoint with AR Padel in desc
- Fix: do not fail if can't set an sku
- Fix: looks for other product stati in queries
- Feature: sets regular & sale prices for products
- Fix: override with pub method, not pvt members

### 1.3.0

> 09 November 2024

- Feature: override elementor product image dyn tag

### 1.2.1

> 22 October 2024

- Fix: specify no. of args for fibosearch filter

### 1.2.0

> 22 October 2024

- Feature: sets to have api images in their gallery
- Feature: sets to have api image thumbnails
- Fix: removes srcset containing placeholder img
- Feature: articles to have api images in gallery
- Feature: articles to have api images as thumbnail

### 1.1.0

> 16 October 2024

- Feature: appends custom order note to API orders
- Feature: creates product post with 'draft' status
- Fix: delete transient if catalog is empty
- Fix: incorrect syntax while calling error_log

### 1.0.2

> 01 October 2024

- Fix: correctly identify uncategorized term
- Fix: category parents not importing issue
- Fix: invalid type breaking edit product page

### 1.0.1

> 27 September 2024

- Fix: changed strict php 8.1 requirements

### 1.0.0

> 24 September 2024

- Feature: added a way to initiate imports manually
- Feature: added a 12h cron for fetching catalog
- Feature: added admin notice to show import status
- Feature: update availibility after order is made
- Fix: map PESO with _weight for articles
- Feature: "Update Availability" button for product
- Feature: added job to check product availability
- Fix: changed how stock updates for variation
- Feature: implemented API function to get catalog
- Fix: show add to cart for custom product types
- Fix: for variation, use parent product type
- Feature: use names instead of codes in address
- Feature: forwards an order to PadelPoint via API
- Feature: added an interface to make API requests
- Feature: settings page to save API credentials
- Feature: enable ACF support for product variation
- Feature: added logic to import a set and variants
- Fix: specify slug only for a new term
- Fix: specify orderby to ovveride default by wc
- Fix: forcing metabox re-render for first time
- Feature: logic to import an article product type
- Feature: new improved logic to import categories
- Feature: registered custom product types

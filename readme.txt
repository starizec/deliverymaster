=== Express Label Maker ===
Contributors: expresslabelmaker
Tags: woocommerce, shipping, label printing, DPD, Overseas
Requires at least: 5.3
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Print shipping labels and track parcels for multiple couriers directly from WooCommerce.

== Description ==

ExpressLabelMaker enhances WooCommerce by adding the ability to print shipping labels and track parcels for multiple courier services directly from your WordPress site. This plugin supports both individual and batch label printing, making it an essential tool for businesses looking to streamline their shipping processes.

Key Features:
- Support for popular courier services such as DPD and Overseas.
- Options for both individual and multi-print functionalities.
- Real-time tracking of parcel statuses to keep both shop owners and customers informed.

== External Services ==

This plugin communicates with several external APIs to provide shipping label printing and parcel tracking functionalities. These external connections include:

- **Express Label Maker API**: For creating shipping labels and collection requests.
  - Endpoint: `https://expresslabelmaker.com/api/v1/`
  - Privacy concerns and terms of use should be referenced at [Express Label Maker Privacy Policy](URL_to_privacy_policy)

- **Overseas API**: Used for obtaining parcel statuses.
  - Endpoint: `https://api.overseas.com/`
  - https://www.dpd.com/hr/en/legal-and-copyright-notice/

- **EasyShip API**: Used for real-time parcel tracking.
  - Endpoint: `https://easyship.hr/api/parcel/parcel_status`
  - https://overseas.hr/hr/info/izjava-o-privatnosti-2

It is important that users of the plugin are aware that their location data may be transmitted to these services to enhance functionality and user experience. All data handling practices are compliant with GDPR and other privacy regulations.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/expres-label-maker` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to menu WooCommerce > ExpressLabelMaker to configure the plugin.

== Frequently Asked Questions ==

= Which courier services are supported? =

Currently, ExpressLabelMaker supports DPD and Overseas. We plan to expand this list in future updates.

= How do I print a label? =

After configuring the plugin, go to the WooCommerce orders page, select the orders, and choose the 'DPD Print Label' or 'Overseas Print Label' option.

= How does tracking work? =

Parcel tracking is integrated directly into the WooCommerce orders page, allowing you to view real-time status updates.

== Screenshots ==

1. The main configuration screen for setting up courier services.
2. Example of printing multiple labels at once.
3. Tracking interface showing the current status of a parcel.

== Changelog ==

= 1.0.0 =
* Initial release: Support for DPD and Overseas, multi-print capabilities, and integrated parcel tracking.

== Upgrade Notice ==

= 1.0.0 =
Initial release. Please update immediately to start enjoying comprehensive label printing and tracking features.

# Elementor Meta CAPI Integration

A lightweight, server-side WordPress plugin designed to seamlessly integrate **Elementor Forms** with **Meta's Conversions API (CAPI)**. By processing form submissions entirely on the server, it bypasses client-side restrictions and ad blockers, ensuring highly accurate conversion data, robust event deduplication, and maximum Event Match Quality (EMQ).

## Features
- **Server-Side Tracking Validation**: Directly transmits data from your server to Meta (Facebook), immune to browser cookie restrictions or ad-blockers.
- **Native Elementor Integration**: Adds a clean "Meta Conversions API" option directly into the "Actions After Submit" menu of Elementor Forms.
- **Robust Event Deduplication**: Effectively manages and pairs `event_id` keys protecting against artificially inflated metrics or duplicate event firing.
- **Database Caching & Resilience**: Implements background database logging for events to aid in debugging, auditing, and fallback transmission.
- **Developer-Friendly & Lean**: Completely avoids bloated client-side JS injections, maintaining a strict and fast server-side processing flow.

## Prerequisites
- WordPress 5.8 or higher
- PHP 7.4 or higher
- **Elementor Pro** (Required for the Form Builder module)
- A Meta (Facebook) Business Manager with an active **Pixel ID** and **Conversions API Access Token**

## Installation

1. Clone or download this repository.
2. If downloaded as a ZIP, upload the ZIP file through your WordPress admin dashboard via `Plugins > Add New > Upload Plugin`. If you cloned it, move the folder into your `/wp-content/plugins/` directory.
3. Activate the plugin through the *Plugins* menu in WordPress.
4. Navigate to **Settings > Elementor Meta CAPI** in the native WordPress admin menu.
5. Provide your **CAPI Access Token** and **Pixel ID**.

## Usage
1. Open up any page or template containing an **Elementor Form**.
2. Select your form to edit its properties.
3. Expand the **Actions After Submit** section.
4. Click the plus icon and select **Meta Conversions API (CAPI)**.
5. A new section labeled *Meta Conversions API* will appear. Expand it and select your desired standard or custom event (e.g., `Lead`, `Purchase`, `CompleteRegistration`).

## Security
This plugin respects and employs strict WordPress core standards, including direct abstraction checks (`ABSPATH`), query parameter binding, input validation (`sanitize_text_field`), and output escaping mechanisms. All tokens and sensitive information are strictly accessed server-side and never injected into client files or inline scripts.

## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

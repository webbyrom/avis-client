Customer Reviews (Token-Verified)
A robust WordPress plugin to collect and display authentic customer reviews. The system uses secure links (HMAC-SHA256) sent via email to ensure each review comes from a real customer.

🚀 Features
Dedicated Custom Post Type: Simplified review management within the dashboard.

HMAC Security: Unique, timestamped, and digitally signed invitation links.

Duplicate Protection: Only one review allowed per link/customer ID.

"Verified Review" Badge: Visual identification for reviews submitted via secure links.

Invitation Interface: Send review requests directly from the admin area.

Flexible Shortcodes: Customizable submission form and display list.

🛠️ Installation
Upload the plugin folder to /wp-content/plugins/.

Activate the plugin via the WordPress Plugins menu.

Create a page (e.g., "Leave a Review") and insert the shortcode [formulaire_avis].

(Optional) For enhanced security, add a secret key to your wp-config.php file:

PHP
define( 'AVIS_TOKEN_SECRET', 'your_very_long_random_phrase' );
📖 Usage
Displaying Reviews

Use the following shortcode to display the list of published reviews:
[liste_avis nombre="5" note_min="4" only_verified="1"]

Available parameters:

nombre: Number of reviews to display (default: 10).

note_min: Filter by minimum rating from 1 to 5 (default: 1).

only_verified: Display only reviews with a verified badge (0 or 1).

Collecting Reviews

Go to Customer Reviews > Send Invitation.

Enter the customer's name, email, and select the page where you placed the form.

The customer will receive a unique link valid for 7 days.

🏗️ Code Structure
webbyrom-avis.php: Main file, constant definitions, and module loading.

includes/cpt.php: Custom Post Type declaration for "Customer Reviews".

includes/token.php: Logic for generating and verifying HMAC signatures.

includes/shortcodes.php: Display logic for the form and the list.

admin/admin-page.php: Administration interface and meta-boxes.

🛡️ Security
The plugin uses multiple layers of protection:

WordPress Nonces for CSRF protection on the form.

Systematic Sanitization and Escaping of data (input/output).

Capability checks (manage_options) for administrative functions.

Token Expiration: Links automatically expire after the duration defined by AVIS_TOKEN_TTL.

Technical Information
Version: 2.1.0

License: GPL-2.0-or-later

Author: Romain Fourel (WebByRom)

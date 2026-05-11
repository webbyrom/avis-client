<?php

/**
 * Plugin Name: Avis Client
 * Plugin URI:  https://www.web-byrom.com
 * Description: Système d'avis client sécurisé — token HMAC, shortcodes, badge vérifié.
 * Version:     2.1.0
 * Author:      Romain Fourel
 * Author URI:  https://www.web-byrom.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: avis-client
 */

if (! defined('ABSPATH')) {
  exit;
}

// ── Constantes ────────────────────────────────────────────────

/**
 * Clé secrète HMAC.
 * Priorité : wp-config.php > option BDD > mot de passe aléatoire auto-généré.
 *
 * Bonne pratique multi-site : ajouter dans wp-config.php :
 *   define( 'AVIS_TOKEN_SECRET', 'votre_phrase_ultra_secrete' );
 */
if (! defined('AVIS_TOKEN_SECRET')) {
  $avis_secret = get_option('avis_client_secret');
  if (! $avis_secret) {
    $avis_secret = wp_generate_password(64, true, true);
    update_option('avis_client_secret', $avis_secret, false);
  }
  define('AVIS_TOKEN_SECRET', $avis_secret);
}

/** Durée de validité du lien (défaut : 7 jours). */
if (! defined('AVIS_TOKEN_TTL')) {
  define('AVIS_TOKEN_TTL', 7 * DAY_IN_SECONDS);
}

define('AVIS_NOTE_MIN', 1);
define('AVIS_NOTE_MAX', 5);
define('AVIS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AVIS_PLUGIN_URL', plugin_dir_url(__FILE__));

// ── Chargement des modules ────────────────────────────────────

require_once AVIS_PLUGIN_DIR . 'includes/cpt.php';
require_once AVIS_PLUGIN_DIR . 'includes/token.php';
require_once AVIS_PLUGIN_DIR . 'includes/shortcodes.php';

if (is_admin()) {
  require_once AVIS_PLUGIN_DIR . 'admin/admin-page.php';
}

// ── CSS front-end ─────────────────────────────────────────────

add_action('wp_enqueue_scripts', function (): void {
  wp_enqueue_style(
    'avis-client',
    AVIS_PLUGIN_URL . 'assets/avis-client.css',
    [],
    '2.1.0'
  );
});
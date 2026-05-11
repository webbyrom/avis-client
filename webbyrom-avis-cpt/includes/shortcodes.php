<?php

/**
 * Shortcodes front-end :
 *   [formulaire_avis]  — formulaire de dépôt d'avis (lien signé requis)
 *   [liste_avis]       — affichage public des avis publiés
 *
 * @package AvisClient
 */

if (! defined('ABSPATH')) {
  exit;
}

// ─────────────────────────────────────────────────────────────
// [formulaire_avis]
// ─────────────────────────────────────────────────────────────

add_shortcode('formulaire_avis', 'avis_shortcode_formulaire');

function avis_shortcode_formulaire(): string
{

  // 1. Récupération et assainissement des paramètres URL
  $client_id = isset($_GET['cid']) ? sanitize_text_field(wp_unslash($_GET['cid'])) : '';
  $token     = isset($_GET['tkn']) ? sanitize_text_field(wp_unslash($_GET['tkn'])) : '';
  $timestamp = isset($_GET['ts']) ? absint($_GET['ts'])                              : 0;

  // 2. Lien absent
  if (! $client_id || ! $token || ! $timestamp) {
    return '<p class="avis-error">'
      . esc_html__('Désolé, vous ne pouvez pas accéder à ce formulaire sans lien d\'invitation.', 'avis-client')
      . '</p>';
  }

  // 3. Vérification du token (signature + expiration)
  if (! avis_verifier_token($client_id, $token, $timestamp)) {
    return '<p class="avis-error">'
      . esc_html__('Ce lien est invalide ou a expiré. Veuillez demander un nouveau lien.', 'avis-client')
      . '</p>';
  }

  // 4. Anti-doublon : un seul avis par client_id
  $deja_soumis = get_posts([
    'post_type'   => 'avis_client',
    'post_status' => ['pending', 'publish'],
    'meta_query'  => [['key' => '_avis_client_id', 'value' => $client_id]],
    'numberposts' => 1,
    'fields'      => 'ids',
  ]);
  if ($deja_soumis) {
    return '<p class="avis-info">'
      . esc_html__('Nous avons déjà reçu votre avis. Merci pour votre retour !', 'avis-client')
      . '</p>';
  }

  // 5. Traitement POST
  if (
    'POST' === $_SERVER['REQUEST_METHOD'] &&
    isset($_POST['avis_nonce']) &&
    wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['avis_nonce'])), 'avis_client_submit')
  ) {
    $post_cid   = sanitize_text_field(wp_unslash($_POST['cid'] ?? ''));
    $post_token = sanitize_text_field(wp_unslash($_POST['tkn'] ?? ''));
    $post_ts    = absint($_POST['ts'] ?? 0);

    // Re-vérification côté POST
    if (! avis_verifier_token($post_cid, $post_token, $post_ts)) {
      return '<p class="avis-error">'
        . esc_html__('Erreur de sécurité. Veuillez réessayer.', 'avis-client')
        . '</p>';
    }

    $note        = min(AVIS_NOTE_MAX, max(AVIS_NOTE_MIN, intval($_POST['note'] ?? 5)));
    $commentaire = sanitize_textarea_field(wp_unslash($_POST['commentaire'] ?? ''));
    $nom         = sanitize_text_field(wp_unslash($_POST['nom'] ?? ''));

    if (empty($nom) || empty($commentaire)) {
      return '<p class="avis-error">'
        . esc_html__('Merci de remplir tous les champs obligatoires.', 'avis-client')
        . '</p>';
    }

    $post_id = wp_insert_post([
      'post_title'   => sprintf('Avis de %s (%s)', $nom, current_time('d/m/Y')),
      'post_content' => $commentaire,
      'post_status'  => 'pending',
      'post_type'    => 'avis_client',
    ]);

    if (is_wp_error($post_id) || ! $post_id) {
      return '<p class="avis-error">'
        . esc_html__('Une erreur est survenue. Veuillez réessayer.', 'avis-client')
        . '</p>';
    }

    update_post_meta($post_id, '_avis_note',      $note);
    update_post_meta($post_id, '_avis_client_id', $post_cid);
    update_post_meta($post_id, '_avis_nom',        $nom);
    // Marquer comme vérifié : le token HMAC a été validé, le client est authentique
    update_post_meta($post_id, '_avis_verifie',   '1');

    return '<div class="avis-success"><h3>'
      . esc_html__('Merci !', 'avis-client')
      . '</h3><p>'
      . esc_html__('Votre avis a été soumis et est en attente de modération.', 'avis-client')
      . '</p></div>';
  }

  // 6. Affichage du formulaire
  ob_start(); ?>
  <div class="avis-form-container">
    <form method="POST">
      <?php wp_nonce_field('avis_client_submit', 'avis_nonce'); ?>
      <input type="hidden" name="cid" value="<?php echo esc_attr($client_id); ?>">
      <input type="hidden" name="tkn" value="<?php echo esc_attr($token); ?>">
      <input type="hidden" name="ts" value="<?php echo esc_attr($timestamp); ?>">

      <p>
        <label for="avis-nom"><?php esc_html_e('Votre nom ou pseudo *', 'avis-client'); ?></label>
        <input type="text" id="avis-nom" name="nom" required autocomplete="name">
      </p>

      <p>
        <label for="avis-note"><?php esc_html_e('Votre note *', 'avis-client'); ?></label>
        <select id="avis-note" name="note">
          <option value="5">⭐⭐⭐⭐⭐ <?php esc_html_e('Excellent',  'avis-client'); ?></option>
          <option value="4">⭐⭐⭐⭐ <?php esc_html_e('Très bien', 'avis-client'); ?></option>
          <option value="3">⭐⭐⭐ <?php esc_html_e('Bien',      'avis-client'); ?></option>
          <option value="2">⭐⭐ <?php esc_html_e('Moyen',     'avis-client'); ?></option>
          <option value="1">⭐ <?php esc_html_e('Déçu',      'avis-client'); ?></option>
        </select>
      </p>

      <p>
        <label for="avis-commentaire"><?php esc_html_e('Votre retour d\'expérience *', 'avis-client'); ?></label>
        <textarea id="avis-commentaire" name="commentaire" required rows="5"></textarea>
      </p>

      <button type="submit"><?php esc_html_e('Valider mon avis', 'avis-client'); ?></button>
    </form>
  </div>
<?php
  return ob_get_clean();
}

// ─────────────────────────────────────────────────────────────
// [liste_avis]
// ─────────────────────────────────────────────────────────────

/**
 * Affiche les avis publiés.
 *
 * Attributs disponibles :
 *   nombre         (int)  Nombre d'avis à afficher.   Défaut : 10.
 *   note_min       (int)  Note minimale filtrée (1-5). Défaut : 1.
 *   only_verified  (0|1)  Uniquement les avis vérifiés. Défaut : 0.
 *
 * Exemples :
 *   [liste_avis]
 *   [liste_avis nombre="5" note_min="4"]
 *   [liste_avis only_verified="1"]
 */
add_shortcode('liste_avis', 'avis_shortcode_liste');

function avis_shortcode_liste(array $atts): string
{
  $atts = shortcode_atts([
    'nombre'        => 10,
    'note_min'      => 1,
    'only_verified' => 0,
  ], $atts, 'liste_avis');

  $nombre        = max(1, intval($atts['nombre']));
  $note_min      = min(AVIS_NOTE_MAX, max(AVIS_NOTE_MIN, intval($atts['note_min'])));
  $only_verified = ! empty($atts['only_verified']);

  $meta_query = [
    [
      'key'     => '_avis_note',
      'value'   => $note_min,
      'type'    => 'NUMERIC',
      'compare' => '>=',
    ],
  ];

  if ($only_verified) {
    $meta_query[] = [
      'key'   => '_avis_verifie',
      'value' => '1',
    ];
  }

  $avis_list = get_posts([
    'post_type'   => 'avis_client',
    'post_status' => 'publish',
    'numberposts' => $nombre,
    'orderby'     => 'date',
    'order'       => 'DESC',
    'meta_query'  => $meta_query,
  ]);

  if (empty($avis_list)) {
    return '<p class="avis-aucun">'
      . esc_html__('Aucun avis pour le moment.', 'avis-client')
      . '</p>';
  }

  // Calcul de la note moyenne
  $somme = 0;
  foreach ($avis_list as $avis) {
    $somme += (int) get_post_meta($avis->ID, '_avis_note', true);
  }
  $moyenne = round($somme / count($avis_list), 1);

  ob_start(); ?>
  <div class="avis-liste-wrapper">

    <div class="avis-resume">
      <span class="avis-resume__moyenne"><?php echo esc_html($moyenne); ?></span>
      <span class="avis-resume__etoiles"><?php echo avis_etoiles_html($moyenne); ?></span>
      <span class="avis-resume__total">
        <?php printf(
          esc_html(_n('%d avis', '%d avis', count($avis_list), 'avis-client')),
          count($avis_list)
        ); ?>
      </span>
    </div>

    <div class="avis-liste">
      <?php foreach ($avis_list as $avis) :
        $note    = (int) get_post_meta($avis->ID, '_avis_note',    true);
        $nom     = get_post_meta($avis->ID, '_avis_nom',       true) ?: get_the_title($avis);
        $verifie = get_post_meta($avis->ID, '_avis_verifie',   true);
        $date    = get_the_date('d/m/Y', $avis);
        $contenu = get_the_content(null, false, $avis);
      ?>
        <div class="avis-carte">
          <div class="avis-carte__header">
            <strong class="avis-carte__nom"><?php echo esc_html($nom); ?></strong>
            <span class="avis-carte__etoiles"><?php echo avis_etoiles_html($note); ?></span>
            <?php if ($verifie) : ?>
              <span class="avis-badge-verifie"
                title="<?php esc_attr_e('Cet avis provient d\'un client ayant reçu un lien sécurisé unique.', 'avis-client'); ?>">
                ✔ <?php esc_html_e('Avis vérifié', 'avis-client'); ?>
              </span>
            <?php endif; ?>
            <span class="avis-carte__date"><?php echo esc_html($date); ?></span>
          </div>
          <div class="avis-carte__contenu">
            <?php echo wp_kses_post(wpautop($contenu)); ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  </div>
<?php
  return ob_get_clean();
}

/**
 * Génère le HTML des étoiles (pleines / demi / vides) pour une note donnée.
 *
 * @param  float $note  Note entre 1 et 5.
 * @return string       HTML sécurisé.
 */
function avis_etoiles_html(float $note): string
{
  $html = '<span class="avis-etoiles" aria-label="' . esc_attr($note) . ' sur 5">';
  for ($i = 1; $i <= 5; $i++) {
    if ($note >= $i) {
      $html .= '<span class="etoile pleine" aria-hidden="true">★</span>';
    } elseif ($note >= $i - 0.5) {
      $html .= '<span class="etoile demi"   aria-hidden="true">⯨</span>';
    } else {
      $html .= '<span class="etoile vide"   aria-hidden="true">☆</span>';
    }
  }
  $html .= '</span>';
  return $html;
}

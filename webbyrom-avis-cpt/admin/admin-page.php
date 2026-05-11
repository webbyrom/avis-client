<?php

/**
 * Interface d'administration :
 *   - Méta-box "Détails de l'avis" dans l'éditeur du CPT
 *   - Sous-page "Envoyer une invitation" dans le menu Avis Clients
 *
 * @package AvisClient
 */

if (! defined('ABSPATH')) {
  exit;
}

// ─────────────────────────────────────────────────────────────
// Méta-box — détails de l'avis
// ─────────────────────────────────────────────────────────────

add_action('add_meta_boxes', function (): void {
  add_meta_box(
    'avis_details',
    __('Détails de l\'avis', 'avis-client'),
    'avis_metabox_render',
    'avis_client',
    'side',
    'high'
  );
});

function avis_metabox_render(WP_Post $post): void
{
  $note      = (int) get_post_meta($post->ID, '_avis_note',      true);
  $client_id =       get_post_meta($post->ID, '_avis_client_id', true);
  $nom       =       get_post_meta($post->ID, '_avis_nom',       true);
  $verifie   =       get_post_meta($post->ID, '_avis_verifie',   true);

  echo '<table style="width:100%;border-collapse:collapse;font-size:13px">';

  echo '<tr><th style="text-align:left;padding:4px 0;color:#555">' . esc_html__('Nom', 'avis-client')       . '</th>';
  echo     '<td style="padding:4px 0">'                            . esc_html($nom)                         . '</td></tr>';

  echo '<tr><th style="text-align:left;padding:4px 0;color:#555">' . esc_html__('Client ID', 'avis-client') . '</th>';
  echo     '<td style="padding:4px 0;word-break:break-all">'       . esc_html($client_id)                   . '</td></tr>';

  echo '<tr><th style="text-align:left;padding:4px 0;color:#555">' . esc_html__('Note', 'avis-client')      . '</th>';
  echo     '<td style="padding:4px 0">'                            . str_repeat('⭐', $note) . ' (' . esc_html($note) . '/5)</td></tr>';

  echo '</table>';

  if ($verifie) {
    echo '<p style="margin:10px 0 0"><span style="background:#e6f4ea;color:#1a7335;border:1px solid #a8d5b5;border-radius:20px;font-size:11px;font-weight:600;padding:2px 10px;">✔ '
      . esc_html__('Avis vérifié', 'avis-client')
      . '</span></p>';
  } else {
    echo '<p style="margin:10px 0 0;color:#888;font-size:12px;font-style:italic">'
      . esc_html__('Non vérifié (ajout manuel ou lien expiré)', 'avis-client')
      . '</p>';
  }
}

// ─────────────────────────────────────────────────────────────
// Sous-page — envoyer une invitation par e-mail
// ─────────────────────────────────────────────────────────────

add_action('admin_menu', function (): void {
  add_submenu_page(
    'edit.php?post_type=avis_client',
    __('Envoyer une invitation', 'avis-client'),
    __('Envoyer une invitation', 'avis-client'),
    'manage_options',
    'avis-client-send',
    'avis_render_send_page'
  );
});

function avis_render_send_page(): void
{
  $notice = '';

  if (
    isset($_POST['avis_send_action']) &&
    check_admin_referer('avis_send_invite_action')
  ) {
    $email   = sanitize_email($_POST['client_email'] ?? '');
    $nom     = sanitize_text_field(wp_unslash($_POST['client_nom'] ?? ''));
    $page_id = absint($_POST['target_page'] ?? 0);

    if (is_email($email) && ! empty($nom) && $page_id > 0) {

      $url = avis_construire_url($email, $page_id);

      $sujet = sprintf(
        /* translators: %s: nom du site */
        __('Votre avis nous intéresse — %s', 'avis-client'),
        get_bloginfo('name')
      );

      $corps = sprintf(
        /* translators: 1: prénom client, 2: URL signée, 3: nom du site */
        __("Bonjour %1\$s,\n\nMerci de votre confiance.\nPourriez-vous prendre un instant pour nous laisser un avis via ce lien sécurisé ?\n\n%2\$s\n\nCe lien est valable 7 jours et ne peut être utilisé qu'une seule fois.\n\nCordialement,\nL'équipe de %3\$s", 'avis-client'),
        $nom,
        $url,
        get_bloginfo('name')
      );

      if (wp_mail($email, $sujet, $corps)) {
        $notice = '<div class="notice notice-success is-dismissible"><p>'
          . esc_html__('Invitation envoyée avec succès !', 'avis-client')
          . '</p></div>';
      } else {
        $notice = '<div class="notice notice-error is-dismissible"><p>'
          . esc_html__('L\'envoi a échoué. Vérifiez votre configuration SMTP.', 'avis-client')
          . '</p></div>';
      }
    } else {
      $notice = '<div class="notice notice-error is-dismissible"><p>'
        . esc_html__('Veuillez remplir tous les champs correctement.', 'avis-client')
        . '</p></div>';
    }
  }
?>
<div class="wrap">
  <h1><?php esc_html_e('Envoyer une invitation par e-mail', 'avis-client'); ?></h1>
  <?php echo $notice; ?>

  <form method="POST"
    style="max-width:500px;background:#fff;padding:24px;border:1px solid #ccd0d4;margin-top:20px;border-radius:4px">
    <?php wp_nonce_field('avis_send_invite_action'); ?>

    <p>
      <label for="client_nom"><strong><?php esc_html_e('Nom du client :', 'avis-client'); ?></strong></label><br>
      <input type="text" name="client_nom" id="client_nom" class="regular-text" required
        placeholder="<?php esc_attr_e('Ex : Jean Dupont', 'avis-client'); ?>">
    </p>

    <p>
      <label for="client_email"><strong><?php esc_html_e('E-mail du client :', 'avis-client'); ?></strong></label><br>
      <input type="email" name="client_email" id="client_email" class="regular-text" required
        placeholder="client@exemple.com">
    </p>

    <p>
      <label
        for="target_page"><strong><?php esc_html_e('Page contenant [formulaire_avis] :', 'avis-client'); ?></strong></label><br>
      <?php wp_dropdown_pages([
          'name'             => 'target_page',
          'id'               => 'target_page',
          'show_option_none' => __('— Sélectionnez une page —', 'avis-client'),
        ]); ?>
    </p>

    <input type="submit" name="avis_send_action" class="button button-primary"
      value="<?php esc_attr_e('Envoyer l\'invitation', 'avis-client'); ?>">
  </form>

  <p style="margin-top:16px;color:#666;font-size:13px">
    <?php esc_html_e('Le lien envoyé est valable 7 jours et ne peut être utilisé qu\'une seule fois par adresse e-mail.', 'avis-client'); ?>
  </p>
</div>
<?php
}
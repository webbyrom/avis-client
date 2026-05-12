<?php

/**
 * Interface d'administration :
 * - Méta-box "Détails de l'avis" dans l'éditeur du CPT
 * - Sous-page "Envoyer une invitation" dans le menu Avis Clients
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

      // Sujet du mail
      $sujet = sprintf(
        __('%s, votre avis nous intéresse !', 'avis-client'),
        $nom
      );

      // Headers pour activer le format HTML
      $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>'
      ];

      // Corps du mail en HTML (Design moderne et sécurisé)
      $corps = '
      <div style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: auto; border: 1px solid #eee; padding: 20px; border-radius: 8px;">
          <h2 style="color: #2271b1; text-align: center;">' . sprintf(__('Bonjour %s !', 'avis-client'), esc_html($nom)) . '</h2>
          <p>' . sprintf(__('Merci de votre confiance envers <strong>%s</strong>.', 'avis-client'), get_bloginfo('name')) . '</p>
          <p>' . __('Nous espérons que votre expérience a été à la hauteur de vos attentes. Pourriez-vous prendre un instant pour nous laisser votre avis ?', 'avis-client') . '</p>
          
          <div style="margin: 30px 0; text-align: center;">
              <a href="' . esc_url($url) . '" 
                 style="background-color: #2271b1; color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">
                 ' . __('Donner mon avis suite à ma visite', 'avis-client') . '
              </a>
          </div>

          <p style="font-size: 13px; color: #666; text-align: center;">
              ' . __('Ce lien sécurisé est valable 7 jours.', 'avis-client') . '
          </p>
          
          <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
          
          <p style="font-size: 11px; color: #999;">
              ' . __('Si le bouton ci-dessus ne fonctionne pas, copiez et collez l\'adresse suivante dans votre navigateur :', 'avis-client') . '<br>'
        . esc_url($url) . '
          </p>
      </div>';

      if (wp_mail($email, $sujet, $corps, $headers)) {
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

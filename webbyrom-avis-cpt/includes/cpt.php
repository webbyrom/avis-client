<?php

/**
 * Enregistrement du Custom Post Type "avis_client".
 *
 * @package AvisClient
 */

if (! defined('ABSPATH')) {
  exit;
}

add_action('init', 'avis_client_register_cpt');

function avis_client_register_cpt(): void
{
  register_post_type('avis_client', [
    'labels'          => [
      'name'          => __('Avis Clients',       'avis-client'),
      'singular_name' => __('Avis',               'avis-client'),
      'add_new_item'  => __('Ajouter un avis',    'avis-client'),
      'edit_item'     => __('Modifier l\'avis',   'avis-client'),
      'all_items'     => __('Tous les avis',      'avis-client'),
      'search_items'  => __('Rechercher un avis', 'avis-client'),
    ],
    'public'          => false,
    'show_ui'         => true,
    'show_in_menu'    => true,
    'has_archive'     => false,
    'supports'        => ['title', 'editor'],
    'menu_icon'       => 'dashicons-star-filled',
    'capability_type' => 'post',
    'show_in_rest'    => false, // Pas d'exposition via l'API REST
  ]);
}

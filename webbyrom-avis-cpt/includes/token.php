<?php

/**
 * Gestion des tokens HMAC-SHA256 avec horodatage et expiration.
 *
 * @package AvisClient
 */

if (! defined('ABSPATH')) {
  exit;
}

/**
 * Génère un token HMAC-SHA256 signé avec un timestamp.
 *
 * @param  string $client_id  Identifiant unique du client (e-mail, ID commande…).
 * @param  int    $timestamp  Timestamp UNIX — now si 0.
 * @return string             Token hexadécimal de 64 caractères.
 */
function avis_generer_token(string $client_id, int $timestamp = 0): string
{
  if ($timestamp === 0) {
    $timestamp = time();
  }
  $payload = $client_id . '|' . $timestamp;
  return hash_hmac('sha256', $payload, AVIS_TOKEN_SECRET);
}

/**
 * Vérifie un token reçu : signature HMAC + expiration.
 *
 * La comparaison utilise hash_equals() pour éviter les attaques temporelles.
 *
 * @param  string $client_id   Identifiant client reçu.
 * @param  string $token_recu  Token reçu via l'URL.
 * @param  int    $timestamp   Timestamp reçu via l'URL.
 * @return bool                true si valide et non expiré.
 */
function avis_verifier_token(string $client_id, string $token_recu, int $timestamp): bool
{
  if (time() - $timestamp > AVIS_TOKEN_TTL) {
    return false;
  }
  $token_attendu = avis_generer_token($client_id, $timestamp);
  return hash_equals($token_attendu, $token_recu);
}

/**
 * Construit l'URL signée à envoyer au client par e-mail.
 *
 * @param  string $client_id  Identifiant unique du client.
 * @param  int    $page_id    ID WordPress de la page contenant [formulaire_avis].
 * @return string             URL complète avec paramètres signés.
 */
function avis_construire_url(string $client_id, int $page_id): string
{
  $timestamp = time();
  $token     = avis_generer_token($client_id, $timestamp);

  return add_query_arg(
    [
      'cid' => rawurlencode($client_id),
      'tkn' => $token,
      'ts'  => $timestamp,
    ],
    get_permalink($page_id)
  );
}
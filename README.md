Avis Client (Vérifiés par Token)
Un plugin WordPress robuste pour collecter et afficher des avis clients authentiques. Le système utilise des liens sécurisés (HMAC-SHA256) envoyés par e-mail pour garantir que chaque avis provient d'un client réel.

🚀 Fonctionnalités
Custom Post Type dédié : Gestion simplifiée des avis dans le tableau de bord.

Sécurisation HMAC : Liens d'invitation uniques, horodatés et signés numériquement.

Protection anti-doublon : Un seul avis possible par lien/identifiant client.

Badge "Avis Vérifié" : Identification visuelle des avis issus d'un lien sécurisé.

Interface d'envoi d'invitations : Envoyez des demandes d'avis directement depuis l'administration.

Shortcodes flexibles : Formulaire de dépôt et liste d'affichage personnalisable.

🛠️ Installation
Téléchargez le dossier du plugin dans /wp-content/plugins/.

Activez le plugin via le menu Extensions de WordPress.

Créez une page (ex: "Laissez votre avis") et insérez le shortcode [formulaire_avis].

(Optionnel) Pour plus de sécurité, ajoutez une clé secrète dans votre fichier wp-config.php :

PHP
define( 'AVIS_TOKEN_SECRET', 'votre_phrase_tres_longue_et_aleatoire' );
📖 Utilisation
Afficher les avis

Utilisez le shortcode suivant pour afficher la liste des avis publiés :

[liste_avis nombre="5" note_min="4" only_verified="1"]

Paramètres disponibles :

nombre : Nombre d'avis à afficher (défaut : 10).

note_min : Filtrer par note minimale de 1 à 5 (défaut : 1).

only_verified : Afficher uniquement les avis avec badge vérifié (0 ou 1).

Collecter des avis

Allez dans Avis Clients > Envoyer une invitation.

Remplissez le nom, l'e-mail du client et sélectionnez la page où vous avez placé le formulaire.

Le client recevra un lien unique valable 7 jours.

🏗️ Structure du Code
webbyrom-avis.php : Fichier principal, définitions des constantes et chargement des modules.

includes/cpt.php : Déclaration du type de contenu "Avis Clients".

includes/token.php : Logique de génération et vérification des signatures HMAC.

includes/shortcodes.php : Logique d'affichage du formulaire et de la liste.

admin/admin-page.php : Interface d'administration et méta-box.

🛡️ Sécurité
Le plugin utilise plusieurs couches de protection :

Nonces WordPress pour la protection contre les attaques CSRF sur le formulaire.

Sanitisation et Escaping systématiques des données (entrée/sortie).

Vérification de capacité (manage_options) pour les fonctions d'administration.

Expiration des jetons : Les liens expirent automatiquement après le délai défini par AVIS_TOKEN_TTL.

Informations techniques

Version : 2.1.0

Licence : GPL-2.0-or-later

Auteur : Romain Fourel (WebByRom)

# Blog Navira - API backend

API REST du blog Navira, construite avec Symfony 7.4, PHP 8.2+, API Platform et Doctrine. Elle gère les réalisations, les documents, les commentaires, les utilisateurs, l'authentification et l'envoi de courriels.

## Fonctionnalités

- Gestion des réalisations et de leurs contenus associés.
- Gestion des documents, de leurs informations et de leurs images.
- Consultation des réalisations et documents côté client, avec pagination et recherche.
- Création, consultation, modification et suppression de commentaires.
- Authentification par email/mot de passe avec jeton JWT, et connexion par jeton Google.
- Envoi de notifications par courriel et désinscription des abonnés.
- API Platform pour exposer les ressources et générer les outils de documentation de l'API.

Les images et autres fichiers téléversés sont servis depuis `public/uploads/`.

## Prérequis

- PHP 8.2 ou supérieur avec les extensions `ctype` et `iconv`.
- Composer.
- Un serveur de base de données compatible avec la configuration Doctrine. Le fichier `.env` fourni configure MySQL.
- Docker Compose est facultatif; il peut démarrer les services définis dans `compose.yaml`.

## Installation locale

Depuis la racine du projet :

```bash
composer install
```

Configurez ensuite les paramètres propres à votre machine dans `.env.local` (ce fichier surcharge `.env`). Les variables principales sont :

| Variable | Utilité |
| --- | --- |
| `DATABASE_URL` | URL et identifiants de la base configurée pour Doctrine |
| `APP_SECRET` | Secret de l'application Symfony |
| `JWT_SECRET_KEY`, `JWT_PUBLIC_KEY`, `JWT_PASSPHRASE` | Clés et phrase secrète pour signer les jetons JWT |
| `APP_MAIL_PASSWORD`, `SENDER_MAIL` | Envoi de courriels |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | Authentification Google |
| `GOOGLE_API_KEY`, `GOOGLE_AUTH_CONFIG` | Configuration du client Google si ces services sont utilisés |

Ne publiez pas les valeurs réelles de ces variables, les clés privées JWT ou les identifiants de services.

Créez la base de données et appliquez les migrations :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

Si aucune migration n'existe encore pour vos changements de schéma, créez-en une puis appliquez-la :

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

Lancez le serveur de développement avec Symfony CLI :

```bash
symfony server:start
```

Ou, si Symfony CLI n'est pas installé :

```bash
php -S 127.0.0.1:8000 -t public
```

L'API est alors disponible sur `http://127.0.0.1:8000`. Pour connaître toutes les routes actives :

```bash
php bin/console debug:router
```

## Services Docker

Le projet fournit une base PostgreSQL et Mailpit dans `compose.yaml` et `compose.override.yaml` :

```bash
docker compose up -d database mailer
docker compose ps
docker compose down
```

**Important :** la configuration de développement fournie dans `.env` pointe vers MySQL sur le port 3306, tandis que le service `database` de Docker est PostgreSQL. Pour utiliser la base Docker, configurez `DATABASE_URL` dans `.env.local` pour PostgreSQL et vérifiez la compatibilité du schéma. De même, le mailer Symfony est configuré sur un serveur SMTP externe : pour utiliser Mailpit, configurez `MAILER_DSN` localement afin qu'il pointe vers le service `mailer`. Les ports Docker déclarés comme simples numéros sont attribués automatiquement par Docker; `docker compose ps` permet de les consulter.

## Authentification

- `POST /api/login` : connexion avec les identifiants du compte; l'authentificateur retourne un JWT.
- `POST /api/auth/google` : connexion avec un jeton d'identité Google (`idToken` dans le corps JSON).
- Envoyez le jeton obtenu dans l'en-tête `Authorization: Bearer <jeton>` pour les requêtes authentifiées.
- Les routes commençant par `/api/admin` sont réservées au rôle `ROLE_ADMIN`.

## Principales routes

Les opérations API Platform ci-dessous sont servies sous le préfixe `/api`.

| Méthode | Route | Description |
| --- | --- | --- |
| `GET` | `/api/admin/liste_realisation` | Liste d'administration des réalisations |
| `GET` | `/api/client/realisation/liste_realisation` | Liste des réalisations côté client |
| `POST` | `/api/admin/create_realisation` | Création d'une réalisation |
| `POST` | `/api/admin/update_realisation/{id}` | Modification d'une réalisation |
| `DELETE` | `/api/admin/delete_realisation/{id}` | Suppression d'une réalisation |
| `GET` | `/api/admin/show_realisation/{id}` | Détail d'administration d'une réalisation |
| `GET` | `/api/client/detail_realisation/{id}` | Détail client d'une réalisation |
| `GET` | `/api/admin/liste_document` | Liste d'administration des documents |
| `GET` | `/api/client/document/liste_document` | Liste des documents côté client |
| `POST` | `/api/admin/create_document` | Création d'un document |
| `POST` | `/api/admin/update_document/{id}` | Modification d'un document |
| `DELETE` | `/api/admin/delete_document/{id}` | Suppression d'un document |
| `GET` | `/api/admin/show_document/{id}` | Détail d'administration d'un document |
| `GET` | `/api/client/detail_document/{id}` | Détail client d'un document |
| `GET` | `/api/client/commentaire/liste_commentaire/{id_document}` | Commentaires d'un document |
| `POST` | `/api/client/commentaire/create_commentaire` | Ajout d'un commentaire à un document |
| `POST` | `/api/client/commentaire/update_commentaire/{id}` | Modification d'un commentaire de document |
| `DELETE` | `/api/client/commentaire/delete_commentaire/{id}` | Suppression d'un commentaire de document |
| `GET` | `/api/client/commentaire/realisation/liste_commentaire/{id_realisation}` | Commentaires d'une réalisation |
| `POST` | `/api/client/commentaire/realisation/create_commentaire` | Ajout d'un commentaire à une réalisation |
| `POST` | `/api/client/commentaire/realisation/update_commentaire/{id}` | Modification d'un commentaire de réalisation |
| `DELETE` | `/api/client/commentaire/realisation/delete_commentaire/{id}` | Suppression d'un commentaire de réalisation |
| `GET` | `/api/unsubscribe?email=...` | Désinscription des courriels |

Les opérations de création ou de mise à jour de documents et de réalisations attendent un formulaire multipart lorsqu'une image est envoyée. Pour le détail du format attendu par chaque contrôleur et les paramètres de liste, utilisez les contrôleurs correspondants et vérifiez les routes enregistrées avec `debug:router`.

## Commandes utiles

```bash
# Liste de toutes les commandes Symfony disponibles
php bin/console list

# Vérification de la configuration Doctrine et de la base
php bin/console doctrine:database:create
php bin/console doctrine:migrations:status
php bin/console doctrine:migrations:migrate

# Cache
php bin/console cache:clear

# Générer ou mettre à jour le compte administrateur défini par la commande
php bin/console app:generate-super-admin

# Envoyer un courriel de test (nécessite un mailer et SENDER_MAIL configurés)
php bin/console app:send-test-email

# Tests PHPUnit
php bin/phpunit
```

La commande `app:generate-super-admin` utilise actuellement des valeurs de compte définies dans le code. Vérifiez et modifiez ces valeurs avant de l'exécuter dans un environnement partagé ou de production.

## Tests

La suite PHPUnit se trouve dans `tests/`. Lancez-la avec :

```bash
php bin/phpunit
```

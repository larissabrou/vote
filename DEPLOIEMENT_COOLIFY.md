# Déploiement sur Coolify

## 1. Créer la ressource
Coolify → *New Resource* → *Public/Private Repository* → `larissabrou/vote`, branche de déploiement,
**Build Pack : Docker Compose** (`docker-compose.yaml`). Domaine à affecter au service `app` (port 80).

## 2. Variables d'environnement (Coolify)
| Variable | Valeur |
|---|---|
| `APP_KEY` | la clé de l'ancien `.env` (`base64:...`) — indispensable pour garder les données chiffrées |
| `APP_URL` | `https://<votre-domaine>` (sans `/public`) |
| `DB_DATABASE` / `DB_USERNAME` | `admin_vote` |
| `DB_PASSWORD` / `DB_ROOT_PASSWORD` | à choisir |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS` | ceux de l'ancien `.env` |
| `ADMIN_PASSWORD` | idem ancien `.env` |
| `RUN_MIGRATIONS` | `false` jusqu'à l'import (étape 3), puis `true` |

Ne pas définir `ASSET_URL` (l'ancien `/public` n'a plus lieu d'être).

## 3. Importer l'ancienne base (`admin_vote.sql`)
Le dump contient le schéma + la table `migrations` : il faut l'importer **avant** toute migration.
1. Déployer une première fois (le conteneur `db` démarre).
2. Sur le serveur Coolify (SSH), trouver le conteneur : `docker ps | grep db`
3. Importer :
```bash
docker exec -i <conteneur_db> sh -c 'mariadb -u root -p"$MARIADB_ROOT_PASSWORD" admin_vote' < admin_vote.sql
```
4. Mettre `RUN_MIGRATIONS=true` et redéployer (applique d'éventuelles migrations manquantes, sans effet sinon).

## 4. Fichiers uploadés (logos, photos des candidats)
Ils vivent dans des volumes (`election_assets`, `candidates`, `storage_app`). Copier ceux de l'ancien serveur :
```bash
# depuis le dossier contenant public/election_assets, public/candidates, storage/app/public
docker cp public/election_assets/. <conteneur_app>:/var/www/html/public/election_assets/
docker cp public/candidates/.      <conteneur_app>:/var/www/html/public/candidates/
docker cp storage/app/public/.     <conteneur_app>:/var/www/html/storage/app/public/
docker exec <conteneur_app> chown -R www-data:www-data /var/www/html/public/election_assets /var/www/html/public/candidates /var/www/html/storage/app
```

## Notes
- Socket.io / Redis ne sont pas déployés (la prod utilisait déjà `BROADCAST_DRIVER=log` ; l'envoi Redis est ignoré s'il est absent).
- `config/afribapay.php` contient des clés par défaut en clair : les surcharger via variables Coolify
  (`AFRIBAPAY_MERCHANT_KEY`, `AFRIBAPAY_TOKEN_BASIC`...) et idéalement les retirer du dépôt.

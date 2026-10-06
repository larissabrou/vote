# Système de Vote Électronique

Système de vote électronique développé avec Laravel et Socket.io pour la gestion et le suivi en temps réel des élections.

## Fonctionnalités

### 1. Création d'élection (Admin)
- Formulaire de création avec logo, titre, dates et heures
- Gestion des candidats (photo, nom, prénom, poste)
- Import Excel des votants (colonnes: email, nom, prenom, nombre_voix)
- Génération automatique de tokens uniques
- Envoi automatique d'emails avec liens personnalisés

### 2. Processus de vote (Votant)
- Authentification automatique via token unique
- Affichage du nombre de voix disponibles
- Vote pour candidat(s) (possibilité de voter plusieurs fois pour le même candidat)
- Validation du vote
- Impossibilité de revoter

### 3. Tableau de bord temps réel
- Progression globale
- Nombre total de votants
- Nombre de votants ayant voté
- Votes par candidat (temps réel)
- Pourcentage par candidat
- Nombre de bulletins nuls
- Statut de l'élection

## Installation

### Prérequis
- PHP >= 8.1
- Composer
- Node.js et npm
- MySQL
- Redis

### Étapes d'installation

1. **Cloner le projet et installer les dépendances PHP**
```bash
composer install
```

2. **Installer les dépendances Node.js**
```bash
npm install
```

3. **Configurer l'environnement**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configurer la base de données dans `.env`**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vote_system
DB_USERNAME=root
DB_PASSWORD=
```

5. **Configurer Redis dans `.env`**
```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

6. **Créer la base de données et exécuter les migrations**
```bash
php artisan migrate
```

7. **Créer le lien symbolique pour le stockage**
```bash
php artisan storage:link
```

8. **Démarrer le serveur Socket.io**
```bash
npm run socket
```

9. **Démarrer le serveur Laravel**
```bash
php artisan serve
```

10. **Démarrer Vite (dans un autre terminal)**
```bash
npm run dev
```

## Structure du projet

```
vote/
├── app/
│   ├── Http/Controllers/
│   │   ├── ElectionController.php
│   │   ├── VoteController.php
│   │   └── DashboardController.php
│   ├── Models/
│   │   ├── Election.php
│   │   ├── Candidate.php
│   │   ├── Voter.php
│   │   └── Vote.php
│   └── Services/
│       ├── ExcelImportService.php
│       └── EmailService.php
├── database/migrations/
├── resources/
│   ├── views/
│   ├── css/
│   └── js/
├── routes/
│   └── web.php
└── socket-server.js
```

## Format Excel pour l'import des votants

Le fichier Excel doit contenir les colonnes suivantes:
- `email`: Adresse email du votant
- `nom`: Nom de famille
- `prenom`: Prénom
- `nombre_voix`: Nombre de voix attribuées

## Configuration email

Configurez les paramètres SMTP dans le fichier `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Vote System"
```

## Utilisation

1. **Créer une élection**: Accédez à `/elections/create`
2. **Voir le dashboard**: Accédez à `/dashboard/{election_id}`
3. **Voter**: Les votants reçoivent un email avec un lien unique

## Technologies utilisées

- Laravel 10
- Socket.io
- Redis
- MySQL
- Chart.js
- Vite

## Licence

MIT

"# vote" 
"# vote" 

<div align="center">

# Nimbus

**Application de transfert de fichiers sécurisé**

[![Symfony](https://img.shields.io/badge/Symfony-7.4-000000?style=flat-square&logo=symfony&logoColor=white)](https://symfony.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?style=flat-square&logo=vue.js&logoColor=white)](https://vuejs.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-3-38BDF8?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![PHP](https://img.shields.io/badge/PHP-8.4+-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)

</div>

---

## Présentation

Nimbus est une application web auto-hébergée pour envoyer des fichiers en toute sécurité à vos contacts. Pas de cloud tiers, pas de tracking. Les fichiers sont chiffrés en transit, les transferts expirent automatiquement et les destinataires reçoivent un lien par e-mail.

Conçu avec une interface sombre moderne, Nimbus prend en charge les envois volumineux via le protocole TUS (uploads fragmentés et résumables), la protection par mot de passe, et un système de formules Free/Pro.

---

## Fonctionnalités

- **Glisser-déposer** — fichiers ou dossiers entiers, jusqu'à 10 Go par transfert (Pro)
- **Envoi par lien ou par e-mail** — partagez via un lien direct ou envoyez directement aux destinataires
- **Protection par mot de passe** — accès conditionnel pour les destinataires
- **Expiration configurable** — de 1 heure à 7 jours selon la formule
- **Mes transferts** — les utilisateurs Pro peuvent consulter, gérer et supprimer leurs transferts passés
- **Tableau de bord admin** — statistiques globales, liste des transferts filtrables, paramètres applicatifs en base
- **Formules Free/Pro** — limites configurables stockées en base de données, période d'essai incluse
- **Internationalisation** — français, anglais, espagnol, allemand
- **Mode sombre** — détection automatique de la préférence système, bascule disponible dans la barre latérale

---

## Aperçu

### Envoi de fichiers

![Formulaire de transfert](docs/readme/transfer-form.png)

> Le formulaire principal : déposez vos fichiers, ajoutez des destinataires, rédigez un message, choisissez la durée d'expiration et protégez éventuellement par mot de passe.

---

### Modal de bienvenue (visiteurs)

![Modal visiteur](docs/readme/home-guest-modal.png)

> Lorsqu'un visiteur non connecté arrive sur l'application, une modale l'invite à se connecter ou créer un compte pour accéder au plan Pro.

---

### Comment ça marche ?

![Comment ça marche](docs/readme/how-it-works.png)

> Explication du processus en 3 étapes, récapitulatif des limites de la formule active et liste des formats acceptés.

---

### Connexion

![Connexion](docs/readme/login.png)

> Page de connexion avec présentation des avantages de l'application.

---

### Inscription

![Inscription](docs/readme/register.png)

> Création de compte avec nom complet, adresse e-mail et mot de passe.

---

### Mes transferts (Pro)

![Mes transferts](docs/readme/my-transfers.png)

> Vue des transferts envoyés : référence, statut, taille, nombre de téléchargements et date d'expiration.

---

### Gérer un transfert

![Gérer mon transfert](docs/readme/manage-transfer.png)

> Page de gestion d'un transfert : lien de téléchargement avec QR code, gestion des destinataires et zone de suppression.

---

### Transfert protégé

![Transfert protégé](docs/readme/password-protected.png)

> Page d'accès conditionnel — les destinataires doivent saisir le mot de passe défini à l'envoi.

---

### Téléchargement

![Téléchargement](docs/readme/download.png)

> Page de téléchargement pour le destinataire : aperçu des fichiers disponibles, taille totale et bouton de téléchargement.

---

### Tableau de bord — Statistiques

![Tableau de bord statistiques](docs/readme/dashboard-stats.png)

> Indicateurs globaux (utilisateurs, transferts, destinataires, téléchargements), graphiques d'évolution et paramètres applicatifs.

---

### Tableau de bord — Transferts

![Tableau de bord transferts](docs/readme/dashboard-transfers.png)

> Liste complète des transferts avec filtres par statut (Tous, Ready, Pending, Expired, Deleted).

---

### Formules & Tarifs

![Formules](docs/readme/plan.png)

> Comparaison des formules Free et Pro avec les limites détaillées. Passage en Pro instantané (essai gratuit 30 jours).

---

### Profil

![Profil](docs/readme/profile.png)

> Gestion du profil : langue d'affichage, informations personnelles, changement de mot de passe et suppression du compte.

---

## Uploads résumables — protocole TUS

Les fichiers peuvent peser plusieurs gigaoctets. Un upload HTTP classique n'offre aucune reprise en cas d'interruption réseau : tout recommence de zéro. Nimbus utilise le [protocole TUS](https://tus.io) pour résoudre ce problème.

### Ce que fait TUS

TUS découpe chaque fichier en **fragments de 5 Mo** envoyés séquentiellement. Le serveur conserve un pointeur d'offset : si la connexion est coupée en cours de route, le client reprend exactement là où il s'est arrêté au lieu de tout renvoyer. Chaque fragment est un `PATCH` HTTP standard ; le serveur répond avec la position courante, et le client continue.

### Flow dans Nimbus

```
[Navigateur]                          [Serveur]
    │                                     │
    │  POST /api/transfer                 │  Crée un Transfer (statut: Pending)
    │ ──────────────────────────────────► │  Retourne un token
    │                                     │
    │  POST /tus  (création upload)       │  Initialise un slot TUS
    │ ──────────────────────────────────► │  var/uploads/tus_tmp/
    │                                     │
    │  PATCH /tus/{key}  (fragment 1)     │
    │  PATCH /tus/{key}  (fragment 2)     │  Assemble les chunks au fil des PATCH
    │  PATCH /tus/{key}  (...)            │
    │ ──────────────────────────────────► │
    │                                     │
    │  POST /api/transfer/{token}/finalize│  Valide + déplace vers
    │ ──────────────────────────────────► │  var/uploads/transfers/{token}/
    │                                     │  Crée les entités TransferFile
    │                                     │  Statut → Ready / envoi e-mail
```

1. **Création** — un enregistrement `Transfer` (statut `Pending`) est créé en base avant même le premier octet uploadé.
2. **Upload fragmenté** — `tus-js-client` envoie les fichiers chunk par chunk vers `/tus`. Les métadonnées (nom original, MIME type, token de transfert) voyagent dans les en-têtes TUS. En cas d'erreur réseau, la bibliothèque retente automatiquement après 0 s, 3 s, 5 s, puis 10 s.
3. **Reprise** — l'empreinte de chaque upload est persistée en `localStorage`. Si l'utilisateur recharge la page, `findPreviousUploads()` retrouve les uploads en cours et les reprend depuis leur offset.
4. **Finalisation** — une fois tous les fichiers reçus, un appel `/finalize` valide les contraintes (quota du plan, extensions autorisées, protection anti-zip-bomb), déplace les fichiers de `tus_tmp/` vers leur répertoire définitif et passe le transfert en `Ready`.
5. **Nettoyage** — un scheduler périodique supprime les uploads orphelins (jamais finalisés) et les transferts expirés.

### Pourquoi ce choix

| Approche classique | TUS |
|---|---|
| Tout ou rien — interruption = reprise à zéro | Reprise depuis l'offset exact |
| Timeout serveur sur gros fichiers | Fragments courts, pas de timeout |
| Pas de feedback granulaire | Progression par fichier en temps réel |

---

## Stack technique

| Couche | Technologie |
|--------|-------------|
| Backend | Symfony 7.4, PHP 8.4+ |
| Base de données | PostgreSQL |
| Upload | Protocole TUS (fragmenté, résumable) |
| Queue & Scheduler | Symfony Messenger, Symfony Scheduler |
| Frontend | Vue 3, Vue i18n, vue-chartjs |
| Style | Tailwind CSS 3 |
| Emails | Symfony Mailer (SMTP) |
| Build | Vite 8 |

---

## Installation

### Prérequis

- PHP 8.4+
- PostgreSQL
- Node.js 20+
- Composer
- pnpm

### Mise en place

```bash
git clone https://github.com/axelraboit/nimbus.git
cd nimbus

make install-dev
```

`make install-dev` installe les dépendances Composer (app + outils), pnpm, crée les répertoires runtime et exécute les migrations.

Copier et configurer l'environnement :

```bash
cp .env .env.local
```

Variables minimales à renseigner dans `.env.local` :

```dotenv
DATABASE_URL="postgresql://user:password@127.0.0.1:5432/nimbus"
MAILER_DSN="smtp://localhost:25"
APP_SECRET=your-secret-here
```

Charger des données de démonstration (optionnel — recrée la base entièrement) :

```bash
make fixtures
```

### Développement

```bash
make start              # serveur Symfony + mailer Docker
make dev                # Vite HMR (dans un second terminal)
make start-dev-worker   # worker Messenger + Scheduler (dans un troisième terminal)
```

### Production

```bash
make install-prod   # dépendances, migrations, paramètres, build assets
```

Pour les déploiements suivants (nécessite un tag git sur le commit courant) :

```bash
make deploy-prod
```

---

## Commandes utiles

```bash
# Tests
make test                # suite complète
make test-unit           # tests unitaires uniquement
make test-integration    # tests d'intégration uniquement

# Qualité du code
make fix     # auto-correction (JS, Twig, Rector, PHP-CS-Fixer + PHPStan)
make stan    # PHPStan seul

# Base de données
make migrate             # exécuter les migrations
make migration           # générer une nouvelle migration

# Paramètres applicatifs
php bin/console nimbus:application-parameter

# Promouvoir un utilisateur en admin
php bin/console nimbus:user:role user@example.com ROLE_ADMIN
```

---

## Licence

MIT

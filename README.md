<div align="center">

# Nimbus

**Application de transfert de fichiers sécurisé**

[![Symfony](https://img.shields.io/badge/Symfony-7.4-000000?style=flat-square&logo=symfony&logoColor=white)](https://symfony.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?style=flat-square&logo=vue.js&logoColor=white)](https://vuejs.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-4-38BDF8?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![PHP](https://img.shields.io/badge/PHP-8.4+-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Vite](https://img.shields.io/badge/Vite-8-646CFF?style=flat-square&logo=vite&logoColor=white)](https://vitejs.dev)

</div>

---

## Présentation

Nimbus est une application web auto-hébergée pour envoyer des fichiers en toute sécurité à vos contacts. Pas de cloud tiers, pas de tracking. Les transferts expirent automatiquement et les destinataires reçoivent un lien personnel par e-mail.

Conçu avec une interface sombre moderne, Nimbus prend en charge les envois volumineux via le protocole TUS (uploads fragmentés et résumables), la protection par mot de passe, et un système de formules Free/Pro.

---

## Fonctionnalités

- **Glisser-déposer** — fichiers individuels ou dossiers entiers (non zippés), avec sélection via parcourir en alternative. Limites configurables par l'administrateur (taille max, nombre de fichiers, durée d'expiration)
- **Envoi par e-mail ou lien public** — chaque destinataire reçoit un lien personnel, ou partagez via un lien public direct
- **Protection par mot de passe** — accès conditionnel pour les destinataires
- **Expiration configurable** — durées disponibles paramétrables par l'administrateur
- **Aperçu des fichiers** — prévisualisation inline avant téléchargement
- **Suivi des téléchargements** — statut par destinataire en temps réel
- **Mes transferts** — les utilisateurs Pro consultent, gèrent et suppriment leurs transferts passés
- **Tableau de bord admin** — statistiques globales, liste des transferts, paramètres applicatifs
- **Formules Free/Pro** — limites configurables en base de données, période d'essai incluse
- **Internationalisation** — français, anglais, espagnol, allemand
- **Thème** — mode sombre et mode clair

---

## Aperçu

### Connexion

![Connexion](docs/readme/screenshots/email/login.jpg)

> Page de connexion avec présentation des avantages de l'application : transferts chiffrés, expiration automatique, notifications par e-mail et connexion non obligatoire.

---

### Inscription

![Inscription](docs/readme/screenshots/email/inscription.jpg)

> Formulaire d'inscription : nom complet, adresse e-mail, mot de passe et confirmation.

---

### Comment ça marche ?

![Comment ça marche](docs/readme/screenshots/email/comment-ca-marche.jpg)

> Explication du processus en 3 étapes, récapitulatif des limites du plan actif et liste complète des formats acceptés. Les limites affichées reflètent le paramétrage de l'administrateur.

---

## Parcours — Envoi par e-mail

Le destinataire reçoit un lien personnel par e-mail. Le téléchargement est tracké individuellement par destinataire.

### Formulaire d'envoi

![Formulaire d'envoi](docs/readme/screenshots/email/formulaire.jpg)

> Mode e-mail : ajoutez un ou plusieurs destinataires, un message optionnel, une durée d'expiration et un mot de passe éventuel.

---

### Confirmation d'envoi

![Confirmation](docs/readme/screenshots/email/confirmation.jpg)

> Après l'envoi : référence du transfert et lien de gestion à conserver. Les destinataires reçoivent leur lien personnel par e-mail.

---

### E-mail reçu par le destinataire

![E-mail](docs/readme/screenshots/email/email.png)

> L'e-mail reçu : nom du fichier, taille, indication du mot de passe si protégé, date d'expiration et bouton de téléchargement.

---

### Transfert protégé

![Transfert protégé](docs/readme/screenshots/email/transfert-protege.jpg)

> Page d'accès conditionnel — le destinataire saisit le mot de passe défini à l'envoi avant d'accéder aux fichiers.

---

### Téléchargement

![Téléchargement](docs/readme/screenshots/email/telechargement.jpg)

> Page de téléchargement : aperçu du fichier, expéditeur, date d'expiration et bouton de téléchargement.

---

### Aperçu fichier

![Aperçu fichier](docs/readme/screenshots/email/apercu-fichier.jpg)

> Prévisualisation inline d'un fichier directement dans le navigateur avant téléchargement.

---

### Mes transferts

![Mes transferts](docs/readme/screenshots/email/mes-transferts.jpg)

> Liste des transferts envoyés avec référence, statut, taille, nombre de destinataires ayant téléchargé et date d'expiration.

---

### Gérer un transfert

![Gérer mon transfert](docs/readme/screenshots/email/gerer-transfert.jpg)

> Page de gestion : QR code, lien de téléchargement, statut par destinataire et suppression définitive.

---

### Notification de téléchargement

![Notification téléchargement](docs/readme/screenshots/email/email-telechargement.png)

> L'expéditeur reçoit un e-mail dès qu'un destinataire télécharge ses fichiers, avec la référence du transfert et un lien vers la gestion si besoin de supprimer.

---

## Parcours — Lien public

Le transfert génère un lien unique partageable librement. N'importe qui avec le lien peut télécharger.

### Formulaire d'envoi

![Formulaire d'envoi public](docs/readme/screenshots/public/formulaire.jpg)

> Mode lien public : pas de destinataires, le lien et le QR code sont générés à la confirmation.

---

### Confirmation d'envoi

![Confirmation publique](docs/readme/screenshots/public/confirmation.jpg)

> Après l'envoi : lien public à partager avec QR code téléchargeable, et lien de gestion séparé.

---

### Transfert protégé

![Transfert protégé public](docs/readme/screenshots/public/transfert-protege.jpg)

> Même page d'accès conditionnel par mot de passe, disponible aussi en mode lien public.

---

### Téléchargement

![Téléchargement public](docs/readme/screenshots/public/telechargement.jpg)

> Page de téléchargement accessible via le lien public — sans identification du visiteur.

---

### Aperçu fichier

![Aperçu fichier public](docs/readme/screenshots/public/apercu-fichier.jpg)

> Prévisualisation inline identique au parcours e-mail.

---

### Mes transferts

![Mes transferts](docs/readme/screenshots/public/mes-transferts.jpg)

> Les deux modes coexistent dans la liste : le badge "Lien public" distingue les transferts publics des transferts par e-mail.

---

## Administration

### Vue d'ensemble

![Administration - Vue d'ensemble](docs/readme/screenshots/email/admin-vue-ensemble.jpg)

> Dashboard admin : nombre d'utilisateurs, transferts, fichiers et téléchargements depuis le début, avec graphiques d'évolution sur 6 mois.

---

### Transferts

![Administration - Transferts](docs/readme/screenshots/public/admin-transferts.jpg)

> Liste complète de tous les transferts avec filtres par statut, expéditeur, destinataires et date d'expiration.

---

## Upload résumable — protocole TUS

### Sélection des fichiers

![Sélection fichiers](docs/readme/screenshots/upload/formulaire-fichiers.jpg)

> Plusieurs fichiers volumineux ajoutés avant l'envoi — glisser-déposer ou sélection via le parcourir.

---

### Reprise automatique détectée

![Reprise détectée](docs/readme/screenshots/upload/reprise-detectee.jpg)

> Si la page est rechargée ou la connexion coupée, Nimbus détecte l'upload interrompu et propose de le reprendre automatiquement — les fichiers et le formulaire sont restaurés.

---

### Progression par fichier

![Progression upload](docs/readme/screenshots/upload/progression.jpg)

> La progression est affichée fichier par fichier. Les fichiers déjà uploadés (100%) ne sont pas renvoyés, l'upload reprend à l'offset exact des fichiers en cours.

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

### Stockage

Actuellement, les fichiers sont stockés directement sur le serveur (`var/uploads/`). Une intégration avec un stockage cloud (AWS S3, Cloudflare R2, etc.) est prévue pour permettre de déléguer le stockage et de passer à l'échelle sans contrainte d'espace disque.

---

## Stack technique

| Couche | Technologie |
|--------|-------------|
| Backend | Symfony 7.4, PHP 8.4+ |
| Base de données | PostgreSQL |
| Upload | Protocole TUS (fragmenté, résumable) |
| Queue & Scheduler | Symfony Messenger, Symfony Scheduler |
| Frontend | Vue 3, Vue i18n, vue-chartjs |
| Style | Tailwind CSS 4 |
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

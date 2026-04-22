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
- **Suivi des téléchargements** — statut par destinataire en temps réel
- **Mes transferts** — les utilisateurs Pro consultent, gèrent et suppriment leurs transferts passés
- **Demandes d'accès** — les visiteurs peuvent demander l'accès depuis la page protégée ; l'admin reçoit un e-mail, peut définir une limite de taille personnalisée, puis approuve ou refuse (notification e-mail dans les deux cas)
- **Tableau de bord admin** — gestion des utilisateurs, liste des transferts, paramètres applicatifs
- **Formules Free/Pro** — limites configurables en base de données, période d'essai incluse
- **Internationalisation** — français, anglais, espagnol, allemand
- **Thème** — mode sombre et mode clair

---

## Aperçu

### Connexion

![Connexion](docs/readme/screenshots/35-login.png)

> Page de connexion : logo Nimbus, slogan "Envoyez vos fichiers, simplement.", formulaire e-mail/mot de passe avec lien "Mot de passe oublié ?", "Pas encore de compte ?" et option "Continuer sans connexion".

---

## Parcours — Envoi par e-mail

Le destinataire reçoit un lien personnel par e-mail. Le téléchargement est tracké individuellement par destinataire.

### Formulaire vide

![Formulaire vide](docs/readme/screenshots/01-new-transfer-empty.png)

> Formulaire "Nouveau transfert" en mode e-mail, sans fichier ajouté. Zone de dépôt avec les deux modes de partage disponibles : "Envoyer par e-mail" et "Lien public".

---

### Modal "Comment ça marche ?"

![Comment ça marche](docs/readme/screenshots/02-how-it-works-modal.png)

> Modal "Comment ça marche ?" — explique le processus d'envoi en quelques étapes avec les limites du plan actif et les formats supportés.

---

### Formulaire rempli

![Formulaire e-mail rempli](docs/readme/screenshots/03-new-transfer-email-filled.png)

> Formulaire complété en mode e-mail : un fichier ajouté, destinataire renseigné, message personnalisé et mot de passe de protection défini.

---

### Confirmation d'envoi

![Transfert envoyé — mode e-mail](docs/readme/screenshots/04-transfer-sent-email.png)

> Page "Transfert envoyé !" après envoi en mode e-mail. Référence EC05-2F76 et lien "Gérer mon transfert" à conserver.

---

### E-mail reçu par le destinataire

![E-mail notification destinataire](docs/readme/screenshots/05-email-recipient-notification.png)

> E-mail reçu par le destinataire : "Vous avez reçu des fichiers", avec mention du mot de passe requis et de la date d'expiration du lien.

---

### Page de téléchargement

![Fichiers disponibles — mode e-mail](docs/readme/screenshots/28-download-files-email.png)

> Page "Fichiers disponibles" accessible via le lien reçu par e-mail. Référence EC05-2F76, fichier de 17,3 Mo envoyé par Axel Raboit, avec bouton "Télécharger".

---

### Transfert protégé par mot de passe

![Transfert protégé](docs/readme/screenshots/27-download-password-protected.png)

> Page "Transfert protégé" — le destinataire doit saisir le mot de passe défini à l'envoi pour accéder aux fichiers.

---

## Parcours — Lien public

Le transfert génère un lien unique partageable librement. N'importe qui avec le lien peut télécharger.

### Formulaire rempli

![Formulaire lien public rempli](docs/readme/screenshots/14-new-transfer-public-filled.png)

> Formulaire en mode "Lien public" complété : 1 fichier ajouté, message, expiration 1 heure et mot de passe optionnel.

---

### Plusieurs fichiers

![Plusieurs fichiers](docs/readme/screenshots/15-new-transfer-multiple-files.png)

> 5 fichiers ajoutés en une seule fois avant l'envoi en mode lien public.

---

### Confirmation avec QR code

![Transfert envoyé — lien public avec QR](docs/readme/screenshots/19-transfer-sent-public-qr.png)

> Page "Transfert envoyé !" en mode lien public. Référence B564-CA4C, QR code téléchargeable et lien de gestion séparé à conserver.

---

### Page de téléchargement

![Fichiers disponibles — lien public](docs/readme/screenshots/39-download-files.png)

> Page "Fichiers disponibles" accessible via le lien public. Référence B564-CA4C, 1 fichier de 17,3 Mo, expire le 22 avril 2026 à 04:12.

---

## Gestion du transfert

### Page de gestion

![Gérer mon transfert](docs/readme/screenshots/06-transfer-manage.png)

> Page "Gérer mon transfert" (référence EC05-2F76) : QR code, statut des destinataires, lien de partage et zone de danger pour supprimer le transfert.

---

### Mes transferts

![Mes transferts](docs/readme/screenshots/29-my-transfers-list.png)

> Page "Mes transferts" — transfert EC05-2F76 avec statut "Actif", 1 fichier de 17,3 Mo, 1/1 téléchargé, expiration le 22 avril 2026.

---

## Upload résumable — protocole TUS

### Upload en cours (fichier unique)

![Upload en cours](docs/readme/screenshots/40-upload-progress.png)

> Envoi d'un fichier en cours à 87%. Message "Ne fermez pas cette page" affiché pendant l'upload TUS.

---

### Upload de plusieurs fichiers

![Upload multiple en cours](docs/readme/screenshots/17-upload-in-progress.png)

> Plusieurs fichiers uploadés en parallèle avec barre de progression individuelle par fichier.

---

### Reprise automatique détectée

![Reprise de transfert détectée](docs/readme/screenshots/41-resume-transfer.png)

> Formulaire "Nouveau transfert" avec bannière "Transfert en cours détecté" — Nimbus propose de reprendre automatiquement l'upload interrompu en re-sélectionnant les fichiers.

---

## Demandes d'accès

Lorsque l'accès à Nimbus est protégé, les visiteurs peuvent soumettre une demande directement depuis la page protégée.

### Page d'accès protégée

![Accès protégé](docs/readme/screenshots/10-app-access-protected.png)

> Page "Accès protégé" — l'application est verrouillée. Le visiteur peut cliquer sur "Demander l'accès" pour soumettre une demande à l'administrateur.

---

### Formulaire de demande vide

![Demander l'accès — formulaire vide](docs/readme/screenshots/12-access-request-form-empty.png)

> Formulaire "Demander l'accès" vide — le visiteur saisit son e-mail, son nom et éventuellement un message.

---

### Formulaire de demande rempli

![Demander l'accès — formulaire rempli](docs/readme/screenshots/13-access-request-form-filled.png)

> Formulaire "Demander l'accès" rempli avec e-mail axel.raboit@gmail.com et quota demandé de 5 Go.

---

### Confirmation d'envoi

![Demande envoyée](docs/readme/screenshots/11-access-request-sent.png)

> Page de confirmation "Demande envoyée" — coche verte indiquant que l'administrateur a été notifié.

---

### E-mail reçu par l'administrateur

![E-mail nouvelle demande d'accès](docs/readme/screenshots/18-email-new-access-request.png)

> E-mail "Nouvelle demande d'accès" reçu par l'administrateur avec les informations du demandeur.

---

### E-mail d'approbation reçu par le demandeur

![E-mail demande approuvée](docs/readme/screenshots/07-email-access-approved.png)

> E-mail "Votre demande d'accès a été approuvée" reçu par le demandeur, avec la limite de transfert accordée (1 Go).

---

## Notifications e-mail

### Fichiers téléchargés

![E-mail fichiers téléchargés](docs/readme/screenshots/16-email-files-downloaded.png)

> E-mail "Fichiers téléchargés" envoyé à l'expéditeur lorsqu'un destinataire a téléchargé les fichiers.

---

## Formules & Tarifs

![Formules & Tarifs](docs/readme/screenshots/37-plans.png)

> Page "Formules & Tarifs" — Free (0 €/mois : 100 Mo, 3 fichiers, expiration 24h) vs Pro (9,99 €/mois : 20 Go, 20 fichiers, expiration 7 jours, accès Mes transferts). Changement de formule possible à tout moment.

---

## Profil

![Mon profil](docs/readme/screenshots/38-profile.png)

> Page "Mon profil" — sélection de la langue d'affichage, mise à jour du nom et de l'e-mail, modification du mot de passe, et zone de danger pour supprimer le compte.

---

## Compte

### Inscription

![Créer un compte](docs/readme/screenshots/09-register.png)

> Page "Créer un compte" — formulaire d'inscription avec nom complet, adresse e-mail, mot de passe et confirmation.

---

### Mot de passe oublié

![Mot de passe oublié](docs/readme/screenshots/08-forgot-password.png)

> Page "Mot de passe oublié" — saisie de l'adresse e-mail pour recevoir un lien de réinitialisation.

---

## Administration

### Vue d'ensemble

![Admin — Vue d'ensemble](docs/readme/screenshots/30-admin-dashboard.png)

> Tableau de bord admin : 4 utilisateurs, 39 transferts, 43 fichiers, 1 téléchargement. Graphiques "Nouveaux utilisateurs" et "Transferts créés" sur les 6 derniers mois.

---

### Utilisateurs

![Admin — Utilisateurs](docs/readme/screenshots/31-admin-users-full.png)

> Onglet "Utilisateurs" — liste des 4 comptes (Jean Dupont, Dev User, Axel Raboit, Test User) avec plan, rôle, taille custom et date d'inscription. Actions : éditer, impersonifier, définir une limite, désactiver, supprimer.

---

### Invitations

![Admin — Invitations](docs/readme/screenshots/32-admin-invitations.png)

> Onglet "Invitations" — formulaire pour envoyer une invitation par e-mail afin de rejoindre Nimbus, avec message personnalisé et identifiants optionnels.

---

### Transferts

![Admin — Transferts](docs/readme/screenshots/34-admin-transfers-list.png)

> Onglet "Transferts" — 3 transferts listés avec référence, expéditeur, statut (Actif / Expiré / Supprimé), nombre de fichiers, destinataires et dates.

---

### Demandes d'accès

![Admin — Demandes d'accès](docs/readme/screenshots/20-admin-access-requests.png)

> Onglet "Demandes d'accès" — demande en attente d'Axel Raboit avec quota demandé de 5 Go.

---

### Approbation d'une demande

![Admin — Modale d'approbation](docs/readme/screenshots/21-admin-approve-modal.png)

> Modale d'approbation — l'administrateur peut définir une limite de taille personnalisée avant d'approuver. Laisser vide applique la limite par défaut du plan.

---

### Demande approuvée

![Admin — Demandes d'accès après approbation](docs/readme/screenshots/23-admin-access-request-approved.png)

> Onglet "Demandes d'accès" après traitement — demande d'Axel Raboit avec statut "Approuvé", 5.0 Go demandés, 1.0 Go accordé, expire le 23 avril 2026.

---

### Limite de taille par utilisateur

![Admin — Ligne utilisateur sans quota](docs/readme/screenshots/24-admin-user-row-no-quota.png)

> Ligne Test User dans la liste des utilisateurs — colonne "Taille custom" affiche `—` (aucune limite personnalisée définie).

---

![Admin — Modale limite de taille vide](docs/readme/screenshots/25-admin-size-limit-empty.png)

> Modale "Limite de taille — Test User" ouverte avec le champ vide (placeholder "Défaut plan") — aucune limite personnalisée appliquée pour l'instant.

---

![Admin — Modale limite de taille remplie](docs/readme/screenshots/26-admin-size-limit-filled.png)

> Même modale avec la valeur 1000 Mo saisie — l'administrateur définit une limite personnalisée de 1 Go pour cet utilisateur.

---

![Admin — Ligne utilisateur avec quota](docs/readme/screenshots/36-admin-user-row-quota.png)

> Ligne Test User après enregistrement — colonne "Taille custom" affiche maintenant `1.0 Go`.

---

### Paramètres

![Admin — Paramètres](docs/readme/screenshots/33-admin-settings-params.png)

> Onglet "Paramètres" — table de configuration de l'application : limites par plan, durées d'expiration, quota de stockage, options TUS et paramètres de stockage.

---

## Upload résumable — protocole TUS

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

Par défaut, les fichiers sont stockés sur le serveur (`var/uploads/`). Nimbus supporte également Cloudflare R2 (compatible S3) via les variables `R2_ENDPOINT`, `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY` et `R2_BUCKET`.

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
make start              # mailer Docker + serveur Symfony + Vite HMR
make start-dev-worker   # worker Messenger + Scheduler (dans un second terminal)
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
# Développement
make start              # mailer Docker + serveur Symfony + Vite HMR
make stop               # arrêter le mailer
make start-dev-worker   # worker Messenger + Scheduler (dans un second terminal)

# Tests
make test-backend             # tous les tests backend (PHPUnit)
make test-backend-unit        # tests unitaires backend uniquement
make test-backend-integration # tests d'intégration backend uniquement
make test-frontend            # tests frontend (Vitest)
make ft                       # fix + tous les tests

# Qualité du code
make fix               # auto-correction (Rector, PHP-CS-Fixer, ESLint) + PHPStan
make stan              # PHPStan seul

# Base de données
make migrate           # exécuter les migrations
make migration         # générer une nouvelle migration
make fixtures          # drop DB + migrations + fixtures

# Utilisateurs
make dev-user EMAIL=user@example.com   # passer un utilisateur en ROLE_DEV

# Paramètres applicatifs
php bin/console nimbus:application-parameter

# Promouvoir un utilisateur en admin
php bin/console nimbus:user:role user@example.com ROLE_ADMIN

# Utilitaires
make help              # lister toutes les commandes disponibles
```

---

## Licence

MIT

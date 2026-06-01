# Guide administrateur — Création des identifiants et script `setup_admin.php`

Ce document explique **où** définir les identifiants admin, **quand** supprimer le script sensible, et **quelles commandes** lancer dans le terminal (Windows / WAMP).

**Fichiers concernés :**

| Fichier                            | Versionné git ?    | Rôle                                                      |
| ---------------------------------- | ------------------ | --------------------------------------------------------- |
| `database/setup_admin.php.example` | Oui                | Modèle à garder sur ton PC / dans le dépôt                |
| `database/setup_admin.php`         | Non (`.gitignore`) | Script actif sur le serveur — **à supprimer après usage** |
| `backend/.env.example`             | Oui                | Modèle de configuration backend                           |
| `backend/.env`                     | Non (`.gitignore`) | Ta config réelle (optionnel en local WAMP)                |

---

## 1. Où définir les identifiants ?

**Tu ne modifies pas une « section » dans `setup_admin.php` pour mettre email/mot de passe.**

Le fichier PHP est un **outil**. Les identifiants sont saisis :

1. **Dans le terminal** — via des options `--email`, `--password`, `--name`, **ou**
2. **Dans le menu interactif** — le script te pose les questions une par une.

Ensuite ils sont enregistrés dans **MySQL**, table **`admins`** (colonne `password_hash` = mot de passe hashé, jamais en clair).

| Étape          | Où ?                                                          |
| -------------- | ------------------------------------------------------------- |
| Saisie         | Terminal (commande ou menu)                                   |
| Stockage       | Base `access_informatique` → table `admins`                   |
| Connexion site | http://localhost:5173/admin/login (même email + mot de passe) |

---

## 2. Faut-il un fichier `backend/.env` ?

### Peut-on lancer `setup_admin.php` sans `.env` ?

**Oui, souvent en local WAMP**, si :

- MySQL tourne (WAMP vert),
- la base `access_informatique` existe,
- utilisateur `root` **sans mot de passe** (défaut WAMP).

Sans `.env`, `backend/includes/config.php` utilise ces **valeurs par défaut** :

| Variable  | Défaut sans `.env`    |
| --------- | --------------------- |
| `DB_HOST` | `127.0.0.1`           |
| `DB_NAME` | `access_informatique` |
| `DB_USER` | `root`                |
| `DB_PASS` | _(vide)_              |

### Quand créer quand même un `backend/.env` ?

- Mot de passe MySQL différent de vide
- Nom de base autre que `access_informatique`
- Production, emails SMTP, `JWT_SECRET` personnalisé

**Création du fichier :**

```powershell
cd c:\wamp64\www\Access_Informatique
copy backend\.env.example backend\.env
```

Puis édite `backend\.env` (Notepad, VS Code, Cursor) et adapte au minimum :

```ini
DB_HOST=127.0.0.1
DB_NAME=access_informatique
DB_USER=root
DB_PASS=          # ton mot de passe MySQL WAMP si tu en as un
```

---

## 3. Prérequis avant de créer l’admin

1. **WAMP** démarré (icône verte).
2. Base créée et schéma importé (phpMyAdmin ou terminal) :
   - `database/schema.sql`
   - `database/seeds.sql` _(optionnel pour le contenu du site, pas pour la table `admins`)_
3. Terminal ouvert à la **racine du projet** :

```powershell
cd c:\wamp64\www\Access_Informatique
```

4. Script présent (sinon) :

```powershell
copy database\setup_admin.php.example database\setup_admin.php
```

---

## 4. Vérifier que tout est prêt (test rapide)

```powershell
cd c:\wamp64\www\Access_Informatique
php database/setup_admin.php list
```

| Résultat                                     | Signification                                                      |
| -------------------------------------------- | ------------------------------------------------------------------ |
| Liste des admins ou « Aucun administrateur » | Connexion MySQL OK — tu peux créer l’admin                         |
| `Erreur base de données`                     | Vérifier WAMP, base importée, `backend\.env` si mot de passe MySQL |
| `php` introuvable                            | Utiliser le chemin WAMP (voir section 10)                          |

---

## 5. Créer l’administrateur (définir les identifiants)

### Méthode A — Une commande (recommandée)

Remplace email, nom et mot de passe par **les tiens** (mot de passe : **10 caractères minimum**) :

```powershell
cd c:\wamp64\www\Access_Informatique

php database/setup_admin.php create --email="admin@accessinformatique.com" --name="Administrateur" --password="Admin@Access2024!"
```

### Méthode B — Menu interactif

```powershell
php database/setup_admin.php
```

| Touche | Action                                                    |
| ------ | --------------------------------------------------------- |
| `2`    | Créer un administrateur → saisir email, nom, mot de passe |
| `1`    | Lister les admins                                         |
| `0`    | Quitter                                                   |

### Vérifier

```powershell
php database/setup_admin.php list
```

### Tester sur le site

1. Démarrer le frontend (autre terminal) :

```powershell
cd c:\wamp64\www\Access_Informatique
npm run dev
```

2. Navigateur : **http://localhost:5173/admin/login**
3. Se connecter avec le **même** email et mot de passe que à l’étape « create ».

---

## 6. À quel moment supprimer `setup_admin.php` ?

| Moment                                        | Action                                                    |
| --------------------------------------------- | --------------------------------------------------------- |
| Pendant création / modification               | **Garder** `database/setup_admin.php`                     |
| Après `list` + test réussi sur `/admin/login` | **Supprimer** `setup_admin.php` du serveur                |
| Toujours                                      | **Garder** `setup_admin.php.example` (sur PC ou dans git) |

```powershell
del c:\wamp64\www\Access_Informatique\database\setup_admin.php
```

**Ne pas supprimer :** `setup_admin.php.example`, `database\.htaccess` (bloque l’accès HTTP aux `.php` du dossier `database/`).

### Plus tard (mot de passe oublié)

```powershell
copy database\setup_admin.php.example database\setup_admin.php
php database/setup_admin.php password --email="admin@accessinformatique.com" --password="NouveauMotDePasse10+"
# Tester /admin/login
del database\setup_admin.php
```

---

## 7. Toutes les commandes utiles

Toujours depuis :

```powershell
cd c:\wamp64\www\Access_Informatique
```

### Aide

```powershell
php database/setup_admin.php --help
```

### Lister

```powershell
php database/setup_admin.php list
```

### Créer

```powershell
php database/setup_admin.php create --email="VOTRE@email.com" --name="Votre Nom" --password="MotDePasse10+"
```

### Changer le mot de passe

```powershell
php database/setup_admin.php password --email="VOTRE@email.com" --password="NouveauMotDePasse10+"
```

### Changer l’email

```powershell
php database/setup_admin.php email --email="ancien@email.com" --new-email="nouveau@email.com"
```

### Changer le nom affiché

```powershell
php database/setup_admin.php name --email="VOTRE@email.com" --name="Nouveau Nom"
```

### Supprimer un admin (il doit en rester au moins un)

```powershell
php database/setup_admin.php delete --email="email-a-supprimer@email.com"
php database/setup_admin.php delete --email="email-a-supprimer@email.com" --yes
```

---

## 8. Scénario complet (première installation)

```powershell
cd c:\wamp64\www\Access_Informatique

# Optionnel mais recommandé si MySQL a un mot de passe ou config custom
copy backend\.env.example backend\.env
# Éditer backend\.env si besoin

copy database\setup_admin.php.example database\setup_admin.php

php database/setup_admin.php create --email="admin@accessinformatique.com" --name="Administrateur" --password="Admin@Access2024!"

php database/setup_admin.php list
```

→ Ouvrir **http://localhost:5173/admin/login** et se connecter.

Si OK :

```powershell
del database\setup_admin.php
```

---

## 9. Accès au dashboard admin (URLs)

| URL                                   | Usage                                       |
| ------------------------------------- | ------------------------------------------- |
| http://localhost:5173/admin/login     | Page de **connexion** (formulaire)          |
| http://localhost:5173/admin           | Redirige vers le dashboard si déjà connecté |
| http://localhost:5173/admin/dashboard | Tableau de bord (JWT requis)                |

Il n’y a **pas** de lien « Admin » sur le site public : il faut connaître l’URL `/admin/login`.

**Fichiers frontend :** `src/views/admin/AdminLogin.vue`, routes dans `src/router/index.js`.

**API login (JSON, pas une page web) :** `POST .../backend/api/admin/login.php`

---

## 10. Si `php` n’est pas reconnu dans le terminal

Utilise le PHP de WAMP (adapte la version du dossier, ex. `php8.2.26`) :

```powershell
c:\wamp64\bin\php\php8.2.26\php.exe database/setup_admin.php list
```

---

## 11. Erreurs fréquentes

| Problème                            | Solution                                                |
| ----------------------------------- | ------------------------------------------------------- |
| `Aucun administrateur en base`      | `create` (section 5)                                    |
| `Mot de passe trop court`           | Minimum 10 caractères                                   |
| `Un admin existe déjà avec l'email` | Utiliser `password` ou `email`, pas `create`            |
| `Erreur base de données`            | WAMP + import `schema.sql` + `backend\.env` (`DB_PASS`) |
| Login site échoue                   | `list` puis `password` si besoin                        |
| 5 échecs de login                   | Attendre 5 min (rate limit) ou réessayer plus tard      |

**Script de diagnostic temporaire** (à supprimer après) : `database/debug_login.php` — ne pas laisser en production.

---

## 12. Sécurité

- `setup_admin.php` : **CLI uniquement** (refusé via navigateur).
- `database/.htaccess` : bloque l’exécution des `.php` via Apache.
- Ne jamais committer `backend/.env` ni `database/setup_admin.php`.
- En production : `JWT_SECRET` long et aléatoire dans `.env`.

---

## 13. Récapitulatif en 4 phrases

1. **Identifiants** = saisis dans le **terminal** (`create` ou menu), pas dans une section du fichier PHP.
2. **Stockage** = table MySQL **`admins`**.
3. **`.env`** = optionnel en WAMP local (défauts `root` / mot de passe vide) ; recommandé si ta config MySQL diffère.
4. **Supprimer** `setup_admin.php` **après** test réussi sur `/admin/login` ; garder `.example` pour la prochaine fois.

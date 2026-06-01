# Configuration PHPMailer — Recevoir les emails des formulaires

Les formulaires **Contact** et **Inscription** envoient :

1. Un email **à vous** (`MAIL_ADMIN`) avec le contenu du message  
2. Un email **de confirmation** au visiteur  

Tout passe par **SMTP** (PHPMailer), configuré dans `backend/.env`.

---

## Étape 1 — Fichier `.env`

```powershell
cd c:\wamp64\www\Access_Informatique
copy backend\.env.example backend\.env
```

Ouvrez `backend\.env` dans Cursor ou le Bloc-notes.

---

## Étape 2 — Choisir votre fournisseur email

### Option A — Gmail (recommandé pour les tests)

| Variable | Valeur |
|----------|--------|
| `MAIL_HOST` | `smtp.gmail.com` |
| `MAIL_PORT` | `587` |
| `MAIL_ENCRYPTION` | `tls` |
| `MAIL_USERNAME` | Votre adresse Gmail complète |
| `MAIL_PASSWORD` | **Mot de passe d'application** (pas votre mot de passe Gmail normal) |
| `MAIL_FROM` | **La même adresse** que `MAIL_USERNAME` |
| `MAIL_ADMIN` | L'adresse qui doit **recevoir** les messages du site (souvent la même) |

#### Créer un mot de passe d'application Gmail

1. Compte Google → [Sécurité](https://myaccount.google.com/security)  
2. Activer la **validation en 2 étapes** (obligatoire)  
3. Mots de passe des applications → Créer → nom : `Access Informatique WAMP`  
4. Copier les **16 caractères** (sans espaces) dans `MAIL_PASSWORD`

Exemple `.env` :

```ini
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=monentreprise@gmail.com
MAIL_PASSWORD=abcd efgh ijkl mnop
MAIL_FROM=monentreprise@gmail.com
MAIL_FROM_NAME=Access Informatique
MAIL_ADMIN=monentreprise@gmail.com
```

---

### Option B — Outlook / Microsoft 365

```ini
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=votre@email.com
MAIL_PASSWORD=votre_mot_de_passe
MAIL_FROM=votre@email.com
MAIL_ADMIN=votre@email.com
```

---

### Option C — Autre hébergeur (OVH, cPanel, etc.)

Demandez à votre hébergeur : **serveur SMTP**, **port**, **login**, **SSL/TLS**.

Exemples courants :

| Hébergeur | Host | Port | Encryption |
|-----------|------|------|------------|
| OVH | `ssl0.ovh.net` | 465 | `ssl` |
| OVH (alternatif) | `ssl0.ovh.net` | 587 | `tls` |

---

## Étape 3 — Tester l'envoi

```powershell
cd c:\wamp64\www\Access_Informatique
php backend/scripts/test_mail.php
```

- **✅ succès** → vérifiez la boîte `MAIL_ADMIN` (et les spams)  
- **❌ échec** → mettez `MAIL_DEBUG=1` dans `.env`, relancez le test, lisez les logs WAMP (PHP error log)

---

## Étape 4 — Tester le formulaire Contact

1. `npm run dev` + WAMP actifs  
2. http://localhost:5173/contact  
3. Envoyez un message test  
4. Vous devez recevoir un email sur `MAIL_ADMIN`  
5. Le visiteur reçoit une confirmation (si SMTP OK)

Dans l’admin : **Messages contact** affiche aussi le message en base (même si l’email échoue).

---

## Variables expliquées

| Variable | Rôle |
|----------|------|
| `MAIL_ADMIN` | **Boîte qui reçoit** les notifications (contact + inscription) |
| `MAIL_FROM` | Adresse affichée comme expéditeur |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | Identifiants de connexion au serveur SMTP |
| `MAIL_DEBUG` | `1` = détails SMTP dans les logs PHP (dev seulement) |

---

## Dépannage

| Problème | Solution |
|----------|----------|
| Formulaire OK mais pas d'email | Lancer `php backend/scripts/test_mail.php` |
| `SMTP non configuré` | Remplir `MAIL_USERNAME` et `MAIL_PASSWORD` dans `.env |
| Gmail « Username and Password not accepted » | Utiliser un **mot de passe d'application**, pas le mot de passe du compte |
| Gmail bloque l'expéditeur | `MAIL_FROM` doit être identique à `MAIL_USERNAME` |
| Email dans les spams | Normal en dev ; ajoutez l'expéditeur aux contacts |
| Timeout / connexion refusée | Vérifier pare-feu ; essayer port `465` + `MAIL_ENCRYPTION=ssl` |
| `vendor` manquant | `cd backend && composer install` |

---

## Sécurité

- Ne commitez **jamais** `backend/.env` (déjà dans `.gitignore`)  
- Ne partagez pas le mot de passe d'application  
- En production : compte SMTP dédié (ex. `noreply@votredomaine.com`)

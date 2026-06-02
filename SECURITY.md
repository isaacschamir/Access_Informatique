## Security hardening performed (summary)

Actions réalisées :

- Enforce `JWT_SECRET` : l'application refuse de démarrer en production si la clé est absente ou trop courte.
- CORS : en production, seules les origines listées dans `FRONTEND_URL` sont autorisées. Variantes `localhost` autorisées uniquement en `APP_ENV=development`.
- Uploads : ajouté `backend/uploads/.htaccess` pour interdire l'exécution PHP et désactiver l'indexation.
- Headers : ajout de `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `Content-Security-Policy`.
- Les endpoints admin utilisent `require_auth()` (JWT) pour protéger les routes.

Instructions rapides :

1. Générer un `JWT_SECRET` sûr (ex. 64 hex chars) :
   - OpenSSL (Linux/macOS):

     openssl rand -hex 32

   - PowerShell (Windows):

     [byte[]] $b = New-Object byte[] 32; (New-Object Security.Cryptography.RNGCryptoServiceProvider).GetBytes($b); [System.BitConverter]::ToString($b).Replace('-', '').ToLower()

2. Mettre la valeur dans `backend/.env` (ne pas versionner).

3. Redémarrer WAMP/Apache pour prendre en compte les variables d'environnement.

Notes : conserver une copie locale hors dépôt pour les scripts d'administration (déjà déplacés dans `_local_archive/`).

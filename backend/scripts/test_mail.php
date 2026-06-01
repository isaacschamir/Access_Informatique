<?php
/**
 * test_mail.php — Test d'envoi SMTP (CLI uniquement)
 *
 * Usage :
 *   php backend/scripts/test_mail.php
 *
 * Envoie un email de test à l'adresse MAIL_ADMIN définie dans backend/.env
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI uniquement.\n");
}

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/Mailer.php';

echo "\n=== Test PHPMailer — Access Informatique ===\n\n";

if (!Mailer::isConfigured()) {
    echo "❌ SMTP non configuré.\n\n";
    echo "   Créez backend/.env à partir de backend/.env.example\n";
    echo "   et renseignez au minimum :\n";
    echo "     MAIL_USERNAME, MAIL_PASSWORD, MAIL_ADMIN\n\n";
    echo "   Guide : backend/MAIL_SETUP.md\n\n";
    exit(1);
}

echo "Serveur SMTP : " . MAIL_HOST . ':' . MAIL_PORT . ' (' . MAIL_ENCRYPTION . ")\n";
echo "Compte       : " . MAIL_USERNAME . "\n";
echo "Expéditeur   : " . MAIL_FROM . "\n";
echo "Destinataire : " . MAIL_ADMIN . " (MAIL_ADMIN)\n\n";
echo "Envoi en cours...\n";

$body = Mailer::template(
    'Test SMTP',
    '<p>Si vous recevez cet email, PHPMailer est correctement configuré pour Access Informatique.</p>'
    . '<p>Les formulaires <strong>Contact</strong> et <strong>Inscription</strong> enverront les notifications à <strong>'
    . htmlspecialchars(MAIL_ADMIN, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
);

$ok = Mailer::send(
    MAIL_ADMIN,
    'Test',
    'Test SMTP — Access Informatique',
    $body
);

if ($ok) {
    echo "\n✅ Email envoyé avec succès.\n";
    echo "   Vérifiez la boîte : " . MAIL_ADMIN . " (et le dossier spam).\n\n";
    exit(0);
}

echo "\n❌ Échec de l'envoi.\n";
echo "   Consultez les logs PHP (WAMP) ou activez MAIL_DEBUG=1 dans .env\n";
echo "   Guide : backend/MAIL_SETUP.md\n\n";
exit(1);

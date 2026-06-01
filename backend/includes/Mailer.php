<?php
/**
 * Mailer.php
 * ---------------------------------------------------------------
 * Wrapper centralisé autour de PHPMailer (SMTP).
 *
 * Configuration : backend/.env (voir backend/.env.example et MAIL_SETUP.md)
 * Prérequis       : composer install dans backend/
 * Test            : php backend/scripts/test_mail.php
 * ---------------------------------------------------------------
 */

declare(strict_types=1);

class Mailer
{
    private static function ensureLoaded(): bool
    {
        static $loaded = null;
        if ($loaded !== null) {
            return $loaded;
        }
        $autoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            error_log('[Mailer] vendor/autoload.php introuvable — exécutez : cd backend && composer install');
            $loaded = false;
            return false;
        }
        require_once $autoload;
        $loaded = true;
        return true;
    }

    /**
     * Vérifie que les paramètres SMTP minimaux sont renseignés dans .env
     */
    public static function isConfigured(): bool
    {
        return MAIL_USERNAME !== ''
            && MAIL_PASSWORD !== ''
            && MAIL_ADMIN !== ''
            && filter_var(MAIL_ADMIN, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Envoie un email HTML via SMTP.
     *
     * @param string      $to_email       Destinataire
     * @param string      $to_name        Nom du destinataire
     * @param string      $subject        Objet
     * @param string      $html_body      Corps HTML
     * @param string|null $reply_to_email Répondre à (ex. email du visiteur)
     * @param string|null $reply_to_name  Nom pour Reply-To
     */
    public static function send(
        string $to_email,
        string $to_name,
        string $subject,
        string $html_body,
        ?string $reply_to_email = null,
        ?string $reply_to_name = null
    ): bool {
        if (!self::ensureLoaded()) {
            return false;
        }

        if (!self::isConfigured()) {
            error_log('[Mailer] SMTP non configuré — renseignez MAIL_USERNAME, MAIL_PASSWORD et MAIL_ADMIN dans backend/.env');
            return false;
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            self::applySmtpConfig($mail);

            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);

            if ($reply_to_email !== null && $reply_to_email !== '' && filter_var($reply_to_email, FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo($reply_to_email, $reply_to_name ?? '');
            } else {
                $mail->addReplyTo(MAIL_FROM, MAIL_FROM_NAME);
            }

            $mail->addAddress($to_email, $to_name);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html_body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html_body));

            $mail->send();
            return true;

        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log('[Mailer] Échec vers ' . $to_email . ' : ' . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Notification à l'adresse MAIL_ADMIN (formulaires contact / inscription).
     *
     * @param string|null $reply_to_email Email du visiteur pour « Répondre »
     * @param string|null $reply_to_name  Nom du visiteur
     */
    public static function notifyAdmin(
        string $subject,
        string $html_body,
        ?string $reply_to_email = null,
        ?string $reply_to_name = null
    ): bool {
        return self::send(
            MAIL_ADMIN,
            MAIL_FROM_NAME,
            $subject,
            $html_body,
            $reply_to_email,
            $reply_to_name
        );
    }

    private static function applySmtpConfig(\PHPMailer\PHPMailer\PHPMailer $mail): void
    {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->Port       = MAIL_PORT;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 30;

        $encryption = MAIL_ENCRYPTION;
        if ($encryption === 'ssl') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure  = '';
            $mail->SMTPAutoTLS = false;
        }

        if (MAIL_DEBUG && defined('APP_ENV') && APP_ENV === 'development') {
            $mail->SMTPDebug  = 2;
            $mail->Debugoutput = static function (string $str, int $level): void {
                error_log('[Mailer SMTP] ' . trim($str));
            };
        }
    }

    public static function template(string $title, string $content): string
    {
        $year = date('Y');
        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title>{$title}</title>
          <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
              font-family: 'Segoe UI', Arial, sans-serif;
              background-color: #f4f4f4;
              color: #333;
              line-height: 1.6;
            }
            .wrapper { max-width: 620px; margin: 32px auto; }
            .container {
              background: #ffffff;
              border-radius: 10px;
              overflow: hidden;
              box-shadow: 0 2px 12px rgba(0,0,0,.10);
            }
            .header {
              background: linear-gradient(135deg, #16a34a, #15803d);
              padding: 28px 36px;
            }
            .header h1 {
              color: #ffffff;
              font-size: 22px;
              font-weight: 700;
              margin-bottom: 4px;
            }
            .header p {
              color: rgba(255,255,255,.8);
              font-size: 13px;
            }
            .body { padding: 36px; }
            .body h2 {
              color: #16a34a;
              font-size: 18px;
              margin-bottom: 20px;
              padding-bottom: 12px;
              border-bottom: 2px solid #f0fdf4;
            }
            .info-block {
              background: #f9fafb;
              border-radius: 8px;
              padding: 20px 24px;
              margin: 16px 0;
            }
            .info-row {
              display: flex;
              padding: 8px 0;
              border-bottom: 1px solid #e5e7eb;
            }
            .info-row:last-child { border-bottom: none; }
            .info-label {
              font-weight: 600;
              color: #6b7280;
              min-width: 160px;
              font-size: 13px;
              text-transform: uppercase;
              letter-spacing: .4px;
            }
            .info-value { color: #111827; font-size: 14px; }
            .message-block {
              background: #f0fdf4;
              border-left: 4px solid #16a34a;
              border-radius: 0 8px 8px 0;
              padding: 16px 20px;
              margin: 16px 0;
              font-size: 14px;
              color: #374151;
            }
            .footer {
              background: #f9fafb;
              padding: 20px 36px;
              border-top: 1px solid #e5e7eb;
              text-align: center;
              font-size: 12px;
              color: #9ca3af;
            }
            .footer a { color: #6b7280; text-decoration: none; }
          </style>
        </head>
        <body>
          <div class="wrapper">
            <div class="container">
              <div class="header">
                <h1>Access Informatique</h1>
                <p>Éditeur de solutions de gestion sur mesure</p>
              </div>
              <div class="body">
                <h2>{$title}</h2>
                {$content}
              </div>
              <div class="footer">
                &copy; {$year} Access Informatique &mdash;
                Yopougon Sable, Andokoi, Abidjan, Côte d'Ivoire<br>
                <a href="mailto:info@accessinformatique.com">info@accessinformatique.com</a> &nbsp;|&nbsp;
                (+225) 01 01 57 30 54
              </div>
            </div>
          </div>
        </body>
        </html>
        HTML;
    }
}

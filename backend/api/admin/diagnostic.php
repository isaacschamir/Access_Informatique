<?php
/**
 * diagnostic.php — Outil de diagnostic pour le système d'admin
 * Vérifier:
 *  - Configuration .env
 *  - Connexion base de données
 *  - Table admins et colonnes
 *  - Admins existants
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Response.php';

cors_headers();

header('Content-Type: text/html; charset=utf-8');
echo '<h1>Diagnostic Access Informatique Admin</h1>';
echo '<pre style="font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 8px;">';

echo "=== Configuration ===\n";
echo "APP_ENV: " . APP_ENV . "\n";
echo "JWT_SECRET length: " . strlen(JWT_SECRET) . " chars\n";
echo "Database: " . DB_HOST . " / " . DB_NAME . " as " . DB_USER . "\n\n";

try {
    require_once __DIR__ . '/../../includes/db.php';
    $db = get_db();
    echo "✓ Database connection OK\n\n";

    // Vérifier la table admins
    echo "=== Table ADMINS ===\n";
    $columns = $db->query('DESCRIBE admins')->fetchAll();
    if (empty($columns)) {
        echo "✗ Table 'admins' not found!\n";
    } else {
        echo "Colonnes:\n";
        foreach ($columns as $col) {
            echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    }

    echo "\n=== Admins en base ===\n";
    $admins = $db->query(
        'SELECT id, name, email, role, created_at FROM admins ORDER BY id ASC'
    )->fetchAll();

    if (empty($admins)) {
        echo "✗ Aucun admin trouvé! Vous devez en créer un avec:\n";
        echo "  php database/setup_admin.php create\n";
    } else {
        foreach ($admins as $admin) {
            echo sprintf(
                "  [%d] %s (%s) - Role: %s - Créé: %s\n",
                $admin['id'],
                $admin['name'],
                $admin['email'],
                $admin['role'],
                $admin['created_at']
            );
        }
    }

    echo "\n=== Test POST /admin/admins ===\n";
    echo "Utilisez AdminUsers.vue pour tester la création d'admin.\n";

} catch (PDOException $e) {
    echo "✗ Database Error: " . $e->getMessage() . "\n";
    echo "Vérifiez backend/.env\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";

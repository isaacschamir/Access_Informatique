<?php
/**
 * migrate_add_admin_role.php
 * -------------------------
 * Script CLI pour ajouter la colonne `role` à la table `admins` si
 * elle n'existe pas, et optionnellement définir le premier admin
 * existant en `superadmin`.
 *
 * Usage :
 *   php database/migrate_add_admin_role.php [--auto] [--make-superadmin]
 *
 * Options :
 *   --auto             : exécute sans confirmation interactive
 *   --make-superadmin  : attribue 'superadmin' au premier admin (id ASC)
 *
 * Exécuter depuis la racine du projet :
 *   php database/migrate_add_admin_role.php --auto --make-superadmin
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    echo "Ce script s'exécute en CLI seulement.\n";
    exit(1);
}

require_once __DIR__ . '/../backend/includes/config.php';
require_once __DIR__ . '/../backend/includes/db.php';

$auto = in_array('--auto', $argv, true);
$makeSuper = in_array('--make-superadmin', $argv, true);

try {
    $pdo = get_db();

    // Vérifier si la colonne existe déjà
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([DB_NAME, 'admins', 'role']);
    $row = $stmt->fetch();
    $exists = (int) ($row['c'] ?? 0) > 0;

    if ($exists) {
        echo "La colonne 'role' existe déjà dans la table 'admins'. Rien à faire.\n";
    } else {
        echo "La colonne 'role' est absente — préparation de l'ALTER TABLE.\n";
        if (!$auto) {
            echo "Confirmez-vous l'exécution de : ALTER TABLE admins ADD COLUMN role VARCHAR(50) NOT NULL DEFAULT 'editor' ? [o/N] : ";
            $ans = trim(fgets(STDIN));
            if (!in_array(strtolower($ans), ['o', 'oui', 'y', 'yes'], true)) {
                echo "Annulé par l'utilisateur.\n";
                exit(0);
            }
        }

        $pdo->beginTransaction();
        $sql = "ALTER TABLE `admins` ADD COLUMN `role` VARCHAR(50) NOT NULL DEFAULT 'editor' COMMENT 'superadmin|admin|editor' AFTER `password_hash`";
        $pdo->exec($sql);
        $pdo->commit();
        echo "Colonne 'role' ajoutée avec succès.\n";
    }

    if ($makeSuper) {
        echo "Attribution du rôle 'superadmin' au premier admin (ordre id ASC).\n";
        $pdo->beginTransaction();
        $first = $pdo->query('SELECT id FROM admins ORDER BY id ASC LIMIT 1')->fetch();
        if ($first && isset($first['id'])) {
            $pdo->prepare('UPDATE admins SET role = ? WHERE id = ?')->execute(['superadmin', (int)$first['id']]);
            echo "Administrateur ID " . $first['id'] . " mis en 'superadmin'.\n";
        } else {
            echo "Aucun administrateur trouvé pour attribuer le rôle.\n";
        }
        $pdo->commit();
    }

    echo "Migration terminée. Vérifiez votre base de données.\n";
    exit(0);

} catch (PDOException $e) {
    echo "Erreur PDO : " . $e->getMessage() . "\n";
    exit(2);
}

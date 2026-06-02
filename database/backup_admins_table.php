<?php
/**
 * backup_admins_table.php
 * Sauvegarde la table `admins` en générant un fichier SQL d'INSERT.
 * Usage: php database/backup_admins_table.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    echo "Ce script s'exécute en CLI seulement.\n";
    exit(1);
}

require_once __DIR__ . '/../backend/includes/config.php';
require_once __DIR__ . '/../backend/includes/db.php';

$outFile = __DIR__ . '/admins_backup.sql';
try {
    $pdo = get_db();

    // Récupère colonnes
    $colsStmt = $pdo->query('DESCRIBE admins');
    $cols = [];
    while ($row = $colsStmt->fetch(PDO::FETCH_ASSOC)) {
        $cols[] = $row['Field'];
    }
    if (empty($cols)) {
        echo "Table 'admins' introuvable ou vide.\n";
        exit(1);
    }

    $colList = implode(', ', array_map(function($c){ return "`$c`"; }, $cols));

    $rowsStmt = $pdo->query('SELECT * FROM admins');

    $fp = fopen($outFile, 'w');
    if (!$fp) {
        throw new RuntimeException("Impossible d'ouvrir $outFile en écriture");
    }

    fwrite($fp, "-- Backup de la table admins\n");
    fwrite($fp, "-- Generated: " . date('c') . "\n\n");

    $count = 0;
    while ($row = $rowsStmt->fetch(PDO::FETCH_ASSOC)) {
        $vals = [];
        foreach ($cols as $c) {
            $v = $row[$c];
            if (is_null($v)) {
                $vals[] = 'NULL';
            } else {
                $escaped = str_replace("'", "\\'", $v);
                $vals[] = "'" . $escaped . "'";
            }
        }
        $line = "INSERT INTO `admins` ($colList) VALUES (" . implode(', ', $vals) . ");\n";
        fwrite($fp, $line);
        $count++;
    }

    fclose($fp);
    echo "Sauvegarde terminée: $outFile ($count enregistrements)\n";
    exit(0);

} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    exit(2);
}

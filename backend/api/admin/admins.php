<?php
/**
 * GET    /api/admin/admins         — liste tous les administrateurs
 * POST   /api/admin/admins         — crée un administrateur
 * PUT    /api/admin/admins?id=1    — modifie un administrateur
 * DELETE /api/admin/admins?id=1    — supprime un administrateur
 * ---------------------------------------------------------------
 * Endpoint protégé : JWT requis + rôle superadmin.
 * ---------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Auth.php';

cors_headers();

$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
    error_response('Méthode non autorisée.', 405);
}

$admin = require_role('superadmin');
$db = get_db();

try {
    switch ($method) {
        case 'GET':
            $rows = $db->query(
                'SELECT id, name, email, role, created_at, updated_at
                   FROM admins
                  ORDER BY id ASC'
            )->fetchAll();
            json_response($rows);

        case 'POST':
            $data = get_json_body();
            $name  = trim($data['name'] ?? '');
            $email = trim($data['email'] ?? '');
            $password = trim($data['password'] ?? '');
            $role  = strtolower(trim($data['role'] ?? 'editor'));

            if ($name === '' || $email === '' || $password === '') {
                error_response('Champs "name", "email" et "password" obligatoires.', 422);
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                error_response('Email invalide.', 422);
            }
            if (strlen($password) < 10) {
                error_response('Mot de passe trop court (minimum 10 caractères).', 422);
            }
            if (!in_array($role, ['superadmin', 'admin', 'editor'], true)) {
                error_response('Rôle invalide. Choisissez superadmin, admin ou editor.', 422);
            }

            $stmt = $db->prepare('SELECT 1 FROM admins WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                error_response('Un administrateur existe déjà avec cet email.', 409);
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $insert = $db->prepare(
                'INSERT INTO admins (name, email, password_hash, role)
                 VALUES (?, ?, ?, ?)'
            );
            $insert->execute([$name, $email, $hash, $role]);

            json_response([
                'success' => true,
                'message' => 'Administrateur créé.',
                'id'      => (int) $db->lastInsertId(),
            ], 201);

        case 'PUT':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if ($id <= 0) {
                error_response('Paramètre "id" manquant ou invalide.', 422);
            }

            $data = get_json_body();
            $name  = trim($data['name'] ?? '');
            $email = trim($data['email'] ?? '');
            $role  = strtolower(trim($data['role'] ?? ''));

            $exists = $db->prepare('SELECT id FROM admins WHERE id = ?');
            $exists->execute([$id]);
            if (!$exists->fetch()) {
                error_response('Administrateur introuvable.', 404);
            }

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                error_response('Email invalide.', 422);
            }
            if ($role !== '' && !in_array($role, ['superadmin', 'admin', 'editor'], true)) {
                error_response('Rôle invalide. Choisissez superadmin, admin ou editor.', 422);
            }

            $updates = [];
            $params = [];

            if ($name !== '') {
                $updates[] = 'name = ?';
                $params[]  = substr($name, 0, 100);
            }
            if ($email !== '') {
                $updates[] = 'email = ?';
                $params[]  = substr($email, 0, 150);
            }
            if ($role !== '') {
                $updates[] = 'role = ?';
                $params[]  = $role;
            }

            if (empty($updates)) {
                error_response('Aucune donnée valide fournie pour la mise à jour.', 422);
            }

            if ($email !== '') {
                $check = $db->prepare('SELECT id FROM admins WHERE email = ? AND id != ? LIMIT 1');
                $check->execute([$email, $id]);
                if ($check->fetch()) {
                    error_response('Cet email est déjà utilisé par un autre administrateur.', 409);
                }
            }

            $params[] = $id;
            $db->prepare('UPDATE admins SET ' . implode(', ', $updates) . ' WHERE id = ?')
               ->execute($params);

            json_response(['success' => true, 'message' => 'Administrateur mis à jour.']);

        case 'DELETE':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if ($id <= 0) {
                error_response('Paramètre "id" manquant ou invalide.', 422);
            }

            $count = (int) $db->query('SELECT COUNT(*) FROM admins')->fetchColumn();
            if ($count <= 1) {
                error_response('Impossible de supprimer le dernier administrateur.', 403);
            }

            if ($id === (int) $admin['sub']) {
                error_response('Vous ne pouvez pas supprimer votre propre compte.', 403);
            }

            $exists = $db->prepare('SELECT id FROM admins WHERE id = ?');
            $exists->execute([$id]);
            if (!$exists->fetch()) {
                error_response('Administrateur introuvable.', 404);
            }

            $db->prepare('DELETE FROM admins WHERE id = ?')->execute([$id]);
            json_response(['success' => true, 'message' => 'Administrateur supprimé.']);
    }
} catch (PDOException $e) {
    error_log('[API/admin/admins] PDO : ' . $e->getMessage());
    error_response('Erreur interne du serveur.', 500);
}

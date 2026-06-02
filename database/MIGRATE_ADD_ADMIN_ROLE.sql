-- ALTER TABLE migration : ajouter le rôle des administrateurs
-- Exécuter sur la base existante Access_Informatique.

ALTER TABLE `admins`
  ADD COLUMN `role` VARCHAR(50) NOT NULL DEFAULT 'editor' COMMENT 'superadmin|admin|editor' AFTER `password_hash`;

-- Optionnel : définir le premier administrateur historique comme superadmin
-- UPDATE `admins` SET `role` = 'superadmin' ORDER BY `id` ASC LIMIT 1;

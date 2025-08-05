-- Script pour ajouter le champ prix des matières à la table ecue
-- À exécuter sur votre base de données

-- Ajouter le champ prix_matiere_cheval à la table ecue
ALTER TABLE `ecue` 
ADD COLUMN `prix_matiere_cheval` decimal(10,2) DEFAULT 25000.00 
COMMENT 'Prix de la matière pour les étudiants à cheval';

-- Mettre à jour les prix selon les crédits (exemple)
UPDATE `ecue` SET `prix_matiere_cheval` = 
  CASE 
    WHEN `credit_ecue` = 1 THEN 15000.00
    WHEN `credit_ecue` = 2 THEN 25000.00
    WHEN `credit_ecue` = 3 THEN 35000.00
    WHEN `credit_ecue` = 4 THEN 45000.00
    WHEN `credit_ecue` = 5 THEN 55000.00
    ELSE 25000.00
  END;

-- Vérifier les modifications
SELECT id_ecue, lib_ecue, credit_ecue, prix_matiere_cheval 
FROM ecue 
ORDER BY credit_ecue, lib_ecue 
LIMIT 10; 
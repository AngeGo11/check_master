-- Insertions dans la table moyenne_generale
-- Basé sur les données existantes des étudiants, années académiques et semestres

INSERT INTO `moyenne_generale` (`num_etd`, `id_ac`, `id_semestre`, `moyenne_generale`, `total_credits_obtenus`, `total_credits_inscrits`, `statut_academique`, `date_calcul`) VALUES
-- Étudiant KROUMA Franck Adams (num_etd: 2) - Master 2 (id_niv_etd: 5) - Semestre 9
(2, 2524, 9, 14.75, 45, 60, 'Validé', '2025-01-15 10:30:00'),

-- Étudiant AMANI Yves (num_etd: 3) - Licence 3 (id_niv_etd: 3) - Semestre 5
(3, 2524, 5, 12.50, 30, 45, 'Validé', '2025-01-15 11:15:00'),

-- Étudiant YAO Ama Marie-grâce (num_etd: 4) - Master 2 (id_niv_etd: 5) - Semestre 9
(4, 2524, 9, 13.25, 42, 60, 'Validé', '2025-01-15 12:00:00'),

-- Étudiant Gomez Angeaxel (num_etd: 18) - Master 2 (id_niv_etd: 5) - Semestre 9
(18, 2524, 9, 15.00, 48, 60, 'Validé', '2025-01-15 13:45:00'),

-- Étudiant Brou Kouamé Wa Ambroise (num_etd: 19) - Master 2 (id_niv_etd: 5) - Semestre 9
(19, 2524, 9, 11.75, 35, 60, 'Ajourné', '2025-01-15 14:20:00'),

-- Étudiant Coulibaly Pécory Ismaèl (num_etd: 20) - Master 2 (id_niv_etd: 5) - Semestre 9
(20, 2524, 9, 14.00, 40, 60, 'Validé', '2025-01-15 15:10:00'),

-- Étudiant Diomandé Gondo Patrick (num_etd: 21) - Master 2 (id_niv_etd: 5) - Semestre 9
(21, 2524, 9, 12.25, 38, 60, 'Autorisé', '2025-01-15 16:00:00'),

-- Étudiant Ekponou Georges (num_etd: 22) - Master 2 (id_niv_etd: 5) - Semestre 9
(22, 2524, 9, 13.75, 44, 60, 'Validé', '2025-01-15 16:45:00'),

-- Étudiant Gnaman Arthur Berenger (num_etd: 23) - Master 2 (id_niv_etd: 5) - Semestre 9
(23, 2524, 9, 11.50, 32, 60, 'Ajourné', '2025-01-15 17:30:00'),

-- Étudiant Guiégui Arnaud Kévin Boris (num_etd: 24) - Master 2 (id_niv_etd: 5) - Semestre 9
(24, 2524, 9, 14.50, 46, 60, 'Validé', '2025-01-15 18:15:00'),

-- Étudiant kacou Allou Yves-Roland (num_etd: 25) - Master 2 (id_niv_etd: 5) - Semestre 9
(25, 2524, 9, 12.75, 39, 60, 'Autorisé', '2025-01-15 19:00:00'),

-- Étudiant Kadio Paule Elodie (num_etd: 26) - Master 2 (id_niv_etd: 5) - Semestre 9
(26, 2524, 9, 13.00, 41, 60, 'Validé', '2025-01-15 19:45:00'),

-- Étudiant Kéi Ninsémon Hervé (num_etd: 27) - Master 2 (id_niv_etd: 5) - Semestre 9
(27, 2524, 9, 11.25, 30, 60, 'Ajourné', '2025-01-15 20:30:00'),

-- Étudiant Kinimo habia Elvire (num_etd: 28) - Master 2 (id_niv_etd: 5) - Semestre 9
(28, 2524, 9, 14.25, 43, 60, 'Validé', '2025-01-15 21:15:00'),

-- Étudiant Kouadio Donald (num_etd: 29) - Master 2 (id_niv_etd: 5) - Semestre 9
(29, 2524, 9, 12.00, 36, 60, 'Autorisé', '2025-01-15 22:00:00');

-- Insertions pour l'année académique précédente (2625) - Quelques exemples
INSERT INTO `moyenne_generale` (`num_etd`, `id_ac`, `id_semestre`, `moyenne_generale`, `total_credits_obtenus`, `total_credits_inscrits`, `statut_academique`, `date_calcul`) VALUES
-- Étudiant KROUMA Franck Adams - Semestre 8 (Master 1)
(2, 2625, 8, 13.50, 40, 60, 'Validé', '2024-06-15 10:30:00'),

-- Étudiant AMANI Yves - Semestre 4 (Licence 2)
(3, 2625, 4, 12.75, 35, 45, 'Validé', '2024-06-15 11:15:00'),

-- Étudiant YAO Ama Marie-grâce - Semestre 8 (Master 1)
(4, 2625, 8, 14.00, 42, 60, 'Validé', '2024-06-15 12:00:00');

-- ========================================
-- INSERTIONS DANS EVALUER_UE POUR LES ÉTUDIANTS M2
-- ========================================

-- Insertions pour les UE évaluées directement (Master 2 - Semestre 9)
INSERT INTO `evaluer_ue` (`num_etd`, `id_ue`, `id_semestre`, `id_ac`, `id_personnel_adm`, `note`, `credit`, `date_eval`) VALUES
-- Étudiant KROUMA Franck Adams (num_etd: 2)
(2, 1, 9, 2524, 1, 15.50, 6, '2025-01-15 10:30:00'),
(2, 2, 9, 2524, 1, 14.75, 4, '2025-01-15 10:30:00'),
(2, 3, 9, 2524, 1, 16.00, 5, '2025-01-15 10:30:00'),

-- Étudiant YAO Ama Marie-grâce (num_etd: 4)
(4, 1, 9, 2524, 1, 13.25, 6, '2025-01-15 12:00:00'),
(4, 2, 9, 2524, 1, 12.50, 4, '2025-01-15 12:00:00'),
(4, 3, 9, 2524, 1, 14.00, 5, '2025-01-15 12:00:00'),

-- Étudiant Gomez Angeaxel (num_etd: 18)
(18, 1, 9, 2524, 1, 16.00, 6, '2025-01-15 13:45:00'),
(18, 2, 9, 2524, 1, 15.25, 4, '2025-01-15 13:45:00'),
(18, 3, 9, 2524, 1, 15.75, 5, '2025-01-15 13:45:00'),

-- Étudiant Coulibaly Pécory Ismaèl (num_etd: 20)
(20, 1, 9, 2524, 1, 14.50, 6, '2025-01-15 15:10:00'),
(20, 2, 9, 2524, 1, 13.75, 4, '2025-01-15 15:10:00'),
(20, 3, 9, 2524, 1, 14.25, 5, '2025-01-15 15:10:00'),

-- Étudiant Diomandé Gondo Patrick (num_etd: 21)
(21, 1, 9, 2524, 1, 12.00, 6, '2025-01-15 16:00:00'),
(21, 2, 9, 2524, 1, 11.50, 4, '2025-01-15 16:00:00'),
(21, 3, 9, 2524, 1, 13.25, 5, '2025-01-15 16:00:00'),

-- Étudiant Ekponou Georges (num_etd: 22)
(22, 1, 9, 2524, 1, 14.75, 6, '2025-01-15 16:45:00'),
(22, 2, 9, 2524, 1, 13.50, 4, '2025-01-15 16:45:00'),
(22, 3, 9, 2524, 1, 15.00, 5, '2025-01-15 16:45:00'),

-- Étudiant Guiégui Arnaud Kévin Boris (num_etd: 24)
(24, 1, 9, 2524, 1, 15.25, 6, '2025-01-15 18:15:00'),
(24, 2, 9, 2524, 1, 14.00, 4, '2025-01-15 18:15:00'),
(24, 3, 9, 2524, 1, 15.75, 5, '2025-01-15 18:15:00'),

-- Étudiant kacou Allou Yves-Roland (num_etd: 25)
(25, 1, 9, 2524, 1, 12.75, 6, '2025-01-15 19:00:00'),
(25, 2, 9, 2524, 1, 12.00, 4, '2025-01-15 19:00:00'),
(25, 3, 9, 2524, 1, 13.50, 5, '2025-01-15 19:00:00'),

-- Étudiant Kadio Paule Elodie (num_etd: 26)
(26, 1, 9, 2524, 1, 13.50, 6, '2025-01-15 19:45:00'),
(26, 2, 9, 2524, 1, 12.75, 4, '2025-01-15 19:45:00'),
(26, 3, 9, 2524, 1, 14.25, 5, '2025-01-15 19:45:00'),

-- Étudiant Kinimo habia Elvire (num_etd: 28)
(28, 1, 9, 2524, 1, 14.75, 6, '2025-01-15 21:15:00'),
(28, 2, 9, 2524, 1, 13.50, 4, '2025-01-15 21:15:00'),
(28, 3, 9, 2524, 1, 15.00, 5, '2025-01-15 21:15:00'),

-- Étudiant Kouadio Donald (num_etd: 29)
(29, 1, 9, 2524, 1, 12.25, 6, '2025-01-15 22:00:00'),
(29, 2, 9, 2524, 1, 11.75, 4, '2025-01-15 22:00:00'),
(29, 3, 9, 2524, 1, 13.00, 5, '2025-01-15 22:00:00');

-- ========================================
-- INSERTIONS DANS EVALUER_ECUE POUR LES ÉTUDIANTS M2
-- ========================================

-- Insertions pour les ECUE (Master 2 - Semestre 9)
INSERT INTO `evaluer_ecue` (`num_etd`, `id_ecue`, `id_semestre`, `id_ac`, `id_personnel_adm`, `note`, `credit`, `date_eval`) VALUES
-- Étudiant KROUMA Franck Adams (num_etd: 2)
(2, 1, 9, 2524, 1, 15.75, 3, '2025-01-15 10:30:00'),
(2, 2, 9, 2524, 1, 14.50, 2, '2025-01-15 10:30:00'),
(2, 3, 9, 2524, 1, 16.25, 4, '2025-01-15 10:30:00'),
(2, 4, 9, 2524, 1, 15.00, 3, '2025-01-15 10:30:00'),

-- Étudiant YAO Ama Marie-grâce (num_etd: 4)
(4, 1, 9, 2524, 1, 13.50, 3, '2025-01-15 12:00:00'),
(4, 2, 9, 2524, 1, 12.75, 2, '2025-01-15 12:00:00'),
(4, 3, 9, 2524, 1, 14.25, 4, '2025-01-15 12:00:00'),
(4, 4, 9, 2524, 1, 13.00, 3, '2025-01-15 12:00:00'),

-- Étudiant Gomez Angeaxel (num_etd: 18)
(18, 1, 9, 2524, 1, 16.25, 3, '2025-01-15 13:45:00'),
(18, 2, 9, 2524, 1, 15.50, 2, '2025-01-15 13:45:00'),
(18, 3, 9, 2524, 1, 16.75, 4, '2025-01-15 13:45:00'),
(18, 4, 9, 2524, 1, 15.75, 3, '2025-01-15 13:45:00'),

-- Étudiant Coulibaly Pécory Ismaèl (num_etd: 20)
(20, 1, 9, 2524, 1, 14.75, 3, '2025-01-15 15:10:00'),
(20, 2, 9, 2524, 1, 13.50, 2, '2025-01-15 15:10:00'),
(20, 3, 9, 2524, 1, 15.25, 4, '2025-01-15 15:10:00'),
(20, 4, 9, 2524, 1, 14.00, 3, '2025-01-15 15:10:00'),

-- Étudiant Diomandé Gondo Patrick (num_etd: 21)
(21, 1, 9, 2524, 1, 12.25, 3, '2025-01-15 16:00:00'),
(21, 2, 9, 2524, 1, 11.75, 2, '2025-01-15 16:00:00'),
(21, 3, 9, 2524, 1, 13.50, 4, '2025-01-15 16:00:00'),
(21, 4, 9, 2524, 1, 12.00, 3, '2025-01-15 16:00:00'),

-- Étudiant Ekponou Georges (num_etd: 22)
(22, 1, 9, 2524, 1, 15.00, 3, '2025-01-15 16:45:00'),
(22, 2, 9, 2524, 1, 13.75, 2, '2025-01-15 16:45:00'),
(22, 3, 9, 2524, 1, 15.50, 4, '2025-01-15 16:45:00'),
(22, 4, 9, 2524, 1, 14.25, 3, '2025-01-15 16:45:00'),

-- Étudiant Guiégui Arnaud Kévin Boris (num_etd: 24)
(24, 1, 9, 2524, 1, 15.50, 3, '2025-01-15 18:15:00'),
(24, 2, 9, 2524, 1, 14.25, 2, '2025-01-15 18:15:00'),
(24, 3, 9, 2524, 1, 16.00, 4, '2025-01-15 18:15:00'),
(24, 4, 9, 2524, 1, 15.25, 3, '2025-01-15 18:15:00'),

-- Étudiant kacou Allou Yves-Roland (num_etd: 25)
(25, 1, 9, 2524, 1, 13.00, 3, '2025-01-15 19:00:00'),
(25, 2, 9, 2524, 1, 12.25, 2, '2025-01-15 19:00:00'),
(25, 3, 9, 2524, 1, 14.00, 4, '2025-01-15 19:00:00'),
(25, 4, 9, 2524, 1, 12.75, 3, '2025-01-15 19:00:00'),

-- Étudiant Kadio Paule Elodie (num_etd: 26)
(26, 1, 9, 2524, 1, 13.75, 3, '2025-01-15 19:45:00'),
(26, 2, 9, 2524, 1, 13.00, 2, '2025-01-15 19:45:00'),
(26, 3, 9, 2524, 1, 14.50, 4, '2025-01-15 19:45:00'),
(26, 4, 9, 2524, 1, 13.25, 3, '2025-01-15 19:45:00'),

-- Étudiant Kinimo habia Elvire (num_etd: 28)
(28, 1, 9, 2524, 1, 15.25, 3, '2025-01-15 21:15:00'),
(28, 2, 9, 2524, 1, 13.75, 2, '2025-01-15 21:15:00'),
(28, 3, 9, 2524, 1, 15.50, 4, '2025-01-15 21:15:00'),
(28, 4, 9, 2524, 1, 14.75, 3, '2025-01-15 21:15:00'),

-- Étudiant Kouadio Donald (num_etd: 29)
(29, 1, 9, 2524, 1, 12.50, 3, '2025-01-15 22:00:00'),
(29, 2, 9, 2524, 1, 11.25, 2, '2025-01-15 22:00:00'),
(29, 3, 9, 2524, 1, 13.25, 4, '2025-01-15 22:00:00'),
(29, 4, 9, 2524, 1, 12.00, 3, '2025-01-15 22:00:00'); 
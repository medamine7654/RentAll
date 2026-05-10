-- Migration pour ajouter les champs statut, date_creation et message à la table participant

-- Ajouter la colonne statut
ALTER TABLE participant 
ADD COLUMN statut VARCHAR(50) NOT NULL DEFAULT 'en_attente' AFTER covoiturage_id;

-- Ajouter la colonne date_creation
ALTER TABLE participant 
ADD COLUMN date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER statut;

-- Ajouter la colonne message (optionnel)
ALTER TABLE participant 
ADD COLUMN message TEXT NULL AFTER date_creation;

-- Mettre à jour les participants existants pour avoir le statut 'confirme' (car ils étaient acceptés automatiquement avant)
UPDATE participant SET statut = 'confirme' WHERE statut = 'en_attente';

-- Créer un index sur le statut pour améliorer les performances
CREATE INDEX idx_participant_statut ON participant(statut);

-- Créer un index sur la date de création
CREATE INDEX idx_participant_date_creation ON participant(date_creation);

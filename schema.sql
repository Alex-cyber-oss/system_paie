CREATE DATABASE IF NOT EXISTS systeme_paie CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE systeme_paie;

DROP TABLE IF EXISTS Bulletin_Retenue;
DROP TABLE IF EXISTS Bulletin_Prime;
DROP TABLE IF EXISTS Tranche;
DROP TABLE IF EXISTS Bulletin_paye;
DROP TABLE IF EXISTS Avance_sur_salaire;
DROP TABLE IF EXISTS Releve_horaire;
DROP TABLE IF EXISTS Contrat;
DROP TABLE IF EXISTS Employe;
DROP TABLE IF EXISTS Retenue;
DROP TABLE IF EXISTS Prime;
DROP TABLE IF EXISTS Service;
DROP TABLE IF EXISTS Grade;

CREATE TABLE IF NOT EXISTS Grade (
    Id_Grade INT NOT NULL AUTO_INCREMENT,
    libelle_grade VARCHAR(50) NOT NULL,
    salaire_base DECIMAL(15,2) NOT NULL,
    taux_heures_supp DECIMAL(5,2) NOT NULL,
    CONSTRAINT PK_Grade PRIMARY KEY (Id_Grade)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Service (
    Id_Service INT NOT NULL AUTO_INCREMENT,
    nom_service VARCHAR(50) NOT NULL,
    CONSTRAINT PK_Service PRIMARY KEY (Id_Service)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Prime (
    Id_Prime INT NOT NULL AUTO_INCREMENT,
    libelle VARCHAR(50) NOT NULL,
    CONSTRAINT PK_Prime PRIMARY KEY (Id_Prime)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Retenue (
    Id_Retenue INT NOT NULL AUTO_INCREMENT,
    libelle VARCHAR(50) NOT NULL,
    taux_retenue DECIMAL(5,2) NOT NULL,
    nbr_unite DECIMAL(15,2),
    CONSTRAINT PK_Retenue PRIMARY KEY (Id_Retenue)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Employe (
    Id_Employe INT NOT NULL AUTO_INCREMENT,
    Nom_employe VARCHAR(50) NOT NULL,
    Prenom_employe VARCHAR(50) NOT NULL,
    Adresse VARCHAR(100),
    RIB_employe VARCHAR(30) NOT NULL,
    date_embauche DATE NOT NULL,
    num_secu_sociale VARCHAR(15) NOT NULL,
    taux_horaire DECIMAL(15,2) NOT NULL,
    Id_Grade INT NOT NULL,
    Id_Service INT NOT NULL,
    CONSTRAINT PK_Employe PRIMARY KEY (Id_Employe),
    CONSTRAINT UQ_RIB UNIQUE (RIB_employe),
    CONSTRAINT UQ_NSS UNIQUE (num_secu_sociale),
    CONSTRAINT FK_Emp_Grade FOREIGN KEY (Id_Grade) REFERENCES Grade (Id_Grade),
    CONSTRAINT FK_Emp_Serv FOREIGN KEY (Id_Service) REFERENCES Service (Id_Service)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Contrat (
    Id_Contrat INT NOT NULL AUTO_INCREMENT,
    type_contrat VARCHAR(10) NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE,
    Id_Employe INT NOT NULL,
    CONSTRAINT PK_Contrat PRIMARY KEY (Id_Contrat),
    CONSTRAINT CHK_Type CHECK (type_contrat IN ('CDI','CDD')),
    CONSTRAINT FK_Cont_Emp FOREIGN KEY (Id_Employe) REFERENCES Employe (Id_Employe)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Releve_horaire (
    Id_Releve_horaire INT NOT NULL AUTO_INCREMENT,
    mois_concerne INT NOT NULL,
    annee_concernee INT NOT NULL,
    nb_heures_normales DECIMAL(15,2) NOT NULL,
    nb_heures_supp DECIMAL(15,2) NOT NULL DEFAULT 0,
    taux_heures_supp DECIMAL(5,2) NOT NULL DEFAULT 1.25,
    Id_Employe INT NOT NULL,
    CONSTRAINT PK_Releve PRIMARY KEY (Id_Releve_horaire),
    CONSTRAINT CHK_Mois CHECK (mois_concerne BETWEEN 1 AND 12),
    CONSTRAINT UQ_Releve UNIQUE (Id_Employe, mois_concerne, annee_concernee),
    CONSTRAINT FK_Rel_Emp FOREIGN KEY (Id_Employe) REFERENCES Employe (Id_Employe)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Avance_sur_salaire (
    Id_Avance INT NOT NULL AUTO_INCREMENT,
    Montant DECIMAL(15,2) NOT NULL,
    date_avancement DATE NOT NULL,
    Etat_de_remboursement VARCHAR(20) NOT NULL DEFAULT 'EN COURS',
    Id_Employe INT NOT NULL,
    CONSTRAINT PK_Avance PRIMARY KEY (Id_Avance),
    CONSTRAINT CHK_Etat CHECK (Etat_de_remboursement IN ('EN COURS','SOLDE','ANNULE')),
    CONSTRAINT FK_Av_Emp FOREIGN KEY (Id_Employe) REFERENCES Employe (Id_Employe)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Bulletin_paye (
    Id_Bulletin_paye INT NOT NULL AUTO_INCREMENT,
    date_paye DATE NOT NULL,
    mois_de_paye INT NOT NULL,
    annee_de_paye INT NOT NULL,
    Salaire_brut DECIMAL(15,2) NOT NULL,
    salaire_net DECIMAL(15,2) NOT NULL,
    net_a_payer DECIMAL(15,2) NOT NULL,
    Id_Employe INT NOT NULL,
    Id_Releve_horaire INT NOT NULL,
    CONSTRAINT PK_Bulletin PRIMARY KEY (Id_Bulletin_paye),
    CONSTRAINT UQ_BulletinMois UNIQUE (Id_Employe, mois_de_paye, annee_de_paye),
    CONSTRAINT FK_Bull_Emp FOREIGN KEY (Id_Employe) REFERENCES Employe (Id_Employe),
    CONSTRAINT FK_Bull_Rel FOREIGN KEY (Id_Releve_horaire) REFERENCES Releve_horaire (Id_Releve_horaire)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Tranche (
    Id_Tranche INT NOT NULL AUTO_INCREMENT,
    nbr_mois INT NOT NULL,
    Prelevement_mensuel DECIMAL(15,2) NOT NULL,
    Id_Avance INT NOT NULL,
    Id_Bulletin_paye INT,
    CONSTRAINT PK_Tranche PRIMARY KEY (Id_Tranche),
    CONSTRAINT FK_Tr_Avance FOREIGN KEY (Id_Avance) REFERENCES Avance_sur_salaire (Id_Avance),
    CONSTRAINT FK_Tr_Bull FOREIGN KEY (Id_Bulletin_paye) REFERENCES Bulletin_paye (Id_Bulletin_paye)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Bulletin_Prime (
    Id_Bulletin_paye INT NOT NULL,
    Id_Prime INT NOT NULL,
    Montant DECIMAL(15,2) NOT NULL,
    CONSTRAINT PK_BP PRIMARY KEY (Id_Bulletin_paye, Id_Prime),
    CONSTRAINT FK_BP_B FOREIGN KEY (Id_Bulletin_paye) REFERENCES Bulletin_paye (Id_Bulletin_paye),
    CONSTRAINT FK_BP_P FOREIGN KEY (Id_Prime) REFERENCES Prime (Id_Prime)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Bulletin_Retenue (
    Id_Bulletin_paye INT NOT NULL,
    Id_Retenue INT NOT NULL,
    Montant DECIMAL(15,2) NOT NULL,
    CONSTRAINT PK_BR PRIMARY KEY (Id_Bulletin_paye, Id_Retenue),
    CONSTRAINT FK_BR_B FOREIGN KEY (Id_Bulletin_paye) REFERENCES Bulletin_paye (Id_Bulletin_paye),
    CONSTRAINT FK_BR_R FOREIGN KEY (Id_Retenue) REFERENCES Retenue (Id_Retenue)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Grade_Prime (
    Id_Grade INT NOT NULL,
    Id_Prime INT NOT NULL,
    montant_default DECIMAL(15,2) NOT NULL DEFAULT 0,
    CONSTRAINT PK_GP PRIMARY KEY (Id_Grade, Id_Prime),
    CONSTRAINT FK_GP_G FOREIGN KEY (Id_Grade) REFERENCES Grade (Id_Grade),
    CONSTRAINT FK_GP_P FOREIGN KEY (Id_Prime) REFERENCES Prime (Id_Prime)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Grade_Retenue (
    Id_Grade INT NOT NULL,
    Id_Retenue INT NOT NULL,
    montant_default DECIMAL(15,2) NOT NULL DEFAULT 0,
    CONSTRAINT PK_GR PRIMARY KEY (Id_Grade, Id_Retenue),
    CONSTRAINT FK_GR_G FOREIGN KEY (Id_Grade) REFERENCES Grade (Id_Grade),
    CONSTRAINT FK_GR_R FOREIGN KEY (Id_Retenue) REFERENCES Retenue (Id_Retenue)
) ENGINE=InnoDB;

INSERT INTO Grade (libelle_grade, salaire_base, taux_heures_supp) VALUES
('Agent d exécution', 2500.00, 1.25),
('Technicien', 3500.00, 1.25),
('Cadre A', 5000.00, 1.50);

INSERT INTO Service (nom_service) VALUES
('Ressources Humaines'),
('Comptabilite'),
('Informatique');

INSERT INTO Prime (libelle) VALUES
('Prime de transport'),
('Prime d anciennete'),
('Prime de rendement');

INSERT INTO Retenue (libelle, taux_retenue) VALUES
('CNSS salariale', 4.48),
('Assurance maladie', 2.26),
('Impot sur le revenu', 15.00);

INSERT INTO Grade_Prime (Id_Grade, Id_Prime, montant_default) VALUES
(1, 1, 50.00),  -- Agent d'exécution: Prime de transport
(1, 2, 100.00), -- Prime d'ancienneté
(1, 3, 200.00), -- Prime de rendement
(2, 1, 75.00),  -- Technicien
(2, 2, 150.00),
(2, 3, 300.00),
(3, 1, 100.00), -- Cadre A
(3, 2, 200.00),
(3, 3, 500.00);

INSERT INTO Grade_Retenue (Id_Grade, Id_Retenue, montant_default) VALUES
(1, 1, 112.00), -- CNSS for agent
(1, 2, 56.50),
(1, 3, 375.00),
(2, 1, 156.00),
(2, 2, 78.50),
(2, 3, 525.00),
(3, 1, 224.00),
(3, 2, 112.50),
(3, 3, 750.00);

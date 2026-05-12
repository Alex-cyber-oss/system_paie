# Guide d'installation - Système de Gestion de Paie

## 1️⃣ Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur (ou MariaDB)
- Un serveur web (Apache, Nginx, etc.)
- Un client MySQL (ligne de commande ou interface graphique comme phpMyAdmin)

## 2️⃣ Configuration de la base de données

### Option A : Ligne de commande MySQL

```bash
# Importer le schéma complet (crée la base et toutes les tables)
mysql -u root -p < /chemin/vers/schema.sql

# Ou si MySQL est accessible sans mot de passe :
mysql -u root < /chemin/vers/schema.sql
```

### Option B : phpMyAdmin

1. Ouvrir phpMyAdmin : `http://localhost/phpmyadmin`
2. Cliquer sur l'onglet "Importer"
3. Sélectionner le fichier `schema.sql`
4. Cliquer sur "Importer"

### Option C : MySQL Workbench

1. Ouvrir MySQL Workbench
2. Créer une nouvelle connexion ou utiliser la connexion existante
3. Cliquer sur "File" → "Open SQL Script"
4. Sélectionner `schema.sql`
5. Cliquer sur le bouton "Execute" (éclair)

## 3️⃣ Configuration PHP

Ouvrir le fichier `config.php` et vérifier/ajuster les paramètres :

```php
$host = 'localhost';      // Adresse du serveur MySQL
$db   = 'systeme_paie';   // Nom de la base (laisser comme c'est)
$user = 'root';           // Utilisateur MySQL
$pass = '';               // Mot de passe (laisser vide si pas de mot de passe)
```

Si votre MySQL a un mot de passe, modifiez :
```php
$pass = 'votre_mot_de_passe';
```

## 4️⃣ Déploiement de l'application

### Sur un serveur local (XAMPP, WAMP, MAMP)

1. Copier le dossier `systeme_paie` dans le répertoire web :
   - XAMPP : `C:\xampp\htdocs\systeme_paie` (Windows) ou `/Applications/XAMPP/htdocs/systeme_paie` (Mac)
   - WAMP : `C:\wamp64\www\systeme_paie`
   - MAMP : `/Applications/MAMP/htdocs/systeme_paie`

2. Lancer le serveur (XAMPP Control Panel, WAMP tray, etc.)

3. Ouvrir le navigateur et accéder à : `http://localhost/systeme_paie/index.php`

### Sur un serveur distant (cPanel, Plesk, etc.)

1. Télécharger le dossier `systeme_paie` via FTP
2. Placer les fichiers dans le répertoire public (`public_html` ou `www`)
3. Configurer les identifiants MySQL de l'hébergeur dans `config.php`
4. Importer `schema.sql` via l'interface d'administration ou phpMyAdmin fournie
5. Accéder via : `http://votre-domaine.com/systeme_paie/`

## 5️⃣ Utilisation de l'application

### Workflow complet :

1. **Gestion des références** :
   - Créer les **Grades** (Agent, Technicien, Cadre...)
   - Créer les **Services** (RH, Comptabilité, Informatique...)
   - Créer les **Primes** (Prime de transport, d'ancienneté...)
   - Créer les **Retenues** (CNSS, Assurance maladie, Impôts...)

2. **Gestion des employés** :
   - Créer les **Employés** avec leurs données personnelles
   - Créer les **Contrats** (CDI/CDD) pour chaque employé

3. **Gestion des heures** :
   - Saisir les **Relevés horaires** mensuels (heures normales et supplémentaires)
   - Gérer les **Avances sur salaire** (optionnel)

4. **Génération des bulletins** :
   - Créer un **Bulletin de paie** pour chaque employé et période
   - Ajouter les **Primes** au bulletin
   - Ajouter les **Retenues** au bulletin
   - Cliquer sur "Calculer" pour générer automatiquement le salaire net
   - Valider le bulletin

## 6️⃣ Vérification de l'installation

Pour vérifier que tout fonctionne :

1. Accéder à l'application : `http://localhost/systeme_paie/`
2. Vous devriez voir un **tableau de bord** avec les statistiques
3. Tester la création d'un Grade
4. Vérifier que le message de succès s'affiche

## ❌ Résolution des problèmes

### Erreur : "Échec de connexion à la base de données"
- Vérifier que MySQL est en cours d'exécution
- Vérifier les identifiants dans `config.php`
- S'assurer que la base `systeme_paie` existe

### Erreur SQL : "Table doesn't exist"
- Vérifier que `schema.sql` a été complètement importé
- Réimporter `schema.sql` en supprimant la base existante

### Erreur 500 - White Screen
- Vérifier les fichiers de log PHP
- Vérifier la syntaxe PHP : `php -l config.php`

### Les boutons "Calculer" n'apparaissent pas
- Vérifier que les fichiers `bulletin_calcul.php` et `bulletin_primes.php` existent
- Actualiser le navigateur (Ctrl+F5 ou Cmd+Shift+R)

## 📚 Structure des fichiers

```
systeme_paie/
├── config.php              # Configuration MySQL
├── functions.php           # Fonctions utiles
├── header.php             # En-tête commun (navigation)
├── footer.php             # Pied de page
├── schema.sql             # Schéma de la base de données
├── index.php              # Tableau de bord
├── grade.php              # CRUD Grades
├── service.php            # CRUD Services
├── prime.php              # CRUD Primes
├── retenue.php            # CRUD Retenues
├── employe.php            # CRUD Employés
├── contrat.php            # CRUD Contrats
├── releve_horaire.php     # CRUD Relevés horaires
├── avance.php             # CRUD Avances sur salaire
├── bulletin.php           # CRUD Bulletins
├── bulletin_primes.php    # Gestion primes/retenues du bulletin
├── bulletin_calcul.php    # Calcul complet du bulletin
├── README.md              # Documentation courte
└── SETUP.md              # Ce fichier
```

---

**Support** : Pour toute question, consultez la documentation du code ou les commentaires dans les fichiers PHP.

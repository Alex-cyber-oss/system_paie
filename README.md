# Système de gestion de paie

Ce projet fournit une application PHP simple pour gérer les données de paie : grades, services, primes, retenues, employés et bulletins.

## Installation

1. Copier le dossier `systeme_paie` dans votre serveur web (Apache / Nginx + PHP).
2. Créer la base de données en exécutant `schema.sql` depuis votre client MySQL :

   ```bash
   mysql -u root -p < schema.sql
   ```

3. Vérifier le fichier `config.php` et ajuster les paramètres de connexion MySQL si nécessaire.
4. Ouvrir `index.php` dans votre navigateur via votre serveur local.

## Pages disponibles

- `index.php` : tableau de bord
- `grade.php` : gestion des grades
- `service.php` : gestion des services
- `prime.php` : gestion des primes
- `retenue.php` : gestion des retenues
- `employe.php` : gestion des employés
- `bulletin.php` : gestion des bulletins de paie

## Notes

- Le code est volontairement simple pour faciliter l'adaptation.
- Vous pouvez enrichir le système avec les pages `contrat.php`, `releve_horaire.php`, `avance.php`, `tranche.php` et les tables de jonction plus tard.
- Bootstrap est chargé depuis le CDN pour l'affichage.

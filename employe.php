<?php
require_once __DIR__ . '/functions.php';
$action = $_GET['action'] ?? '';
$grades = queryAll('SELECT Id_Grade, libelle_grade FROM Grade ORDER BY libelle_grade');
$services = queryAll('SELECT Id_Service, nom_service FROM Service ORDER BY nom_service');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $mysqli->real_escape_string($_POST['Nom_employe'] ?? '');
    $prenom = $mysqli->real_escape_string($_POST['Prenom_employe'] ?? '');
    $adresse = $mysqli->real_escape_string($_POST['Adresse'] ?? '');
    $rib = $mysqli->real_escape_string($_POST['RIB_employe'] ?? '');
    $date = $mysqli->real_escape_string($_POST['date_embauche'] ?? '');
    $nss = $mysqli->real_escape_string($_POST['num_secu_sociale'] ?? '');
    $taux = $mysqli->real_escape_string($_POST['taux_horaire'] ?? '0');
    $grade = (int) ($_POST['Id_Grade'] ?? 0);
    $service = (int) ($_POST['Id_Service'] ?? 0);
    if ($_POST['id']) {
        $id = (int) $_POST['id'];
        $mysqli->query("UPDATE Employe SET Nom_employe = '$nom', Prenom_employe = '$prenom', Adresse = '$adresse', RIB_employe = '$rib', date_embauche = '$date', num_secu_sociale = '$nss', taux_horaire = $taux, Id_Grade = $grade, Id_Service = $service WHERE Id_Employe = $id");
        flash('Employé modifié avec succès.');
    } else {
        $mysqli->query("INSERT INTO Employe (Nom_employe, Prenom_employe, Adresse, RIB_employe, date_embauche, num_secu_sociale, taux_horaire, Id_Grade, Id_Service) VALUES ('$nom', '$prenom', '$adresse', '$rib', '$date', '$nss', $taux, $grade, $service)");
        flash('Employé ajouté avec succès.');
    }
    redirect('employe.php');
}
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $mysqli->query("DELETE FROM Employe WHERE Id_Employe = $id");
    flash('Employé supprimé.');
    redirect('employe.php');
}
$employees = queryAll('SELECT e.*, g.libelle_grade, s.nom_service FROM Employe e JOIN Grade g ON e.Id_Grade = g.Id_Grade JOIN Service s ON e.Id_Service = s.Id_Service ORDER BY Id_Employe DESC');
$edited = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $edited = queryAll("SELECT * FROM Employe WHERE Id_Employe = $id")->fetch_assoc();
}
?>
<?php include 'header.php'; ?>
<div class="row">
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header"><?= $edited ? 'Modifier un employé' : 'Ajouter un employé' ?></div>
            <div class="card-body">
                <form method="post" action="employe.php">
                    <input type="hidden" name="id" value="<?= h($edited['Id_Employe'] ?? '') ?>">
                    <div class="mb-3"><label class="form-label">Nom</label><input name="Nom_employe" class="form-control" required value="<?= h($edited['Nom_employe'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label">Prénom</label><input name="Prenom_employe" class="form-control" required value="<?= h($edited['Prenom_employe'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label">Adresse</label><input name="Adresse" class="form-control" value="<?= h($edited['Adresse'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label">RIB</label><input name="RIB_employe" class="form-control" required value="<?= h($edited['RIB_employe'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label">Date d'embauche</label><input name="date_embauche" type="date" class="form-control" required value="<?= h($edited['date_embauche'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label">Numéro de sécurité sociale</label><input name="num_secu_sociale" class="form-control" required value="<?= h($edited['num_secu_sociale'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label">Taux horaire</label><input name="taux_horaire" type="number" step="0.01" class="form-control" required value="<?= h($edited['taux_horaire'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label">Grade</label><select name="Id_Grade" class="form-select" required><option value="">Choisir un grade</option><?php while ($grade = $grades->fetch_assoc()): ?><option value="<?= h($grade['Id_Grade']) ?>" <?= isset($edited['Id_Grade']) && $edited['Id_Grade'] == $grade['Id_Grade'] ? 'selected' : '' ?>><?= h($grade['libelle_grade']) ?></option><?php endwhile; ?></select></div>
                    <?php $services = queryAll('SELECT Id_Service, nom_service FROM Service ORDER BY nom_service'); ?><div class="mb-3"><label class="form-label">Service</label><select name="Id_Service" class="form-select" required><option value="">Choisir un service</option><?php while ($service = $services->fetch_assoc()): ?><option value="<?= h($service['Id_Service']) ?>" <?= isset($edited['Id_Service']) && $edited['Id_Service'] == $service['Id_Service'] ? 'selected' : '' ?>><?= h($service['nom_service']) ?></option><?php endwhile; ?></select></div>
                    <button class="btn btn-primary"><?= $edited ? 'Mettre à jour' : 'Ajouter' ?></button>
                    <?php if ($edited): ?><a href="employe.php" class="btn btn-secondary">Annuler</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Liste des employés</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>ID</th><th>Nom</th><th>Grade</th><th>Service</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php while ($employe = $employees->fetch_assoc()): ?>
                            <tr>
                                <td><?= h($employe['Id_Employe']) ?></td>
                                <td><?= h($employe['Nom_employe'] . ' ' . $employe['Prenom_employe']) ?></td>
                                <td><?= h($employe['libelle_grade']) ?></td>
                                <td><?= h($employe['nom_service']) ?></td>
                                <td>
                                    <a href="employe.php?action=edit&id=<?= h($employe['Id_Employe']) ?>" class="btn btn-sm btn-outline-secondary">Modifier</a>
                                    <a href="employe.php?action=delete&id=<?= h($employe['Id_Employe']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cet employé ?');">Supprimer</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
<?php
require_once __DIR__ . '/functions.php';
$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $libelle = $mysqli->real_escape_string($_POST['libelle'] ?? '');
    $salaire = $mysqli->real_escape_string($_POST['salaire_base'] ?? '0');
    $taux = $mysqli->real_escape_string($_POST['taux_heures_supp'] ?? '0');
    if ($_POST['id']) {
        $id = (int) $_POST['id'];
        $mysqli->query("UPDATE Grade SET libelle_grade = '$libelle', salaire_base = $salaire, taux_heures_supp = $taux WHERE Id_Grade = $id");
        flash('Grade modifié avec succès.');
    } else {
        $mysqli->query("INSERT INTO Grade (libelle_grade, salaire_base, taux_heures_supp) VALUES ('$libelle', $salaire, $taux)");
        flash('Grade ajouté avec succès.');
    }
    redirect('grade.php');
}
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $mysqli->query("DELETE FROM Grade WHERE Id_Grade = $id");
    flash('Grade supprimé.');
    redirect('grade.php');
}
$grades = queryAll('SELECT * FROM Grade ORDER BY Id_Grade DESC');
$edited = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $edited = queryAll("SELECT * FROM Grade WHERE Id_Grade = $id")->fetch_assoc();
}
?>
<?php include 'header.php'; ?>

<div class="mb-4">
    <h2 class="mb-1"><i class="fas fa-briefcase"></i> Gestion des grades</h2>
    <p class="text-muted">Créez et gérez les grades de votre entreprise</p>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-<?= $edited ? 'edit' : 'plus' ?>"></i> <?= $edited ? 'Modifier un grade' : 'Ajouter un nouveau grade' ?>
            </div>
            <div class="card-body">
                <form method="post" action="grade.php">
                    <input type="hidden" name="id" value="<?= h($edited['Id_Grade'] ?? '') ?>">
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-tag"></i> Libellé</label>
                        <input name="libelle" class="form-control" placeholder="Ex: Agent d'exécution" required value="<?= h($edited['libelle_grade'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-dollar-sign"></i> Salaire de base (€)</label>
                        <input name="salaire_base" type="number" step="0.01" class="form-control" placeholder="Ex: 2500" required value="<?= h($edited['salaire_base'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-percent"></i> Taux heures supplémentaires</label>
                        <input name="taux_heures_supp" type="number" step="0.01" class="form-control" placeholder="Ex: 1.25" required value="<?= h($edited['taux_heures_supp'] ?? '') ?>">
                    </div>

                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-save"></i> <?= $edited ? 'Mettre à jour' : 'Ajouter' ?>
                        </button>
                        <?php if ($edited): ?>
                            <a href="grade.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> Liste des grades (<?= $grades->num_rows ?>)
            </div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-tag"></i> Libellé</th>
                            <th><i class="fas fa-euro-sign"></i> Salaire</th>
                            <th><i class="fas fa-percent"></i> Taux</th>
                            <th><i class="fas fa-cogs"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($grade = $grades->fetch_assoc()): ?>
                            <tr>
                                <td><span class="badge bg-primary"><?= h($grade['Id_Grade']) ?></span></td>
                                <td><?= h($grade['libelle_grade']) ?></td>
                                <td><strong><?= h($grade['salaire_base']) ?> €</strong></td>
                                <td><?= h($grade['taux_heures_supp']) ?></td>
                                <td>
                                    <a href="grade.php?action=edit&id=<?= h($grade['Id_Grade']) ?>" class="btn btn-sm btn-outline-secondary" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="grade.php?action=delete&id=<?= h($grade['Id_Grade']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ce grade ?');" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </a>
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
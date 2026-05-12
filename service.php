<?php
require_once __DIR__ . '/functions.php';
$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $mysqli->real_escape_string($_POST['nom_service'] ?? '');
    if ($_POST['id']) {
        $id = (int) $_POST['id'];
        $mysqli->query("UPDATE Service SET nom_service = '$nom' WHERE Id_Service = $id");
        flash('Service modifié avec succès.');
    } else {
        $mysqli->query("INSERT INTO Service (nom_service) VALUES ('$nom')");
        flash('Service ajouté avec succès.');
    }
    redirect('service.php');
}
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $mysqli->query("DELETE FROM Service WHERE Id_Service = $id");
    flash('Service supprimé.');
    redirect('service.php');
}
$services = queryAll('SELECT * FROM Service ORDER BY Id_Service DESC');
$edited = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $edited = queryAll("SELECT * FROM Service WHERE Id_Service = $id")->fetch_assoc();
}
?>
<?php include 'header.php'; ?>

<div class="mb-4">
    <h2 class="mb-1"><i class="fas fa-sitemap"></i> Gestion des services</h2>
    <p class="text-muted">Organisez vos départements et services</p>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-<?= $edited ? 'edit' : 'plus' ?>"></i> <?= $edited ? 'Modifier un service' : 'Ajouter un nouveau service' ?>
            </div>
            <div class="card-body">
                <form method="post" action="service.php">
                    <input type="hidden" name="id" value="<?= h($edited['Id_Service'] ?? '') ?>">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-folder"></i> Nom du service</label>
                        <input name="nom_service" class="form-control" placeholder="Ex: Ressources Humaines" required value="<?= h($edited['nom_service'] ?? '') ?>">
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-success" type="submit">
                            <i class="fas fa-save"></i> <?= $edited ? 'Mettre à jour' : 'Ajouter' ?>
                        </button>
                        <?php if ($edited): ?>
                            <a href="service.php" class="btn btn-secondary">
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
                <i class="fas fa-list"></i> Liste des services (<?= $services->num_rows ?>)
            </div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-folder"></i> Service</th>
                            <th><i class="fas fa-cogs"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($service = $services->fetch_assoc()): ?>
                            <tr>
                                <td><span class="badge bg-success"><?= h($service['Id_Service']) ?></span></td>
                                <td><?= h($service['nom_service']) ?></td>
                                <td>
                                    <a href="service.php?action=edit&id=<?= h($service['Id_Service']) ?>" class="btn btn-sm btn-outline-secondary" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="service.php?action=delete&id=<?= h($service['Id_Service']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ce service ?');" title="Supprimer">
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
<?php
require_once __DIR__ . '/functions.php';

$grades = queryAll('SELECT * FROM Grade ORDER BY libelle_grade');
$primes = queryAll('SELECT * FROM Prime ORDER BY libelle');
$retenues = queryAll('SELECT * FROM Retenue ORDER BY libelle');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Grade_Prime
    if (isset($_POST['grade_prime'])) {
        foreach ($_POST['grade_prime'] as $gradeId => $primesData) {
            foreach ($primesData as $primeId => $amount) {
                $amount = (float) $amount;
                $existing = queryAll("SELECT 1 FROM Grade_Prime WHERE Id_Grade = {$gradeId} AND Id_Prime = {$primeId}")->fetch_assoc();
                if ($existing) {
                    $mysqli->query("UPDATE Grade_Prime SET montant_default = {$amount} WHERE Id_Grade = {$gradeId} AND Id_Prime = {$primeId}");
                } else {
                    $mysqli->query("INSERT INTO Grade_Prime (Id_Grade, Id_Prime, montant_default) VALUES ({$gradeId}, {$primeId}, {$amount})");
                }
            }
        }
    }

    // Update Grade_Retenue
    if (isset($_POST['grade_retenue'])) {
        foreach ($_POST['grade_retenue'] as $gradeId => $retenuesData) {
            foreach ($retenuesData as $retenueId => $amount) {
                $amount = (float) $amount;
                $existing = queryAll("SELECT 1 FROM Grade_Retenue WHERE Id_Grade = {$gradeId} AND Id_Retenue = {$retenueId}")->fetch_assoc();
                if ($existing) {
                    $mysqli->query("UPDATE Grade_Retenue SET montant_default = {$amount} WHERE Id_Grade = {$gradeId} AND Id_Retenue = {$retenueId}");
                } else {
                    $mysqli->query("INSERT INTO Grade_Retenue (Id_Grade, Id_Retenue, montant_default) VALUES ({$gradeId}, {$retenueId}, {$amount})");
                }
            }
        }
    }

    flash('Paramètres mis à jour avec succès.', 'success');
    redirect('parametres.php');
}

// Get current defaults
$gradePrimes = [];
$gradeRetenues = [];

foreach ($grades as $grade) {
    $gradeId = $grade['Id_Grade'];
    $gradePrimes[$gradeId] = [];
    $gradeRetenues[$gradeId] = [];

    $primesQuery = queryAll("SELECT gp.Id_Prime, gp.montant_default FROM Grade_Prime gp WHERE gp.Id_Grade = {$gradeId}");
    while ($row = $primesQuery->fetch_assoc()) {
        $gradePrimes[$gradeId][$row['Id_Prime']] = $row['montant_default'];
    }

    $retenuesQuery = queryAll("SELECT gr.Id_Retenue, gr.montant_default FROM Grade_Retenue gr WHERE gr.Id_Grade = {$gradeId}");
    while ($row = $retenuesQuery->fetch_assoc()) {
        $gradeRetenues[$gradeId][$row['Id_Retenue']] = $row['montant_default'];
    }
}
?>
<?php include 'header.php'; ?>
<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-cogs"></i> Paramètres des primes et retenues par grade</h2>
        <p class="text-muted">Définissez les montants par défaut des primes et retenues pour chaque grade.</p>
    </div>
</div>

<form method="post" action="parametres.php">
    <?php foreach ($grades as $grade): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5>Grade : <?= h($grade['libelle_grade']) ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Primes</h6>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Prime</th>
                                    <th>Montant par défaut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($primes as $prime): ?>
                                    <tr>
                                        <td><?= h($prime['libelle']) ?></td>
                                        <td>
                                            <input type="number" step="0.01" name="grade_prime[<?= $grade['Id_Grade'] ?>][<?= $prime['Id_Prime'] ?>]" class="form-control form-control-sm" value="<?= h($gradePrimes[$grade['Id_Grade']][$prime['Id_Prime']] ?? 0) ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Retenues</h6>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Retenue</th>
                                    <th>Montant par défaut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($retenues as $retenue): ?>
                                    <tr>
                                        <td><?= h($retenue['libelle']) ?></td>
                                        <td>
                                            <input type="number" step="0.01" name="grade_retenue[<?= $grade['Id_Grade'] ?>][<?= $retenue['Id_Retenue'] ?>]" class="form-control form-control-sm" value="<?= h($gradeRetenues[$grade['Id_Grade']][$retenue['Id_Retenue']] ?? 0) ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer les paramètres</button>
    </div>
</form>

<?php include 'footer.php'; ?>
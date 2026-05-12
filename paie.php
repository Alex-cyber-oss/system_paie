<?php
require_once __DIR__ . '/functions.php';

$grades = queryAll('SELECT * FROM Grade ORDER BY libelle_grade');
$services = queryAll('SELECT * FROM Service ORDER BY nom_service');
$primes = queryAll('SELECT * FROM Prime ORDER BY libelle');
$retenues = queryAll('SELECT * FROM Retenue ORDER BY libelle');
$employeesResult = queryAll('SELECT e.*, g.Id_Grade FROM Employe e JOIN Grade g ON e.Id_Grade = g.Id_Grade ORDER BY e.Nom_employe, e.Prenom_employe');

// Store employees in array for reuse
$employees = [];
$employeesGrades = [];
while ($emp = $employeesResult->fetch_assoc()) {
    $employees[] = $emp;
    $employeesGrades[$emp['Id_Employe']] = $emp['Id_Grade'];
}

// Get defaults
$gradePrimeDefaults = [];
$gradeRetenueDefaults = [];
$allGrades = queryAll('SELECT Id_Grade FROM Grade');
while ($g = $allGrades->fetch_assoc()) {
    $gid = $g['Id_Grade'];
    $gradePrimeDefaults[$gid] = [];
    $gradeRetenueDefaults[$gid] = [];
    $p = queryAll("SELECT Id_Prime, montant_default FROM Grade_Prime WHERE Id_Grade = $gid");
    while ($row = $p->fetch_assoc()) {
        $gradePrimeDefaults[$gid][$row['Id_Prime']] = $row['montant_default'];
    }
    $r = queryAll("SELECT Id_Retenue, montant_default FROM Grade_Retenue WHERE Id_Grade = $gid");
    while ($row = $r->fetch_assoc()) {
        $gradeRetenueDefaults[$gid][$row['Id_Retenue']] = $row['montant_default'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = (int) ($_POST['Id_Employe'] ?? 0);
    $mois = (int) ($_POST['mois_de_paye'] ?? 0);
    $annee = (int) ($_POST['annee_de_paye'] ?? 0);
    $datePaye = $mysqli->real_escape_string($_POST['date_paye'] ?? '');
    $nbHeuresNormales = (float) ($_POST['nb_heures_normales'] ?? 0);
    $nbHeuresSupp = (float) ($_POST['nb_heures_supp'] ?? 0);

    if (!$employeeId) {
        flash('Veuillez sélectionner un employé existant.', 'danger');
        redirect('paie.php');
    }

    $employeeData = queryAll(
        "SELECT e.taux_horaire, g.taux_heures_supp FROM Employe e JOIN Grade g ON e.Id_Grade = g.Id_Grade WHERE e.Id_Employe = {$employeeId}"
    )->fetch_assoc();
    $tauxHoraire = (float) ($employeeData['taux_horaire'] ?? 0);
    $tauxHeuresSupp = (float) ($employeeData['taux_heures_supp'] ?? 1.25);

    if ($tauxHoraire <= 0) {
        flash('Le taux horaire de l\'employé est invalide.', 'danger');
        redirect('paie.php');
    }

    $releve = queryAll("SELECT Id_Releve_horaire FROM Releve_horaire WHERE Id_Employe = {$employeeId} AND mois_concerne = {$mois} AND annee_concernee = {$annee}")->fetch_assoc();
    if ($releve) {
        $releveId = (int) $releve['Id_Releve_horaire'];
        $mysqli->query("UPDATE Releve_horaire SET nb_heures_normales = {$nbHeuresNormales}, nb_heures_supp = {$nbHeuresSupp}, taux_heures_supp = {$tauxHeuresSupp} WHERE Id_Releve_horaire = {$releveId}");
    } else {
        $mysqli->query("INSERT INTO Releve_horaire (mois_concerne, annee_concernee, nb_heures_normales, nb_heures_supp, taux_heures_supp, Id_Employe) VALUES ({$mois}, {$annee}, {$nbHeuresNormales}, {$nbHeuresSupp}, {$tauxHeuresSupp}, {$employeeId})");
        $releveId = $mysqli->insert_id;
    }

    $salaireBrut = ($nbHeuresNormales * $tauxHoraire) + ($nbHeuresSupp * $tauxHoraire * $tauxHeuresSupp);
    $existingBulletin = queryAll("SELECT Id_Bulletin_paye FROM Bulletin_paye WHERE Id_Employe = {$employeeId} AND mois_de_paye = {$mois} AND annee_de_paye = {$annee}")->fetch_assoc();
    if ($existingBulletin) {
        $bulletinId = (int) $existingBulletin['Id_Bulletin_paye'];
        $mysqli->query("UPDATE Bulletin_paye SET date_paye = '{$datePaye}', Salaire_brut = {$salaireBrut}, salaire_net = {$salaireBrut}, net_a_payer = {$salaireBrut}, Id_Releve_horaire = {$releveId} WHERE Id_Bulletin_paye = {$bulletinId}");
        $mysqli->query("DELETE FROM Bulletin_Prime WHERE Id_Bulletin_paye = {$bulletinId}");
        $mysqli->query("DELETE FROM Bulletin_Retenue WHERE Id_Bulletin_paye = {$bulletinId}");
    } else {
        $mysqli->query("INSERT INTO Bulletin_paye (date_paye, mois_de_paye, annee_de_paye, Salaire_brut, salaire_net, net_a_payer, Id_Employe, Id_Releve_horaire) VALUES ('{$datePaye}', {$mois}, {$annee}, {$salaireBrut}, {$salaireBrut}, {$salaireBrut}, {$employeeId}, {$releveId})");
        $bulletinId = $mysqli->insert_id;
    }

    $totalPrimes = 0;
    $selectedPrimes = $_POST['selected_primes'] ?? [];
    foreach ($selectedPrimes as $primeData) {
        list($primeId, $primeLabel, $primeAmount) = explode('|', $primeData);
        $primeId = (int) $primeId;
        $primeAmount = (float) $primeAmount;
        $primeLabel = $mysqli->real_escape_string(trim($primeLabel));
        if ($primeAmount <= 0) {
            continue;
        }
        if (!$primeId && $primeLabel !== '') {
            $mysqli->query("INSERT INTO Prime (libelle) VALUES ('{$primeLabel}')");
            $primeId = $mysqli->insert_id;
        }
        if ($primeId) {
            $mysqli->query("INSERT INTO Bulletin_Prime (Id_Bulletin_paye, Id_Prime, Montant) VALUES ({$bulletinId}, {$primeId}, {$primeAmount})");
            $totalPrimes += $primeAmount;
        }
    }

    $totalRetenues = 0;
    $selectedRetenues = $_POST['selected_retenues'] ?? [];
    foreach ($selectedRetenues as $retenueData) {
        list($retenueId, $retenueLabel, $retenueAmount) = explode('|', $retenueData);
        $retenueId = (int) $retenueId;
        $retenueAmount = (float) $retenueAmount;
        $retenueLabel = $mysqli->real_escape_string(trim($retenueLabel));
        if ($retenueAmount <= 0) {
            continue;
        }
        if (!$retenueId && $retenueLabel !== '') {
            $mysqli->query("INSERT INTO Retenue (libelle, taux_retenue) VALUES ('{$retenueLabel}', 0)");
            $retenueId = $mysqli->insert_id;
        }
        if ($retenueId) {
            $mysqli->query("INSERT INTO Bulletin_Retenue (Id_Bulletin_paye, Id_Retenue, Montant) VALUES ({$bulletinId}, {$retenueId}, {$retenueAmount})");
            $totalRetenues += $retenueAmount;
        }
    }

    $salaireNet = $salaireBrut + $totalPrimes - $totalRetenues;
    $mysqli->query("UPDATE Bulletin_paye SET salaire_net = {$salaireNet}, net_a_payer = {$salaireNet} WHERE Id_Bulletin_paye = {$bulletinId}");

    flash('Bulletin généré avec succès.', 'success');
    redirect('paie.php');
}

$historyMois = isset($_GET['history_mois']) ? (int) $_GET['history_mois'] : 0;
$historyAnnee = isset($_GET['history_annee']) ? (int) $_GET['history_annee'] : 0;
$where = [];
if ($historyMois >= 1 && $historyMois <= 12) {
    $where[] = "r.mois_concerne = {$historyMois}";
}
if ($historyAnnee >= 2000) {
    $where[] = "r.annee_concernee = {$historyAnnee}";
}
$whereSql = '';
if ($where) {
    $whereSql = ' WHERE ' . implode(' AND ', $where);
}
$bulletins = queryAll('SELECT b.*, e.Nom_employe, e.Prenom_employe, r.mois_concerne, r.annee_concernee FROM Bulletin_paye b JOIN Employe e ON b.Id_Employe = e.Id_Employe JOIN Releve_horaire r ON b.Id_Releve_horaire = r.Id_Releve_horaire' . $whereSql . ' ORDER BY r.annee_concernee DESC, r.mois_concerne DESC, b.date_paye DESC');
?>
<?php include 'header.php'; ?>
<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-file-invoice-dollar"></i> Création de bulletin</h2>
        <p class="text-muted">Formulaire unique : employé, grade, service, heures, primes et retenues.</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">Formulaire de paie</div>
    <div class="card-body">
        <form method="post" action="paie.php">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Employé</label>
                    <select name="Id_Employe" class="form-select" required onchange="updateGrade(this.value)">
                        <option value="">-- Choisir un employé --</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= h($emp['Id_Employe']) ?>"><?= h($emp['Nom_employe'] . ' ' . $emp['Prenom_employe']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date de paiement</label>
                    <input type="date" name="date_paye" class="form-control" required value="<?= h(date('Y-m-d')) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Mois</label>
                    <input type="number" name="mois_de_paye" class="form-control" min="1" max="12" required value="<?= h(date('m')) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Année</label>
                    <input type="number" name="annee_de_paye" class="form-control" min="2000" required value="<?= h(date('Y')) ?>">
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <div class="alert alert-info">
                        Le formulaire de paie utilise uniquement des employés existants.
                        Pour créer ou modifier un employé, un grade ou un service, utilisez les pages dédiées du menu.
                    </div>
                </div>
            </div>
            <hr class="my-4">

            <div class="row g-3">
                <div class="col-12"><h5>Heures</h5></div>
                <div class="col-md-3"><input type="number" step="0.01" name="nb_heures_normales" class="form-control" placeholder="Heures normales" required></div>
                <div class="col-md-3"><input type="number" step="0.01" name="nb_heures_supp" class="form-control" placeholder="Heures supp"></div>
            </div>

            <hr class="my-4">

            <div class="row g-3">
                <div class="col-12"><h5>Primes</h5></div>
                <div class="col-md-6">
                    <label class="form-label">Choisir une prime</label>
                    <select id="commonPrimes" class="form-select">
                        <option value="" disabled selected>Choisir une prime</option>
                        <?php foreach ($primes as $prime): ?>
                            <option value="<?= h($prime['Id_Prime']) ?>" data-label="<?= h($prime['libelle']) ?>"><?= h($prime['libelle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Cliquez pour ouvrir le menu déroulant, puis choisissez une prime.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ajouter une prime particulière</label>
                    <div class="input-group mb-2">
                        <input type="text" id="particularPrimeLabel" class="form-control" placeholder="Nom de la prime">
                        <input type="number" step="0.01" id="particularPrimeAmount" class="form-control" placeholder="Montant">
                        <button type="button" id="addParticularPrime" class="btn btn-outline-primary">Ajouter</button>
                    </div>
                </div>
                <div class="col-12">
                    <h6>Primes sélectionnées</h6>
                    <ul id="selectedPrimes" class="list-group">
                        <!-- Selected primes will appear here -->
                    </ul>
                </div>
            </div>

            <hr class="my-4">

            <div class="row g-3">
                <div class="col-12"><h5>Retenues</h5></div>
                <div class="col-md-6">
                    <label class="form-label">Choisir une retenue</label>
                    <select id="commonRetenues" class="form-select">
                        <option value="" disabled selected>Choisir une retenue</option>
                        <?php foreach ($retenues as $retenue): ?>
                            <option value="<?= h($retenue['Id_Retenue']) ?>" data-label="<?= h($retenue['libelle']) ?>"><?= h($retenue['libelle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Cliquez pour ouvrir le menu déroulant, puis choisissez une retenue.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ajouter une retenue particulière</label>
                    <div class="input-group mb-2">
                        <input type="text" id="particularRetenueLabel" class="form-control" placeholder="Nom de la retenue">
                        <input type="number" step="0.01" id="particularRetenueAmount" class="form-control" placeholder="Montant">
                        <button type="button" id="addParticularRetenue" class="btn btn-outline-primary">Ajouter</button>
                    </div>
                </div>
                <div class="col-12">
                    <h6>Retenues sélectionnées</h6>
                    <ul id="selectedRetenues" class="list-group">
                        <!-- Selected retenues will appear here -->
                    </ul>
                </div>
            </div>

            <div id="hiddenPrimes"></div>
            <div id="hiddenRetenues"></div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="fas fa-check-circle"></i> Générer le bulletin</button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">Historique complet des paies</div>
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Mois</label>
                <input type="number" name="history_mois" class="form-control" min="1" max="12" value="<?= h($historyMois) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Année</label>
                <input type="number" name="history_annee" class="form-control" min="2000" value="<?= h($historyAnnee) ?>">
            </div>
            <div class="col-md-6">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrer l'historique</button>
                <a href="paie.php" class="btn btn-secondary">Afficher tout</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">Historique des bulletins</div>
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Employé</th>
                    <th>Période</th>
                    <th>Date paiement</th>
                    <th>Net à payer</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($bulletin = $bulletins->fetch_assoc()): ?>
                    <tr>
                        <td><?= h($bulletin['Id_Bulletin_paye']) ?></td>
                        <td><?= h($bulletin['Nom_employe'] . ' ' . $bulletin['Prenom_employe']) ?></td>
                        <td><?= h($bulletin['mois_concerne'] . '/' . $bulletin['annee_concernee']) ?></td>
                        <td><?= h($bulletin['date_paye']) ?></td>
                        <td><?= h($bulletin['net_a_payer']) ?></td>
                        <td>
                            <a href="bulletin_pdf.php?bulletin_id=<?= h($bulletin['Id_Bulletin_paye']) ?>" class="btn btn-sm btn-success"><i class="fas fa-file-pdf"></i></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
var gradePrimeDefaults = <?= json_encode($gradePrimeDefaults) ?>;
var gradeRetenueDefaults = <?= json_encode($gradeRetenueDefaults) ?>;
var employeesGrades = <?= json_encode($employeesGrades) ?>;
var currentGrade = null;

function updateGrade(empId) {
    currentGrade = employeesGrades[empId] || null;
}

function addPrime(id, label, amount) {
    if (id !== '0' && !currentGrade) return;
    const list = document.getElementById('selectedPrimes');
    const existing = list.querySelector(`li[data-id="${id}"]`);
    if (existing) return;
    const li = document.createElement('li');
    li.className = 'list-group-item d-flex justify-content-between align-items-center';
    li.setAttribute('data-id', id);
    li.innerHTML = `
        <span>${label}</span>
        <span>${amount}</span>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePrime(this, '${id}')">Supprimer</button>
    `;
    list.appendChild(li);
    const hidden = document.getElementById('hiddenPrimes');
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'selected_primes[]';
    input.value = `${id}|${label}|${amount}`;
    input.setAttribute('data-id', id);
    hidden.appendChild(input);
}

function removePrime(button, id) {
    button.parentElement.remove();
    const hidden = document.querySelector(`#hiddenPrimes input[data-id="${id}"]`);
    if (hidden) hidden.remove();
}

document.getElementById('commonPrimes').addEventListener('change', function() {
    if (!currentGrade) {
        alert('Veuillez d\'abord sélectionner un employé.');
        this.selectedIndex = 0;
        return;
    }
    const selected = Array.from(this.selectedOptions);
    selected.forEach(option => {
        if (!option.value) {
            return;
        }
        const amount = gradePrimeDefaults[currentGrade]?.[option.value] || 0;
        addPrime(option.value, option.getAttribute('data-label'), amount);
    });
    this.selectedIndex = 0;
});

document.getElementById('addParticularPrime').addEventListener('click', function() {
    const label = document.getElementById('particularPrimeLabel').value.trim();
    const amount = document.getElementById('particularPrimeAmount').value;
    if (label && amount) {
        addPrime('0', label, amount);
        document.getElementById('particularPrimeLabel').value = '';
        document.getElementById('particularPrimeAmount').value = '';
    }
});

function addRetenue(id, label, amount) {
    if (id !== '0' && !currentGrade) return;
    const list = document.getElementById('selectedRetenues');
    const existing = list.querySelector(`li[data-id="${id}"]`);
    if (existing) return;
    const li = document.createElement('li');
    li.className = 'list-group-item d-flex justify-content-between align-items-center';
    li.setAttribute('data-id', id);
    li.innerHTML = `
        <span>${label}</span>
        <span>${amount}</span>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRetenue(this, '${id}')">Supprimer</button>
    `;
    list.appendChild(li);
    const hidden = document.getElementById('hiddenRetenues');
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'selected_retenues[]';
    input.value = `${id}|${label}|${amount}`;
    input.setAttribute('data-id', id);
    hidden.appendChild(input);
}

function removeRetenue(button, id) {
    button.parentElement.remove();
    const hidden = document.querySelector(`#hiddenRetenues input[data-id="${id}"]`);
    if (hidden) hidden.remove();
}

document.getElementById('commonRetenues').addEventListener('change', function() {
    if (!currentGrade) {
        alert('Veuillez d\'abord sélectionner un employé.');
        this.selectedIndex = 0;
        return;
    }
    const selected = Array.from(this.selectedOptions);
    selected.forEach(option => {
        if (!option.value) {
            return;
        }
        const amount = gradeRetenueDefaults[currentGrade]?.[option.value] || 0;
        addRetenue(option.value, option.getAttribute('data-label'), amount);
    });
    this.selectedIndex = 0;
});

document.getElementById('addParticularRetenue').addEventListener('click', function() {
    const label = document.getElementById('particularRetenueLabel').value.trim();
    const amount = document.getElementById('particularRetenueAmount').value;
    if (label && amount) {
        addRetenue('0', label, amount);
        document.getElementById('particularRetenueLabel').value = '';
        document.getElementById('particularRetenueAmount').value = '';
    }
});
</script>

<?php include 'footer.php'; ?>
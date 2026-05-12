<?php
require_once __DIR__ . '/functions.php';

$mois = isset($_GET['mois']) ? (int) $_GET['mois'] : (int) date('m');
$annee = isset($_GET['annee']) ? (int) $_GET['annee'] : (int) date('Y');
if ($mois < 1 || $mois > 12) {
    $mois = (int) date('m');
}
if ($annee < 2000) {
    $annee = (int) date('Y');
}

$employees = queryAll(
    'SELECT e.*, g.libelle_grade, s.nom_service FROM Employe e '
    . 'JOIN Grade g ON e.Id_Grade = g.Id_Grade '
    . 'JOIN Service s ON e.Id_Service = s.Id_Service '
    . 'ORDER BY e.Nom_employe, e.Prenom_employe'
);

$releves = queryAll(
    "SELECT * FROM Releve_horaire WHERE mois_concerne = $mois AND annee_concernee = $annee"
);
$releveMap = [];
while ($row = $releves->fetch_assoc()) {
    $releveMap[$row['Id_Employe']] = $row;
}

$bulletinInfo = queryAll(
    "SELECT b.Id_Employe, b.Id_Bulletin_paye, COALESCE(SUM(bp.Montant), 0) AS total_primes, COALESCE(SUM(br.Montant), 0) AS total_retenues "
    . "FROM Bulletin_paye b "
    . "LEFT JOIN Bulletin_Prime bp ON b.Id_Bulletin_paye = bp.Id_Bulletin_paye "
    . "LEFT JOIN Bulletin_Retenue br ON b.Id_Bulletin_paye = br.Id_Bulletin_paye "
    . "WHERE b.mois_de_paye = $mois AND b.annee_de_paye = $annee "
    . "GROUP BY b.Id_Bulletin_paye, b.Id_Employe"
);
$bulletinMap = [];
while ($row = $bulletinInfo->fetch_assoc()) {
    $bulletinMap[$row['Id_Employe']] = $row;
}

function formatCurrency($value) {
    return number_format((float) $value, 2, ',', ' ') . ' €';
}

function formatCurrencyPdf($value) {
    return number_format((float) $value, 2, ',', ' ') . ' EUR';
}

function pdfEscape($text) {
    $map = [
        'À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A','Å'=>'A','Ç'=>'C','È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E',
        'Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I','Ñ'=>'N','Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O','Ù'=>'U',
        'Ú'=>'U','Û'=>'U','Ü'=>'U','Ý'=>'Y','à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','ç'=>'c',
        'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ñ'=>'n','ò'=>'o','ó'=>'o',
        'ô'=>'o','õ'=>'o','ö'=>'o','ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ý'=>'y','ÿ'=>'y','’'=>'\'',
        '“'=>'"','”'=>'"','–'=>'-','—'=>'-','€'=>'EUR'
    ];
    $text = strtr($text, $map);
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function buildPdfText($content) {
    $objects = [];
    $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $objects[] = "2 0 obj\n<< /Type /Pages /Count 1 /Kids [3 0 R] >>\nendobj\n";
    $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
    $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
    $objects[] = "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream\nendobj\n";

    $offset = 0;
    $xref = "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    foreach ($objects as $object) {
        $xref .= sprintf('%010d 00000 n \n', $offset);
        $offset += strlen($object);
    }

    $pdf = "%PDF-1.3\n";
    foreach ($objects as $object) {
        $pdf .= $object;
    }
    $xrefPos = strlen($pdf);
    $pdf .= $xref;
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";
    return $pdf;
}

function addText(&$content, $x, $y, $text, $fontSize = 12) {
    $content .= sprintf("BT /F1 %s Tf %s %s Td (%s) Tj ET\n", $fontSize, $x, $y, pdfEscape($text));
}

$rows = [];
while ($employee = $employees->fetch_assoc()) {
    $empId = $employee['Id_Employe'];
    $releve = $releveMap[$empId] ?? null;
    $brut = 0;
    $primes = 0;
    $retenues = 0;
    $net = 0;
    $status = 'Sans relevé';

    if ($releve) {
        $brut = ($releve['nb_heures_normales'] * $employee['taux_horaire']) + ($releve['nb_heures_supp'] * $employee['taux_horaire'] * $releve['taux_heures_supp']);
        $info = $bulletinMap[$empId] ?? null;
        if ($info) {
            $primes = $info['total_primes'];
            $retenues = $info['total_retenues'];
            $net = $brut + $primes - $retenues;
            $status = 'Bulletin existant';
        } else {
            $status = 'Relevé trouvé';
            $net = $brut;
        }
    }

    $rows[] = [
        'employe' => $employee['Nom_employe'] . ' ' . $employee['Prenom_employe'],
        'grade' => $employee['libelle_grade'],
        'service' => $employee['nom_service'],
        'brut' => $brut,
        'primes' => $primes,
        'retenues' => $retenues,
        'net' => $net,
        'status' => $status,
    ];
}

if (isset($_GET['export'])) {
    $export = $_GET['export'];
    if ($export === 'excel') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="salaires_' . $mois . '_' . $annee . '.csv"');
        echo "\xEF\xBB\xBF";
        echo "Employé;Grade;Service;Salaire brut;Primes;Retenues;Salaire net;Statut\n";
        foreach ($rows as $row) {
            echo sprintf(
                "%s;%s;%s;%s;%s;%s;%s;%s\n",
                $row['employe'],
                $row['grade'],
                $row['service'],
                formatCurrency($row['brut']),
                formatCurrency($row['primes']),
                formatCurrency($row['retenues']),
                formatCurrency($row['net']),
                $row['status']
            );
        }
        exit;
    }

    if ($export === 'pdf') {
        $content = "BT /F1 14 Tf 40 820 Td (Rapport des salaires - $mois/$annee) Tj ET\n";
        $content .= "BT /F1 10 Tf 40 800 Td (Date : " . date('d/m/Y') . ") Tj ET\n";
        $y = 770;
        addText($content, 40, $y, 'Employé', 10);
        addText($content, 170, $y, 'Brut', 10);
        addText($content, 250, $y, 'Primes', 10);
        addText($content, 330, $y, 'Retenues', 10);
        addText($content, 420, $y, 'Net', 10);
        addText($content, 500, $y, 'Statut', 10);
        $y -= 20;

        foreach ($rows as $row) {
            if ($y < 60) {
                break;
            }
            addText($content, 40, $y, $row['employe'], 9);
            addText($content, 170, $y, formatCurrencyPdf($row['brut']), 9);
            addText($content, 250, $y, formatCurrencyPdf($row['primes']), 9);
            addText($content, 330, $y, formatCurrencyPdf($row['retenues']), 9);
            addText($content, 420, $y, formatCurrencyPdf($row['net']), 9);
            addText($content, 500, $y, $row['status'], 9);
            $y -= 14;
        }

        $pdf = buildPdfText($content);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="rapport_salaires_' . $mois . '_' . $annee . '.pdf"');
        echo $pdf;
        exit;
    }
}
?>
<?php include 'header.php'; ?>
<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-file-alt"></i> Rapport des salaires</h2>
        <p class="text-muted">Liste des employés et salaires calculés pour le mois sélectionné.</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row gy-2 gx-3 align-items-end">
            <div class="col-sm-3">
                <label class="form-label">Mois</label>
                <input type="number" name="mois" class="form-control" min="1" max="12" value="<?= h($mois) ?>">
            </div>
            <div class="col-sm-3">
                <label class="form-label">Année</label>
                <input type="number" name="annee" class="form-control" min="2000" value="<?= h($annee) ?>">
            </div>
            <div class="col-sm-6">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrer</button>
                <a href="rapport_salaires.php?mois=<?= h($mois) ?>&annee=<?= h($annee) ?>&export=pdf" class="btn btn-danger"><i class="fas fa-file-pdf"></i> Exporter PDF</a>
                <a href="rapport_salaires.php?mois=<?= h($mois) ?>&annee=<?= h($annee) ?>&export=excel" class="btn btn-success"><i class="fas fa-file-csv"></i> Exporter Excel</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Employé</th>
                    <th>Grade</th>
                    <th>Service</th>
                    <th>Brut</th>
                    <th>Primes</th>
                    <th>Retenues</th>
                    <th>Net</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= h($row['employe']) ?></td>
                        <td><?= h($row['grade']) ?></td>
                        <td><?= h($row['service']) ?></td>
                        <td><?= h(formatCurrency($row['brut'])) ?></td>
                        <td><?= h(formatCurrency($row['primes'])) ?></td>
                        <td><?= h(formatCurrency($row['retenues'])) ?></td>
                        <td><?= h(formatCurrency($row['net'])) ?></td>
                        <td><?= h($row['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
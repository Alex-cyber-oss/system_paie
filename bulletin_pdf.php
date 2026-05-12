<?php
require_once __DIR__ . '/functions.php';

$bulletin_id = isset($_GET['bulletin_id']) ? (int) $_GET['bulletin_id'] : 0;
if (!$bulletin_id) {
    flash('Bulletin requis.', 'danger');
    redirect('paie.php');
}

$bulletin = queryAll(
    'SELECT b.*, e.Nom_employe, e.Prenom_employe, e.taux_horaire, e.Adresse, g.libelle_grade AS Nom_Grade, s.nom_service AS Nom_Service, r.nb_heures_normales, r.nb_heures_supp, r.taux_heures_supp, r.mois_concerne, r.annee_concernee ' .
    'FROM Bulletin_paye b ' .
    'JOIN Employe e ON b.Id_Employe = e.Id_Employe ' .
    'JOIN Grade g ON e.Id_Grade = g.Id_Grade ' .
    'JOIN Service s ON e.Id_Service = s.Id_Service ' .
    'JOIN Releve_horaire r ON b.Id_Releve_horaire = r.Id_Releve_horaire ' .
    'WHERE b.Id_Bulletin_paye = ' . $bulletin_id
)->fetch_assoc();

if (!$bulletin) {
    flash('Bulletin non trouvé.', 'danger');
    redirect('paie.php');
}

$primes = queryAll(
    'SELECT p.Libelle, bp.Montant FROM Bulletin_Prime bp JOIN Prime p ON bp.Id_Prime = p.Id_Prime WHERE bp.Id_Bulletin_paye = ' . $bulletin_id
)->fetch_all(MYSQLI_ASSOC);

$retenues = queryAll(
    'SELECT r.Libelle, br.Montant FROM Bulletin_Retenue br JOIN Retenue r ON br.Id_Retenue = r.Id_Retenue WHERE br.Id_Bulletin_paye = ' . $bulletin_id
)->fetch_all(MYSQLI_ASSOC);

$salaire_brut = ($bulletin['nb_heures_normales'] * $bulletin['taux_horaire']) + ($bulletin['nb_heures_supp'] * $bulletin['taux_horaire'] * $bulletin['taux_heures_supp']);
$tot_primes = array_sum(array_column($primes, 'Montant'));
$tot_retenues = array_sum(array_column($retenues, 'Montant'));
$salaire_net = $salaire_brut + $tot_primes - $tot_retenues;

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

function mmToPt($mm) {
    return $mm * 72 / 25.4;
}

function formatCurrency($value) {
    return number_format((float) $value, 2, ',', ' ') . ' EUR';
}

$pageWidth = mmToPt(210);
$pageHeight = mmToPt(297);

$content = '';

function addText(&$content, $x, $y, $text, $fontSize = 12) {
    global $pageHeight;
    $text = pdfEscape($text);
    $content .= sprintf("BT /F1 %s Tf %s %s Td (%s) Tj ET\n", $fontSize, mmToPt($x), $pageHeight - mmToPt($y), $text);
}

function addLine(&$content, $x1, $y1, $x2, $y2) {
    global $pageHeight;
    $content .= sprintf("%s %s m %s %s l S\n", mmToPt($x1), $pageHeight - mmToPt($y1), mmToPt($x2), $pageHeight - mmToPt($y2));
}

function buildPdf($contentStream) {
    $objects = [];
    $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $objects[] = "2 0 obj\n<< /Type /Pages /Count 1 /Kids [3 0 R] >>\nendobj\n";
    $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
    $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
    $objects[] = "5 0 obj\n<< /Length " . strlen($contentStream) . " >>\nstream\n" . $contentStream . "endstream\nendobj\n";

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

$y = 20;
addText($content, 20, $y, 'SOCIETE DE PAIE', 18);
addText($content, 20, $y + 8, 'Adresse: 123 Rue du Paie - Ville, France', 10);
addText($content, 20, $y + 14, 'Téléphone: +33 1 23 45 67 89', 10);
addText($content, 20, $y + 20, 'Email: contact@entreprise.fr', 10);
addText($content, 140, $y, 'BULLETIN DE PAIE', 16);
addText($content, 140, $y + 8, 'Bulletin # ' . $bulletin['Id_Bulletin_paye'], 12);
addText($content, 140, $y + 14, 'Période: ' . $bulletin['mois_de_paye'] . '/' . $bulletin['annee_de_paye'], 12);

addLine($content, 20, $y + 26, 190, $y + 26);

$y += 32;
addText($content, 20, $y, 'Employé: ' . $bulletin['Nom_employe'] . ' ' . $bulletin['Prenom_employe'], 12);
addText($content, 20, $y + 6, 'Grade: ' . $bulletin['Nom_Grade'], 12);
addText($content, 20, $y + 12, 'Service: ' . $bulletin['Nom_Service'], 12);
addText($content, 20, $y + 18, 'Date paiement: ' . $bulletin['date_paye'], 12);

addText($content, 110, $y, 'Taux horaire: ' . formatCurrency($bulletin['taux_horaire']) . ' /h', 12);
addText($content, 110, $y + 6, 'Heures normales: ' . $bulletin['nb_heures_normales'] . ' h', 12);
addText($content, 110, $y + 12, 'Heures supp: ' . $bulletin['nb_heures_supp'] . ' h', 12);
addText($content, 110, $y + 18, 'Majoration supp: x' . $bulletin['taux_heures_supp'], 12);

$y += 28;
addText($content, 20, $y, 'Détail du salaire', 12);
addLine($content, 20, $y + 2, 190, $y + 2);
$y += 6;
addText($content, 20, $y, 'Salaire normal: ' . formatCurrency($bulletin['nb_heures_normales'] * $bulletin['taux_horaire']), 12);
addText($content, 20, $y + 6, 'Salaire supplémentaire: ' . formatCurrency($bulletin['nb_heures_supp'] * $bulletin['taux_horaire'] * $bulletin['taux_heures_supp']), 12);
addText($content, 20, $y + 12, 'Salaire brut: ' . formatCurrency($salaire_brut), 12);

$y += 24;
addText($content, 20, $y, 'Primes', 12);
addLine($content, 20, $y + 2, 190, $y + 2);
$y += 6;
if (count($primes) === 0) {
    addText($content, 20, $y, '- Aucune prime enregistrée -', 12);
    $y += 6;
} else {
    foreach ($primes as $prime) {
        addText($content, 20, $y, $prime['Libelle'] . ': ' . formatCurrency($prime['Montant']), 12);
        $y += 6;
    }
}
addText($content, 20, $y, 'Total primes: ' . formatCurrency($tot_primes), 12);

$y += 18;
addText($content, 20, $y, 'Retenues', 12);
addLine($content, 20, $y + 2, 190, $y + 2);
$y += 6;
if (count($retenues) === 0) {
    addText($content, 20, $y, '- Aucune retenue enregistrée -', 12);
    $y += 6;
} else {
    foreach ($retenues as $retenue) {
        addText($content, 20, $y, $retenue['Libelle'] . ': ' . formatCurrency($retenue['Montant']), 12);
        $y += 6;
    }
}
addText($content, 20, $y, 'Total retenues: ' . formatCurrency($tot_retenues), 12);

$y += 18;
addLine($content, 20, $y, 190, $y);
addText($content, 20, $y + 4, 'Net à payer: ' . formatCurrency($salaire_net), 14);

$pdf = buildPdf($content);

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="bulletin_' . $bulletin_id . '.pdf"');
echo $pdf;
exit;

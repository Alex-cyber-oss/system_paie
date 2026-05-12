<?php
require_once __DIR__ . '/functions.php';
$gradeCount = queryAll('SELECT COUNT(*) AS total FROM Grade')->fetch_assoc()['total'];
$serviceCount = queryAll('SELECT COUNT(*) AS total FROM Service')->fetch_assoc()['total'];
$primeCount = queryAll('SELECT COUNT(*) AS total FROM Prime')->fetch_assoc()['total'];
$retenueCount = queryAll('SELECT COUNT(*) AS total FROM Retenue')->fetch_assoc()['total'];
$employeCount = queryAll('SELECT COUNT(*) AS total FROM Employe')->fetch_assoc()['total'];
$bulletinCount = queryAll('SELECT COUNT(*) AS total FROM Bulletin_paye')->fetch_assoc()['total'];
?>
<?php include 'header.php'; ?>

<div class="mb-4">
    <h2 class="mb-1"><i class="fas fa-chart-line"></i> Tableau de bord</h2>
    <p class="text-muted">Gestion complète de paie - Vue d'ensemble</p>
</div>

<div class="row g-4">
    <!-- Paie -->
    <div class="col-lg-6 col-md-6">
        <div class="stat-card" style="border-left-color: #1abc9c;">
            <i class="fas fa-file-invoice-dollar" style="font-size: 2.5rem; color: #1abc9c; margin-bottom: 1rem;"></i>
            <h5>Paie</h5>
            <p><?= h($bulletinCount) ?> bulletins</p>
            <a href="paie.php" class="btn btn-sm btn-info mt-2">
                <i class="fas fa-cogs"></i> Ouvrir la page de paie
            </a>
        </div>
    </div>

    <!-- Rapports -->
    <div class="col-lg-6 col-md-6">
        <div class="stat-card" style="border-left-color: #34495e;">
            <i class="fas fa-file-alt" style="font-size: 2.5rem; color: #34495e; margin-bottom: 1rem;"></i>
            <h5>Rapports salaires</h5>
            <p>Exporter les données bancaires</p>
            <a href="rapport_salaires.php" class="btn btn-sm btn-dark mt-2">
                <i class="fas fa-download"></i> Exporter
            </a>
        </div>
    </div>
</div>

<div class="row mt-5">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-rocket"></i> Démarrage rapide
            </div>
            <div class="card-body">
                <p>Pour créer votre premier bulletin de paie, suivez ces étapes :</p>
                <ol>
                    <li><strong>Ouvrir la page de paie unique</strong> - <a href="paie.php">Aller à Paie</a></li>
                    <li><strong>Remplir le formulaire</strong> - employé, heures, primes et retenues</li>
                    <li><strong>Générer le bulletin</strong> - le calcul est automatique</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> À propos
            </div>
            <div class="card-body">
                <p><strong>Système de Gestion de Paie</strong></p>
                <p>Application PHP pour gérer complètement la paie de vos employés :</p>
                <ul>
                    <li>✅ Gestion des données personnelles</li>
                    <li>✅ Calcul automatique des salaires</li>
                    <li>✅ Gestion des primes et retenues</li>
                    <li>✅ Génération des bulletins de paie</li>
                    <li>✅ Suivi des avances sur salaire</li>
                    <li>✅ Interface intuitive et moderne</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
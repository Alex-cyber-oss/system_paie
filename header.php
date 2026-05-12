<?php
require_once __DIR__ . '/functions.php';
$navItems = [
    'index.php' => 'Tableau de bord',
    'paie.php' => 'Paie',
    'employe.php' => 'Employés',
    'grade.php' => 'Grades',
    'service.php' => 'Services',
    'parametres.php' => 'Paramètres',
    'rapport_salaires.php' => 'Rapports',
];
$currentPage = basename($_SERVER['PHP_SELF']);
$flash = get_flash();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Gestion de Paie</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-HX6jOfp3Y6lN8M6naeN7MRV6E/U5x2b+VyLlZt8+pwSXeJMjZZAOHuQ9b2K/bb75" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">Système de Paie</a>
        <ul class="navbar-nav ms-auto d-flex flex-row">
                <?php foreach ($navItems as $file => $label): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $file === $currentPage ? 'active' : '' ?>" href="<?= $file ?>">
                            <i class="fas fa-cog"></i> <?= $label ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
    </div>
</nav>

<main class="py-4">
<div class="container">
    <?php if ($flash): ?>
        <div class="alert alert-<?= h($flash['type']) ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-circle' : 'info-circle') ?>"></i>
            <?= h($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

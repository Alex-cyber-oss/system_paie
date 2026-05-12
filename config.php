<?php
// Configuration pour InfinityFree (adapter avec vos identifiants)
// Vérifiez dans le panneau de contrôle d'InfinityFree
$host = 'localhost';
$db   = 'if0_41685884_systeme_paie';
$user = 'if0_41685884';
$pass = '';  // Entrez le mot de passe fourni par InfinityFree
$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    die('Échec de connexion à la base de données : ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

function h($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function flash($message, $type = 'success') {
    if (!isset($_SESSION)) {
        session_start();
    }
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function get_flash() {
    if (!isset($_SESSION)) {
        session_start();
    }
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

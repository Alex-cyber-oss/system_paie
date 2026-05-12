<?php
$host = '127.0.0.1';
$db   = 'systeme_paie';
$user = 'root';
$pass = '';
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

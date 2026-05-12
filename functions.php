<?php
require_once __DIR__ . '/config.php';

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function queryAll($sql) {
    global $mysqli;
    $result = $mysqli->query($sql);
    if (!$result) {
        die('Erreur SQL : ' . $mysqli->error);
    }
    return $result;
}

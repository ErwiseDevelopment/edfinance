<?php
$host = 'localhost';
$db   = 'financas_pessoais';
$user = 'root';
$pass = '';
$sgbd = 'mysql';

try {
    $pdo = new PDO("$sgbd:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
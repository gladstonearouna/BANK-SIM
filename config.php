<?php

$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '11992';
$dbname = getenv('DB_NAME') ?: 'defaultdb';
$user = getenv('DB_USER');
$password = getenv('DB_PASSWORD');

$caFile = __DIR__ . '/ca.pem';

try {
    if (!$host || !$user || !$password) {
        throw new RuntimeException(
            'Variables de connexion à la base de données manquantes.'
        );
    }

    if (!file_exists($caFile)) {
        throw new RuntimeException(
            'Le certificat SSL ca.pem est introuvable.'
        );
    }


    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        \Pdo\Mysql::ATTR_SSL_CA => $caFile,
        \Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT => true,
    ]);


} catch (Throwable $e) {
    error_log('Erreur de connexion BANK-SIM : ' . $e->getMessage());
    http_response_code(500);
    exit('Connexion à la base de données indisponible.');
}

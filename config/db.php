<?php
function getDatabase(): PDO
{
    $host = getenv('DB_HOST');
    $port = getenv('DB_PORT');
    $name = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $password = getenv('DB_PASSWORD');

    $caPath = dirname(__DIR__) . '/certs/ca.pem';

    $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";

    // PHP 8.4+ : \Pdo\Mysql::ATTR_*  |  versions anciennes : PDO::MYSQL_ATTR_*
    $sslCa     = class_exists('Pdo\Mysql') ? \Pdo\Mysql::ATTR_SSL_CA : \PDO::MYSQL_ATTR_SSL_CA;
    $sslVerify = class_exists('Pdo\Mysql') ? \Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT : \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        $sslCa                       => $caPath,
        $sslVerify                   => true,
    ];

    return new PDO($dsn, $user, $password, $options);
}
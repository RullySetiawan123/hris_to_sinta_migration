<?php
function getMySQLConnection() {
    $mysqlHost = '10.4.198.26';
    $mysqlUser = 'hris_dev';
    $mysqlPass = '#######';
    $mysqlDb = 'hris_dev';

    try {
        $conn = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDb;charset=utf8mb4", $mysqlUser, $mysqlPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // $conn->exec("SET NAMES 'utf8mb4'");
        return $conn;
    } catch (PDOException $e) {
        die("MySQL Connection failed: " . $e->getMessage());
    }
}

function getSQLServerConnection() {
    $sqlServerHost = 'localhost';
    $sqlServerUser = 'rully';
    $sqlServerPass = 'rully';
    $sqlServerDb = 'sinta_new';

    try {
        $conn = new PDO("sqlsrv:Server=$sqlServerHost;Database=$sqlServerDb", $sqlServerUser, $sqlServerPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        die("SQL Server Connection failed: " . $e->getMessage());
    }
}
?>

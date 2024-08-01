<?php
// migrate.php
include 'db.php';

// try {
//     $mysqlConn = getMySQLConnection();
//     $sqlServerConn = getSQLServerConnection();

//     // Fetch data from MySQL
//     $query = $mysqlConn->query("SELECT * FROM source_table");
//     $data = $query->fetchAll(PDO::FETCH_ASSOC);

//     // Prepare SQL Server upsert statement
//     $upsertQuery = "
//         MERGE INTO destination_table AS target
//         USING (SELECT :column1 AS column1, :column2 AS column2, :column3 AS column3) AS source
//         ON target.column1 = source.column1
//         WHEN MATCHED THEN
//             UPDATE SET target.column2 = source.column2, target.column3 = source.column3
//         WHEN NOT MATCHED THEN
//             INSERT (column1, column2, column3) VALUES (source.column1, source.column2, source.column3);
//     ";
//     $stmt = $sqlServerConn->prepare($upsertQuery);

//     // Insert or update data in SQL Server
//     foreach ($data as $row) {
//         $stmt->execute([
//             ':column1' => $row['column1'],
//             ':column2' => $row['column2'],
//             ':column3' => $row['column3'],
//         ]);
//     }

//     echo "Data migration completed successfully.";

// } catch (PDOException $e) {
//     echo "Error: " . $e->getMessage();
// } finally {
//     $mysqlConn = null;
//     $sqlServerConn = null;
// }

try {
    $mysqlConn = getMySQLConnection();
    $sqlServerConn = getSQLServerConnection();

    // Fetch table structure from MySQL (assuming the structure is identical in both MySQL and SQL Server)
    $query = $mysqlConn->query("SHOW COLUMNS FROM t_overtime");
    $columns = $query->fetchAll(PDO::FETCH_ASSOC);

    // Construct column lists for both source and destination tables
    $sourceColumns = array_map(function($col) { return "`".$col['Field']."`"; }, $columns);
    $destinationColumns = array_map(function($col) { return $col['Field']; }, $columns);

    // Prepare SQL Server upsert statement
    $sourceColumnsStr = implode(", ", $sourceColumns);
    $destinationColumnsStr = implode(", ", $destinationColumns);

    $upsertQuery = "
        MERGE INTO t_overtime AS target
        USING (
            SELECT $sourceColumnsStr FROM t_overtime
        ) AS source
        ON target.$destinationColumnsStr = source.$destinationColumnsStr
        WHEN MATCHED THEN
            UPDATE SET " . implode(", ", array_map(function($col) {
                return "target.$col = source.$col";
            }, $destinationColumns)) . "
        WHEN NOT MATCHED THEN
            INSERT ($destinationColumnsStr) VALUES (" . implode(", ", array_map(function($col) {
                return "source.$col";
            }, $destinationColumns)) . ");
    ";
    $stmt = $sqlServerConn->prepare($upsertQuery);

    // Insert or update data in SQL Server
    $stmt->execute();

    echo "Data migration completed successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
} finally {
    $mysqlConn = null;
    $sqlServerConn = null;
}
?>
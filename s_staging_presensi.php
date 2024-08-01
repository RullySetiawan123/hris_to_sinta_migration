<?php
// migrate.php
include 'db.php';
include 'fungsi.php';

try {
    $mysqlConn = getMySQLConnection();
    $sqlServerConn = getSQLServerConnection();

    $table_source = 's_staging_presensi';
    $table_target = 'Hris_SStagingPresensi';
    $column_primary = 'p_company';
    $column_primary_2 = 'p_year';
    $column_primary_3 = 'p_month';

    $sql = "SELECT COUNT($column_primary) FROM $table_source";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->execute();

    $total_data = $stmt->fetchColumn();
    $offset = 0;
    $limit = 100;

    // Fetch column types from MySQL
    $columnTypesQuery = "
    SELECT COLUMN_NAME, DATA_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = '$table_source'
";
    $columnTypesStmt = $mysqlConn->query($columnTypesQuery);
    $columnTypes = $columnTypesStmt->fetchAll(PDO::FETCH_ASSOC);
    $columnTypeMap = array_column($columnTypes, 'DATA_TYPE', 'COLUMN_NAME');

    // Fetch 1 data from MySQL
    $query = $mysqlConn->query("SELECT * FROM $table_source LIMIT 1");
    $data = $query->fetchAll(PDO::FETCH_ASSOC);

    if (empty($data)) {
        exit('No data found in the source table.');
    }

    // Get column names dynamically
    $columns = array_keys($data[0]);

    // Escape column names for SQL Server
    $escapedColumns = array_map(function($col) {
        return '[' . str_replace(']', ']]', $col) . ']';
    }, $columns);

    // Prepare column list for SQL Server
    $columnsList = implode(', ', $escapedColumns);

    // Exclude identity column primary_key from updates
    $updateColumns = array_slice($columns, 3);

    echo intval($offset / $limit) . '/' . ceil($total_data / $limit) . ' completed' . PHP_EOL;

    while ($offset < $total_data) {
        // Fetch data from MySQL
        $query = $mysqlConn->query("SELECT * FROM $table_source LIMIT $limit OFFSET $offset");
        $data = $query->fetchAll(PDO::FETCH_ASSOC);

        // Build VALUES part of the SQL statement
        $values = [];
        foreach ($data as $row) {

            foreach ($row as $column => $value) {
                if($value != null && extract_date($value) !== false){
                    $date = extract_date($value);
                    if(!validate_date($date)){
                        $row[$column] = str_replace($date, '1900-01-25', $value);
                    }
                }

                if($columnTypeMap=='datetime'){
                    $row[$column] = $row[$column] ?? '1900-01-25 00:00:00';
                }elseif($columnTypeMap=='date'){
                    $row[$column] = $row[$column] ?? '1900-01-25';
                }elseif($columnTypeMap=='time'){
                    $row[$column] = $row[$column] ?? '00:00:00';
                }
            }

            $rowValues = [];
            foreach ($columns as $column) {
                $value = $row[$column];
                if (is_null($value)) {
                    $rowValues[] = "NULL";
                }else {
                    $value = str_replace("'", "''", $value); // Escape single quotes
                    $rowValues[] = sprintf("'%s'", $value); 
                }
            }
            $values[] = sprintf("(%s)", implode(', ', $rowValues));
        }

        $valuesString = implode(', ', $values);

        // Construct the batch upsert query
        $upsertQuery = "
           -- SET IDENTITY_INSERT $table_target ON;

            MERGE INTO $table_target AS target
            USING (
                SELECT
                    $columnsList
                FROM (VALUES
                    $valuesString
                ) AS source ($columnsList)
            ) AS source
            ON target.[$column_primary] = source.[$column_primary] AND target.[$column_primary_2] = source.[$column_primary_2] AND target.[$column_primary_3] = source.[$column_primary_3]
            WHEN NOT MATCHED THEN
                INSERT ($columnsList)
                VALUES ($columnsList);
        ";

        // echo "<pre>$upsertQuery</pre>";

        $sqlServerConn->exec($upsertQuery);

        $offset += $limit;

        echo intval($offset / $limit) . '/' . ceil($total_data / $limit) . ' completed' . PHP_EOL;
    }


    echo "Data migration completed successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
} finally {
    $mysqlConn = null;
    $sqlServerConn = null;
}
?>
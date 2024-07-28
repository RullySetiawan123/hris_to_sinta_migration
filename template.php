<?php
// migrate.php
include 'db.php';

try {
    $mysqlConn = getMySQLConnection();
    $sqlServerConn = getSQLServerConnection();

    $sql = "SELECT count(ot_id) FROM t_overtime";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->execute();

    $total_data = $stmt->fetchColumn();
    $offset = 0;
    $limit = 10000;
    $current_id = 0;
    
    while($offset < $total_data):

        // Fetch data from MySQL
        $query = $mysqlConn->query("SELECT * FROM t_overtime LIMIT $limit OFFSET $offset");
        $data = $query->fetchAll(PDO::FETCH_ASSOC);

        // Prepare SQL Server upsert statement
        $upsertQuery = "
            SET IDENTITY_INSERT t_overtime ON; -- Enable identity insert

            MERGE INTO t_overtime AS target
            USING (
                SELECT 
                    :ot_id AS ot_id, 
                    :ot_date AS ot_date, 
                    :mk_nopeg AS mk_nopeg
            ) AS source
            ON target.ot_id = source.ot_id
            WHEN MATCHED THEN
                UPDATE SET 
                    target.ot_date = source.ot_date, 
                    target.mk_nopeg = source.mk_nopeg,
                    target.ot_begin = source.ot_begin
                WHEN NOT MATCHED THEN
                    INSERT (
                        ot_id, 
                        ot_date, 
                        mk_nopeg,
                    ) 
                    VALUES (
                        source.ot_id, 
                        source.ot_date, 
                        source.mk_nopeg,
                    );
        ";
        $stmt = $sqlServerConn->prepare($upsertQuery);

        // Insert or update data in SQL Server
        foreach ($data as $row) {
            $current_id = $row['ot_id'];
            $stmt->execute([
                ':ot_id' => $row['ot_id'],
                ':ot_date' => $row['ot_date'],
                ':mk_nopeg' => $row['mk_nopeg'],
            ]);
        }        

        $offset += $limit;

        echo $offset/$limit . '/' . ceil($total_data/$limit) . ' completed' . PHP_EOL;

    endwhile;

    echo "Data migration completed successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . '>> id: '. $current_id;
} finally {
    $mysqlConn = null;
    $sqlServerConn = null;
}


?>
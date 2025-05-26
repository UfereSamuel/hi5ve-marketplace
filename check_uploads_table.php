<?php
require_once 'config/config.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>Uploads Table Check</h2>\n";
    
    // Check if uploads table exists
    $query = "SHOW TABLES LIKE 'uploads'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $table_exists = $stmt->fetch();
    
    if ($table_exists) {
        echo "✓ Uploads table exists\n<br><br>";
        
        // Show table structure
        echo "<h3>Table Structure:</h3>\n";
        $query = "DESCRIBE uploads";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $columns = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n<br>";
        
        // Test insert
        echo "<h3>Test Insert:</h3>\n";
        $test_query = "INSERT INTO uploads (filename, original_name, file_path, file_size, mime_type, uploaded_by, upload_type) 
                      VALUES ('test.jpg', 'test.jpg', 'products/test.jpg', 1024, 'image/jpeg', 1, 'product')";
        
        try {
            $stmt = $conn->prepare($test_query);
            $result = $stmt->execute();
            
            if ($result) {
                $insert_id = $conn->lastInsertId();
                echo "✓ Test insert successful (ID: $insert_id)\n<br>";
                
                // Clean up test record
                $cleanup_query = "DELETE FROM uploads WHERE id = :id";
                $stmt = $conn->prepare($cleanup_query);
                $stmt->bindParam(':id', $insert_id);
                $stmt->execute();
                echo "✓ Test record cleaned up\n<br>";
            } else {
                echo "✗ Test insert failed\n<br>";
            }
        } catch (PDOException $e) {
            echo "✗ Test insert error: " . $e->getMessage() . "\n<br>";
        }
        
    } else {
        echo "✗ Uploads table does not exist\n<br>";
        echo "Creating uploads table...\n<br>";
        
        $create_table = "
        CREATE TABLE uploads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            uploaded_by INT NULL,
            upload_type VARCHAR(50) DEFAULT 'other',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        try {
            $conn->exec($create_table);
            echo "✓ Uploads table created successfully\n<br>";
        } catch (PDOException $e) {
            echo "✗ Failed to create uploads table: " . $e->getMessage() . "\n<br>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n<br>";
}
?> 
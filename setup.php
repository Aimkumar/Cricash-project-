<?php
// Aiven Database Connection using Render Environment Variables
$host = getenv('DB_HOST');
$db   = getenv('DB_NAME') ?: 'defaultdb';
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$port = getenv('DB_PORT') ?: '3306';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 1. Table Creation with your exact format: Username | password | target website | balance | parent_user
    $sqlTable = "CREATE TABLE IF NOT EXISTS `users` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `username` VARCHAR(100) NOT NULL UNIQUE,
      `password` VARCHAR(255) NOT NULL,
      `target_website` VARCHAR(150) NOT NULL,
      `balance` DECIMAL(10, 2) DEFAULT 100.00,
      `parent_user` VARCHAR(100) NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlTable);

    // 2. Inserting your users data
    $sqlInsert = "INSERT INTO `users` (`username`, `password`, `target_website`, `balance`, `parent_user`) 
    VALUES 
      ('Amal321', '\$2y\$10\$e.w2T3P0/Z9g/3E5T7z9u.oX8B.4h2n9z5k7L2v4B6n8M0P2Q4R6S', 'Cricaza247.com', 100.00, 'Deepak1517'),
      ('karman321', '\$2y\$10\$e.w2T3P0/Z9g/3E5T7z9u.oX8B.4h2n9z5k7L2v4B6n8M0P2Q4R6S', 'Cricaza247.com', 100.00, 'Manu3711'),
      ('bhobesh321', '\$2y\$10\$e.w2T3P0/Z9g/3E5T7z9u.oX8B.4h2n9z5k7L2v4B6n8M0P2Q4R6S', 'Winexch24.com', 100.00, 'Aiay5273'),
      ('pinkii32', '\$2y\$10\$e.w2T3P0/Z9g/3E5T7z9u.oX8B.4h2n9z5k7L2v4B6n8M0P2Q4R6S', 'Cricaza247.com', 100.00, 'Rajeev2430'),
      ('bhobesh32', '\$2y\$10\$e.w2T3P0/Z9g/3E5T7z9u.oX8B.4h2n9z5k7L2v4B6n8M0P2Q4R6S', 'Cricaza247.com', 100.00, 'Aiav52731')
    ON DUPLICATE KEY UPDATE 
      `target_website` = VALUES(`target_website`),
      `balance` = VALUES(`balance`),
      `parent_user` = VALUES(`parent_user`)";
    
    $pdo->exec($sqlInsert);

    echo "<h3>Database Setup Successfully Completed! Saare users add ho gaye hain.</h3>";

} catch (\PDOException $e) {
    echo "Connection Error: " . $e->getMessage();
}
?>

<?php
// Environment variables se details fetch karna
$host = getenv('DB_HOST');
$db   = getenv('DB_NAME') ?: 'defaultdb';
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$port = getenv('DB_PORT') ?: 3306;

// Connection check using mysqli
$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Table Creation Query
$sqlTable = "CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `target_website` VARCHAR(150) NOT NULL,
  `balance` DECIMAL(10, 2) DEFAULT 100.00,
  `parent_user` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($sqlTable)) {
    die("Error creating table: " . $conn->error);
}

// 2. Data Insertion Query
$sqlInsert = "INSERT INTO `users` (`username`, `password`, `target_website`, `balance`, `parent_user`) 
VALUES 
  ('Amal321', '$2y$10$e.w2T3P0/Z9g/3E5T7z9u.oX8B.4h2n9z5k7L2v4B6n8M0P2Q4R6S', 'Cricaza247.com', 100.00, 'Deepak1517'),
  ('karman321', '$2y$10$e.w2T3P0/Z9g/3E5T7z9u.oX8B.4h2n9z5k7L2v4B6n8M0P2Q4R6S', 'Cricaza247.com', 100.00, 'Manu3711'),
  ('bhobesh321', '$2y$10$e.w2T3P0/Z9g/3E5T7z9u.oX8B.4h2n9z5k7L2v4B6n8M0P2Q4R6S', 'Winexch24.com', 100.00, 'Aiay5273'),
  ('pinkii32', '$2y$10$e.w2T3P0/Z9g/3E5T7z9u.oX8B.4h2n9z5k7L2v4B6n8M0P2Q4R6S', 'Cricaza247.com', 100.00, 'Rajeev2430'),
  ('bhobesh32', '$2y$10$e.w2T3P0/Z9g/3E5T7z9u.oX8B.4h2n9z5k7L2v4B6n8M0P2Q4R6S', 'Cricaza247.com', 100.00, 'Aiav52731')
ON DUPLICATE KEY UPDATE 
  `target_website` = VALUES(`target_website`),
  `balance` = VALUES(`balance`),
  `parent_user` = VALUES(`parent_user`)";

if ($conn->query($sqlInsert)) {
    echo "<h3>Database Setup Successfully Completed! Saare users add ho gaye hain.</h3>";
} else {
    echo "Error inserting data: " . $conn->error;
}

$conn->close();
?>


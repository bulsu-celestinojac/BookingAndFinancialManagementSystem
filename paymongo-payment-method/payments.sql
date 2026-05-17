CREATE DATABASE IF NOT EXISTS resort_db;
USE resort_db;

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    package INT NOT NULL,
    booked_date DATE NOT NULL,
    status ENUM('pending','paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

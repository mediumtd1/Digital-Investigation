-- Create Database
CREATE DATABASE IF NOT EXISTS toll_directory_db;
USE toll_directory_db;

-- Create Tolls Table
CREATE TABLE IF NOT EXISTS tolls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plaza_code VARCHAR(20) NOT NULL UNIQUE,
    plaza_name VARCHAR(255) NOT NULL,
    plaza_type VARCHAR(50) DEFAULT 'Public',
    state VARCHAR(100) NOT NULL,
    district VARCHAR(100) NOT NULL,
    concessionaire VARCHAR(255),
    lat DECIMAL(10, 8) DEFAULT 0,
    lng DECIMAL(11, 8) DEFAULT 0,
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
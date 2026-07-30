-- CyberNotice Database Schema
CREATE DATABASE IF NOT EXISTS cyber_notice_db;
USE cyber_notice_db;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    rank VARCHAR(50),
    police_station VARCHAR(100),
    belt_number VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cases Table
CREATE TABLE IF NOT EXISTS cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fir_number VARCHAR(50) UNIQUE NOT NULL,
    sections VARCHAR(255),
    complainant_name VARCHAR(100),
    investigating_officer_id INT,
    status ENUM('Open', 'Closed', 'Pending') DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (investigating_officer_id) REFERENCES users(id)
);

-- Notices Table
CREATE TABLE IF NOT EXISTS notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT,
    user_id INT,
    notice_type VARCHAR(100), -- Section 94 BNSS, Section 179 BNSS, etc.
    recipient_name VARCHAR(255),
    recipient_address TEXT,
    content_json JSON, -- Stores the dynamic fields like IP, Phone, etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Seed Data
INSERT INTO users (username, password, full_name, rank, police_station, belt_number) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Inspector Rajesh Kumar', 'Inspector', 'Cyber Crime Cell, New Delhi', 'DL-12345');
-- Password is 'password'

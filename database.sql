-- Create database
CREATE DATABASE IF NOT EXISTS lost_and_found;
USE lost_and_found;

-- Create lost_items table
CREATE TABLE IF NOT EXISTS lost_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    date_lost DATE NOT NULL,
    location VARCHAR(255) NOT NULL,
    image VARCHAR(255),
    contact_name VARCHAR(255) NOT NULL,
    contact_email VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(50),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create found_items table
CREATE TABLE IF NOT EXISTS found_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    date_found DATE NOT NULL,
    location VARCHAR(255) NOT NULL,
    image VARCHAR(255),
    contact_name VARCHAR(255) NOT NULL,
    contact_email VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(50),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create admin table
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create matches table
CREATE TABLE IF NOT EXISTS matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lost_id INT NOT NULL,
    found_id INT NOT NULL,
    score FLOAT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lost_id) REFERENCES lost_items(id) ON DELETE CASCADE,
    FOREIGN KEY (found_id) REFERENCES found_items(id) ON DELETE CASCADE
);

-- Insert default admin user (username: admin, password: admin123)
INSERT INTO admin (username, password, email) VALUES 
('admin', '$2y$10$8WxhXQQBCIrSVttLJ9Ik1.Nbv0R.dY.lfTVLrNcmcTfQeHrFnXDDa', 'admin@example.com');

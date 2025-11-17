-- =============================================
-- REGISTRAR QUEUE SYSTEM - DATABASE SCHEMA
-- =============================================

-- Drop tables if they exist (for clean reinstall)
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS staff;
DROP TABLE IF EXISTS queue_status;

-- =============================================
-- 1. SERVICES TABLE
-- =============================================
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_key VARCHAR(50) UNIQUE NOT NULL,
    service_name VARCHAR(100) NOT NULL,
    service_type ENUM('standard', 'express') NOT NULL,
    description TEXT,
    estimated_duration INT DEFAULT 15 COMMENT 'Minutes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 2. STAFF TABLE
-- =============================================
CREATE TABLE staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'staff') DEFAULT 'staff',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 3. BOOKINGS TABLE
-- =============================================
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_number VARCHAR(20) UNIQUE NOT NULL,
    service_id INT NOT NULL,
    service_type ENUM('standard', 'express') NOT NULL,
    
    -- Standard batch info
    booking_date DATE NULL COMMENT 'For standard batches',
    time_window VARCHAR(20) NULL COMMENT 'e.g., 09:00-09:30',
    
    -- Student info (optional - can be anonymous)
    student_name VARCHAR(100) NULL,
    student_id VARCHAR(50) NULL,
    student_email VARCHAR(100) NULL,
    
    -- Queue status
    status ENUM('pending', 'waiting', 'now_serving', 'completed', 'cancelled') DEFAULT 'pending',
    queue_position INT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    called_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    
    -- Foreign keys
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
    
    -- Indexes
    INDEX idx_status (status),
    INDEX idx_service_type (service_type),
    INDEX idx_booking_date (booking_date),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 4. QUEUE STATUS TABLE (Real-time tracking)
-- =============================================
CREATE TABLE queue_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_type ENUM('standard', 'express') NOT NULL UNIQUE,
    current_batch_number VARCHAR(20) NULL,
    current_time_window VARCHAR(20) NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (current_batch_number) REFERENCES bookings(batch_number) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 5. INSERT INITIAL DATA
-- =============================================

-- Insert Services
INSERT INTO services (service_key, service_name, service_type, description, estimated_duration) VALUES
('add-drop', 'Add/Drop Subjects', 'standard', 'For complex schedule changes or solving registration holds', 30),
('inc-clearance', 'INC Clearance / Grade Correction', 'standard', 'Submitting and processing grade-related paperwork', 20),
('submit-form', 'Submit a Form', 'express', 'Just dropping off a pre-signed form (e.g., transcript request, graduation application)', 5),
('pickup-doc', 'Pick up a Document or Form', 'express', 'Getting a physical form or picking up a requested document', 5),
('quick-question', 'Ask a Quick Question', 'express', 'A simple, 1-2 minute question for the staff', 3);

-- Insert Default Staff Account (password: admin123 - CHANGE IN PRODUCTION!)
INSERT INTO staff (username, password_hash, full_name, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@university.edu', 'admin');
-- Note: The hash above is for 'admin123' - use password_hash() in PHP to create new ones

-- Initialize Queue Status
INSERT INTO queue_status (queue_type, current_batch_number) VALUES
('standard', NULL),
('express', NULL);

-- =============================================
-- 6. SAMPLE DATA (Optional - for testing)
-- =============================================

-- Sample bookings
INSERT INTO bookings (batch_number, service_id, service_type, booking_date, time_window, status, queue_position) VALUES
('S-10', 1, 'standard', CURDATE(), '08:30-09:00', 'completed', 1),
('S-12', 1, 'standard', CURDATE(), '09:00-09:30', 'pending', 2),
('S-13', 2, 'standard', CURDATE(), '09:00-09:30', 'pending', 3),
('S-14', 1, 'standard', CURDATE(), '09:30-10:00', 'pending', 4),
('Q-101', 5, 'express', NULL, NULL, 'waiting', 1),
('Q-102', 3, 'express', NULL, NULL, 'waiting', 2);

-- =============================================
-- 7. USEFUL QUERIES FOR MONITORING
-- =============================================

-- View today's standard queue
-- SELECT b.batch_number, s.service_name, b.time_window, b.status 
-- FROM bookings b 
-- JOIN services s ON b.service_id = s.id 
-- WHERE b.booking_date = CURDATE() AND b.service_type = 'standard'
-- ORDER BY b.time_window, b.queue_position;

-- View express queue
-- SELECT b.batch_number, s.service_name, b.status, b.created_at
-- FROM bookings b 
-- JOIN services s ON b.service_id = s.id 
-- WHERE b.service_type = 'express' AND b.status IN ('waiting', 'pending')
-- ORDER BY b.queue_position, b.created_at;

-- Check current queue status
-- SELECT * FROM queue_status;
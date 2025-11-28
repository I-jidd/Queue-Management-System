-- =============================================
-- REGISTRAR QUEUE SYSTEM - DATABASE SCHEMA (PostgreSQL)
-- =============================================

-- Drop tables if they exist (for clean reinstall)
DROP TABLE IF EXISTS bookings CASCADE;
DROP TABLE IF EXISTS services CASCADE;
DROP TABLE IF EXISTS staff CASCADE;
DROP TABLE IF EXISTS queue_status CASCADE;

-- Drop custom types if they exist
DROP TYPE IF EXISTS service_type_enum CASCADE;
DROP TYPE IF EXISTS booking_status_enum CASCADE;
DROP TYPE IF EXISTS staff_role_enum CASCADE;
DROP TYPE IF EXISTS queue_type_enum CASCADE;

-- Create ENUM types
CREATE TYPE service_type_enum AS ENUM ('standard', 'express');
CREATE TYPE booking_status_enum AS ENUM ('pending', 'waiting', 'now_serving', 'completed', 'cancelled');
CREATE TYPE staff_role_enum AS ENUM ('admin', 'staff');
CREATE TYPE queue_type_enum AS ENUM ('standard', 'express');

-- =============================================
-- 1. SERVICES TABLE
-- =============================================
CREATE TABLE services (
    id SERIAL PRIMARY KEY,
    service_key VARCHAR(50) UNIQUE NOT NULL,
    service_name VARCHAR(100) NOT NULL,
    service_type service_type_enum NOT NULL,
    description TEXT,
    estimated_duration INTEGER DEFAULT 15,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- 2. STAFF TABLE
-- =============================================
CREATE TABLE staff (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role staff_role_enum DEFAULT 'staff',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- 3. BOOKINGS TABLE
-- =============================================
CREATE TABLE bookings (
    id SERIAL PRIMARY KEY,
    batch_number VARCHAR(20) UNIQUE NOT NULL,
    service_id INTEGER NOT NULL,
    service_type service_type_enum NOT NULL,
    
    -- Standard batch info
    booking_date DATE NULL,
    time_window VARCHAR(20) NULL,
    
    -- Student info (optional - can be anonymous)
    student_name VARCHAR(100) NULL,
    student_id VARCHAR(50) NULL,
    student_email VARCHAR(100) NULL,
    
    -- Queue status
    status booking_status_enum DEFAULT 'pending',
    queue_position INTEGER NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    called_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    
    -- Foreign keys
    CONSTRAINT fk_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT
);

-- Create indexes
CREATE INDEX idx_status ON bookings(status);
CREATE INDEX idx_service_type ON bookings(service_type);
CREATE INDEX idx_booking_date ON bookings(booking_date);
CREATE INDEX idx_created_at ON bookings(created_at);

-- =============================================
-- 4. QUEUE STATUS TABLE (Real-time tracking)
-- =============================================
CREATE TABLE queue_status (
    id SERIAL PRIMARY KEY,
    queue_type queue_type_enum NOT NULL UNIQUE,
    current_batch_number VARCHAR(20) NULL,
    current_time_window VARCHAR(20) NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_batch_number FOREIGN KEY (current_batch_number) REFERENCES bookings(batch_number) ON DELETE SET NULL
);

-- Create function to update last_updated timestamp
CREATE OR REPLACE FUNCTION update_last_updated()
RETURNS TRIGGER AS $$
BEGIN
    NEW.last_updated = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create trigger for queue_status
CREATE TRIGGER update_queue_status_timestamp
    BEFORE UPDATE ON queue_status
    FOR EACH ROW
    EXECUTE FUNCTION update_last_updated();

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
('S-10', 1, 'standard', CURRENT_DATE, '08:30-09:00', 'completed', 1),
('S-12', 1, 'standard', CURRENT_DATE, '09:00-09:30', 'pending', 2),
('S-13', 2, 'standard', CURRENT_DATE, '09:00-09:30', 'pending', 3),
('S-14', 1, 'standard', CURRENT_DATE, '09:30-10:00', 'pending', 4),
('Q-101', 5, 'express', NULL, NULL, 'waiting', 1),
('Q-102', 3, 'express', NULL, NULL, 'waiting', 2);

-- =============================================
-- 7. USEFUL QUERIES FOR MONITORING
-- =============================================

-- View today's standard queue
-- SELECT b.batch_number, s.service_name, b.time_window, b.status 
-- FROM bookings b 
-- JOIN services s ON b.service_id = s.id 
-- WHERE b.booking_date = CURRENT_DATE AND b.service_type = 'standard'
-- ORDER BY b.time_window, b.queue_position;

-- View express queue
-- SELECT b.batch_number, s.service_name, b.status, b.created_at
-- FROM bookings b 
-- JOIN services s ON b.service_id = s.id 
-- WHERE b.service_type = 'express' AND b.status IN ('waiting', 'pending')
-- ORDER BY b.queue_position, b.created_at;

-- Check current queue status
-- SELECT * FROM queue_status;

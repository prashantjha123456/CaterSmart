ALTER TABLE bookings
ADD COLUMN payment_status ENUM('Pending', 'Pending Verification', 'Verified', 'Failed') DEFAULT 'Pending',
ADD COLUMN transaction_id VARCHAR(100),
ADD COLUMN payment_date DATE; 
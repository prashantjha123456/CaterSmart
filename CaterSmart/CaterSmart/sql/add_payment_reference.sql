ALTER TABLE events
ADD COLUMN payment_reference VARCHAR(100) DEFAULT NULL AFTER payment_status; 
-- Add payment_method column to events table if it doesn't exist
ALTER TABLE events
ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT NULL AFTER payment_reference; 
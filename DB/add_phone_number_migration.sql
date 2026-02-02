-- Migration: Add phone_number column to Users table
-- Run this if you already have an existing Users table without phone_number

-- Check if column exists before adding (MySQL 5.7+)
-- If you get an error that the column already exists, you can ignore it

ALTER TABLE Users 
ADD COLUMN IF NOT EXISTS phone_number VARCHAR(20) NULL AFTER email;

-- Optional: Add an index for faster lookups
CREATE INDEX idx_phone_number ON Users(phone_number);

-- Verify the column was added
DESCRIBE Users;

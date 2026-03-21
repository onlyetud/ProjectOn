-- Migration: add deleted_at column to devis for soft deletes
ALTER TABLE devis ADD COLUMN deleted_at TIMESTAMP NULL;

-- Run this once in your MySQL shell or via phpMyAdmin.
-- One-time migration for existing databases that predate visit reason categories.
-- Back up cqams before running this file.
USE cqams;

ALTER TABLE appointments
    ADD COLUMN reason_category ENUM('routine', 'followup', 'emergency', 'refill', 'reschedule', 'other')
        NOT NULL DEFAULT 'routine' AFTER end_time;

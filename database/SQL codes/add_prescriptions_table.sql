-- Adds the prescriptions table to existing databases that predate this feature.
USE cqams;

CREATE TABLE IF NOT EXISTS prescriptions (
    prescription_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    consultation_id BIGINT UNSIGNED NOT NULL,
    medicine_name VARCHAR(120) NOT NULL,
    dosage VARCHAR(80) NOT NULL,
    frequency VARCHAR(120) NOT NULL,
    duration VARCHAR(80) NOT NULL,
    instructions VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (prescription_id),
    KEY idx_prescriptions_consultation (consultation_id),
    CONSTRAINT fk_prescriptions_consultation
        FOREIGN KEY (consultation_id) REFERENCES consultations (consultation_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE = InnoDB;

-- Back up cqams before running. This removes receptionist accounts and their staff rows.
USE cqams;

DROP PROCEDURE IF EXISTS remove_receptionist_role;
DELIMITER //
CREATE PROCEDURE remove_receptionist_role()
BEGIN
    IF EXISTS (
        SELECT 1
        FROM user_accounts u
        INNER JOIN staff s ON s.user_account_id = u.user_account_id
        INNER JOIN doctors d ON d.staff_id = s.staff_id
        WHERE u.role = 'receptionist'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'A receptionist account is linked to a doctor profile; resolve it before migration.';
    END IF;

    START TRANSACTION;
    DELETE s
    FROM staff s
    INNER JOIN user_accounts u ON u.user_account_id = s.user_account_id
    WHERE u.role = 'receptionist';

    DELETE FROM user_accounts WHERE role = 'receptionist';
    COMMIT;
END//
DELIMITER ;

CALL remove_receptionist_role();
DROP PROCEDURE remove_receptionist_role;

ALTER TABLE user_accounts
    MODIFY COLUMN role ENUM('administrator', 'doctor', 'patient') NOT NULL;

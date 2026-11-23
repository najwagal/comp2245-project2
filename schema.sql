DROP DATABASE IF EXISTS helpdesk;
CREATE DATABASE helpdesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE helpdesk;

SET @pw_hash = '$2y$12$0Qf8Y1FCgv7gCyZHzAzKaOSumBFkMlHXVawszlGMuArlLYF.hF18K';

CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'member') NOT NULL DEFAULT 'member',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE tickets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Medium',
    status ENUM('Open', 'In progress', 'Closed') NOT NULL DEFAULT 'Open',
    created_by INT UNSIGNED NOT NULL,
    assigned_to INT UNSIGNED NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_tickets_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_tickets_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE notes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id INT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_notes_ticket_id FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_notes_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

INSERT INTO users (firstname, lastname, email, password, role) VALUES
('Admin', 'User', 'admin@fixit.test', @pw_hash, 'admin'),
('Keisha', 'Brown', 'keisha@fixit.test', @pw_hash, 'member'),
('Marcus', 'Campbell', 'marcus@fixit.test', @pw_hash, 'member');

INSERT INTO tickets (title, description, priority, status, created_by, assigned_to, created_at, updated_at) VALUES
('Printer in Room 4 jams on every page', 'Paper jams in the main feed tray whenever printing multi-page PDFs.', 'Medium', 'Open', 2, 3, '2026-10-26 09:12:00', '2026-10-26 09:12:00'),
('Cannot connect to staff Wi-Fi', 'Laptop sees StaffNet but fails authentication after password change.', 'High', 'In progress', 3, 2, '2026-10-27 14:30:00', '2026-10-28 09:40:00'),
('Projector remote missing in Lab B', 'Presentation remote is not in the podium drawer.', 'Low', 'Open', 3, NULL, '2026-10-28 08:05:00', '2026-10-28 08:05:00'),
('Email bounces from the finance office', 'Outbound messages to finance distribution list return 550 error.', 'High', 'Open', 1, 1, '2026-10-29 11:47:00', '2026-10-29 11:47:00'),
('New starter needs an account', 'Create email and help desk account for Monday onboarding.', 'Medium', 'Closed', 1, 2, '2026-10-20 10:00:00', '2026-10-21 15:10:00');

INSERT INTO notes (ticket_id, body, created_by, created_at) VALUES
(2, 'Re-entered credentials and checked network profile.', 2, '2026-10-27 15:02:00'),
(2, 'Tested wired connection; working fine. Will re-issue Wi-Fi profile.', 2, '2026-10-28 09:40:00'),
(4, 'Checked mail queue and SPF alignment for finance relay.', 1, '2026-10-29 12:15:00');

CREATE USER IF NOT EXISTS 'comp2245'@'localhost' IDENTIFIED BY 'comp2245-demo';
CREATE USER IF NOT EXISTS 'comp2245'@'127.0.0.1' IDENTIFIED BY 'comp2245-demo';
GRANT SELECT, INSERT, UPDATE, DELETE ON helpdesk.* TO 'comp2245'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON helpdesk.* TO 'comp2245'@'127.0.0.1';
FLUSH PRIVILEGES;
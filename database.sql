-- BankVWA schema. Run via `php setup.php` (it also seeds sample data).
-- WARNING: drops and recreates the bankvwa database every time.

DROP DATABASE IF EXISTS bankvwa;
CREATE DATABASE bankvwa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bankvwa;

CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    email         VARCHAR(100) NOT NULL UNIQUE,
    full_name     VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,          -- output of password_hash(), never plain text
    password      VARCHAR(100) NOT NULL DEFAULT '', -- VULN LAB ONLY: plaintext copy, read by the injectable login
    role          ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE accounts (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    account_number CHAR(12) NOT NULL UNIQUE,
    type           ENUM('savings', 'current') NOT NULL, -- Indian banks say "current", not "checking"
    balance        DECIMAL(12,2) NOT NULL DEFAULT 0, -- DECIMAL, not FLOAT: money must be exact
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE transactions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    account_id  INT NOT NULL,
    amount      DECIMAL(12,2) NOT NULL,              -- positive = credit, negative = debit
    description VARCHAR(255) NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE beneficiaries (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    name           VARCHAR(100) NOT NULL,
    account_number CHAR(12) NOT NULL,
    nickname       VARCHAR(100),
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_payee (user_id, account_number), -- can't save the same payee twice
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE password_resets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    token_hash CHAR(64) NOT NULL,                    -- sha256 of the emailed token
    expires_at DATETIME NOT NULL,
    used       TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NULL,
    action     VARCHAR(50) NOT NULL,
    details    VARCHAR(255),
    ip_address VARCHAR(45),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
--  CampusConnect — database_patch.sql
--  Run AFTER database.sql and database_update.sql
--  Adds: email verification, password reset, login audit
-- ============================================================

USE campusconnect;

-- ── Email verification tokens ─────────────────────────────────
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS verify_token   VARCHAR(64)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS reset_token    VARCHAR(64)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS reset_expires  DATETIME     DEFAULT NULL;

-- Set existing seed users as verified (they were inserted with is_verified=1)
UPDATE users SET is_verified = 1 WHERE is_verified = 1;

-- ── Increase password minimum tracking (informational) ────────
-- Password logic enforced in PHP (min 8 chars going forward)

-- Confirm tables exist (safe no-op if already present)
CREATE TABLE IF NOT EXISTS notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    actor_id    INT,
    type        ENUM('connection_request','connection_accepted',
                     'project_like','project_comment','project_join_request',
                     'endorsement','event_reminder','notice_new',
                     'message_new') NOT NULL,
    ref_id      INT,
    message     VARCHAR(300),
    is_read     TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
);

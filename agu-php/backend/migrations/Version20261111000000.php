<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Esquema inicial autocontido; alterações posteriores exigem nova migration.
 * MySQL executa DDL com commit implícito: não há rollback transacional.
 */
final class Version20261111000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria o esquema inicial do To-Do List e os catálogos de status e transições';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'Esta migration exige MySQL 8.0.16 ou superior.'
        );

        $this->addSql(<<<'SQL'
CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(180) NOT NULL,
    password VARCHAR(255) NOT NULL,
    roles JSON NOT NULL DEFAULT ('["ROLE_USER"]'),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT uq_users_username UNIQUE (username),
    CONSTRAINT uq_users_email UNIQUE (email),
    CONSTRAINT chk_users_username CHECK (CHAR_LENGTH(TRIM(username)) >= 3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE task_statuses (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(30) NOT NULL,
    name VARCHAR(60) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_terminal BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY (id),
    CONSTRAINT uq_task_statuses_code UNIQUE (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO task_statuses (id, code, name, sort_order, is_terminal) VALUES
(1, 'backlog', 'Backlog', 1, FALSE),
(2, 'todo', 'A fazer', 2, FALSE),
(3, 'in_progress', 'Em andamento', 3, FALSE),
(4, 'blocked', 'Bloqueada', 4, FALSE),
(5, 'done', 'Concluida', 5, TRUE),
(6, 'canceled', 'Cancelada', 6, TRUE)
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE task_status_transitions (
    from_status_id SMALLINT UNSIGNED NOT NULL,
    to_status_id SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (from_status_id, to_status_id),
    CONSTRAINT fk_transition_from FOREIGN KEY (from_status_id)
        REFERENCES task_statuses (id) ON DELETE RESTRICT,
    CONSTRAINT fk_transition_to FOREIGN KEY (to_status_id)
        REFERENCES task_statuses (id) ON DELETE RESTRICT,
    CONSTRAINT chk_transition_different CHECK (from_status_id <> to_status_id),
    INDEX idx_transition_to (to_status_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO task_status_transitions (from_status_id, to_status_id) VALUES
(1,2), (1,6),
(2,1), (2,3), (2,4), (2,6),
(3,2), (3,4), (3,5), (3,6),
(4,2), (4,3), (4,6),
(5,2), (6,2)
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE tasks (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    created_by INT UNSIGNED NOT NULL,
    assigned_to INT UNSIGNED NULL,
    status_id SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    parent_task_id INT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    priority VARCHAR(12) NOT NULL DEFAULT 'medium',
    start_date DATETIME NULL,
    due_date DATETIME NULL,
    completed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_tasks_id_creator UNIQUE (id, created_by),
    CONSTRAINT fk_tasks_creator FOREIGN KEY (created_by)
        REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_tasks_assignee FOREIGN KEY (assigned_to)
        REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_tasks_status FOREIGN KEY (status_id)
        REFERENCES task_statuses (id) ON DELETE RESTRICT,
    CONSTRAINT fk_tasks_parent_owner FOREIGN KEY (parent_task_id, created_by)
        REFERENCES tasks (id, created_by) ON DELETE RESTRICT,
    CONSTRAINT chk_tasks_title CHECK (CHAR_LENGTH(TRIM(title)) > 0),
    CONSTRAINT chk_tasks_priority CHECK (priority IN ('low', 'medium', 'high', 'urgent')),
    CONSTRAINT chk_tasks_dates CHECK (
        start_date IS NULL OR due_date IS NULL OR due_date >= start_date
    ),
    INDEX idx_tasks_creator_status (created_by, deleted_at, status_id),
    INDEX idx_tasks_assignee_status (assigned_to, deleted_at, status_id),
    INDEX idx_tasks_creator_due (created_by, deleted_at, due_date),
    INDEX idx_tasks_creator_created (created_by, deleted_at, created_at),
    INDEX idx_tasks_status (status_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->addSql(<<<'SQL'
CREATE TRIGGER trg_tasks_before_insert
BEFORE INSERT ON tasks
FOR EACH ROW
BEGIN
    IF NEW.parent_task_id IS NOT NULL THEN
        IF NEW.parent_task_id = NEW.id THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Uma tarefa nao pode ser seu proprio pai';
        END IF;
        IF NOT EXISTS (SELECT 1 FROM tasks WHERE id = NEW.parent_task_id) THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'A tarefa pai deve existir antes da subtarefa';
        END IF;
    END IF;
    IF EXISTS (SELECT 1 FROM task_statuses WHERE id = NEW.status_id AND code = 'done') THEN
        SET NEW.completed_at = COALESCE(NEW.completed_at, UTC_TIMESTAMP());
    ELSE
        SET NEW.completed_at = NULL;
    END IF;
END
SQL);

        $this->addSql(<<<'SQL'
CREATE TRIGGER trg_tasks_before_update
BEFORE UPDATE ON tasks
FOR EACH ROW
BEGIN
    IF NEW.parent_task_id IS NOT NULL AND NEW.parent_task_id = NEW.id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Uma tarefa nao pode ser seu proprio pai';
    END IF;
    IF NEW.status_id <> OLD.status_id AND NOT EXISTS (
        SELECT 1 FROM task_status_transitions
        WHERE from_status_id = OLD.status_id AND to_status_id = NEW.status_id
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Transicao de status nao permitida';
    END IF;
    IF EXISTS (SELECT 1 FROM task_statuses WHERE id = NEW.status_id AND code = 'done') THEN
        SET NEW.completed_at = COALESCE(NEW.completed_at, UTC_TIMESTAMP());
    ELSE
        SET NEW.completed_at = NULL;
    END IF;
END
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE task_attachments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    task_id INT UNSIGNED NOT NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    storage_key VARCHAR(512) NOT NULL,
    mime_type VARCHAR(150) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL,
    checksum_sha256 CHAR(64) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_attachments_task FOREIGN KEY (task_id)
        REFERENCES tasks (id) ON DELETE RESTRICT,
    CONSTRAINT fk_attachments_user FOREIGN KEY (uploaded_by)
        REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT uq_attachments_storage_key UNIQUE (storage_key),
    CONSTRAINT chk_attachments_size CHECK (size_bytes > 0),
    INDEX idx_attachments_task (task_id, deleted_at, created_at),
    INDEX idx_attachments_uploader (uploaded_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE task_history (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    task_id INT UNSIGNED NOT NULL,
    actor_id INT UNSIGNED NOT NULL,
    action VARCHAR(40) NOT NULL,
    field_name VARCHAR(80) NULL,
    old_value JSON NULL,
    new_value JSON NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    CONSTRAINT fk_history_task FOREIGN KEY (task_id)
        REFERENCES tasks (id) ON DELETE RESTRICT,
    CONSTRAINT fk_history_actor FOREIGN KEY (actor_id)
        REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT chk_history_action CHECK (action IN (
        'created', 'updated', 'status_changed', 'assigned', 'completed',
        'reopened', 'deleted', 'restored', 'attachment_added',
        'attachment_removed', 'comment_added', 'comment_removed',
        'tag_added', 'tag_removed', 'subtask_added'
    )),
    INDEX idx_history_task_date (task_id, created_at, id),
    INDEX idx_history_actor_date (actor_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE task_comments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    task_id INT UNSIGNED NOT NULL,
    author_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_comments_task FOREIGN KEY (task_id)
        REFERENCES tasks (id) ON DELETE RESTRICT,
    CONSTRAINT fk_comments_author FOREIGN KEY (author_id)
        REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT chk_comments_content CHECK (CHAR_LENGTH(TRIM(content)) > 0),
    INDEX idx_comments_task_date (task_id, deleted_at, created_at),
    INDEX idx_comments_author (author_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE tags (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(60) NOT NULL,
    color CHAR(7) NOT NULL DEFAULT '#64748B',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_tags_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT uq_tags_user_name UNIQUE (user_id, name),
    CONSTRAINT uq_tags_id_user UNIQUE (id, user_id),
    CONSTRAINT chk_tags_name CHECK (CHAR_LENGTH(TRIM(name)) > 0),
    CONSTRAINT chk_tags_color CHECK (color REGEXP '^#[0-9A-Fa-f]{6}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE task_tags (
    task_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (task_id, tag_id),
    CONSTRAINT fk_task_tags_task_owner FOREIGN KEY (task_id, user_id)
        REFERENCES tasks (id, created_by) ON DELETE RESTRICT,
    CONSTRAINT fk_task_tags_tag_owner FOREIGN KEY (tag_id, user_id)
        REFERENCES tags (id, user_id) ON DELETE RESTRICT,
    INDEX idx_task_tags_tag_user (tag_id, user_id),
    INDEX idx_task_tags_task_user (task_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'Esta migration exige MySQL.'
        );

        $this->addSql('DROP TRIGGER IF EXISTS trg_tasks_before_update');
        $this->addSql('DROP TRIGGER IF EXISTS trg_tasks_before_insert');
        $this->addSql('DROP TABLE IF EXISTS task_tags');
        $this->addSql('DROP TABLE IF EXISTS tags');
        $this->addSql('DROP TABLE IF EXISTS task_comments');
        $this->addSql('DROP TABLE IF EXISTS task_history');
        $this->addSql('DROP TABLE IF EXISTS task_attachments');
        $this->addSql('DROP TABLE IF EXISTS tasks');
        $this->addSql('DROP TABLE IF EXISTS task_status_transitions');
        $this->addSql('DROP TABLE IF EXISTS task_statuses');
        $this->addSql('DROP TABLE IF EXISTS users');
    }
}

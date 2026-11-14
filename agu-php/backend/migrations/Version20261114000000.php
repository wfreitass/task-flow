<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * RBAC: roles (capacidades), profiles (perfis) e a ligação N:N entre eles; cada usuário tem um profile.
 *
 *   users ──N:1──▶ profiles ◀──N:N (profile_roles)──▶ roles
 *
 * Substitui a coluna JSON `users.roles`. Os usuários existentes são migrados: quem tinha ROLE_ADMIN vira o profile
 * `admin`; os demais, `executor`. MySQL executa DDL com commit implícito: não há rollback transacional.
 */
final class Version20261114000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria roles, profiles e profile_roles (RBAC) e troca users.roles por users.profile_id';
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
CREATE TABLE roles (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(60) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT uq_roles_code UNIQUE (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE profiles (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    is_system BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT uq_profiles_code UNIQUE (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE profile_roles (
    profile_id SMALLINT UNSIGNED NOT NULL,
    role_id SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (profile_id, role_id),
    CONSTRAINT fk_profile_roles_profile FOREIGN KEY (profile_id) REFERENCES profiles (id) ON DELETE CASCADE,
    CONSTRAINT fk_profile_roles_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        // Catálogo de roles (os códigos são os que o código da aplicação conhece: App\Shared\Domain\Role).
        $this->addSql(<<<'SQL'
INSERT INTO roles (id, code, name, description) VALUES
    (1, 'task.create', 'Criar tarefas', 'Criar novas tarefas'),
    (2, 'task.delete', 'Excluir tarefas', 'Excluir tarefas (exclusão lógica)'),
    (3, 'task.cancel', 'Cancelar tarefas', 'Mudar o status de uma tarefa para cancelada'),
    (4, 'user.manage', 'Gerenciar usuários', 'Vincular um perfil a um usuário'),
    (5, 'profile.manage', 'Gerenciar perfis', 'Criar, editar e excluir perfis e escolher as roles de cada um')
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO profiles (id, code, name, description, is_system) VALUES
    (1, 'admin', 'Administrador', 'Acesso total', TRUE),
    (2, 'executor', 'Executor', 'Executa as tarefas: cria, edita o que é seu, muda o status e comenta; não exclui nem cancela', TRUE)
SQL);

        // admin: todas as roles; executor: só criar tarefas.
        $this->addSql('INSERT INTO profile_roles (profile_id, role_id) SELECT 1, id FROM roles');
        $this->addSql("INSERT INTO profile_roles (profile_id, role_id) SELECT 2, id FROM roles WHERE code = 'task.create'");

        // users.profile_id: cria, preenche a partir de users.roles e só então torna obrigatório.
        $this->addSql('ALTER TABLE users ADD COLUMN profile_id SMALLINT UNSIGNED NULL AFTER password');
        $this->addSql(<<<'SQL'
UPDATE users SET profile_id = IF(JSON_CONTAINS(roles, '"ROLE_ADMIN"'), 1, 2)
SQL);
        $this->addSql(<<<'SQL'
ALTER TABLE users
    MODIFY COLUMN profile_id SMALLINT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_users_profile FOREIGN KEY (profile_id) REFERENCES profiles (id) ON DELETE RESTRICT
SQL);
        $this->addSql('ALTER TABLE users DROP COLUMN roles');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE users ADD COLUMN roles JSON NOT NULL DEFAULT ('[\"ROLE_USER\"]') AFTER password");
        $this->addSql(<<<'SQL'
UPDATE users u JOIN profiles p ON p.id = u.profile_id
SET u.roles = IF(p.code = 'admin', JSON_ARRAY('ROLE_ADMIN'), JSON_ARRAY('ROLE_USER'))
SQL);
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY fk_users_profile');
        $this->addSql('ALTER TABLE users DROP COLUMN profile_id');
        $this->addSql('DROP TABLE profile_roles');
        $this->addSql('DROP TABLE profiles');
        $this->addSql('DROP TABLE roles');
    }
}

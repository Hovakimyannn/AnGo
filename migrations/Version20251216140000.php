<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251216140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add password reset token fields to user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD COLUMN password_reset_token_hash VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD COLUMN password_reset_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        $this->addSql('CREATE INDEX IDX_USER_PASSWORD_RESET_TOKEN_HASH ON "user" (password_reset_token_hash)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS IDX_USER_PASSWORD_RESET_TOKEN_HASH');

        $this->addSql('ALTER TABLE "user" DROP COLUMN password_reset_token_hash');
        $this->addSql('ALTER TABLE "user" DROP COLUMN password_reset_token_expires_at');
    }
}



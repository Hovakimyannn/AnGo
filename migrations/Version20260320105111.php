<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260320105111 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist_post_service DROP CONSTRAINT fk_34e4c364ed5ca9e6');
        $this->addSql('ALTER TABLE artist_post_service DROP CONSTRAINT fk_40ef1a7c95a3bb01');
        $this->addSql('ALTER TABLE artist_profile DROP CONSTRAINT fk_artist_profile_category');
        $this->addSql('ALTER TABLE artist_profile ADD slug VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist_profile DROP slug');
    }
}

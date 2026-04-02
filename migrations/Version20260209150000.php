<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260209150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add footer fields to home_page_settings (tagline, social URLs, copyright).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE home_page_settings ADD footer_tagline TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD contact_instagram_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD contact_facebook_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD contact_copyright_text VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE home_page_settings DROP footer_tagline');
        $this->addSql('ALTER TABLE home_page_settings DROP contact_instagram_url');
        $this->addSql('ALTER TABLE home_page_settings DROP contact_facebook_url');
        $this->addSql('ALTER TABLE home_page_settings DROP contact_copyright_text');
    }
}

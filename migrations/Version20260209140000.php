<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260209140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add services_subtitle to home_page_settings for editable services section paragraph.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE home_page_settings ADD services_subtitle TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE home_page_settings DROP services_subtitle');
    }
}

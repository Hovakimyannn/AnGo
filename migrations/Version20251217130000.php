<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251217130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add home page image settings.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE home_page_settings (id SERIAL NOT NULL, hero_image VARCHAR(255) DEFAULT NULL, service_hair_image VARCHAR(255) DEFAULT NULL, service_makeup_image VARCHAR(255) DEFAULT NULL, service_nails_image VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE home_page_settings');
    }
}



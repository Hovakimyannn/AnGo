<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251219123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add service categories table (labels & ordering) for booking UI and admin management.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE service_category (id SERIAL NOT NULL, key_name VARCHAR(50) NOT NULL, label VARCHAR(255) NOT NULL, sort_order INT NOT NULL DEFAULT 100, is_active BOOLEAN NOT NULL DEFAULT TRUE, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_service_category_key ON service_category (key_name)');

        // Default categories (used as fallback in booking flow)
        $this->addSql("INSERT INTO service_category (key_name, label, sort_order, is_active) VALUES ('hair', 'Վարսահարդարում', 1, TRUE)");
        $this->addSql("INSERT INTO service_category (key_name, label, sort_order, is_active) VALUES ('makeup', 'Դիմահարդարում', 2, TRUE)");
        $this->addSql("INSERT INTO service_category (key_name, label, sort_order, is_active) VALUES ('nails', 'Մատնահարդարում', 3, TRUE)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE service_category');
    }
}



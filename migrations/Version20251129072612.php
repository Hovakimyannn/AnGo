<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251129072612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE appointment (id INT AUTO_INCREMENT NOT NULL, client_name VARCHAR(255) NOT NULL, client_email VARCHAR(255) NOT NULL, client_phone VARCHAR(20) NOT NULL, start_datetime DATETIME NOT NULL, end_datetime DATETIME NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME DEFAULT NULL, artist_id INT NOT NULL, service_id INT NOT NULL, INDEX IDX_FE38F844B7970CF8 (artist_id), INDEX IDX_FE38F844ED5CA9E6 (service_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE availability (id INT AUTO_INCREMENT NOT NULL, day_of_week INT NOT NULL, start_time TIME DEFAULT NULL, end_time TIME DEFAULT NULL, is_day_off TINYINT(1) NOT NULL, artist_id INT NOT NULL, INDEX IDX_3FB7A2BFB7970CF8 (artist_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844B7970CF8 FOREIGN KEY (artist_id) REFERENCES artist_profile (id)');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('ALTER TABLE availability ADD CONSTRAINT FK_3FB7A2BFB7970CF8 FOREIGN KEY (artist_id) REFERENCES artist_profile (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844B7970CF8');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844ED5CA9E6');
        $this->addSql('ALTER TABLE availability DROP FOREIGN KEY FK_3FB7A2BFB7970CF8');
        $this->addSql('DROP TABLE appointment');
        $this->addSql('DROP TABLE availability');
    }
}

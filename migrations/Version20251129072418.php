<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251129072418 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE artist_profile (id INT AUTO_INCREMENT NOT NULL, specialization VARCHAR(100) NOT NULL, bio LONGTEXT DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_3618F438A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE artist_profile_service (artist_profile_id INT NOT NULL, service_id INT NOT NULL, INDEX IDX_9228E07D2F85CDC1 (artist_profile_id), INDEX IDX_9228E07DED5CA9E6 (service_id), PRIMARY KEY(artist_profile_id, service_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE service (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, category VARCHAR(50) NOT NULL, duration_minutes INT NOT NULL, price DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE artist_profile ADD CONSTRAINT FK_3618F438A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE artist_profile_service ADD CONSTRAINT FK_9228E07D2F85CDC1 FOREIGN KEY (artist_profile_id) REFERENCES artist_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE artist_profile_service ADD CONSTRAINT FK_9228E07DED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist_profile DROP FOREIGN KEY FK_3618F438A76ED395');
        $this->addSql('ALTER TABLE artist_profile_service DROP FOREIGN KEY FK_9228E07D2F85CDC1');
        $this->addSql('ALTER TABLE artist_profile_service DROP FOREIGN KEY FK_9228E07DED5CA9E6');
        $this->addSql('DROP TABLE artist_profile');
        $this->addSql('DROP TABLE artist_profile_service');
        $this->addSql('DROP TABLE service');
        $this->addSql('DROP TABLE `user`');
    }
}

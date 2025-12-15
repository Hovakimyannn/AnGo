<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251215091000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link artist posts to services for filtering';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE artist_post_service (artist_post_id INT NOT NULL, service_id INT NOT NULL, PRIMARY KEY(artist_post_id, service_id))');
        $this->addSql('CREATE INDEX IDX_40EF1A7C95A3BB01 ON artist_post_service (artist_post_id)');
        $this->addSql('CREATE INDEX IDX_40EF1A7CED5CA9E6 ON artist_post_service (service_id)');
        $this->addSql('ALTER TABLE artist_post_service ADD CONSTRAINT FK_40EF1A7C95A3BB01 FOREIGN KEY (artist_post_id) REFERENCES artist_post (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE artist_post_service ADD CONSTRAINT FK_40EF1A7CED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE artist_post_service DROP CONSTRAINT FK_40EF1A7C95A3BB01');
        $this->addSql('ALTER TABLE artist_post_service DROP CONSTRAINT FK_40EF1A7CED5CA9E6');
        $this->addSql('DROP TABLE artist_post_service');
    }
}



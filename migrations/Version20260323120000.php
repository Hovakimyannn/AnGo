<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'List/grid thumbnails for Did You Know and artist posts (image_thumbnail_url).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE did_you_know_post ADD image_thumbnail_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE artist_post ADD image_thumbnail_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE did_you_know_post DROP image_thumbnail_url');
        $this->addSql('ALTER TABLE artist_post DROP image_thumbnail_url');
    }
}

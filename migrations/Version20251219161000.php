<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251219161000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cover image for artist profile page header.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE artist_profile ADD cover_image_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE artist_profile DROP cover_image_url');
    }
}



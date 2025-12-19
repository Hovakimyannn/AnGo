<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251219125500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop legacy artist_profile.specialization column; category relation is used instead.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE artist_profile DROP specialization');
    }

    public function down(Schema $schema): void
    {
        // Re-add legacy column (nullable, because previous migration made it nullable)
        $this->addSql('ALTER TABLE artist_profile ADD specialization VARCHAR(100) DEFAULT NULL');
    }
}



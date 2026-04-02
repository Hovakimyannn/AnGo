<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260303130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add SEO fields to artist_post.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE artist_post ADD seo_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE artist_post ADD meta_description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE artist_post ADD canonical_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE artist_post ADD robots_directive VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE artist_post ADD og_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE artist_post ADD og_description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE artist_post ADD og_image_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE artist_post ADD og_image_alt VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE artist_post DROP seo_title');
        $this->addSql('ALTER TABLE artist_post DROP meta_description');
        $this->addSql('ALTER TABLE artist_post DROP canonical_url');
        $this->addSql('ALTER TABLE artist_post DROP robots_directive');
        $this->addSql('ALTER TABLE artist_post DROP og_title');
        $this->addSql('ALTER TABLE artist_post DROP og_description');
        $this->addSql('ALTER TABLE artist_post DROP og_image_url');
        $this->addSql('ALTER TABLE artist_post DROP og_image_alt');
    }
}


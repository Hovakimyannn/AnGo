<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251215090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add artist blog posts, comments and ratings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE artist_post (id SERIAL NOT NULL, artist_id INT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, content TEXT NOT NULL, image_url VARCHAR(255) DEFAULT NULL, is_published BOOLEAN DEFAULT FALSE NOT NULL, published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_58A76F3B4F4E2A1E ON artist_post (artist_id)');
        $this->addSql('CREATE INDEX idx_artist_post_published ON artist_post (is_published, published_at)');
        $this->addSql('ALTER TABLE artist_post ADD CONSTRAINT FK_58A76F3B4F4E2A1E FOREIGN KEY (artist_id) REFERENCES artist_profile (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE artist_post_comment (id SERIAL NOT NULL, post_id INT NOT NULL, user_id INT NOT NULL, body TEXT NOT NULL, is_approved BOOLEAN DEFAULT TRUE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5C2B88F54B89032C ON artist_post_comment (post_id)');
        $this->addSql('CREATE INDEX IDX_5C2B88F5A76ED395 ON artist_post_comment (user_id)');
        $this->addSql('CREATE INDEX idx_post_comment_approved ON artist_post_comment (is_approved, created_at)');
        $this->addSql('ALTER TABLE artist_post_comment ADD CONSTRAINT FK_5C2B88F54B89032C FOREIGN KEY (post_id) REFERENCES artist_post (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE artist_post_comment ADD CONSTRAINT FK_5C2B88F5A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE artist_post_rating (id SERIAL NOT NULL, post_id INT NOT NULL, user_id INT NOT NULL, value SMALLINT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A4B44C9B4B89032C ON artist_post_rating (post_id)');
        $this->addSql('CREATE INDEX IDX_A4B44C9BA76ED395 ON artist_post_rating (user_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_post_user ON artist_post_rating (post_id, user_id)');
        $this->addSql('ALTER TABLE artist_post_rating ADD CONSTRAINT FK_A4B44C9B4B89032C FOREIGN KEY (post_id) REFERENCES artist_post (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE artist_post_rating ADD CONSTRAINT FK_A4B44C9BA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE artist_post_rating ADD CONSTRAINT chk_post_rating_value CHECK (value >= 1 AND value <= 5)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE artist_post_comment DROP CONSTRAINT FK_5C2B88F54B89032C');
        $this->addSql('ALTER TABLE artist_post_comment DROP CONSTRAINT FK_5C2B88F5A76ED395');
        $this->addSql('ALTER TABLE artist_post_rating DROP CONSTRAINT FK_A4B44C9B4B89032C');
        $this->addSql('ALTER TABLE artist_post_rating DROP CONSTRAINT FK_A4B44C9BA76ED395');
        $this->addSql('ALTER TABLE artist_post DROP CONSTRAINT FK_58A76F3B4F4E2A1E');
        $this->addSql('DROP TABLE artist_post_comment');
        $this->addSql('DROP TABLE artist_post_rating');
        $this->addSql('DROP TABLE artist_post');
    }
}



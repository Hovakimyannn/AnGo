<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251215120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store artist aggregate rating on user and keep it updated via SQL trigger';
    }

    public function up(Schema $schema): void
    {
        // Add cached aggregate fields
        $this->addSql('ALTER TABLE "user" ADD COLUMN artist_rating_avg DOUBLE PRECISION NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE "user" ADD COLUMN artist_rating_count INT NOT NULL DEFAULT 0');

        // Recalc helper
        $this->addSql(<<<'SQL'
CREATE OR REPLACE FUNCTION refresh_artist_user_rating(p_artist_id INT) RETURNS void AS $$
DECLARE
    v_user_id INT;
    v_avg DOUBLE PRECISION;
    v_count INT;
BEGIN
    SELECT user_id INTO v_user_id FROM artist_profile WHERE id = p_artist_id;
    IF v_user_id IS NULL THEN
        RETURN;
    END IF;

    SELECT
        COALESCE(ROUND(AVG(r.value)::numeric, 1)::double precision, 0),
        COALESCE(COUNT(r.id), 0)::int
    INTO v_avg, v_count
    FROM artist_post_rating r
    JOIN artist_post p ON p.id = r.post_id
    WHERE p.artist_id = p_artist_id;

    UPDATE "user"
    SET artist_rating_avg = v_avg,
        artist_rating_count = v_count
    WHERE id = v_user_id;
END;
$$ LANGUAGE plpgsql;
SQL);

        // Trigger that refreshes the owning artist user on rating insert/update/delete
        $this->addSql(<<<'SQL'
CREATE OR REPLACE FUNCTION trg_artist_post_rating_refresh() RETURNS trigger AS $$
DECLARE
    v_artist_id INT;
BEGIN
    IF TG_OP = 'DELETE' THEN
        SELECT artist_id INTO v_artist_id FROM artist_post WHERE id = OLD.post_id;
        IF v_artist_id IS NOT NULL THEN
            PERFORM refresh_artist_user_rating(v_artist_id);
        END IF;
        RETURN NULL;
    END IF;

    SELECT artist_id INTO v_artist_id FROM artist_post WHERE id = NEW.post_id;
    IF v_artist_id IS NOT NULL THEN
        PERFORM refresh_artist_user_rating(v_artist_id);
    END IF;

    IF TG_OP = 'UPDATE' AND NEW.post_id <> OLD.post_id THEN
        SELECT artist_id INTO v_artist_id FROM artist_post WHERE id = OLD.post_id;
        IF v_artist_id IS NOT NULL THEN
            PERFORM refresh_artist_user_rating(v_artist_id);
        END IF;
    END IF;

    RETURN NULL;
END;
$$ LANGUAGE plpgsql;
SQL);

        $this->addSql('DROP TRIGGER IF EXISTS artist_post_rating_refresh_trg ON artist_post_rating');
        $this->addSql('CREATE TRIGGER artist_post_rating_refresh_trg AFTER INSERT OR UPDATE OR DELETE ON artist_post_rating FOR EACH ROW EXECUTE FUNCTION trg_artist_post_rating_refresh()');

        // Initialize values for existing data
        $this->addSql('SELECT refresh_artist_user_rating(id) FROM artist_profile');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TRIGGER IF EXISTS artist_post_rating_refresh_trg ON artist_post_rating');
        $this->addSql('DROP FUNCTION IF EXISTS trg_artist_post_rating_refresh()');
        $this->addSql('DROP FUNCTION IF EXISTS refresh_artist_user_rating(INT)');

        $this->addSql('ALTER TABLE "user" DROP COLUMN artist_rating_avg');
        $this->addSql('ALTER TABLE "user" DROP COLUMN artist_rating_count');
    }
}



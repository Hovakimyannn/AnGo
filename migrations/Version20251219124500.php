<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251219124500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link artist_profile specialization to service_category via FK (category_id) and backfill from services.';
    }

    public function up(Schema $schema): void
    {
        // Allow specialization to be nullable (we will derive it from category label in code).
        $this->addSql('ALTER TABLE artist_profile ALTER COLUMN specialization DROP NOT NULL');

        // Link to service_category
        $this->addSql('ALTER TABLE artist_profile ADD category_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_ARTIST_PROFILE_CATEGORY ON artist_profile (category_id)');
        $this->addSql('ALTER TABLE artist_profile ADD CONSTRAINT FK_ARTIST_PROFILE_CATEGORY FOREIGN KEY (category_id) REFERENCES service_category (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Backfill from the artist services (pick the first category by service_category.sort_order)
        $this->addSql(<<<'SQL'
WITH ranked AS (
    SELECT
        aps.artist_profile_id,
        sc.id AS category_id,
        ROW_NUMBER() OVER (
            PARTITION BY aps.artist_profile_id
            ORDER BY sc.sort_order ASC, sc.label ASC
        ) AS rn
    FROM artist_profile_service aps
    INNER JOIN service s ON s.id = aps.service_id
    INNER JOIN service_category sc ON sc.key_name = s.category
)
UPDATE artist_profile ap
SET category_id = ranked.category_id
FROM ranked
WHERE ranked.artist_profile_id = ap.id
  AND ranked.rn = 1
  AND ap.category_id IS NULL
SQL);

        // Fallback: try to infer from legacy specialization text (best-effort)
        $this->addSql(<<<'SQL'
UPDATE artist_profile ap
SET category_id = sc.id
FROM service_category sc
WHERE ap.category_id IS NULL
  AND ap.specialization IS NOT NULL
  AND (
    (sc.key_name = 'hair' AND ap.specialization ILIKE '%վարս%')
    OR (sc.key_name = 'makeup' AND ap.specialization ILIKE '%դիմ%')
    OR (sc.key_name = 'nails' AND ap.specialization ILIKE '%մատ%')
  )
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE artist_profile DROP CONSTRAINT FK_ARTIST_PROFILE_CATEGORY');
        $this->addSql('DROP INDEX IDX_ARTIST_PROFILE_CATEGORY');
        $this->addSql('ALTER TABLE artist_profile DROP category_id');

        // Revert specialization nullability
        $this->addSql("UPDATE artist_profile SET specialization = '' WHERE specialization IS NULL");
        $this->addSql('ALTER TABLE artist_profile ALTER COLUMN specialization SET NOT NULL');
    }
}



<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260227120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Split manicure and pedicure categories.';
    }

    public function up(Schema $schema): void
    {
        // Booking/UI categories (DB-driven)
        $this->addSql(
            "UPDATE service_category
             SET label = 'Մատնահարդարում'
             WHERE key_name = 'nails'
               AND label IN ('Մատնահարդարում', 'Մատնահարդարում և Ոտնահարդարում', 'Մատնահարդարում / Ոտնահարդարում')"
        );
        $this->addSql(
            "INSERT INTO service_category (key_name, label, sort_order, is_active)
             VALUES ('pedicure', 'Ոտնահարդարում', 4, TRUE)
             ON CONFLICT (key_name) DO NOTHING"
        );

        // Homepage settings: only update legacy defaults (avoid overwriting custom admin copy)
        $this->addSql(
            "UPDATE home_page_settings
             SET service_nails_title = 'Մատնահարդարում'
             WHERE service_nails_title IN ('Մատնահարդարում և Ոտնահարդարում', 'Մատնահարդարում / Ոտնահարդարում')"
        );

        $this->addSql(
            "UPDATE home_page_settings
             SET service_nails_subtitle = 'Մանիկյուր, շելլակ, գել լաք'
             WHERE service_nails_subtitle IN ('Մանիկյուր և Պեդիկյուր', 'Մանիկյուր և Ոտնահարդարում (pedicure)')"
        );

        $this->addSql(
            "UPDATE home_page_settings
             SET services_subtitle = 'Աբովյանում (Abovyanum) AnGo-ում՝ Վարսահարդարում, Մատնահարդարում, Ոտնահարդարում (pedicure) և Դիմահարդարում․ նաև մազերի խնամք ու մանիկյուր՝ Shellac-ով։'
             WHERE services_subtitle = 'Աբովյանում (Abovyanum) AnGo-ում՝ Վարսահարդարում, Մատնահարդարում և Դիմահարդարում․ նաև մազերի խնամք ու մանիկյուր՝ Shellac-ով։'"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "UPDATE service_category
             SET label = 'Մատնահարդարում / Ոտնահարդարում'
             WHERE key_name = 'nails'
               AND label = 'Մատնահարդարում'"
        );
        $this->addSql(
            "DELETE FROM service_category WHERE key_name = 'pedicure'"
        );

        $this->addSql(
            "UPDATE home_page_settings
             SET service_nails_title = 'Մատնահարդարում / Ոտնահարդարում'
             WHERE service_nails_title = 'Մատնահարդարում'"
        );

        $this->addSql(
            "UPDATE home_page_settings
             SET service_nails_subtitle = 'Մանիկյուր և Պեդիկյուր'
             WHERE service_nails_subtitle = 'Մանիկյուր, շելլակ, գել լաք'"
        );

        $this->addSql(
            "UPDATE home_page_settings
             SET services_subtitle = 'Աբովյանում (Abovyanum) AnGo-ում՝ Վարսահարդարում, Մատնահարդարում և Դիմահարդարում․ նաև մազերի խնամք ու մանիկյուր՝ Shellac-ով։'
             WHERE services_subtitle = 'Աբովյանում (Abovyanum) AnGo-ում՝ Վարսահարդարում, Մատնահարդարում, Ոտնահարդարում (pedicure) և Դիմահարդարում․ նաև մազերի խնամք ու մանիկյուր՝ Shellac-ով։'"
        );
    }
}


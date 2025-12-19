<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251219160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add editable homepage content fields to home_page_settings.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE home_page_settings ADD hero_title_pre VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD hero_title_highlight VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD hero_subtitle TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD hero_primary_button_label VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD hero_secondary_button_label VARCHAR(255) DEFAULT NULL');

        $this->addSql('ALTER TABLE home_page_settings ADD services_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD service_hair_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD service_hair_subtitle VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD service_makeup_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD service_makeup_subtitle VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD service_nails_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD service_nails_subtitle VARCHAR(255) DEFAULT NULL');

        $this->addSql('ALTER TABLE home_page_settings ADD artists_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD artists_subtitle VARCHAR(255) DEFAULT NULL');

        $this->addSql('ALTER TABLE home_page_settings ADD about_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD about_text1 TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD about_text2 TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD why_us_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD why_us_items TEXT DEFAULT NULL');

        $this->addSql('ALTER TABLE home_page_settings ADD contact_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD contact_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD contact_phone VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD contact_hours_line1 VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD contact_hours_line2 VARCHAR(255) DEFAULT NULL');

        // Backfill existing settings row(s) with the current hardcoded defaults (only where NULL).
        $this->addSql(
            "UPDATE home_page_settings SET
                hero_title_pre = COALESCE(hero_title_pre, 'Բացահայտեք Ձեր'),
                hero_title_highlight = COALESCE(hero_title_highlight, 'Կատարելությունը'),
                hero_subtitle = COALESCE(hero_subtitle, 'Պրոֆեսիոնալ մոտեցում, բարձրակարգ սպասարկում և հարմարավետ միջավայր հենց Աբովյանի սրտում։'),
                hero_primary_button_label = COALESCE(hero_primary_button_label, 'Ամրագրել Այց'),
                hero_secondary_button_label = COALESCE(hero_secondary_button_label, 'Տեսնել Վարպետներին'),

                services_title = COALESCE(services_title, 'Մեր Ծառայությունները'),
                service_hair_title = COALESCE(service_hair_title, 'Վարսահարդարում'),
                service_hair_subtitle = COALESCE(service_hair_subtitle, 'Կտրվածքներ, ներկում և խնամք'),
                service_makeup_title = COALESCE(service_makeup_title, 'Դիմահարդարում'),
                service_makeup_subtitle = COALESCE(service_makeup_subtitle, 'Երեկոյան և ամենօրյա make-up'),
                service_nails_title = COALESCE(service_nails_title, 'Մատնահարդարում'),
                service_nails_subtitle = COALESCE(service_nails_subtitle, 'Մանիկյուր և Պեդիկյուր'),

                artists_title = COALESCE(artists_title, 'Թոփ Վարպետներ'),
                artists_subtitle = COALESCE(artists_subtitle, 'Ծանոթացեք մեր պրոֆեսիոնալ թիմի հետ'),

                about_title = COALESCE(about_title, 'Մեր մասին'),
                about_text1 = COALESCE(about_text1, 'AnGo-ը ստեղծվել է՝ մեկ նպատակով․ առաջարկել բարձրակարգ ծառայություններ, պրոֆեսիոնալ մոտեցում և հարմարավետ միջավայր՝ յուրաքանչյուր այցը դարձնելով հաճելի փորձ։'),
                about_text2 = COALESCE(about_text2, 'Մեր թիմը մշտապես հետևում է նորաձևության թրենդներին և աշխատում է որակյալ նյութերով՝ ապահովելով լավագույն արդյունքը։'),
                why_us_title = COALESCE(why_us_title, 'Ինչու՞ մենք'),
                why_us_items = COALESCE(why_us_items, E'Պրոֆեսիոնալ վարպետներ\\nԱնհատական մոտեցում\\nՈրակյալ նյութեր\\nՀարմարավետ միջավայր'),

                contact_title = COALESCE(contact_title, 'Կապ'),
                contact_address = COALESCE(contact_address, 'Ք.Աբովյան, Սարալանջի 22'),
                contact_phone = COALESCE(contact_phone, '+374 94 64 99 24'),
                contact_hours_line1 = COALESCE(contact_hours_line1, 'Երկ - Շաբ: 10:00 - 20:00'),
                contact_hours_line2 = COALESCE(contact_hours_line2, 'Կիր: 11:00 - 18:00')
            "
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE home_page_settings DROP hero_title_pre');
        $this->addSql('ALTER TABLE home_page_settings DROP hero_title_highlight');
        $this->addSql('ALTER TABLE home_page_settings DROP hero_subtitle');
        $this->addSql('ALTER TABLE home_page_settings DROP hero_primary_button_label');
        $this->addSql('ALTER TABLE home_page_settings DROP hero_secondary_button_label');

        $this->addSql('ALTER TABLE home_page_settings DROP services_title');
        $this->addSql('ALTER TABLE home_page_settings DROP service_hair_title');
        $this->addSql('ALTER TABLE home_page_settings DROP service_hair_subtitle');
        $this->addSql('ALTER TABLE home_page_settings DROP service_makeup_title');
        $this->addSql('ALTER TABLE home_page_settings DROP service_makeup_subtitle');
        $this->addSql('ALTER TABLE home_page_settings DROP service_nails_title');
        $this->addSql('ALTER TABLE home_page_settings DROP service_nails_subtitle');

        $this->addSql('ALTER TABLE home_page_settings DROP artists_title');
        $this->addSql('ALTER TABLE home_page_settings DROP artists_subtitle');

        $this->addSql('ALTER TABLE home_page_settings DROP about_title');
        $this->addSql('ALTER TABLE home_page_settings DROP about_text1');
        $this->addSql('ALTER TABLE home_page_settings DROP about_text2');
        $this->addSql('ALTER TABLE home_page_settings DROP why_us_title');
        $this->addSql('ALTER TABLE home_page_settings DROP why_us_items');

        $this->addSql('ALTER TABLE home_page_settings DROP contact_title');
        $this->addSql('ALTER TABLE home_page_settings DROP contact_address');
        $this->addSql('ALTER TABLE home_page_settings DROP contact_phone');
        $this->addSql('ALTER TABLE home_page_settings DROP contact_hours_line1');
        $this->addSql('ALTER TABLE home_page_settings DROP contact_hours_line2');
    }
}



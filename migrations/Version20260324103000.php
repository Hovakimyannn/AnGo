<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260324103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Home page service card: pedicure (ոտնահարդարում) image and copy in admin.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE home_page_settings ADD service_pedicure_image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD service_pedicure_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD service_pedicure_subtitle VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE home_page_settings DROP service_pedicure_image');
        $this->addSql('ALTER TABLE home_page_settings DROP service_pedicure_title');
        $this->addSql('ALTER TABLE home_page_settings DROP service_pedicure_subtitle');
    }
}

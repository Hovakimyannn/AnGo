<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260320105748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE home_page_settings ADD faq_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD faq_item1_question VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD faq_item1_answer TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD faq_item2_question VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD faq_item2_answer TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD faq_item3_question VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE home_page_settings ADD faq_item3_answer TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE home_page_settings DROP faq_title');
        $this->addSql('ALTER TABLE home_page_settings DROP faq_item1_question');
        $this->addSql('ALTER TABLE home_page_settings DROP faq_item1_answer');
        $this->addSql('ALTER TABLE home_page_settings DROP faq_item2_question');
        $this->addSql('ALTER TABLE home_page_settings DROP faq_item2_answer');
        $this->addSql('ALTER TABLE home_page_settings DROP faq_item3_question');
        $this->addSql('ALTER TABLE home_page_settings DROP faq_item3_answer');
    }
}

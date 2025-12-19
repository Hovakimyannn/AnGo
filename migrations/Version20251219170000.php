<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251219170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store service price at booking time on appointments (service_price_at_booking).';
    }

    public function up(Schema $schema): void
    {
        // 1) Add column (nullable first to allow backfill)
        $this->addSql('ALTER TABLE appointment ADD service_price_at_booking DOUBLE PRECISION DEFAULT NULL');

        // 2) Backfill from current service price for existing rows
        $this->addSql(
            'UPDATE appointment a SET service_price_at_booking = s.price FROM service s WHERE a.service_id = s.id AND a.service_price_at_booking IS NULL'
        );

        // 3) Enforce NOT NULL going forward
        $this->addSql('ALTER TABLE appointment ALTER COLUMN service_price_at_booking SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE appointment DROP service_price_at_booking');
    }
}



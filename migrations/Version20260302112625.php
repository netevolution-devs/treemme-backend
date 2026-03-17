<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260302112625 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE leather_provenance ADD psp_yield_coefficient DOUBLE PRECISION NOT NULL, ADD grain_yield_coefficient DOUBLE PRECISION NOT NULL, ADD crust_yield_coefficient DOUBLE PRECISION NOT NULL, DROP psp_yield_coefficent, DROP grain_yield_coefficent, DROP crust_yield_coefficent');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE leather_provenance ADD psp_yield_coefficent DOUBLE PRECISION NOT NULL, ADD grain_yield_coefficent DOUBLE PRECISION NOT NULL, ADD crust_yield_coefficent DOUBLE PRECISION NOT NULL, DROP psp_yield_coefficient, DROP grain_yield_coefficient, DROP crust_yield_coefficient');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260429120446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article ADD client_code_note LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE client_order_row ADD row_note_production LONGTEXT DEFAULT NULL, ADD row_note_administration LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article DROP client_code_note');
        $this->addSql('ALTER TABLE client_order_row DROP row_note_production, DROP row_note_administration');
    }
}

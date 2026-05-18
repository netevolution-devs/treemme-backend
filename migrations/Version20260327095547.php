<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260327095547 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE warehouse_movement ADD contact_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE warehouse_movement ADD CONSTRAINT FK_D495F751E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('CREATE INDEX IDX_D495F751E7A1254A ON warehouse_movement (contact_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE warehouse_movement DROP FOREIGN KEY FK_D495F751E7A1254A');
        $this->addSql('DROP INDEX IDX_D495F751E7A1254A ON warehouse_movement');
        $this->addSql('ALTER TABLE warehouse_movement DROP contact_id');
    }
}

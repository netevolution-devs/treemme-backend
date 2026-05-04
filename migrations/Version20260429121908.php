<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260429121908 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_order_row ADD address_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE client_order_row ADD CONSTRAINT FK_2C4B69B4F5B7AF75 FOREIGN KEY (address_id) REFERENCES contact_address (id)');
        $this->addSql('CREATE INDEX IDX_2C4B69B4F5B7AF75 ON client_order_row (address_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_order_row DROP FOREIGN KEY FK_2C4B69B4F5B7AF75');
        $this->addSql('DROP INDEX IDX_2C4B69B4F5B7AF75 ON client_order_row');
        $this->addSql('ALTER TABLE client_order_row DROP address_id');
    }
}

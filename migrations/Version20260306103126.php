<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260306103126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE shipment_condition (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, borne_by_customer TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE client_order ADD shipment_condition_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE client_order ADD CONSTRAINT FK_56440F2F7F3AFA73 FOREIGN KEY (shipment_condition_id) REFERENCES shipment_condition (id)');
        $this->addSql('CREATE INDEX IDX_56440F2F7F3AFA73 ON client_order (shipment_condition_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_order DROP FOREIGN KEY FK_56440F2F7F3AFA73');
        $this->addSql('DROP TABLE shipment_condition');
        $this->addSql('DROP INDEX IDX_56440F2F7F3AFA73 ON client_order');
        $this->addSql('ALTER TABLE client_order DROP shipment_condition_id');
    }
}

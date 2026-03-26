<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260326112343 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact ADD shipment_condition_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E6387F3AFA73 FOREIGN KEY (shipment_condition_id) REFERENCES shipment_condition (id)');
        $this->addSql('CREATE INDEX IDX_4C62E6387F3AFA73 ON contact (shipment_condition_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E6387F3AFA73');
        $this->addSql('DROP INDEX IDX_4C62E6387F3AFA73 ON contact');
        $this->addSql('ALTER TABLE contact DROP shipment_condition_id');
    }
}

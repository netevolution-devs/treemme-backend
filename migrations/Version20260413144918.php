<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260413144918 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch_data ADD shipment_condition_id INT DEFAULT NULL, ADD shipment_subcontractor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE batch_data ADD CONSTRAINT FK_3D97199C7F3AFA73 FOREIGN KEY (shipment_condition_id) REFERENCES shipment_condition (id)');
        $this->addSql('ALTER TABLE batch_data ADD CONSTRAINT FK_3D97199C9DB4C034 FOREIGN KEY (shipment_subcontractor_id) REFERENCES contact (id)');
        $this->addSql('CREATE INDEX IDX_3D97199C7F3AFA73 ON batch_data (shipment_condition_id)');
        $this->addSql('CREATE INDEX IDX_3D97199C9DB4C034 ON batch_data (shipment_subcontractor_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch_data DROP FOREIGN KEY FK_3D97199C7F3AFA73');
        $this->addSql('ALTER TABLE batch_data DROP FOREIGN KEY FK_3D97199C9DB4C034');
        $this->addSql('DROP INDEX IDX_3D97199C7F3AFA73 ON batch_data');
        $this->addSql('DROP INDEX IDX_3D97199C9DB4C034 ON batch_data');
        $this->addSql('ALTER TABLE batch_data DROP shipment_condition_id, DROP shipment_subcontractor_id');
    }
}

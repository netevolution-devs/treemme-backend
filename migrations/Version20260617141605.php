<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260617141605 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact ADD shipping_carrier_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E638992497C9 FOREIGN KEY (shipping_carrier_id) REFERENCES shipping_carrier (id)');
        $this->addSql('CREATE INDEX IDX_4C62E638992497C9 ON contact (shipping_carrier_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E638992497C9');
        $this->addSql('DROP INDEX IDX_4C62E638992497C9 ON contact');
        $this->addSql('ALTER TABLE contact DROP shipping_carrier_id');
    }
}

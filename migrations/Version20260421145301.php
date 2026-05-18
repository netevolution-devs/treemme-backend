<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260421145301 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact_address DROP FOREIGN KEY FK_97614E0075E23604');
        $this->addSql('DROP INDEX IDX_97614E0075E23604 ON contact_address');
        $this->addSql('ALTER TABLE contact_address ADD zip_code VARCHAR(255) DEFAULT NULL, DROP town_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact_address ADD town_id INT DEFAULT NULL, DROP zip_code');
        $this->addSql('ALTER TABLE contact_address ADD CONSTRAINT FK_97614E0075E23604 FOREIGN KEY (town_id) REFERENCES town (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_97614E0075E23604 ON contact_address (town_id)');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260615123443 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact_address ADD different_destination_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contact_address ADD CONSTRAINT FK_97614E0093892BDD FOREIGN KEY (different_destination_id) REFERENCES contact_address (id)');
        $this->addSql('CREATE INDEX IDX_97614E0093892BDD ON contact_address (different_destination_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact_address DROP FOREIGN KEY FK_97614E0093892BDD');
        $this->addSql('DROP INDEX IDX_97614E0093892BDD ON contact_address');
        $this->addSql('ALTER TABLE contact_address DROP different_destination_id');
    }
}

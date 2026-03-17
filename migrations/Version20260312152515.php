<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260312152515 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact_subcontractor (id INT AUTO_INCREMENT NOT NULL, contact_id INT NOT NULL, subcontractor_id INT NOT NULL, INDEX IDX_3D7276CBE7A1254A (contact_id), INDEX IDX_3D7276CBFD2F7858 (subcontractor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contact_subcontractor ADD CONSTRAINT FK_3D7276CBE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE contact_subcontractor ADD CONSTRAINT FK_3D7276CBFD2F7858 FOREIGN KEY (subcontractor_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE contact ADD subcontractor TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact_subcontractor DROP FOREIGN KEY FK_3D7276CBE7A1254A');
        $this->addSql('ALTER TABLE contact_subcontractor DROP FOREIGN KEY FK_3D7276CBFD2F7858');
        $this->addSql('DROP TABLE contact_subcontractor');
        $this->addSql('ALTER TABLE contact DROP subcontractor');
    }
}

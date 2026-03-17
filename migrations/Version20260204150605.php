<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204150605 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, contact_type_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, contact_note LONGTEXT DEFAULT NULL, INDEX IDX_4C62E6385F63AD12 (contact_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contact_address (id INT AUTO_INCREMENT NOT NULL, contact_id INT NOT NULL, address_1 VARCHAR(255) DEFAULT NULL, address_2 VARCHAR(255) DEFAULT NULL, address_3 VARCHAR(255) NOT NULL, address_4 VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(10) DEFAULT NULL, weight INT DEFAULT NULL, INDEX IDX_97614E00E7A1254A (contact_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contact_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E6385F63AD12 FOREIGN KEY (contact_type_id) REFERENCES contact_type (id)');
        $this->addSql('ALTER TABLE contact_address ADD CONSTRAINT FK_97614E00E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E6385F63AD12');
        $this->addSql('ALTER TABLE contact_address DROP FOREIGN KEY FK_97614E00E7A1254A');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE contact_address');
        $this->addSql('DROP TABLE contact_type');
    }
}

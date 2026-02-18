<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260218133436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE province (id INT AUTO_INCREMENT NOT NULL, acronym VARCHAR(10) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE town (id INT AUTO_INCREMENT NOT NULL, province_id INT NOT NULL, cap VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_4CE6C7A4E946114A (province_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE town ADD CONSTRAINT FK_4CE6C7A4E946114A FOREIGN KEY (province_id) REFERENCES province (id)');
        $this->addSql('ALTER TABLE contact_address ADD town_id INT DEFAULT NULL, ADD address_2 VARCHAR(255) DEFAULT NULL, ADD address_3 VARCHAR(255) DEFAULT NULL, DROP city, DROP province, CHANGE postal_code address_4 VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE contact_address ADD CONSTRAINT FK_97614E0075E23604 FOREIGN KEY (town_id) REFERENCES town (id)');
        $this->addSql('CREATE INDEX IDX_97614E0075E23604 ON contact_address (town_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact_address DROP FOREIGN KEY FK_97614E0075E23604');
        $this->addSql('ALTER TABLE town DROP FOREIGN KEY FK_4CE6C7A4E946114A');
        $this->addSql('DROP TABLE province');
        $this->addSql('DROP TABLE town');
        $this->addSql('DROP INDEX IDX_97614E0075E23604 ON contact_address');
        $this->addSql('ALTER TABLE contact_address ADD city VARCHAR(255) DEFAULT NULL, ADD province VARCHAR(255) DEFAULT NULL, DROP town_id, DROP address_2, DROP address_3, CHANGE address_4 postal_code VARCHAR(10) DEFAULT NULL');
    }
}

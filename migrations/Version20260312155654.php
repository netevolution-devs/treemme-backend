<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260312155654 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ddt (id INT AUTO_INCREMENT NOT NULL, subcontractor_id INT NOT NULL, ddt_purpose_id INT NOT NULL, ddt_number VARCHAR(255) NOT NULL, ddt_date DATETIME NOT NULL, ddt_start_date DATETIME DEFAULT NULL, INDEX IDX_E666A168FD2F7858 (subcontractor_id), INDEX IDX_E666A168DAC22313 (ddt_purpose_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ddt_purpose (id INT AUTO_INCREMENT NOT NULL, ddt_purpose_id INT NOT NULL, INDEX IDX_5724EF7ADAC22313 (ddt_purpose_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ddt ADD CONSTRAINT FK_E666A168FD2F7858 FOREIGN KEY (subcontractor_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE ddt ADD CONSTRAINT FK_E666A168DAC22313 FOREIGN KEY (ddt_purpose_id) REFERENCES ddt_purpose (id)');
        $this->addSql('ALTER TABLE ddt_purpose ADD CONSTRAINT FK_5724EF7ADAC22313 FOREIGN KEY (ddt_purpose_id) REFERENCES ddt_purpose (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ddt DROP FOREIGN KEY FK_E666A168FD2F7858');
        $this->addSql('ALTER TABLE ddt DROP FOREIGN KEY FK_E666A168DAC22313');
        $this->addSql('ALTER TABLE ddt_purpose DROP FOREIGN KEY FK_5724EF7ADAC22313');
        $this->addSql('DROP TABLE ddt');
        $this->addSql('DROP TABLE ddt_purpose');
    }
}

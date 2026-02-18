<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260218110909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact_detail_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(10) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contact_detail ADD detail_type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contact_detail ADD CONSTRAINT FK_BE944812A2830C46 FOREIGN KEY (detail_type_id) REFERENCES contact_detail_type (id)');
        $this->addSql('CREATE INDEX IDX_BE944812A2830C46 ON contact_detail (detail_type_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact_detail DROP FOREIGN KEY FK_BE944812A2830C46');
        $this->addSql('DROP TABLE contact_detail_type');
        $this->addSql('DROP INDEX IDX_BE944812A2830C46 ON contact_detail');
        $this->addSql('ALTER TABLE contact_detail DROP detail_type_id');
    }
}

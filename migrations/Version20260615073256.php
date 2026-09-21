<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260615073256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact_processing (contact_id INT NOT NULL, processing_id INT NOT NULL, INDEX IDX_4FD5F2BCE7A1254A (contact_id), INDEX IDX_4FD5F2BC5BAE24E8 (processing_id), PRIMARY KEY(contact_id, processing_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contact_processing ADD CONSTRAINT FK_4FD5F2BCE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contact_processing ADD CONSTRAINT FK_4FD5F2BC5BAE24E8 FOREIGN KEY (processing_id) REFERENCES processing (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE batch ADD compensation_waste DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact_processing DROP FOREIGN KEY FK_4FD5F2BCE7A1254A');
        $this->addSql('ALTER TABLE contact_processing DROP FOREIGN KEY FK_4FD5F2BC5BAE24E8');
        $this->addSql('DROP TABLE contact_processing');
        $this->addSql('ALTER TABLE batch DROP compensation_waste');
    }
}

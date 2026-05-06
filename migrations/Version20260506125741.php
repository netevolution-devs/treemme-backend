<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506125741 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ddt_row_processing (id INT AUTO_INCREMENT NOT NULL, ddt_row_id INT NOT NULL, processing_id INT DEFAULT NULL, INDEX IDX_A82382ACF5EED2B1 (ddt_row_id), INDEX IDX_A82382AC5BAE24E8 (processing_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ddt_row_processing ADD CONSTRAINT FK_A82382ACF5EED2B1 FOREIGN KEY (ddt_row_id) REFERENCES ddt_row (id)');
        $this->addSql('ALTER TABLE ddt_row_processing ADD CONSTRAINT FK_A82382AC5BAE24E8 FOREIGN KEY (processing_id) REFERENCES processing (id)');
        $this->addSql('ALTER TABLE ddt_row DROP FOREIGN KEY FK_95BD8CF65BAE24E8');
        $this->addSql('DROP INDEX IDX_95BD8CF65BAE24E8 ON ddt_row');
        $this->addSql('ALTER TABLE ddt_row DROP processing_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ddt_row_processing DROP FOREIGN KEY FK_A82382ACF5EED2B1');
        $this->addSql('ALTER TABLE ddt_row_processing DROP FOREIGN KEY FK_A82382AC5BAE24E8');
        $this->addSql('DROP TABLE ddt_row_processing');
        $this->addSql('ALTER TABLE ddt_row ADD processing_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ddt_row ADD CONSTRAINT FK_95BD8CF65BAE24E8 FOREIGN KEY (processing_id) REFERENCES processing (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_95BD8CF65BAE24E8 ON ddt_row (processing_id)');
    }
}

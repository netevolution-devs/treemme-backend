<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260313095615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ddt_row ADD ddt_id INT NOT NULL');
        $this->addSql('ALTER TABLE ddt_row ADD CONSTRAINT FK_95BD8CF63348168B FOREIGN KEY (ddt_id) REFERENCES ddt (id)');
        $this->addSql('CREATE INDEX IDX_95BD8CF63348168B ON ddt_row (ddt_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ddt_row DROP FOREIGN KEY FK_95BD8CF63348168B');
        $this->addSql('DROP INDEX IDX_95BD8CF63348168B ON ddt_row');
        $this->addSql('ALTER TABLE ddt_row DROP ddt_id');
    }
}

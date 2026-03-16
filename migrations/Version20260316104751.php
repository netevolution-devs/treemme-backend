<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260316104751 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ddt_row ADD selection_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ddt_row ADD CONSTRAINT FK_95BD8CF6E48EFE78 FOREIGN KEY (selection_id) REFERENCES selection (id)');
        $this->addSql('CREATE INDEX IDX_95BD8CF6E48EFE78 ON ddt_row (selection_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ddt_row DROP FOREIGN KEY FK_95BD8CF6E48EFE78');
        $this->addSql('DROP INDEX IDX_95BD8CF6E48EFE78 ON ddt_row');
        $this->addSql('ALTER TABLE ddt_row DROP selection_id');
    }
}

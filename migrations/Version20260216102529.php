<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216102529 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch ADD leather_id INT NOT NULL');
        $this->addSql('ALTER TABLE batch ADD CONSTRAINT FK_F80B52D4D7801D41 FOREIGN KEY (leather_id) REFERENCES leather (id)');
        $this->addSql('CREATE INDEX IDX_F80B52D4D7801D41 ON batch (leather_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch DROP FOREIGN KEY FK_F80B52D4D7801D41');
        $this->addSql('DROP INDEX IDX_F80B52D4D7801D41 ON batch');
        $this->addSql('ALTER TABLE batch DROP leather_id');
    }
}

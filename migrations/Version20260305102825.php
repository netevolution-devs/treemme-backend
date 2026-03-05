<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305102825 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_order ADD agent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE client_order ADD CONSTRAINT FK_56440F2F3414710B FOREIGN KEY (agent_id) REFERENCES contact (id)');
        $this->addSql('CREATE INDEX IDX_56440F2F3414710B ON client_order (agent_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_order DROP FOREIGN KEY FK_56440F2F3414710B');
        $this->addSql('DROP INDEX IDX_56440F2F3414710B ON client_order');
        $this->addSql('ALTER TABLE client_order DROP agent_id');
    }
}

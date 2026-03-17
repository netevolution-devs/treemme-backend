<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260302154405 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact_agent (id INT AUTO_INCREMENT NOT NULL, contact_id INT NOT NULL, agent_id INT NOT NULL, INDEX IDX_BA18C92E7A1254A (contact_id), INDEX IDX_BA18C923414710B (agent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contact_agent ADD CONSTRAINT FK_BA18C92E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE contact_agent ADD CONSTRAINT FK_BA18C923414710B FOREIGN KEY (agent_id) REFERENCES agent (id)');
        $this->addSql('ALTER TABLE contact CHANGE tolerance_start_days tolerance_start_days INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact_agent DROP FOREIGN KEY FK_BA18C92E7A1254A');
        $this->addSql('ALTER TABLE contact_agent DROP FOREIGN KEY FK_BA18C923414710B');
        $this->addSql('DROP TABLE contact_agent');
        $this->addSql('ALTER TABLE contact CHANGE tolerance_start_days tolerance_start_days INT NOT NULL');
    }
}

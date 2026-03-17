<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260313133544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ddt_row DROP FOREIGN KEY FK_95BD8CF67294869C');
        $this->addSql('DROP INDEX IDX_95BD8CF67294869C ON ddt_row');
        $this->addSql('ALTER TABLE ddt_row DROP article_id, CHANGE batch_id batch_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ddt_row ADD article_id INT NOT NULL, CHANGE batch_id batch_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ddt_row ADD CONSTRAINT FK_95BD8CF67294869C FOREIGN KEY (article_id) REFERENCES article (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_95BD8CF67294869C ON ddt_row (article_id)');
    }
}

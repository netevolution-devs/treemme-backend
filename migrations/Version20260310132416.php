<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260310132416 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_order_row DROP FOREIGN KEY FK_2C4B69B47294869C');
        $this->addSql('DROP INDEX IDX_2C4B69B47294869C ON client_order_row');
        $this->addSql('ALTER TABLE client_order_row DROP article_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_order_row ADD article_id INT NOT NULL');
        $this->addSql('ALTER TABLE client_order_row ADD CONSTRAINT FK_2C4B69B47294869C FOREIGN KEY (article_id) REFERENCES article (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_2C4B69B47294869C ON client_order_row (article_id)');
    }
}

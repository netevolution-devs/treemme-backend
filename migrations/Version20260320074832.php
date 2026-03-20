<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260320074832 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_order_row ADD selection_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE client_order_row ADD CONSTRAINT FK_2C4B69B4E48EFE78 FOREIGN KEY (selection_id) REFERENCES selection (id)');
        $this->addSql('CREATE INDEX IDX_2C4B69B4E48EFE78 ON client_order_row (selection_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_order_row DROP FOREIGN KEY FK_2C4B69B4E48EFE78');
        $this->addSql('DROP INDEX IDX_2C4B69B4E48EFE78 ON client_order_row');
        $this->addSql('ALTER TABLE client_order_row DROP selection_id');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260310132526 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_order_row ADD product_id INT NOT NULL');
        $this->addSql('ALTER TABLE client_order_row ADD CONSTRAINT FK_2C4B69B44584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('CREATE INDEX IDX_2C4B69B44584665A ON client_order_row (product_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_order_row DROP FOREIGN KEY FK_2C4B69B44584665A');
        $this->addSql('DROP INDEX IDX_2C4B69B44584665A ON client_order_row');
        $this->addSql('ALTER TABLE client_order_row DROP product_id');
    }
}

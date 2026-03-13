<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260313084614 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ddt_row ADD currency_id INT DEFAULT NULL, ADD quantity DOUBLE PRECISION NOT NULL, ADD price DOUBLE PRECISION DEFAULT NULL, ADD total_value DOUBLE PRECISION DEFAULT NULL, ADD currency_price DOUBLE PRECISION DEFAULT NULL, ADD currency_change DOUBLE PRECISION DEFAULT NULL, ADD currency_total_value DOUBLE PRECISION DEFAULT NULL, ADD kg_weight DOUBLE PRECISION DEFAULT NULL, ADD row_note LONGTEXT DEFAULT NULL, ADD whole_piece INT DEFAULT NULL, ADD half_piece INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ddt_row ADD CONSTRAINT FK_95BD8CF638248176 FOREIGN KEY (currency_id) REFERENCES currency (id)');
        $this->addSql('CREATE INDEX IDX_95BD8CF638248176 ON ddt_row (currency_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ddt_row DROP FOREIGN KEY FK_95BD8CF638248176');
        $this->addSql('DROP INDEX IDX_95BD8CF638248176 ON ddt_row');
        $this->addSql('ALTER TABLE ddt_row DROP currency_id, DROP quantity, DROP price, DROP total_value, DROP currency_price, DROP currency_change, DROP currency_total_value, DROP kg_weight, DROP row_note, DROP whole_piece, DROP half_piece');
    }
}

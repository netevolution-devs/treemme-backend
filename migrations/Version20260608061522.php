<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260608061522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch ADD half_pieces_count INT DEFAULT NULL, CHANGE pieces pieces DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE batch_composition CHANGE father_batch_piece father_batch_piece DOUBLE PRECISION NOT NULL, CHANGE father_batch_piece_available father_batch_piece_available DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE batch_selection CHANGE pieces pieces DOUBLE PRECISION DEFAULT NULL, CHANGE stock_pieces stock_pieces DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE client_order_row CHANGE quantity quantity DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE ddt_reason ADD is_shipment_reason TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE warehouse_movement CHANGE piece piece DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch DROP half_pieces_count, CHANGE pieces pieces INT NOT NULL');
        $this->addSql('ALTER TABLE batch_composition CHANGE father_batch_piece father_batch_piece INT NOT NULL, CHANGE father_batch_piece_available father_batch_piece_available INT DEFAULT NULL');
        $this->addSql('ALTER TABLE batch_selection CHANGE pieces pieces INT DEFAULT NULL, CHANGE stock_pieces stock_pieces INT DEFAULT NULL');
        $this->addSql('ALTER TABLE client_order_row CHANGE quantity quantity INT NOT NULL');
        $this->addSql('ALTER TABLE ddt_reason DROP is_shipment_reason');
        $this->addSql('ALTER TABLE warehouse_movement CHANGE piece piece INT DEFAULT NULL');
    }
}

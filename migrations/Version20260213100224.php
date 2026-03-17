<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260213100224 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client_order_row (id INT AUTO_INCREMENT NOT NULL, client_order_id INT NOT NULL, product_id INT NOT NULL, measurement_unit_id INT NOT NULL, processed TINYINT(1) NOT NULL, cancelled TINYINT(1) NOT NULL, weight INT DEFAULT NULL, quantity INT NOT NULL, price DOUBLE PRECISION DEFAULT NULL, total_price DOUBLE PRECISION DEFAULT NULL, currency_price DOUBLE PRECISION DEFAULT NULL, currency_exchange DOUBLE PRECISION DEFAULT NULL, total_currency_price DOUBLE PRECISION DEFAULT NULL, agent_percentage_row DOUBLE PRECISION DEFAULT NULL, tolerance_quantity_percentage DOUBLE PRECISION DEFAULT NULL, shipment_schedule DOUBLE PRECISION DEFAULT NULL, production_schedule DOUBLE PRECISION DEFAULT NULL, delivey_date_request DATETIME DEFAULT NULL, delivery_date_confirmed DATETIME DEFAULT NULL, iso_row_note LONGTEXT DEFAULT NULL, production_row_note LONGTEXT DEFAULT NULL, administration_row_note LONGTEXT DEFAULT NULL, INDEX IDX_2C4B69B4A3795DFD (client_order_id), INDEX IDX_2C4B69B44584665A (product_id), INDEX IDX_2C4B69B4B6BD3460 (measurement_unit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE client_order_row ADD CONSTRAINT FK_2C4B69B4A3795DFD FOREIGN KEY (client_order_id) REFERENCES client_order (id)');
        $this->addSql('ALTER TABLE client_order_row ADD CONSTRAINT FK_2C4B69B44584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE client_order_row ADD CONSTRAINT FK_2C4B69B4B6BD3460 FOREIGN KEY (measurement_unit_id) REFERENCES measurement_unit (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_order_row DROP FOREIGN KEY FK_2C4B69B4A3795DFD');
        $this->addSql('ALTER TABLE client_order_row DROP FOREIGN KEY FK_2C4B69B44584665A');
        $this->addSql('ALTER TABLE client_order_row DROP FOREIGN KEY FK_2C4B69B4B6BD3460');
        $this->addSql('DROP TABLE client_order_row');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260413095016 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE batch_data (id INT AUTO_INCREMENT NOT NULL, sea_port_id INT DEFAULT NULL, pallet_id INT DEFAULT NULL, delivery_date DATETIME DEFAULT NULL, amount DOUBLE PRECISION NOT NULL, currency_exchange DOUBLE PRECISION DEFAULT NULL, payment_date DATETIME DEFAULT NULL, sea_port_date DATETIME DEFAULT NULL, declered_gross_weight DOUBLE PRECISION DEFAULT NULL, declered_net_weight DOUBLE PRECISION DEFAULT NULL, declered_average_weight DOUBLE PRECISION DEFAULT NULL, founded_gross_weight DOUBLE PRECISION DEFAULT NULL, founded_net_weight DOUBLE PRECISION DEFAULT NULL, founded_average_weight DOUBLE PRECISION DEFAULT NULL, container_code VARCHAR(255) DEFAULT NULL, shipping_cost DOUBLE PRECISION DEFAULT NULL, pallet_number INT DEFAULT NULL, pallet_weight DOUBLE PRECISION DEFAULT NULL, INDEX IDX_3D97199C1712CB45 (sea_port_id), INDEX IDX_3D97199C15444D3A (pallet_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pallet (id INT AUTO_INCREMENT NOT NULL, measurement_unit_id INT NOT NULL, name VARCHAR(255) NOT NULL, weight DOUBLE PRECISION NOT NULL, INDEX IDX_616DA2A7B6BD3460 (measurement_unit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE batch_data ADD CONSTRAINT FK_3D97199C1712CB45 FOREIGN KEY (sea_port_id) REFERENCES sea_port (id)');
        $this->addSql('ALTER TABLE batch_data ADD CONSTRAINT FK_3D97199C15444D3A FOREIGN KEY (pallet_id) REFERENCES pallet (id)');
        $this->addSql('ALTER TABLE pallet ADD CONSTRAINT FK_616DA2A7B6BD3460 FOREIGN KEY (measurement_unit_id) REFERENCES measurement_unit (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch_data DROP FOREIGN KEY FK_3D97199C1712CB45');
        $this->addSql('ALTER TABLE batch_data DROP FOREIGN KEY FK_3D97199C15444D3A');
        $this->addSql('ALTER TABLE pallet DROP FOREIGN KEY FK_616DA2A7B6BD3460');
        $this->addSql('DROP TABLE batch_data');
        $this->addSql('DROP TABLE pallet');
    }
}

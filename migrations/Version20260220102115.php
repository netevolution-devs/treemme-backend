<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260220102115 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE warehouse_movement (id INT AUTO_INCREMENT NOT NULL, batch_id INT NOT NULL, reason_id INT NOT NULL, father_movement_id INT DEFAULT NULL, date DATETIME NOT NULL, piece INT DEFAULT NULL, price DOUBLE PRECISION DEFAULT NULL, quantity DOUBLE PRECISION NOT NULL, total_value DOUBLE PRECISION DEFAULT NULL, ddt_number VARCHAR(255) DEFAULT NULL, ddt_date DATETIME DEFAULT NULL, movement_note LONGTEXT DEFAULT NULL, INDEX IDX_D495F751F39EBE7A (batch_id), INDEX IDX_D495F75159BB1592 (reason_id), INDEX IDX_D495F751292F7D92 (father_movement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE warehouse_movement_reason (id INT AUTO_INCREMENT NOT NULL, reason_type_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_FF6E22837D3AC4BB (reason_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE warehouse_movement_reason_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, movement_type VARCHAR(5) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE warehouse_movement ADD CONSTRAINT FK_D495F751F39EBE7A FOREIGN KEY (batch_id) REFERENCES batch (id)');
        $this->addSql('ALTER TABLE warehouse_movement ADD CONSTRAINT FK_D495F75159BB1592 FOREIGN KEY (reason_id) REFERENCES warehouse_movement_reason (id)');
        $this->addSql('ALTER TABLE warehouse_movement ADD CONSTRAINT FK_D495F751292F7D92 FOREIGN KEY (father_movement_id) REFERENCES warehouse_movement (id)');
        $this->addSql('ALTER TABLE warehouse_movement_reason ADD CONSTRAINT FK_FF6E22837D3AC4BB FOREIGN KEY (reason_type_id) REFERENCES warehouse_movement_reason_type (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE warehouse_movement DROP FOREIGN KEY FK_D495F751F39EBE7A');
        $this->addSql('ALTER TABLE warehouse_movement DROP FOREIGN KEY FK_D495F75159BB1592');
        $this->addSql('ALTER TABLE warehouse_movement DROP FOREIGN KEY FK_D495F751292F7D92');
        $this->addSql('ALTER TABLE warehouse_movement_reason DROP FOREIGN KEY FK_FF6E22837D3AC4BB');
        $this->addSql('DROP TABLE warehouse_movement');
        $this->addSql('DROP TABLE warehouse_movement_reason');
        $this->addSql('DROP TABLE warehouse_movement_reason_type');
    }
}

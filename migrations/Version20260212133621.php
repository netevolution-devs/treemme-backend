<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260212133621 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, contact_id INT DEFAULT NULL, address_id INT DEFAULT NULL, check_user_id INT DEFAULT NULL, payment_id INT DEFAULT NULL, suspended TINYINT(1) NOT NULL, client_code VARCHAR(255) NOT NULL, tolerance_quantity DOUBLE PRECISION DEFAULT NULL, client_note LONGTEXT DEFAULT NULL, client_shipment_note LONGTEXT DEFAULT NULL, tolerance_start_days INT NOT NULL, specific_order_reference TINYINT(1) DEFAULT NULL, checked TINYINT(1) DEFAULT NULL, check_date DATETIME DEFAULT NULL, INDEX IDX_C7440455E7A1254A (contact_id), INDEX IDX_C7440455F5B7AF75 (address_id), INDEX IDX_C7440455E1729D15 (check_user_id), INDEX IDX_C74404554C3A3BB (payment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455F5B7AF75 FOREIGN KEY (address_id) REFERENCES contact_address (id)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455E1729D15 FOREIGN KEY (check_user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C74404554C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455E7A1254A');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455F5B7AF75');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455E1729D15');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C74404554C3A3BB');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE payment');
    }
}

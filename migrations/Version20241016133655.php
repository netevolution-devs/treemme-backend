<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241016133655 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, trip_type VARCHAR(255) NOT NULL, destination VARCHAR(255) NOT NULL, trip_date DATE NOT NULL, user_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(255) DEFAULT NULL, contractor_name VARCHAR(255) DEFAULT NULL, contractor_address VARCHAR(255) NOT NULL, contractor_fiscal_code VARCHAR(255) NOT NULL, adults_number INT DEFAULT NULL, children_number INT DEFAULT NULL, participants_names VARCHAR(255) DEFAULT NULL, lunch VARCHAR(255) DEFAULT NULL, pref_bus_seat VARCHAR(255) DEFAULT NULL, start_point VARCHAR(255) DEFAULT NULL, note VARCHAR(255) DEFAULT NULL, payment_type VARCHAR(255) DEFAULT NULL, case_number VARCHAR(50) DEFAULT NULL, case_note LONGTEXT DEFAULT NULL, value NUMERIC(10, 2) DEFAULT NULL, email_sent TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, ok TINYINT(1) DEFAULT NULL, payment VARCHAR(20) DEFAULT NULL, payment_date DATETIME DEFAULT NULL, ip VARCHAR(15) DEFAULT NULL, execute_error TINYINT(1) DEFAULT NULL, vis TINYINT(1) NOT NULL, shop_id VARCHAR(255) NOT NULL, uni_link VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE payment');
    }
}

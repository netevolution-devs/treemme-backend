<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260212141931 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client_order (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, payment_id INT DEFAULT NULL, check_user_id INT DEFAULT NULL, processed TINYINT(1) NOT NULL, cancelled TINYINT(1) NOT NULL, checked TINYINT(1) NOT NULL, order_number VARCHAR(255) DEFAULT NULL, order_date DATETIME DEFAULT NULL, percentage_agent DOUBLE PRECISION DEFAULT NULL, client_order_number VARCHAR(255) NOT NULL, client_order_date DATETIME DEFAULT NULL, agent_order_number VARCHAR(255) DEFAULT NULL, agent_order_date DATETIME DEFAULT NULL, percentage_tolerance_quantity DOUBLE PRECISION DEFAULT NULL, order_note LONGTEXT DEFAULT NULL, order_note_iso LONGTEXT DEFAULT NULL, order_note_production LONGTEXT DEFAULT NULL, order_note_administration LONGTEXT DEFAULT NULL, check_date DATETIME DEFAULT NULL, printed TINYINT(1) NOT NULL, print_date DATETIME DEFAULT NULL, INDEX IDX_56440F2F19EB6921 (client_id), INDEX IDX_56440F2F4C3A3BB (payment_id), INDEX IDX_56440F2FE1729D15 (check_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE client_order ADD CONSTRAINT FK_56440F2F19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE client_order ADD CONSTRAINT FK_56440F2F4C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id)');
        $this->addSql('ALTER TABLE client_order ADD CONSTRAINT FK_56440F2FE1729D15 FOREIGN KEY (check_user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_order DROP FOREIGN KEY FK_56440F2F19EB6921');
        $this->addSql('ALTER TABLE client_order DROP FOREIGN KEY FK_56440F2F4C3A3BB');
        $this->addSql('ALTER TABLE client_order DROP FOREIGN KEY FK_56440F2FE1729D15');
        $this->addSql('DROP TABLE client_order');
    }
}

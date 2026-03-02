<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260302133936 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE agent (id INT AUTO_INCREMENT NOT NULL, address_id INT DEFAULT NULL, payment_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, agent_percentage DOUBLE PRECISION DEFAULT NULL, note LONGTEXT DEFAULT NULL, INDEX IDX_268B9C9DF5B7AF75 (address_id), INDEX IDX_268B9C9D4C3A3BB (payment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE agent ADD CONSTRAINT FK_268B9C9DF5B7AF75 FOREIGN KEY (address_id) REFERENCES contact_address (id)');
        $this->addSql('ALTER TABLE agent ADD CONSTRAINT FK_268B9C9D4C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id)');
        $this->addSql('ALTER TABLE contact ADD check_user_id INT DEFAULT NULL, ADD payment_id INT DEFAULT NULL, ADD client TINYINT(1) NOT NULL, ADD tolerance_quantity DOUBLE PRECISION DEFAULT NULL, ADD client_note LONGTEXT DEFAULT NULL, ADD client_shipment_note LONGTEXT DEFAULT NULL, ADD tolerance_start_days INT NOT NULL, ADD specific_order_reference TINYINT(1) DEFAULT NULL, ADD checked TINYINT(1) DEFAULT NULL, ADD check_date DATETIME DEFAULT NULL, ADD supplier TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E638E1729D15 FOREIGN KEY (check_user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E6384C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id)');
        $this->addSql('CREATE INDEX IDX_4C62E638E1729D15 ON contact (check_user_id)');
        $this->addSql('CREATE INDEX IDX_4C62E6384C3A3BB ON contact (payment_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agent DROP FOREIGN KEY FK_268B9C9DF5B7AF75');
        $this->addSql('ALTER TABLE agent DROP FOREIGN KEY FK_268B9C9D4C3A3BB');
        $this->addSql('DROP TABLE agent');
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E638E1729D15');
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E6384C3A3BB');
        $this->addSql('DROP INDEX IDX_4C62E638E1729D15 ON contact');
        $this->addSql('DROP INDEX IDX_4C62E6384C3A3BB ON contact');
        $this->addSql('ALTER TABLE contact DROP check_user_id, DROP payment_id, DROP client, DROP tolerance_quantity, DROP client_note, DROP client_shipment_note, DROP tolerance_start_days, DROP specific_order_reference, DROP checked, DROP check_date, DROP supplier');
    }
}

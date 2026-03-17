<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260302133638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C74404554C3A3BB');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455E1729D15');
        $this->addSql('DROP INDEX IDX_C74404554C3A3BB ON client');
        $this->addSql('DROP INDEX IDX_C7440455E1729D15 ON client');
        $this->addSql('ALTER TABLE client DROP check_user_id, DROP payment_id, DROP tolerance_quantity, DROP client_note, DROP client_shipment_note, DROP tolerance_start_days, DROP specific_order_reference, DROP checked, DROP check_date');

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client ADD check_user_id INT DEFAULT NULL, ADD payment_id INT DEFAULT NULL, ADD tolerance_quantity DOUBLE PRECISION DEFAULT NULL, ADD client_note LONGTEXT DEFAULT NULL, ADD client_shipment_note LONGTEXT DEFAULT NULL, ADD tolerance_start_days INT NOT NULL, ADD specific_order_reference TINYINT(1) DEFAULT NULL, ADD checked TINYINT(1) DEFAULT NULL, ADD check_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C74404554C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455E1729D15 FOREIGN KEY (check_user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_C74404554C3A3BB ON client (payment_id)');
        $this->addSql('CREATE INDEX IDX_C7440455E1729D15 ON client (check_user_id)');
    }
}

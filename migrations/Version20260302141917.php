<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260302141917 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_order DROP FOREIGN KEY FK_56440F2F19EB6921');
        $this->addSql('ALTER TABLE client_order ADD CONSTRAINT FK_56440F2F19EB6921 FOREIGN KEY (client_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE leather DROP FOREIGN KEY FK_29091B5A2ADD6D8C');
        $this->addSql('ALTER TABLE leather ADD CONSTRAINT FK_29091B5A2ADD6D8C FOREIGN KEY (supplier_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD2ADD6D8C');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD2ADD6D8C FOREIGN KEY (supplier_id) REFERENCES contact (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
         $this->addSql('ALTER TABLE client_order DROP FOREIGN KEY FK_56440F2F19EB6921');
        $this->addSql('ALTER TABLE client_order ADD CONSTRAINT FK_56440F2F19EB6921 FOREIGN KEY (client_id) REFERENCES client (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE leather DROP FOREIGN KEY FK_29091B5A2ADD6D8C');
        $this->addSql('ALTER TABLE leather ADD CONSTRAINT FK_29091B5A2ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD2ADD6D8C');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD2ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}

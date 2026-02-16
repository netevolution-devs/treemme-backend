<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216080531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE leather_weight (id INT AUTO_INCREMENT NOT NULL, weight VARCHAR(255) NOT NULL, kg_weight DOUBLE PRECISION NOT NULL, sqft_leather_expected DOUBLE PRECISION DEFAULT NULL, kg_leather_expected DOUBLE PRECISION DEFAULT NULL, cost_stripped_crust_various DOUBLE PRECISION DEFAULT NULL, cost_stripped_crust_manual DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nation (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contact_address ADD nation_id INT DEFAULT NULL, ADD address_note LONGTEXT DEFAULT NULL, ADD address VARCHAR(255) DEFAULT NULL, ADD city VARCHAR(255) DEFAULT NULL, ADD province VARCHAR(255) DEFAULT NULL, DROP address_1, DROP address_2, DROP address_3, DROP address_4');
        $this->addSql('ALTER TABLE contact_address ADD CONSTRAINT FK_97614E00AE3899 FOREIGN KEY (nation_id) REFERENCES nation (id)');
        $this->addSql('CREATE INDEX IDX_97614E00AE3899 ON contact_address (nation_id)');
        $this->addSql('ALTER TABLE product ADD weight_measurement_unit_id INT DEFAULT NULL, ADD thickness_measurement_unit_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD3D41F919 FOREIGN KEY (weight_measurement_unit_id) REFERENCES measurement_unit (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD5796E249 FOREIGN KEY (thickness_measurement_unit_id) REFERENCES measurement_unit (id)');
        $this->addSql('CREATE INDEX IDX_D34A04AD3D41F919 ON product (weight_measurement_unit_id)');
        $this->addSql('CREATE INDEX IDX_D34A04AD5796E249 ON product (thickness_measurement_unit_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact_address DROP FOREIGN KEY FK_97614E00AE3899');
        $this->addSql('DROP TABLE leather_weight');
        $this->addSql('DROP TABLE nation');
        $this->addSql('DROP INDEX IDX_97614E00AE3899 ON contact_address');
        $this->addSql('ALTER TABLE contact_address ADD address_1 VARCHAR(255) DEFAULT NULL, ADD address_2 VARCHAR(255) DEFAULT NULL, ADD address_3 VARCHAR(255) NOT NULL, ADD address_4 VARCHAR(255) DEFAULT NULL, DROP nation_id, DROP address_note, DROP address, DROP city, DROP province');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD3D41F919');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD5796E249');
        $this->addSql('DROP INDEX IDX_D34A04AD3D41F919 ON product');
        $this->addSql('DROP INDEX IDX_D34A04AD5796E249 ON product');
        $this->addSql('ALTER TABLE product DROP weight_measurement_unit_id, DROP thickness_measurement_unit_id');
    }
}

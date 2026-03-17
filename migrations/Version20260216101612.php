<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216101612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE leather (id INT AUTO_INCREMENT NOT NULL, weight_id INT NOT NULL, species_id INT NOT NULL, contact_id INT DEFAULT NULL, thickness_id INT NOT NULL, supplier_id INT NOT NULL, flay_id INT NOT NULL, provenance_id INT NOT NULL, type_id INT NOT NULL, status_id INT NOT NULL, code VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, sqft_leather_min DOUBLE PRECISION DEFAULT NULL, sqft_leather_max DOUBLE PRECISION DEFAULT NULL, sqft_leather_media DOUBLE PRECISION DEFAULT NULL, sqft_leather_expected DOUBLE PRECISION NOT NULL, kg_leather_min DOUBLE PRECISION DEFAULT NULL, kg_leather_max DOUBLE PRECISION DEFAULT NULL, kg_leather_media DOUBLE PRECISION DEFAULT NULL, kg_leather_expected DOUBLE PRECISION DEFAULT NULL, container_piece INT DEFAULT NULL, statistic_update TINYINT(1) DEFAULT NULL, crust_revenue_expected DOUBLE PRECISION DEFAULT NULL, INDEX IDX_29091B5A350035DC (weight_id), INDEX IDX_29091B5AB2A1D860 (species_id), INDEX IDX_29091B5AE7A1254A (contact_id), INDEX IDX_29091B5AAE5B05B1 (thickness_id), INDEX IDX_29091B5A2ADD6D8C (supplier_id), INDEX IDX_29091B5A2159C449 (flay_id), INDEX IDX_29091B5AC24AFBDB (provenance_id), INDEX IDX_29091B5AC54C8C93 (type_id), INDEX IDX_29091B5A6BF700BD (status_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE leather_flay (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE leather_provenance (id INT AUTO_INCREMENT NOT NULL, area_id INT DEFAULT NULL, nation_id INT NOT NULL, flay_id INT NOT NULL, code VARCHAR(255) NOT NULL, trip_day INT DEFAULT NULL, psp_yield_coefficent DOUBLE PRECISION NOT NULL, grain_yield_coefficent DOUBLE PRECISION NOT NULL, crust_yield_coefficent DOUBLE PRECISION NOT NULL, sea_shipment TINYINT(1) NOT NULL, INDEX IDX_ED8B1D60BD0F409C (area_id), INDEX IDX_ED8B1D60AE3899 (nation_id), INDEX IDX_ED8B1D602159C449 (flay_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE leather_provenance_area (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE leather_species (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE leather_status (id INT AUTO_INCREMENT NOT NULL, measurement_unit_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, flower_yield_coefficient DOUBLE PRECISION DEFAULT NULL, INDEX IDX_BCAD44FBB6BD3460 (measurement_unit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE leather_thickness (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, thickness_mm DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE leather_type (id INT AUTO_INCREMENT NOT NULL, thickness_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, INDEX IDX_6FD784A2AE5B05B1 (thickness_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE leather ADD CONSTRAINT FK_29091B5A350035DC FOREIGN KEY (weight_id) REFERENCES leather_weight (id)');
        $this->addSql('ALTER TABLE leather ADD CONSTRAINT FK_29091B5AB2A1D860 FOREIGN KEY (species_id) REFERENCES leather_species (id)');
        $this->addSql('ALTER TABLE leather ADD CONSTRAINT FK_29091B5AE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE leather ADD CONSTRAINT FK_29091B5AAE5B05B1 FOREIGN KEY (thickness_id) REFERENCES leather_thickness (id)');
        $this->addSql('ALTER TABLE leather ADD CONSTRAINT FK_29091B5A2ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id)');
        $this->addSql('ALTER TABLE leather ADD CONSTRAINT FK_29091B5A2159C449 FOREIGN KEY (flay_id) REFERENCES leather_flay (id)');
        $this->addSql('ALTER TABLE leather ADD CONSTRAINT FK_29091B5AC24AFBDB FOREIGN KEY (provenance_id) REFERENCES leather_provenance (id)');
        $this->addSql('ALTER TABLE leather ADD CONSTRAINT FK_29091B5AC54C8C93 FOREIGN KEY (type_id) REFERENCES leather_type (id)');
        $this->addSql('ALTER TABLE leather ADD CONSTRAINT FK_29091B5A6BF700BD FOREIGN KEY (status_id) REFERENCES leather_status (id)');
        $this->addSql('ALTER TABLE leather_provenance ADD CONSTRAINT FK_ED8B1D60BD0F409C FOREIGN KEY (area_id) REFERENCES leather_provenance_area (id)');
        $this->addSql('ALTER TABLE leather_provenance ADD CONSTRAINT FK_ED8B1D60AE3899 FOREIGN KEY (nation_id) REFERENCES nation (id)');
        $this->addSql('ALTER TABLE leather_provenance ADD CONSTRAINT FK_ED8B1D602159C449 FOREIGN KEY (flay_id) REFERENCES leather_flay (id)');
        $this->addSql('ALTER TABLE leather_status ADD CONSTRAINT FK_BCAD44FBB6BD3460 FOREIGN KEY (measurement_unit_id) REFERENCES measurement_unit (id)');
        $this->addSql('ALTER TABLE leather_type ADD CONSTRAINT FK_6FD784A2AE5B05B1 FOREIGN KEY (thickness_id) REFERENCES leather_thickness (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE leather DROP FOREIGN KEY FK_29091B5A350035DC');
        $this->addSql('ALTER TABLE leather DROP FOREIGN KEY FK_29091B5AB2A1D860');
        $this->addSql('ALTER TABLE leather DROP FOREIGN KEY FK_29091B5AE7A1254A');
        $this->addSql('ALTER TABLE leather DROP FOREIGN KEY FK_29091B5AAE5B05B1');
        $this->addSql('ALTER TABLE leather DROP FOREIGN KEY FK_29091B5A2ADD6D8C');
        $this->addSql('ALTER TABLE leather DROP FOREIGN KEY FK_29091B5A2159C449');
        $this->addSql('ALTER TABLE leather DROP FOREIGN KEY FK_29091B5AC24AFBDB');
        $this->addSql('ALTER TABLE leather DROP FOREIGN KEY FK_29091B5AC54C8C93');
        $this->addSql('ALTER TABLE leather DROP FOREIGN KEY FK_29091B5A6BF700BD');
        $this->addSql('ALTER TABLE leather_provenance DROP FOREIGN KEY FK_ED8B1D60BD0F409C');
        $this->addSql('ALTER TABLE leather_provenance DROP FOREIGN KEY FK_ED8B1D60AE3899');
        $this->addSql('ALTER TABLE leather_provenance DROP FOREIGN KEY FK_ED8B1D602159C449');
        $this->addSql('ALTER TABLE leather_status DROP FOREIGN KEY FK_BCAD44FBB6BD3460');
        $this->addSql('ALTER TABLE leather_type DROP FOREIGN KEY FK_6FD784A2AE5B05B1');
        $this->addSql('DROP TABLE leather');
        $this->addSql('DROP TABLE leather_flay');
        $this->addSql('DROP TABLE leather_provenance');
        $this->addSql('DROP TABLE leather_provenance_area');
        $this->addSql('DROP TABLE leather_species');
        $this->addSql('DROP TABLE leather_status');
        $this->addSql('DROP TABLE leather_thickness');
        $this->addSql('DROP TABLE leather_type');
    }
}

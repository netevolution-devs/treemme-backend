<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260130150428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE batch (id INT AUTO_INCREMENT NOT NULL, batch_type_id INT DEFAULT NULL, measurement_unit_id INT DEFAULT NULL, check_user_id INT DEFAULT NULL, completed TINYINT(1) NOT NULL, checked TINYINT(1) NOT NULL, batch_code VARCHAR(50) NOT NULL, batch_date DATETIME DEFAULT NULL, pieces INT NOT NULL, quantity DOUBLE PRECISION NOT NULL, stock_items DOUBLE PRECISION NOT NULL, storage DOUBLE PRECISION NOT NULL, selection_note LONGTEXT DEFAULT NULL, batch_note LONGTEXT DEFAULT NULL, sampling TINYINT(1) NOT NULL, split_selected TINYINT(1) NOT NULL, sq_ft_avarage_expected DOUBLE PRECISION NOT NULL, sq_ft_avarage_found DOUBLE PRECISION NOT NULL, check_date DATETIME DEFAULT NULL, check_note LONGTEXT DEFAULT NULL, INDEX IDX_F80B52D4959A06C9 (batch_type_id), INDEX IDX_F80B52D4B6BD3460 (measurement_unit_id), INDEX IDX_F80B52D4E1729D15 (check_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, product_code VARCHAR(50) DEFAULT NULL, name VARCHAR(255) NOT NULL, internal_name VARCHAR(255) NOT NULL, external_name VARCHAR(255) NOT NULL, vendor_code VARCHAR(50) DEFAULT NULL, product_note LONGTEXT DEFAULT NULL, exclude_mrp TINYINT(1) NOT NULL, alarm INT DEFAULT NULL, stock DOUBLE PRECISION DEFAULT NULL, weight DOUBLE PRECISION DEFAULT NULL, thickness DOUBLE PRECISION DEFAULT NULL, use_coefficient DOUBLE PRECISION DEFAULT NULL, bill_of_material_quantity DOUBLE PRECISION DEFAULT NULL, last_cost DOUBLE PRECISION DEFAULT NULL, last_price DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE batch ADD CONSTRAINT FK_F80B52D4959A06C9 FOREIGN KEY (batch_type_id) REFERENCES batch_type (id)');
        $this->addSql('ALTER TABLE batch ADD CONSTRAINT FK_F80B52D4B6BD3460 FOREIGN KEY (measurement_unit_id) REFERENCES measurement_unit (id)');
        $this->addSql('ALTER TABLE batch ADD CONSTRAINT FK_F80B52D4E1729D15 FOREIGN KEY (check_user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch DROP FOREIGN KEY FK_F80B52D4959A06C9');
        $this->addSql('ALTER TABLE batch DROP FOREIGN KEY FK_F80B52D4B6BD3460');
        $this->addSql('ALTER TABLE batch DROP FOREIGN KEY FK_F80B52D4E1729D15');
        $this->addSql('DROP TABLE batch');
        $this->addSql('DROP TABLE product');
    }
}

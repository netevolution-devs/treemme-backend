<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260205083931 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE color (id INT AUTO_INCREMENT NOT NULL, color_type_id INT NOT NULL, color VARCHAR(255) NOT NULL, shade VARCHAR(255) DEFAULT NULL, var_color VARCHAR(255) DEFAULT NULL, color_note LONGTEXT DEFAULT NULL, client_color VARCHAR(255) NOT NULL, INDEX IDX_665648E996DA01BD (color_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE color_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE material_bill (id INT AUTO_INCREMENT NOT NULL, product_id INT DEFAULT NULL, review INT NOT NULL, bill_note LONGTEXT DEFAULT NULL, INDEX IDX_2E4974D64584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE color ADD CONSTRAINT FK_665648E996DA01BD FOREIGN KEY (color_type_id) REFERENCES color_type (id)');
        $this->addSql('ALTER TABLE material_bill ADD CONSTRAINT FK_2E4974D64584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product ADD supplier_id INT DEFAULT NULL, ADD measurement_unit_id INT NOT NULL, ADD color_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD2ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADB6BD3460 FOREIGN KEY (measurement_unit_id) REFERENCES measurement_unit (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD7ADA1FB5 FOREIGN KEY (color_id) REFERENCES color (id)');
        $this->addSql('CREATE INDEX IDX_D34A04AD2ADD6D8C ON product (supplier_id)');
        $this->addSql('CREATE INDEX IDX_D34A04ADB6BD3460 ON product (measurement_unit_id)');
        $this->addSql('CREATE INDEX IDX_D34A04AD7ADA1FB5 ON product (color_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD7ADA1FB5');
        $this->addSql('ALTER TABLE color DROP FOREIGN KEY FK_665648E996DA01BD');
        $this->addSql('ALTER TABLE material_bill DROP FOREIGN KEY FK_2E4974D64584665A');
        $this->addSql('DROP TABLE color');
        $this->addSql('DROP TABLE color_type');
        $this->addSql('DROP TABLE material_bill');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD2ADD6D8C');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADB6BD3460');
        $this->addSql('DROP INDEX IDX_D34A04AD2ADD6D8C ON product');
        $this->addSql('DROP INDEX IDX_D34A04ADB6BD3460 ON product');
        $this->addSql('DROP INDEX IDX_D34A04AD7ADA1FB5 ON product');
        $this->addSql('ALTER TABLE product DROP supplier_id, DROP measurement_unit_id, DROP color_id');
    }
}

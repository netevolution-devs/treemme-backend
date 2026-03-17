<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260306145158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE material_bill_step (id INT AUTO_INCREMENT NOT NULL, material_bill_id INT NOT NULL, processing_id INT NOT NULL, recipe_id INT DEFAULT NULL, INDEX IDX_88388C76AB16C95A (material_bill_id), INDEX IDX_88388C765BAE24E8 (processing_id), INDEX IDX_88388C7659D8A214 (recipe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE processing (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, external TINYINT(1) NOT NULL, color_recipe TINYINT(1) NOT NULL, final_check TINYINT(1) NOT NULL, processing_recipe TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recipe (id INT AUTO_INCREMENT NOT NULL, recipe_type_id INT DEFAULT NULL, product_id INT DEFAULT NULL, processing_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, review INT NOT NULL, recipe_note LONGTEXT DEFAULT NULL, INDEX IDX_DA88B13789A882D3 (recipe_type_id), INDEX IDX_DA88B1374584665A (product_id), INDEX IDX_DA88B1375BAE24E8 (processing_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recipe_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE material_bill_step ADD CONSTRAINT FK_88388C76AB16C95A FOREIGN KEY (material_bill_id) REFERENCES material_bill (id)');
        $this->addSql('ALTER TABLE material_bill_step ADD CONSTRAINT FK_88388C765BAE24E8 FOREIGN KEY (processing_id) REFERENCES processing (id)');
        $this->addSql('ALTER TABLE material_bill_step ADD CONSTRAINT FK_88388C7659D8A214 FOREIGN KEY (recipe_id) REFERENCES recipe (id)');
        $this->addSql('ALTER TABLE recipe ADD CONSTRAINT FK_DA88B13789A882D3 FOREIGN KEY (recipe_type_id) REFERENCES recipe_type (id)');
        $this->addSql('ALTER TABLE recipe ADD CONSTRAINT FK_DA88B1374584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE recipe ADD CONSTRAINT FK_DA88B1375BAE24E8 FOREIGN KEY (processing_id) REFERENCES processing (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE material_bill_step DROP FOREIGN KEY FK_88388C76AB16C95A');
        $this->addSql('ALTER TABLE material_bill_step DROP FOREIGN KEY FK_88388C765BAE24E8');
        $this->addSql('ALTER TABLE material_bill_step DROP FOREIGN KEY FK_88388C7659D8A214');
        $this->addSql('ALTER TABLE recipe DROP FOREIGN KEY FK_DA88B13789A882D3');
        $this->addSql('ALTER TABLE recipe DROP FOREIGN KEY FK_DA88B1374584665A');
        $this->addSql('ALTER TABLE recipe DROP FOREIGN KEY FK_DA88B1375BAE24E8');
        $this->addSql('DROP TABLE material_bill_step');
        $this->addSql('DROP TABLE processing');
        $this->addSql('DROP TABLE recipe');
        $this->addSql('DROP TABLE recipe_type');
    }
}

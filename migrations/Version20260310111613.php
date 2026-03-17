<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260310111613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, article_type_id INT NOT NULL, thickness_id INT NOT NULL, print_id INT DEFAULT NULL, color_type_id INT DEFAULT NULL, code VARCHAR(255) DEFAULT NULL, full_grain TINYINT(1) NOT NULL, article_variation VARCHAR(255) NOT NULL, note LONGTEXT DEFAULT NULL, shade VARCHAR(255) DEFAULT NULL, color VARCHAR(255) DEFAULT NULL, color_variation VARCHAR(255) DEFAULT NULL, color_note LONGTEXT DEFAULT NULL, client_color VARCHAR(255) DEFAULT NULL, INDEX IDX_23A0E6619EB6921 (client_id), INDEX IDX_23A0E66289EC824 (article_type_id), INDEX IDX_23A0E66AE5B05B1 (thickness_id), INDEX IDX_23A0E66C62133AC (print_id), INDEX IDX_23A0E6696DA01BD (color_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E6619EB6921 FOREIGN KEY (client_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66289EC824 FOREIGN KEY (article_type_id) REFERENCES article_type (id)');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66AE5B05B1 FOREIGN KEY (thickness_id) REFERENCES leather_thickness (id)');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66C62133AC FOREIGN KEY (print_id) REFERENCES article_print (id)');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E6696DA01BD FOREIGN KEY (color_type_id) REFERENCES color_type (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E6619EB6921');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66289EC824');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66AE5B05B1');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66C62133AC');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E6696DA01BD');
        $this->addSql('DROP TABLE article');
    }
}

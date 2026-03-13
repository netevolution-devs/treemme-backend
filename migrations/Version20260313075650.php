<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260313075650 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ddt_row (id INT AUTO_INCREMENT NOT NULL, batch_id INT DEFAULT NULL, article_id INT NOT NULL, measurement_unit_id INT DEFAULT NULL, order_note LONGTEXT DEFAULT NULL, pieces INT NOT NULL, INDEX IDX_95BD8CF6F39EBE7A (batch_id), INDEX IDX_95BD8CF67294869C (article_id), INDEX IDX_95BD8CF6B6BD3460 (measurement_unit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ddt_row ADD CONSTRAINT FK_95BD8CF6F39EBE7A FOREIGN KEY (batch_id) REFERENCES batch (id)');
        $this->addSql('ALTER TABLE ddt_row ADD CONSTRAINT FK_95BD8CF67294869C FOREIGN KEY (article_id) REFERENCES article (id)');
        $this->addSql('ALTER TABLE ddt_row ADD CONSTRAINT FK_95BD8CF6B6BD3460 FOREIGN KEY (measurement_unit_id) REFERENCES measurement_unit (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ddt_row DROP FOREIGN KEY FK_95BD8CF6F39EBE7A');
        $this->addSql('ALTER TABLE ddt_row DROP FOREIGN KEY FK_95BD8CF67294869C');
        $this->addSql('ALTER TABLE ddt_row DROP FOREIGN KEY FK_95BD8CF6B6BD3460');
        $this->addSql('DROP TABLE ddt_row');
    }
}

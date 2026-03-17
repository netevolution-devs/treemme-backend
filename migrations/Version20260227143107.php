<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260227143107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE batch_selection (id INT AUTO_INCREMENT NOT NULL, batch_id INT NOT NULL, selection_id INT NOT NULL, pieces INT DEFAULT NULL, quantity DOUBLE PRECISION DEFAULT NULL, stock_pieces INT DEFAULT NULL, stock_quantity DOUBLE PRECISION DEFAULT NULL, INDEX IDX_DB9C83E1F39EBE7A (batch_id), INDEX IDX_DB9C83E1E48EFE78 (selection_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE selection (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, weight INT DEFAULT NULL, value DOUBLE PRECISION NOT NULL, INDEX IDX_96A50CD7727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE batch_selection ADD CONSTRAINT FK_DB9C83E1F39EBE7A FOREIGN KEY (batch_id) REFERENCES batch (id)');
        $this->addSql('ALTER TABLE batch_selection ADD CONSTRAINT FK_DB9C83E1E48EFE78 FOREIGN KEY (selection_id) REFERENCES selection (id)');
        $this->addSql('ALTER TABLE selection ADD CONSTRAINT FK_96A50CD7727ACA70 FOREIGN KEY (parent_id) REFERENCES selection (id)');
        $this->addSql('ALTER TABLE batch CHANGE storage stock_quantity DOUBLE PRECISION NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch_selection DROP FOREIGN KEY FK_DB9C83E1F39EBE7A');
        $this->addSql('ALTER TABLE batch_selection DROP FOREIGN KEY FK_DB9C83E1E48EFE78');
        $this->addSql('ALTER TABLE selection DROP FOREIGN KEY FK_96A50CD7727ACA70');
        $this->addSql('DROP TABLE batch_selection');
        $this->addSql('DROP TABLE selection');
        $this->addSql('ALTER TABLE batch CHANGE stock_quantity storage DOUBLE PRECISION NOT NULL');
    }
}

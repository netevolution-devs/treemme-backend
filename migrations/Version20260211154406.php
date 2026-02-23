<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211154406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE batch_cost (id INT AUTO_INCREMENT NOT NULL, batch_id INT NOT NULL, batch_cost_type_id INT DEFAULT NULL, currency_id INT NOT NULL, date DATETIME NOT NULL, cost DOUBLE PRECISION DEFAULT NULL, currency_cost DOUBLE PRECISION DEFAULT NULL, currency_exchange DOUBLE PRECISION NOT NULL, cost_note LONGTEXT DEFAULT NULL, INDEX IDX_88427E03F39EBE7A (batch_id), INDEX IDX_88427E0340DA914F (batch_cost_type_id), INDEX IDX_88427E0338248176 (currency_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE batch_cost ADD CONSTRAINT FK_88427E03F39EBE7A FOREIGN KEY (batch_id) REFERENCES batch (id)');
        $this->addSql('ALTER TABLE batch_cost ADD CONSTRAINT FK_88427E0340DA914F FOREIGN KEY (batch_cost_type_id) REFERENCES batch_cost_type (id)');
        $this->addSql('ALTER TABLE batch_cost ADD CONSTRAINT FK_88427E0338248176 FOREIGN KEY (currency_id) REFERENCES currency (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch_cost DROP FOREIGN KEY FK_88427E03F39EBE7A');
        $this->addSql('ALTER TABLE batch_cost DROP FOREIGN KEY FK_88427E0340DA914F');
        $this->addSql('ALTER TABLE batch_cost DROP FOREIGN KEY FK_88427E0338248176');
        $this->addSql('DROP TABLE batch_cost');
    }
}

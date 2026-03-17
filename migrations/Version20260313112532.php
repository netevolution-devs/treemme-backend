<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260313112532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ddt_reason (id INT AUTO_INCREMENT NOT NULL, warehouse_movement_reason_id INT NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_4DF4334F888A6A69 (warehouse_movement_reason_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ddt_reason ADD CONSTRAINT FK_4DF4334F888A6A69 FOREIGN KEY (warehouse_movement_reason_id) REFERENCES warehouse_movement_reason (id)');
        $this->addSql('ALTER TABLE ddt ADD reason_id INT NOT NULL');
        $this->addSql('ALTER TABLE ddt ADD CONSTRAINT FK_E666A16859BB1592 FOREIGN KEY (reason_id) REFERENCES ddt_reason (id)');
        $this->addSql('CREATE INDEX IDX_E666A16859BB1592 ON ddt (reason_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ddt DROP FOREIGN KEY FK_E666A16859BB1592');
        $this->addSql('ALTER TABLE ddt_reason DROP FOREIGN KEY FK_4DF4334F888A6A69');
        $this->addSql('DROP TABLE ddt_reason');
        $this->addSql('DROP INDEX IDX_E666A16859BB1592 ON ddt');
        $this->addSql('ALTER TABLE ddt DROP reason_id');
    }
}

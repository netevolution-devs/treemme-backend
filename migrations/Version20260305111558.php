<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305111558 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE production (id INT AUTO_INCREMENT NOT NULL, batch_id INT NOT NULL, machine_id INT NOT NULL, production_note LONGTEXT DEFAULT NULL, scheduled_date DATETIME NOT NULL, INDEX IDX_D3EDB1E0F39EBE7A (batch_id), INDEX IDX_D3EDB1E0F6B75B26 (machine_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE production ADD CONSTRAINT FK_D3EDB1E0F39EBE7A FOREIGN KEY (batch_id) REFERENCES batch (id)');
        $this->addSql('ALTER TABLE production ADD CONSTRAINT FK_D3EDB1E0F6B75B26 FOREIGN KEY (machine_id) REFERENCES machine (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE production DROP FOREIGN KEY FK_D3EDB1E0F39EBE7A');
        $this->addSql('ALTER TABLE production DROP FOREIGN KEY FK_D3EDB1E0F6B75B26');
        $this->addSql('DROP TABLE production');
    }
}

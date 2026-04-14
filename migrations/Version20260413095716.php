<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260413095716 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch_data ADD batch_id INT NOT NULL');
        $this->addSql('ALTER TABLE batch_data ADD CONSTRAINT FK_3D97199CF39EBE7A FOREIGN KEY (batch_id) REFERENCES batch (id)');
        $this->addSql('CREATE INDEX IDX_3D97199CF39EBE7A ON batch_data (batch_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch_data DROP FOREIGN KEY FK_3D97199CF39EBE7A');
        $this->addSql('DROP INDEX IDX_3D97199CF39EBE7A ON batch_data');
        $this->addSql('ALTER TABLE batch_data DROP batch_id');
    }
}

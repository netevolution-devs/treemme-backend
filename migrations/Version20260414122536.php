<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260414122536 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch_data ADD currency_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE batch_data ADD CONSTRAINT FK_3D97199C38248176 FOREIGN KEY (currency_id) REFERENCES currency (id)');
        $this->addSql('CREATE INDEX IDX_3D97199C38248176 ON batch_data (currency_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch_data DROP FOREIGN KEY FK_3D97199C38248176');
        $this->addSql('DROP INDEX IDX_3D97199C38248176 ON batch_data');
        $this->addSql('ALTER TABLE batch_data DROP currency_id');
    }
}

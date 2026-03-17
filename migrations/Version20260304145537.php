<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260304145537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch_selection ADD thickness_id INT NOT NULL');
        $this->addSql('ALTER TABLE batch_selection ADD CONSTRAINT FK_DB9C83E1AE5B05B1 FOREIGN KEY (thickness_id) REFERENCES leather_thickness (id)');
        $this->addSql('CREATE INDEX IDX_DB9C83E1AE5B05B1 ON batch_selection (thickness_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch_selection DROP FOREIGN KEY FK_DB9C83E1AE5B05B1');
        $this->addSql('DROP INDEX IDX_DB9C83E1AE5B05B1 ON batch_selection');
        $this->addSql('ALTER TABLE batch_selection DROP thickness_id');
    }
}

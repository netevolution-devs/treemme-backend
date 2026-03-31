<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260330070706 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE color DROP FOREIGN KEY FK_665648E996DA01BD');
        $this->addSql('DROP INDEX IDX_665648E996DA01BD ON color');
        $this->addSql('ALTER TABLE color DROP color_type_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE color ADD color_type_id INT NOT NULL');
        $this->addSql('ALTER TABLE color ADD CONSTRAINT FK_665648E996DA01BD FOREIGN KEY (color_type_id) REFERENCES color_type (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_665648E996DA01BD ON color (color_type_id)');
    }
}

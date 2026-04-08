<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260408073607 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE internal_color (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE color ADD internal_color_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE color ADD CONSTRAINT FK_665648E9B578926D FOREIGN KEY (internal_color_id) REFERENCES internal_color (id)');
        $this->addSql('CREATE INDEX IDX_665648E9B578926D ON color (internal_color_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE color DROP FOREIGN KEY FK_665648E9B578926D');
        $this->addSql('DROP TABLE internal_color');
        $this->addSql('DROP INDEX IDX_665648E9B578926D ON color');
        $this->addSql('ALTER TABLE color DROP internal_color_id');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260304150240 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE measurement_unit_coefficient (id INT AUTO_INCREMENT NOT NULL, start_um_id INT NOT NULL, end_um_id INT NOT NULL, coefficient DOUBLE PRECISION NOT NULL, INDEX IDX_91C8B0BFD209B1AF (start_um_id), INDEX IDX_91C8B0BFD6C3EC5B (end_um_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE measurement_unit_coefficient ADD CONSTRAINT FK_91C8B0BFD209B1AF FOREIGN KEY (start_um_id) REFERENCES measurement_unit (id)');
        $this->addSql('ALTER TABLE measurement_unit_coefficient ADD CONSTRAINT FK_91C8B0BFD6C3EC5B FOREIGN KEY (end_um_id) REFERENCES measurement_unit (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE measurement_unit_coefficient DROP FOREIGN KEY FK_91C8B0BFD209B1AF');
        $this->addSql('ALTER TABLE measurement_unit_coefficient DROP FOREIGN KEY FK_91C8B0BFD6C3EC5B');
        $this->addSql('DROP TABLE measurement_unit_coefficient');
    }
}

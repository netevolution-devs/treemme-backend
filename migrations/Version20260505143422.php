<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260505143422 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch_composition ADD thickness_id INT DEFAULT NULL, ADD father_batch_piece_available INT DEFAULT NULL, ADD father_batch_quantity_available DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE batch_composition ADD CONSTRAINT FK_F86401BAAE5B05B1 FOREIGN KEY (thickness_id) REFERENCES leather_thickness (id)');
        $this->addSql('CREATE INDEX IDX_F86401BAAE5B05B1 ON batch_composition (thickness_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch_composition DROP FOREIGN KEY FK_F86401BAAE5B05B1');
        $this->addSql('DROP INDEX IDX_F86401BAAE5B05B1 ON batch_composition');
        $this->addSql('ALTER TABLE batch_composition DROP thickness_id, DROP father_batch_piece_available, DROP father_batch_quantity_available');
    }
}

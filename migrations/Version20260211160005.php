<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211160005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE batch_composition (id INT AUTO_INCREMENT NOT NULL, batch_id INT NOT NULL, father_batch_id INT NOT NULL, father_batch_piece INT NOT NULL, father_batch_quantity DOUBLE PRECISION DEFAULT NULL, composition_note LONGTEXT DEFAULT NULL, INDEX IDX_F86401BAF39EBE7A (batch_id), INDEX IDX_F86401BA1ABE7026 (father_batch_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE batch_composition ADD CONSTRAINT FK_F86401BAF39EBE7A FOREIGN KEY (batch_id) REFERENCES batch (id)');
        $this->addSql('ALTER TABLE batch_composition ADD CONSTRAINT FK_F86401BA1ABE7026 FOREIGN KEY (father_batch_id) REFERENCES batch (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch_composition DROP FOREIGN KEY FK_F86401BAF39EBE7A');
        $this->addSql('ALTER TABLE batch_composition DROP FOREIGN KEY FK_F86401BA1ABE7026');
        $this->addSql('DROP TABLE batch_composition');
    }
}

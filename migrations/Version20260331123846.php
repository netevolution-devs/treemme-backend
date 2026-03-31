<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260331123846 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch_composition ADD selection_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE batch_composition ADD CONSTRAINT FK_F86401BAE48EFE78 FOREIGN KEY (selection_id) REFERENCES batch_selection (id)');
        $this->addSql('CREATE INDEX IDX_F86401BAE48EFE78 ON batch_composition (selection_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch_composition DROP FOREIGN KEY FK_F86401BAE48EFE78');
        $this->addSql('DROP INDEX IDX_F86401BAE48EFE78 ON batch_composition');
        $this->addSql('ALTER TABLE batch_composition DROP selection_id');
    }
}

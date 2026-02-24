<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224075456 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE group_role_work_area ADD can_get TINYINT(1) DEFAULT 1 NOT NULL, ADD can_post TINYINT(1) DEFAULT 0 NOT NULL, ADD can_put TINYINT(1) DEFAULT 0 NOT NULL, ADD can_delete TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE group_role_work_area DROP can_get, DROP can_post, DROP can_put, DROP can_delete');
    }
}

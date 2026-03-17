<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224075806 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE group_role_work_area DROP FOREIGN KEY FK_E72E96FE1D829221');
        $this->addSql('DROP INDEX IDX_E72E96FE1D829221 ON group_role_work_area');
        $this->addSql('ALTER TABLE group_role_work_area CHANGE groupp_id group_id INT NOT NULL');
        $this->addSql('ALTER TABLE group_role_work_area ADD CONSTRAINT FK_E72E96FEFE54D947 FOREIGN KEY (group_id) REFERENCES `group` (id)');
        $this->addSql('CREATE INDEX IDX_E72E96FEFE54D947 ON group_role_work_area (group_id)');
        $this->addSql('ALTER TABLE group_user DROP FOREIGN KEY FK_A4C98D391D829221');
        $this->addSql('DROP INDEX IDX_A4C98D391D829221 ON group_user');
        $this->addSql('ALTER TABLE group_user CHANGE groupp_id group_id INT NOT NULL');
        $this->addSql('ALTER TABLE group_user ADD CONSTRAINT FK_A4C98D39FE54D947 FOREIGN KEY (group_id) REFERENCES `group` (id)');
        $this->addSql('CREATE INDEX IDX_A4C98D39FE54D947 ON group_user (group_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE group_role_work_area DROP FOREIGN KEY FK_E72E96FEFE54D947');
        $this->addSql('DROP INDEX IDX_E72E96FEFE54D947 ON group_role_work_area');
        $this->addSql('ALTER TABLE group_role_work_area CHANGE group_id groupp_id INT NOT NULL');
        $this->addSql('ALTER TABLE group_role_work_area ADD CONSTRAINT FK_E72E96FE1D829221 FOREIGN KEY (groupp_id) REFERENCES `group` (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_E72E96FE1D829221 ON group_role_work_area (groupp_id)');
        $this->addSql('ALTER TABLE group_user DROP FOREIGN KEY FK_A4C98D39FE54D947');
        $this->addSql('DROP INDEX IDX_A4C98D39FE54D947 ON group_user');
        $this->addSql('ALTER TABLE group_user CHANGE group_id groupp_id INT NOT NULL');
        $this->addSql('ALTER TABLE group_user ADD CONSTRAINT FK_A4C98D391D829221 FOREIGN KEY (groupp_id) REFERENCES `group` (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_A4C98D391D829221 ON group_user (groupp_id)');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260202101221 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE group_role_work_area (id INT AUTO_INCREMENT NOT NULL, groupp_id INT NOT NULL, role_id INT NOT NULL, work_area_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_E72E96FE1D829221 (groupp_id), INDEX IDX_E72E96FED60322AC (role_id), INDEX IDX_E72E96FE68C4F8BE (work_area_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE group_role_work_area ADD CONSTRAINT FK_E72E96FE1D829221 FOREIGN KEY (groupp_id) REFERENCES `group` (id)');
        $this->addSql('ALTER TABLE group_role_work_area ADD CONSTRAINT FK_E72E96FED60322AC FOREIGN KEY (role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE group_role_work_area ADD CONSTRAINT FK_E72E96FE68C4F8BE FOREIGN KEY (work_area_id) REFERENCES work_area (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE group_role_work_area DROP FOREIGN KEY FK_E72E96FE1D829221');
        $this->addSql('ALTER TABLE group_role_work_area DROP FOREIGN KEY FK_E72E96FED60322AC');
        $this->addSql('ALTER TABLE group_role_work_area DROP FOREIGN KEY FK_E72E96FE68C4F8BE');
        $this->addSql('DROP TABLE group_role_work_area');
    }
}

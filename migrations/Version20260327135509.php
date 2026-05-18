<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260327135509 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E6696DA01BD');
        $this->addSql('DROP INDEX IDX_23A0E6696DA01BD ON article');
        $this->addSql('ALTER TABLE article DROP shade, DROP color, DROP color_variation, DROP color_note, DROP client_color, CHANGE color_type_id color_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E667ADA1FB5 FOREIGN KEY (color_id) REFERENCES color (id)');
        $this->addSql('CREATE INDEX IDX_23A0E667ADA1FB5 ON article (color_id)');
        $this->addSql('ALTER TABLE color ADD client_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE color ADD CONSTRAINT FK_665648E919EB6921 FOREIGN KEY (client_id) REFERENCES contact (id)');
        $this->addSql('CREATE INDEX IDX_665648E919EB6921 ON color (client_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E667ADA1FB5');
        $this->addSql('DROP INDEX IDX_23A0E667ADA1FB5 ON article');
        $this->addSql('ALTER TABLE article ADD shade VARCHAR(255) DEFAULT NULL, ADD color VARCHAR(255) DEFAULT NULL, ADD color_variation VARCHAR(255) DEFAULT NULL, ADD color_note LONGTEXT DEFAULT NULL, ADD client_color VARCHAR(255) DEFAULT NULL, CHANGE color_id color_type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E6696DA01BD FOREIGN KEY (color_type_id) REFERENCES color_type (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_23A0E6696DA01BD ON article (color_type_id)');
        $this->addSql('ALTER TABLE color DROP FOREIGN KEY FK_665648E919EB6921');
        $this->addSql('DROP INDEX IDX_665648E919EB6921 ON color');
        $this->addSql('ALTER TABLE color DROP client_id');
    }
}

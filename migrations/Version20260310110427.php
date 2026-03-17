<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260310110427 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article_class (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE article_print (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE article_type (id INT AUTO_INCREMENT NOT NULL, leather_type_id INT NOT NULL, article_class_id INT NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_3C9CD028FBE55669 (leather_type_id), INDEX IDX_3C9CD028BFE8FF7B (article_class_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE article_type ADD CONSTRAINT FK_3C9CD028FBE55669 FOREIGN KEY (leather_type_id) REFERENCES leather_type (id)');
        $this->addSql('ALTER TABLE article_type ADD CONSTRAINT FK_3C9CD028BFE8FF7B FOREIGN KEY (article_class_id) REFERENCES article_class (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article_type DROP FOREIGN KEY FK_3C9CD028FBE55669');
        $this->addSql('ALTER TABLE article_type DROP FOREIGN KEY FK_3C9CD028BFE8FF7B');
        $this->addSql('DROP TABLE article_class');
        $this->addSql('DROP TABLE article_print');
        $this->addSql('DROP TABLE article_type');
    }
}

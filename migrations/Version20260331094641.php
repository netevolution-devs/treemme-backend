<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260331094641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article ADD client_code VARCHAR(255) DEFAULT NULL, CHANGE article_variation article_variation VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE client_order CHANGE client_order_number client_order_number VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article DROP client_code, CHANGE article_variation article_variation VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE client_order CHANGE client_order_number client_order_number VARCHAR(255) NOT NULL');
    }
}

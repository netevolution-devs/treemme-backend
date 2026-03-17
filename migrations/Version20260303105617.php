<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260303105617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch ADD sq_ft_average_expected DOUBLE PRECISION NOT NULL, ADD sq_ft_average_found DOUBLE PRECISION NOT NULL, DROP sq_ft_avarage_expected, DROP sq_ft_avarage_found');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch ADD sq_ft_avarage_expected DOUBLE PRECISION NOT NULL, ADD sq_ft_avarage_found DOUBLE PRECISION NOT NULL, DROP sq_ft_average_expected, DROP sq_ft_average_found');
    }
}

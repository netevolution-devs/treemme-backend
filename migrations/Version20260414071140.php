<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260414071140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch_data ADD declared_gross_weight DOUBLE PRECISION DEFAULT NULL, ADD declared_net_weight DOUBLE PRECISION DEFAULT NULL, ADD declared_average_weight DOUBLE PRECISION DEFAULT NULL, DROP declered_gross_weight, DROP declered_net_weight, DROP declered_average_weight');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch_data ADD declered_gross_weight DOUBLE PRECISION DEFAULT NULL, ADD declered_net_weight DOUBLE PRECISION DEFAULT NULL, ADD declered_average_weight DOUBLE PRECISION DEFAULT NULL, DROP declared_gross_weight, DROP declared_net_weight, DROP declared_average_weight');
    }
}

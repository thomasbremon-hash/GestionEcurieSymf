<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251224145103 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE deplacement ADD structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE deplacement ADD CONSTRAINT FK_1296FAC22534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('CREATE INDEX IDX_1296FAC22534008B ON deplacement (structure_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE deplacement DROP FOREIGN KEY FK_1296FAC22534008B');
        $this->addSql('DROP INDEX IDX_1296FAC22534008B ON deplacement');
        $this->addSql('ALTER TABLE deplacement DROP structure_id');
    }
}

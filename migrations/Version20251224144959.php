<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251224144959 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE deplacement ADD cheval_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE deplacement ADD CONSTRAINT FK_1296FAC2C8BE953B FOREIGN KEY (cheval_id) REFERENCES cheval (id)');
        $this->addSql('CREATE INDEX IDX_1296FAC2C8BE953B ON deplacement (cheval_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE deplacement DROP FOREIGN KEY FK_1296FAC2C8BE953B');
        $this->addSql('DROP INDEX IDX_1296FAC2C8BE953B ON deplacement');
        $this->addSql('ALTER TABLE deplacement DROP cheval_id');
    }
}

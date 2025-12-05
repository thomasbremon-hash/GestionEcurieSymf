<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205101014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE entreprise (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, rue VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, cp VARCHAR(255) NOT NULL, pays VARCHAR(255) NOT NULL, siren VARCHAR(255) NOT NULL, siret VARCHAR(255) NOT NULL, telephone VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_entreprise (user_id INT NOT NULL, entreprise_id INT NOT NULL, INDEX IDX_AA7E3C8CA76ED395 (user_id), INDEX IDX_AA7E3C8CA4AEAFEA (entreprise_id), PRIMARY KEY (user_id, entreprise_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_entreprise ADD CONSTRAINT FK_AA7E3C8CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_entreprise ADD CONSTRAINT FK_AA7E3C8CA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_entreprise DROP FOREIGN KEY FK_AA7E3C8CA76ED395');
        $this->addSql('ALTER TABLE user_entreprise DROP FOREIGN KEY FK_AA7E3C8CA4AEAFEA');
        $this->addSql('DROP TABLE entreprise');
        $this->addSql('DROP TABLE user_entreprise');
    }
}

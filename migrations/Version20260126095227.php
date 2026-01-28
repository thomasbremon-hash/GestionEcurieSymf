<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260126095227 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit_entreprise_taxes ADD CONSTRAINT FK_9496F80CA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
        $this->addSql('ALTER TABLE produit_entreprise_taxes ADD CONSTRAINT FK_9496F80CF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE produit_entreprise_taxes ADD CONSTRAINT FK_9496F80C36D06393 FOREIGN KEY (taxes_id) REFERENCES taxes (id)');
        $this->addSql('ALTER TABLE taxes CHANGE pourcentage pourcentage DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE user_entreprise ADD CONSTRAINT FK_AA7E3C8CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_entreprise ADD CONSTRAINT FK_AA7E3C8CA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit_entreprise_taxes DROP FOREIGN KEY FK_9496F80CA4AEAFEA');
        $this->addSql('ALTER TABLE produit_entreprise_taxes DROP FOREIGN KEY FK_9496F80CF347EFB');
        $this->addSql('ALTER TABLE produit_entreprise_taxes DROP FOREIGN KEY FK_9496F80C36D06393');
        $this->addSql('ALTER TABLE taxes CHANGE pourcentage pourcentage INT NOT NULL');
        $this->addSql('ALTER TABLE user_entreprise DROP FOREIGN KEY FK_AA7E3C8CA76ED395');
        $this->addSql('ALTER TABLE user_entreprise DROP FOREIGN KEY FK_AA7E3C8CA4AEAFEA');
    }
}

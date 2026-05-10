<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260509225956 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE etiquette (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom VARCHAR(50) NOT NULL, couleur VARCHAR(7) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1E0E195A6C6E55B5 ON etiquette (nom)');
        $this->addSql('CREATE TABLE projet (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, description CLOB DEFAULT NULL, date_creation DATETIME NOT NULL, date_limite DATE NOT NULL, statut VARCHAR(20) NOT NULL, image_name VARCHAR(255) DEFAULT NULL, createur_id INTEGER NOT NULL, CONSTRAINT FK_50159CA973A201E5 FOREIGN KEY (createur_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_50159CA973A201E5 ON projet (createur_id)');
        $this->addSql('CREATE TABLE tache (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, priorite VARCHAR(10) NOT NULL, statut VARCHAR(20) NOT NULL, date_creation DATETIME NOT NULL, date_echeance DATE DEFAULT NULL, piece_jointe_name VARCHAR(255) DEFAULT NULL, projet_id INTEGER NOT NULL, assigne_a_id INTEGER DEFAULT NULL, CONSTRAINT FK_93872075C18272 FOREIGN KEY (projet_id) REFERENCES projet (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_93872075BB1B0F33 FOREIGN KEY (assigne_a_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_93872075C18272 ON tache (projet_id)');
        $this->addSql('CREATE INDEX IDX_93872075BB1B0F33 ON tache (assigne_a_id)');
        $this->addSql('CREATE TABLE tache_etiquette (tache_id INTEGER NOT NULL, etiquette_id INTEGER NOT NULL, PRIMARY KEY (tache_id, etiquette_id), CONSTRAINT FK_46DD945AD2235D39 FOREIGN KEY (tache_id) REFERENCES tache (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_46DD945A7BD2EA57 FOREIGN KEY (etiquette_id) REFERENCES etiquette (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_46DD945AD2235D39 ON tache_etiquette (tache_id)');
        $this->addSql('CREATE INDEX IDX_46DD945A7BD2EA57 ON tache_etiquette (etiquette_id)');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, pseudo VARCHAR(50) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64986CC499D ON "user" (pseudo)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE etiquette');
        $this->addSql('DROP TABLE projet');
        $this->addSql('DROP TABLE tache');
        $this->addSql('DROP TABLE tache_etiquette');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE messenger_messages');
    }
}

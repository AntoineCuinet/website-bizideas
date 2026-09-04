<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260904160016 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE release_note ADD created_at DATETIME DEFAULT NULL, ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE release_note ADD CONSTRAINT FK_824A4A03B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE release_note ADD CONSTRAINT FK_824A4A03896DBBDE FOREIGN KEY (updated_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_824A4A03B03A8386 ON release_note (created_by_id)');
        $this->addSql('CREATE INDEX IDX_824A4A03896DBBDE ON release_note (updated_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE release_note DROP FOREIGN KEY FK_824A4A03B03A8386');
        $this->addSql('ALTER TABLE release_note DROP FOREIGN KEY FK_824A4A03896DBBDE');
        $this->addSql('DROP INDEX IDX_824A4A03B03A8386 ON release_note');
        $this->addSql('DROP INDEX IDX_824A4A03896DBBDE ON release_note');
        $this->addSql('ALTER TABLE release_note DROP created_at, DROP created_by_id, DROP updated_by_id');
    }
}

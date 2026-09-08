<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260908134700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE business_idea_category (business_idea_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_2E4E166365C5430 (business_idea_id), INDEX IDX_2E4E166312469DE2 (category_id), PRIMARY KEY (business_idea_id, category_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, created_by_id INT NOT NULL, INDEX IDX_64C19C1B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE business_idea_category ADD CONSTRAINT FK_2E4E166365C5430 FOREIGN KEY (business_idea_id) REFERENCES business_idea (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE business_idea_category ADD CONSTRAINT FK_2E4E166312469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE business_idea_category DROP FOREIGN KEY FK_2E4E166365C5430');
        $this->addSql('ALTER TABLE business_idea_category DROP FOREIGN KEY FK_2E4E166312469DE2');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1B03A8386');
        $this->addSql('DROP TABLE business_idea_category');
        $this->addSql('DROP TABLE category');
    }
}

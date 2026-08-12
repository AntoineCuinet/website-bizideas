<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260812102231 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE business_idea (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, revenue_model VARCHAR(20) NOT NULL, target_audience VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, creator_id INT NOT NULL, INDEX IDX_5A10C84861220EA6 (creator_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `rating` (id INT AUTO_INCREMENT NOT NULL, scores JSON NOT NULL, business_idea_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_D889262265C5430 (business_idea_id), INDEX IDX_D8892622A76ED395 (user_id), UNIQUE INDEX unique_user_idea_rating (user_id, business_idea_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, preferences JSON NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE business_idea ADD CONSTRAINT FK_5A10C84861220EA6 FOREIGN KEY (creator_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE `rating` ADD CONSTRAINT FK_D889262265C5430 FOREIGN KEY (business_idea_id) REFERENCES business_idea (id)');
        $this->addSql('ALTER TABLE `rating` ADD CONSTRAINT FK_D8892622A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE business_idea DROP FOREIGN KEY FK_5A10C84861220EA6');
        $this->addSql('ALTER TABLE `rating` DROP FOREIGN KEY FK_D889262265C5430');
        $this->addSql('ALTER TABLE `rating` DROP FOREIGN KEY FK_D8892622A76ED395');
        $this->addSql('DROP TABLE business_idea');
        $this->addSql('DROP TABLE `rating`');
        $this->addSql('DROP TABLE `user`');
    }
}

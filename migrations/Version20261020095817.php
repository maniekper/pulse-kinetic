<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261020095817 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE goal (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, base_compound_reps_min INT NOT NULL, base_compound_reps_max INT NOT NULL, base_isolation_reps_min INT NOT NULL, base_isolation_reps_max INT NOT NULL, rest_compound_seconds INT NOT NULL, rest_isolation_seconds INT NOT NULL, rest_between_machines_seconds INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE machine (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, tips LONGTEXT NOT NULL, image_start VARCHAR(255) NOT NULL, image_end VARCHAR(255) NOT NULL, category VARCHAR(255) NOT NULL, mechanic_type VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE machine_primary_muscle (machine_id INT NOT NULL, muscle_id INT NOT NULL, INDEX IDX_681CD07EF6B75B26 (machine_id), INDEX IDX_681CD07E354FDBB4 (muscle_id), PRIMARY KEY (machine_id, muscle_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE machine_secondary_muscle (machine_id INT NOT NULL, muscle_id INT NOT NULL, INDEX IDX_FC4150E4F6B75B26 (machine_id), INDEX IDX_FC4150E4354FDBB4 (muscle_id), PRIMARY KEY (machine_id, muscle_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE machine_record (id INT AUTO_INCREMENT NOT NULL, max_weight DOUBLE PRECISION NOT NULL, max_reps_at_max_weight INT NOT NULL, achieved_at DATETIME NOT NULL, user_id INT NOT NULL, machine_id INT NOT NULL, INDEX IDX_521130D4A76ED395 (user_id), INDEX IDX_521130D4F6B75B26 (machine_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE muscle (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE routine (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, user_id INT NOT NULL, INDEX IDX_4BF6D8D6A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE routine_machine (id INT AUTO_INCREMENT NOT NULL, sort_order INT NOT NULL, routine_id INT NOT NULL, machine_id INT NOT NULL, INDEX IDX_D5FB9F1CF27A94C7 (routine_id), INDEX IDX_D5FB9F1CF6B75B26 (machine_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, age INT NOT NULL, gender VARCHAR(255) NOT NULL, weight DOUBLE PRECISION NOT NULL, height DOUBLE PRECISION NOT NULL, name VARCHAR(255) NOT NULL, current_goal_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), INDEX IDX_8D93D649CC709F7B (current_goal_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE workout_session (id INT AUTO_INCREMENT NOT NULL, started_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, total_tonnage DOUBLE PRECISION DEFAULT NULL, user_id INT NOT NULL, routine_id INT DEFAULT NULL, INDEX IDX_AC82B97CA76ED395 (user_id), INDEX IDX_AC82B97CF27A94C7 (routine_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE workout_set (id INT AUTO_INCREMENT NOT NULL, set_number INT NOT NULL, weight DOUBLE PRECISION NOT NULL, reps INT NOT NULL, is_completed TINYINT NOT NULL, logged_at DATETIME NOT NULL, workout_session_id INT NOT NULL, machine_id INT NOT NULL, INDEX IDX_6FDEFB94D1BA355 (workout_session_id), INDEX IDX_6FDEFB94F6B75B26 (machine_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE machine_primary_muscle ADD CONSTRAINT FK_681CD07EF6B75B26 FOREIGN KEY (machine_id) REFERENCES machine (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE machine_primary_muscle ADD CONSTRAINT FK_681CD07E354FDBB4 FOREIGN KEY (muscle_id) REFERENCES muscle (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE machine_secondary_muscle ADD CONSTRAINT FK_FC4150E4F6B75B26 FOREIGN KEY (machine_id) REFERENCES machine (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE machine_secondary_muscle ADD CONSTRAINT FK_FC4150E4354FDBB4 FOREIGN KEY (muscle_id) REFERENCES muscle (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE machine_record ADD CONSTRAINT FK_521130D4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE machine_record ADD CONSTRAINT FK_521130D4F6B75B26 FOREIGN KEY (machine_id) REFERENCES machine (id)');
        $this->addSql('ALTER TABLE routine ADD CONSTRAINT FK_4BF6D8D6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE routine_machine ADD CONSTRAINT FK_D5FB9F1CF27A94C7 FOREIGN KEY (routine_id) REFERENCES routine (id)');
        $this->addSql('ALTER TABLE routine_machine ADD CONSTRAINT FK_D5FB9F1CF6B75B26 FOREIGN KEY (machine_id) REFERENCES machine (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649CC709F7B FOREIGN KEY (current_goal_id) REFERENCES goal (id)');
        $this->addSql('ALTER TABLE workout_session ADD CONSTRAINT FK_AC82B97CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE workout_session ADD CONSTRAINT FK_AC82B97CF27A94C7 FOREIGN KEY (routine_id) REFERENCES routine (id)');
        $this->addSql('ALTER TABLE workout_set ADD CONSTRAINT FK_6FDEFB94D1BA355 FOREIGN KEY (workout_session_id) REFERENCES workout_session (id)');
        $this->addSql('ALTER TABLE workout_set ADD CONSTRAINT FK_6FDEFB94F6B75B26 FOREIGN KEY (machine_id) REFERENCES machine (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE machine_primary_muscle DROP FOREIGN KEY FK_681CD07EF6B75B26');
        $this->addSql('ALTER TABLE machine_primary_muscle DROP FOREIGN KEY FK_681CD07E354FDBB4');
        $this->addSql('ALTER TABLE machine_secondary_muscle DROP FOREIGN KEY FK_FC4150E4F6B75B26');
        $this->addSql('ALTER TABLE machine_secondary_muscle DROP FOREIGN KEY FK_FC4150E4354FDBB4');
        $this->addSql('ALTER TABLE machine_record DROP FOREIGN KEY FK_521130D4A76ED395');
        $this->addSql('ALTER TABLE machine_record DROP FOREIGN KEY FK_521130D4F6B75B26');
        $this->addSql('ALTER TABLE routine DROP FOREIGN KEY FK_4BF6D8D6A76ED395');
        $this->addSql('ALTER TABLE routine_machine DROP FOREIGN KEY FK_D5FB9F1CF27A94C7');
        $this->addSql('ALTER TABLE routine_machine DROP FOREIGN KEY FK_D5FB9F1CF6B75B26');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649CC709F7B');
        $this->addSql('ALTER TABLE workout_session DROP FOREIGN KEY FK_AC82B97CA76ED395');
        $this->addSql('ALTER TABLE workout_session DROP FOREIGN KEY FK_AC82B97CF27A94C7');
        $this->addSql('ALTER TABLE workout_set DROP FOREIGN KEY FK_6FDEFB94D1BA355');
        $this->addSql('ALTER TABLE workout_set DROP FOREIGN KEY FK_6FDEFB94F6B75B26');
        $this->addSql('DROP TABLE goal');
        $this->addSql('DROP TABLE machine');
        $this->addSql('DROP TABLE machine_primary_muscle');
        $this->addSql('DROP TABLE machine_secondary_muscle');
        $this->addSql('DROP TABLE machine_record');
        $this->addSql('DROP TABLE muscle');
        $this->addSql('DROP TABLE routine');
        $this->addSql('DROP TABLE routine_machine');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE workout_session');
        $this->addSql('DROP TABLE workout_set');
    }
}

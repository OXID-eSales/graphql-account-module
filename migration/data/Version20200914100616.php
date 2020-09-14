<?php declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200914100616 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `oxvouchers`
          ADD COLUMN `OXUSERBASKETID` char(32)
          character set latin1 collate latin1_general_ci NOT NULL
          COMMENT 'Basket id which reserves the voucher';");
    }

    public function down(Schema $schema): void
    {
    }
}

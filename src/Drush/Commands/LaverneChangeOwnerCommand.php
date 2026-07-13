<?php

namespace Drupal\laverne\Drush\Commands;

use Drupal\Core\Database\Connection;
use Drush\Commands\AutowireTrait;
use Drush\Formatters\FormatterTrait;
use Drush\Style\DrushStyle;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: self::NAME,
    description: 'Update the owner of a shURLy URL slug.',
    aliases: ['lco'],
)]
final class LaverneChangeOwnerCommand extends Command
{

    use AutowireTrait;
    use FormatterTrait;

    public const string NAME = 'laverne:change-owner';

    public function __construct(
        private readonly Connection $db,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    public function doExecute(InputInterface $input, OutputInterface $output, string $onid, string $url): bool
    {
        $uid = $this->db->select('authmap', 'am')
            ->fields('am', ['uid'])
            ->condition('am.authname', $onid)
            ->execute()
            ->fetchField();
        if (!$uid) {
            $this->logger->error(
                "No user found with ONID '{onid}'.", [
                '%onid' => $onid,
                ]
            );
            return false;
        }
        else {
            $this->db->update('shurly')
                ->fields(['uid' => $uid])
                ->condition('source', $url)
                ->execute();
            $this->logger->info(
                "Owner of shURLy URL slug '{url}' changed to ONID '{onid}'.", [
                '%url' => $input->getArgument('url'),
                '%onid' => $input->getArgument('onid'),
                ]
            );
            return true;
        }
    }

    #[Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new DrushStyle($input, $output);
        $results = $this->doExecute($input, $output, $input->getArgument('onid'), $input->getArgument('url'));
        if ($results) {
            $io->success(sprintf("Changed owner of %s to ONID %s", $input->getArgument('url'), $input->getArgument("onid")));
            return Command::SUCCESS;
        }
        $io->error(sprintf("Failed to change owner of %s to ONID %s", $input->getArgument("url"), $input->getArgument("onid")));
        return Command::FAILURE;
    }

    #[Override]
    protected function configure(): void
    {
        $this->setHelp("Change the owner of a shURLy URL slug.")
            ->addArgument("onid", InputArgument::REQUIRED, "A user's ONID")
            ->addArgument("url", InputArgument::REQUIRED, "The shURLy URL slug to chang the owner for.")
            ->addUsage("laverne:change-owner beaverb aBc");
    }

}

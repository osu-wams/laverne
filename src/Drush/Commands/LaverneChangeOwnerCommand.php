<?php

namespace Drupal\laverne\Drush\Commands;

use Consolidation\OutputFormatters\FormatterManager;
use Drupal\Core\Database\Connection;
use Drush\Commands\AutowireTrait;
use Drush\Formatters\FormatterTrait;
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
class LaverneChangeOwnerCommand extends Command {

  use AutowireTrait;
  use FormatterTrait;

  /**
   * Drush command name.
   */
  public const string NAME = 'laverne:change-owner';

  /**
   * Constructor method to initialize the object with the required database
   * connection.
   *
   * @param Connection $db The database connection instance.
   *
   * @return void
   */
  public function __construct(
    private readonly FormatterManager $formatterManager,
    private readonly Connection $db,
    private readonly LoggerInterface $logger
  ) {
    parent::__construct();
  }

  /**
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   * @param string $onid
   * @param string $url
   *
   * @return bool
   * @throws \Exception
   */
  public function doExecute(InputInterface $input, OutputInterface $output, string $onid, string $url): bool {
    $uid = $this->db->select('authmap', 'am')
      ->fields('am', ['uid'])
      ->condition('am.authname', $onid)
      ->execute()
      ->fetchField();
    if (!$uid) {
      $this->logger->error("No user found with ONID '{onid}'.", [
        '%onid' => $onid,
      ]);
      return FALSE;
    }
    else {
      $this->db->update('shurly')
        ->fields(['uid' => $uid])
        ->condition('source', $url)
        ->execute();
      $this->logger->info("Owner of shURLy URL slug '{url}' changed to ONID '{onid}'.", [
        '%url' => $input->getArgument('url'),
        '%onid' => $input->getArgument('onid'),
      ]);
      return TRUE;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $results = $this->doExecute($input, $output, $input->getArgument('onid'), $input->getArgument('url'));
    if ($results) {
      $output->writeln("Changed owner of {$input->getArgument('url')} to ONID {$input->getArgument('onid')}");
      return Command::SUCCESS;
    }
    $output->writeln("Failed to change owner of {$input->getArgument('url')} to ONID {$input->getArgument('onid')}");
    return Command::FAILURE;
  }

  /**
   * Configures the command with help text, required arguments, and usage
   * information.
   *
   * @return void
   */
  protected function configure(): void {
    $this->setHelp("Change the owner of a shURLy URL slug.")
      ->addArgument("onid", InputArgument::REQUIRED, "A user's ONID")
      ->addArgument("url", InputArgument::REQUIRED, "The shURLy URL slug to chang the owner for.")
      ->addUsage("laverne:change-owner beaverb aBc");
  }

}

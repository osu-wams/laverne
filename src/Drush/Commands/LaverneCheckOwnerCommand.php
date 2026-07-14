<?php

declare(strict_types=1);

namespace Drupal\laverne\Drush\Commands;

use Consolidation\OutputFormatters\FormatterManager;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Database\Connection;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Formatters\FormatterTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: self::NAME,
    description: 'Check the current owner of a shURLy URL slug.',
    aliases: ['lcko'],
)]
#[CLI\FieldLabels(
    labels: [
    'url' => 'Short URL',
    'onid' => 'ONID',
    'owner' => 'Owner',
    'destination' => 'Destination',
    ]
)]
#[CLI\DefaultTableFields(fields: ['url', 'onid', 'owner', 'destination'])]
#[CLI\FilterDefaultField(field: 'owner')]
#[CLI\Formatter(returnType: RowsOfFields::class, defaultFormatter: 'table')]
final class LaverneCheckOwnerCommand extends Command
{
    use AutowireTrait;
    use FormatterTrait;

    public const string NAME = 'laverne:check-owner';

    public function __construct(
        private readonly FormatterManager $formatterManager,
        private readonly Connection $db,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $results = $this->doExecute($input, $output, (string) $input->getArgument('url'));
        $this->writeFormattedOutput($input, $output, $results);

        return Command::SUCCESS;
    }

    #[\Override]
    protected function configure()
    {
        $this->addArgument('url', InputArgument::REQUIRED, 'The URL to check the current owner of.');
    }

  /**
   * Executes a database query to retrieve specific information based on the given URL
   * and returns the result formatted as rows of fields.
   */
    protected function doExecute(InputInterface $input, OutputInterface $output, string $url): RowsOfFields
    {
        $query = $this->db->select('shurly', 's');
        $query->fields('s', ['uid', 'source', 'destination']);
        $query->fields('u', ['name']);
        $query->fields('am', ['authname']);
        $query->join('users_field_data', 'u', 'u.uid = s.uid');
        $query->join('authmap', 'am', 'am.uid = u.uid');
        $query->condition('s.source', $url);
        $owner = $query->execute()->fetchObject();

        return new RowsOfFields(
            [$owner->source => [
            'url' => $owner->source,
            'owner' => $owner->name,
            'onid' => $owner->authname,
            'destination' => $owner->destination,
            ],
            ]
        );
    }
}

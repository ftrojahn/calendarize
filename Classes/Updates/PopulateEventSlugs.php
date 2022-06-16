<?php

declare(strict_types=1);

namespace HDNET\Calendarize\Updates;

use HDNET\Calendarize\Service\IndexerService;
use Verdigado\Multisite\Querybuilder\PagesQuerybuilder;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\Model\RecordStateFactory;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;

class PopulateEventSlugs extends AbstractUpdate implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected $title = 'Introduce URL parts ("slugs") to calendarize event model';

    protected $description = 'Updates slug field of EXT:calendarize event records and runs a reindex';

    /**
     * @var string
     */
    protected $table = 'tx_calendarize_domain_model_event';

    /**
     * @var string
     */
    protected $fieldName = 'slug';

    /**
     * @var IndexerService
     */
    protected $indexerService;

    /**
     * @var int
     */
    protected $siteIds = array();

    /**
     * @var Log
     */
    protected $log = null;

    /**
    /**
     * PopulateEventSlugs constructor.
     */
    public function __construct()
    {
        $this->indexerService = GeneralUtility::makeInstance(IndexerService::class);
        $this->initializeSiteIds();
    }

    public function getIdentifier(): string
    {
        return 'calendarize_populateEventSlugs';
    }

    public function executeUpdate(): bool
    {
        $this->output->writeln(
            'Start populating event slugs (sites: ' . count($this->siteIds) . ') ...'
        );
        foreach ($this->siteIds as $siteid) {
            $this->populateSlugs($this->table, $this->fieldName, $siteid);
            $this->indexerService->reindexAll();
        }
        $this->output->writeln(
            'Stop populating event slugs (empty slugs left: ' . $this->checkEmptySlug($this->table, $this->fieldName) . ').'
        );
        return true;
    }

    /**
     * get all site ids
     *
     */
    public function initializeSiteIds(): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
        $queryBuilder = $connection->createQueryBuilder();
        $statement = $queryBuilder
            ->select('uid','title')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('is_siteroot', $queryBuilder->createNamedParameter('1')),
                    $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter('0'))
                )
            )
            ->execute();
        while ($record = $statement->fetch()) {
            $this->siteIds[] = (int)$record['uid'];
        }
    }

    /**
     * Populate the slug fields in the table using SlugHelper.
     *
     * @param string $table
     * @param string $field
     */
    public function populateSlugs(string $table, string $field, int $siteid): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $statement = $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq($field, $queryBuilder->createNamedParameter('')),
                    $queryBuilder->expr()->isNull($field)
                ),
                $queryBuilder->expr()->eq('rootpid', $queryBuilder->createNamedParameter($siteid, \PDO::PARAM_INT)),
            )
            ->execute();

        $fieldConfig = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
        // @todo: uniq using rootpid?
        $evalInfo = !empty($fieldConfig['eval']) ? GeneralUtility::trimExplode(',', $fieldConfig['eval'], true) : [];
        $hasToBeUnique = \in_array('unique', $evalInfo, true);
        $hasToBeUniqueInSite = \in_array('uniqueInSite', $evalInfo, true);
        $hasToBeUniqueInPid = \in_array('uniqueInPid', $evalInfo, true);
        /** @var SlugHelper $slugHelper */
        $slugHelper = GeneralUtility::makeInstance(SlugHelper::class, $table, $field, $fieldConfig);
        while ($record = $statement->fetch()) {
            $recordId = (int)$record['uid'];
            $pid = (int)$record['pid'];
            $slug = $slugHelper->generate($record, $pid);

            $state = RecordStateFactory::forName($table)
                ->fromArray($record, $pid, $recordId);
            if ($hasToBeUnique && !$slugHelper->isUniqueInTable($slug, $state)) {
                $slug = $slugHelper->buildSlugForUniqueInTable($slug, $state);
            }
            if ($hasToBeUniqueInSite && !$slugHelper->isUniqueInSite($slug, $state)) {
                $slug = $slugHelper->buildSlugForUniqueInSite($slug, $state);
            }
            if ($hasToBeUniqueInPid && !$slugHelper->isUniqueInPid($slug, $state)) {
                $slug = $slugHelper->buildSlugForUniqueInPid($slug, $state);
            }

            $connection->update(
                $table,
                [$field => $slug],
                ['uid' => $recordId]
            );
        }
    }

    /**
     * Check if any slug field in the table has an empty value.
     *
     * @param string $table
     * @param string $field
     *
     * @return int
     */
    protected function checkEmptySlug(string $table, string $field): int
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $numberOfEntries = $queryBuilder
            ->count('uid')
            ->from($table)
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq($field, $queryBuilder->createNamedParameter('')),
                    $queryBuilder->expr()->isNull($field)
                )
            )
            ->execute()
            ->fetchColumn();

        return $numberOfEntries;
    }

    public function updateNecessary(): bool
    {
        return $this->checkEmptySlug($this->table, $this->fieldName) > 0;
    }

    /**
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }
}

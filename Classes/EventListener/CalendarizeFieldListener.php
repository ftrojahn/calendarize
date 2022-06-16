<?php

declare(strict_types=1);

namespace HDNET\Calendarize\EventListener;

use HDNET\Calendarize\Register;
use TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent;

class CalendarizeFieldListener
{
    public function __invoke(AlterTableDefinitionStatementsEvent $event): void
    {
        $event->addSqlData($this->getCalendarizeDatabaseString());
    }

    /**
     * Get the calendarize string for the registered tables.
     *
     * @return string
     */
    protected function getCalendarizeDatabaseString()
    {
        $sql = [];
        foreach (Register::getRegister() as $configuration) {
            $fieldName = $configuration['fieldName'] ?? 'calendarize';
            $sql[] = 'CREATE TABLE ' . $configuration['tableName'] . ' (' . $fieldName . ' mediumtext);';
        }

        return implode(LF, $sql);
    }
}

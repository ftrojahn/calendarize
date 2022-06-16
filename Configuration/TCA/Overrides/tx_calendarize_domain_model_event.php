<?php

declare(strict_types=1);
defined('TYPO3') or exit();

if (!(bool)\HDNET\Calendarize\Utility\ConfigurationUtility::get('disableDefaultEvent')) {
    \HDNET\Calendarize\Register::extTables(
        \HDNET\Calendarize\Register::getDefaultCalendarizeConfiguration()
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable(
        'calendarize',
        'tx_calendarize_domain_model_event',
        'categories',
        [
            // Allow backend users to edit this record
            'exclude' => false,
        ]
    );
}

$GLOBALS['TCA']['tx_calendarize_domain_model_event']['columns']['categories']['config']['foreign_table_where'] = ''
    . ' AND sys_category.sys_language_uid IN (-1, 0) '
    // Configuration EXT:multisite begin
    . HDNET\Calendarize\TCA\Rootpid::AndWhere('sys_category');
    // Configuration EXT:multisite end;

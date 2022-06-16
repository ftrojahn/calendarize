<?php

namespace HDNET\Calendarize\TCA;

class Rootpid
{

    /**
     * Returns an andWhere statement for the TCA configuration foreign_table_where.
     * If EXT:multisite isn't installed, the method returns nothing.
     *
     * @param string $tableName Foreign table
     * @return mixed
     */
    static public function AndWhere($tableName)
    {
        if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('multisite')) {
            return;
        }

        $rootpid = \Verdigado\Multisite\Utility\TcaTablesUtility::GetRootpidLabel($tableName);
        if (!empty($rootpid)) {
            $andWhere = 'AND ' . $tableName . '.' . $rootpid . ' = ###SITEROOT### ';
            return $andWhere;
        }

        $prompt = 'FATAL ERROR: rootpid is empty. Table ' . $tableName . '. '
            . 'Error occurs @ ' . __METHOD__ . '#' . __LINE__;
        die($prompt);
    }

}
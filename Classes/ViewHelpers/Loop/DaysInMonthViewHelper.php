<?php
/**
 * Days in month view helper
 *
 * @author  Tim Lochmüller
 */

namespace HDNET\Calendarize\ViewHelpers\Loop;

/**
 * Days in month view helper
 *
 * @author Tim Lochmüller
 */
class DaysInMonthViewHelper extends AbstractLoopViewHelper
{

    /**
     * Get items
     *
     * @param \DateTime $date
     *
     * @return array
     */
    protected function getItems(\DateTime $date)
    {
        $daysInMonth = $date->format('t');
        $days = [];
        $move = (int)($date->format('j') - 1);
        $date->modify('-' . $move . ' days');

        for ($i = 0; $i < $daysInMonth; $i++) {
            $days[] = [
                'day'  => $i,
                'date' => clone $date,
            ];
            $date->modify('+1 day');
        }
        return $days;
    }
}
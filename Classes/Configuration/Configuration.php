<?php

declare(strict_types=1);

namespace Mirko\T3customroutes\Configuration;

class Configuration
{
    /**
     * @return array
     */
    public static function getProcessors(): array
    {
        return self::getClassNamesSortedByPriority(
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3customroutes']['processors']
        );
    }

    /**
     * @param array|null $items
     * @return array
     */
    protected static function getClassNamesSortedByPriority(?array $items): array
    {
        $items = $items ?: [];
        $items = array_map(
            static function ($class, $priority) {
                return [
                    'className' => $class,
                    'priority' => is_numeric($priority) ? $priority : 50,
                ];
            },
            array_keys($items),
            $items
        );

        usort(
            $items,
            static function (array $itemA, array $itemB) {
                return $itemB['priority'] <=> $itemA['priority'];
            }
        );

        return array_column($items, 'className');
    }
}

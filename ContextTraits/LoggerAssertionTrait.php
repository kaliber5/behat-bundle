<?php

namespace Kaliber5\BehatBundle\ContextTraits;

/**
 * Trait LoggerAssertionTrait
 *
 * @package Kaliber5\BehatBundle\ContextTraits
 */
trait LoggerAssertionTrait
{
    public function assertLogEntryCount($expectedCount, $priority, $channel)
    {
        $logger = $this->getContainer()->get('logger');
        $logs = array_filter(
            $logger->getLogs(),
            function ($logEntry) use ($channel, $priority) {
                return ($logEntry['channel'] === strtolower($channel) && $logEntry['priorityName'] === strtoupper($priority));
            }
        );
        assertCount((int) $expectedCount, $logs, 'Unexpected count of Logs');
    }
}

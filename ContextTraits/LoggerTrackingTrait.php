<?php

namespace Kaliber5\BehatBundle\ContextTraits;

/**
 * Trait LoggerTrackingTrait
 *
 * @package Kaliber5\BehatBundle\ContextTraits
 */
trait LoggerTrackingTrait
{
    use LoggerAssertionTrait;

    /**
     * @Then :count :priority should be sent to :channel
     */
    public function countMsgShouldBeSentTochannel($count, $priority, $channel)
    {
        $this->assertLogEntryCount((int) $count, $priority, $channel);
    }
}

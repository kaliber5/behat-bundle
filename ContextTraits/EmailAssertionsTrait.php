<?php
/**
 * Created by PhpStorm.
 * User: andreasschacht
 * Date: 13.07.16
 * Time: 11:41
 */

namespace Kaliber5\BehatBundle\ContextTraits;

/**
 * Trait to assert that emails were sent
 *
 * THIS TRAIT HAS TO BE USED ONLY IN JsonMinkContext and subclasses
 * OR YOU HAVE TO OVERRIDE getMessages and getMessageCount Methods
 *
 * Trait EmailAssertionsTrait
 *
 * @package Kaliber5\BehatBundle\ContextTraits
 */
trait EmailAssertionsTrait
{

    /**
     * asserts no email was sent
     */
    public function assertNoEmailSent()
    {
        assertEquals(0, $this->getMessageCount(), 'An Email was sent');
    }

    /**
     * Assert that an email was sent to the given recipient email address
     * The callback will be called with the found messages. Use that do add any custom assertions!
     *
     * @param string   $recipient
     * @param callable $callback
     * @param integer  $count
     *
     * @throws \Exception
     */
    public function assertEmailWasSent($recipient, callable $callback = null, $count = null)
    {
        $foundMessages = [];
        foreach ($this->getMessages() as $message) {
            /** @var \Swift_Message $message */
            // Checking the recipient email and the X-Swift-To
            // header to handle the RedirectingPlugin.
            // If the recipient is not the expected one, check
            // the next mail.

            $correctRecipient = array_key_exists(
                $recipient,
                $message->getTo()
            );
            $headers = $message->getHeaders();
            $correctXToHeader = false;
            if ($headers->has('X-Swift-To')) {
                $correctXToHeader = array_key_exists(
                    $recipient,
                    $headers->get('X-Swift-To')->getFieldBodyModel()
                );
            }

            if (!$correctRecipient && !$correctXToHeader) {
                continue;
            } else {
                $foundMessages[$message->getId()] = $message;
            }
        }
        assertNotCount(0, $foundMessages, 'No email was sent to '.$recipient);
        if ($count !== null) {
            assertCount($count, $foundMessages, 'Found '.count($foundMessages).', expected '.$count);
        }
        if (is_callable($callback)) {
            call_user_func($callback, $foundMessages);
        }
    }

    /**
     * @return \Swift_Mime_Message[] array with messages
     *
     * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
     */
    public function getMessages()
    {
        try {
            $profile = $this->getSymfonyProfile();
            /** @var MessageDataCollector $collector */
            $collector = $profile->getCollector('swiftmailer');

            return $collector->getMessages();
        } catch (\Exception $e) {
            /** @var \Swift_Plugins_MessageLogger $logger */
            $logger = $this->getContainer()->get('swiftmailer.plugin.messagelogger');

            return $logger->getMessages();
        }
    }

    /**
     * @return int
     *
     * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
     */
    public function getMessageCount()
    {
        try {
            $profile = $this->getSymfonyProfile();
            /** @var MessageDataCollector $collector */
            $collector = $profile->getCollector('swiftmailer');

            return $collector->getMessageCount();
        } catch (\Exception $e) {
            /** @var \Swift_Plugins_MessageLogger $logger */
            $logger = $this->getContainer()->get('swiftmailer.plugin.messagelogger');

            return $logger->countMessages();
        }
    }
}

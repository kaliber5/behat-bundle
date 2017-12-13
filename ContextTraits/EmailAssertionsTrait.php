<?php
/**
 * Created by PhpStorm.
 * User: andreasschacht
 * Date: 13.07.16
 * Time: 11:41
 */

namespace Kaliber5\BehatBundle\ContextTraits;

use Coduo\PHPMatcher\PHPUnit\PHPMatcherAssertions;

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
    use PHPMatcherAssertions;

    /** @BeforeScenario */
    public function clearLogger()
    {
        $this->getContainer()->get('swiftmailer.plugin.messagelogger')->clear();
    }

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
     * Assert the email body of emails to the given recipient match the given fixture file
     *
     * @param $recipient
     * @param $contentFilename
     */
    public function assertEmailHasContent($recipient, $contentFilename)
    {
        $expected = $this->getEmailFileContent($contentFilename);
        $subject = null;
        if (preg_match('/^Subject: (.*)\n/', $expected, $matches)) {
            $expected = str_replace($matches[0], '', $expected);
            $subject = $matches[1];
        }
        $this->assertEmailWasSent($recipient, function($messages) use ($expected, $subject) {
            foreach ($messages as $message) {
                /** @var \Swift_Message $message */
                $actual = rtrim($message->getBody());
                assertThat($actual, self::matchesPattern($expected), 'The email content is not as expected');
                if ($subject !== null) {
                    assertThat($message->getSubject(), self::matchesPattern($subject), 'The email content is not as expected');
                }
            }
        });
    }

    /**
     * @return \Swift_Mime_Message[] array with messages
     *
     */
    public function getMessages()
    {
        /** @var \Swift_Plugins_MessageLogger $logger */
        $logger = $this->getContainer()->get('swiftmailer.plugin.messagelogger');
        $messages = [];
        foreach ($logger->getMessages() as $message) {
            $messages[$message->getId()] = $message;
        }

        return $messages;
    }

    /**
     * @return int
     *
     */
    public function getMessageCount()
    {
        return count($this->getMessages());
    }

    /**
     * @param $filename
     *
     * @return bool|string
     */
    protected function getEmailFileContent($filename) {
        if (!$this->basePath) {
            $this->generatePaths();
        }
        $fullPath = $this->basePath . 'Features' . DIRECTORY_SEPARATOR . 'EmailContent' . DIRECTORY_SEPARATOR . $filename;
        return file_get_contents($fullPath);
    }
}

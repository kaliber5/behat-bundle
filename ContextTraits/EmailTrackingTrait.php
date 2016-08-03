<?php
/**
 * Created by PhpStorm.
 * User: simonihmig
 * Date: 08.07.16
 * Time: 12:33
 */

namespace Kaliber5\BehatBundle\ContextTraits;

/**
 * Trait to assert that emails were sent
 *
 * THIS TRAIT HAS TO BE USED ONLY IN JsonMinkContext and subclasses
 * OR YOU HAVE TO OVERRIDE getMessages and getMessageCount Methods
 *
 * Trait EmailTrackingTrait
 *
 * @package Kaliber5\BehatBundle\ContextTraits
 */
trait EmailTrackingTrait
{
    use EmailAssertionsTrait;

    /**
     * assert an email was sent
     *
     * @Then /^an email should be sent to "([^"]*)"$/
     */
    public function anEmailShouldBeSentTo($recipient)
    {
        $this->assertEmailWasSent($recipient);
    }


    /**
     * assert no email was sent
     *
     * @Then /^no email should be sent$/
     */
    public function noEmailShouldBeSent()
    {
        $this->assertNoEmailSent();
    }
}

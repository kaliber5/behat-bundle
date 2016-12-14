<?php

namespace Kaliber5\BehatBundle\ContextTraits;

/**
 * Created by PhpStorm.
 * User: andreasschacht
 * Date: 06.04.16
 * Time: 21:07
 */
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

/**
 * Trait AuthenticatedUserTrait
 *
 * THIS TRAIT HAS TO BE USED ONLY IN MinkContext and subclasses
 */
trait AuthenticatedUserTrait
{

    /**
     * Authenticate as a User from Usermanager
     *
     * @Given /^I am authenticated as "([^"]*)"$/
     */
    public function iAmAuthenticatedAs($username)
    {
        $this->authenticatedAs($username);
    }

    /**
     * Authenticate as a (maybe not existing) User with a Role
     *
     * @Given I am authenticated as :arg1 with role :arg2
     */
    public function iAmAuthenticatedAsWithRole($arg1, $arg2)
    {
        $this->authenticatedAsWithRole($arg1, $arg2);
    }


    public function hasRole($role)
    {
        try {
            $client = $this->getSession()->getDriver()->getClient();
            return $client->getContainer()->get('security.authorization_checker')->isGranted($role);
        } catch (AuthenticationCredentialsNotFoundException $e) {
            return false;
        }
    }


    public function authenticatedAs($username, $context = 'user')
    {
        $client = $this->getSession()->getDriver()->getClient();
        $user = $client->getContainer()->get('fos_user.user_manager')->findUserByUsername($username);
        if (!$user) {
            throw new \Exception('User not found');
        }
        $this->authenticatedAsWithRole($user, $user->getRoles(), $context);
    }

    /**
     * @param string       $user    User
     * @param array|string $roles   roles
     * @param string       $context
     *
     * @throws UnsupportedDriverActionException
     */
    public function authenticatedAsWithRole($user, $roles, $context = 'user')
    {
        $driver = $this->getSession()->getDriver();
        if (!$driver instanceof BrowserKitDriver) {
            throw new UnsupportedDriverActionException(
                'This step is only supported by the BrowserKitDriver',
                $driver
            );
        }

        $client = $driver->getClient();
        $client->getCookieJar()->clear();

        $this->visit('/');//This is needed to get a seesion started
        $session = $client->getContainer()->get('session');
        $providerKey = $context; // the context of the firewall
        if (!is_array($roles)) {
            $roles = array($roles);
        }

        $token = new UsernamePasswordToken($user, null, $providerKey, $roles);
        $session->set('_security_'.$providerKey, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);
    }
}

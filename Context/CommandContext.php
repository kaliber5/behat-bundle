<?php
/**
 * Created by PhpStorm.
 * User: andreasschacht
 * Date: 12.07.16
 * Time: 11:12
 */

namespace Kaliber5\BehatBundle\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Kaliber5\BehatBundle\ContextTraits\EmailAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class CommandContext
 *
 * A Context to run and tests commands
 */
class CommandContext implements KernelAwareContext, SnippetAcceptingContext
{
    use EmailAssertionsTrait;

    /**
     * @var string
     */
    private $consoleAppClass;

    /**
     * @var \Symfony\Component\Console\Application
     */
    private $application;

    /**
     * @var array
     */
    private $registeredCommands;

    /**
     * @var array
     */
    private $loadedCommands;

    /**
     * @var \Symfony\Component\Console\Tester\CommandTester
     */
    private $tester;

    /**
     * @var \Exception
     */
    private $commandException;

    /**
     * @var array
     */
    private $commandParameters;

    /**
     * @var array
     */
    private $commandOptions;

    /**
     * @var string
     */
    private $runCommand;

    /**
     * @var array
     */
    private $listeners;

    /**
     * @var int
     */
    private $exitCode;

    /**
     * @var KernelInterface
     */
    protected $kernel;


    /**
     *
     * @param array  $registeredCommands
     * @param string $consoleAppClass
     * @param array  $listeners
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(array $registeredCommands = [], $consoleAppClass = Application::class, array $listeners = [])
    {
        $this->checkClassIsLoaded($consoleAppClass);

        $this->consoleAppClass = $consoleAppClass;
        $this->registeredCommands = $registeredCommands;
        $this->loadedCommands = [];
        $this->listeners = $listeners;
        $this->commandParameters = [];
        $this->commandOptions = [];
        $this->exitCode = 0;
    }

    /**
     * @BeforeScenario
     */
    public function resetMessageLogger()
    {
        $this->getContainer()->get('swiftmailer.plugin.messagelogger')->clear();
    }

    /**
     * {@inheritDoc}
     */
    public function setKernel(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @Given the mailspool is empty
     */
    public function clearMailSpool()
    {
        $this->resetMessageLogger();
    }

    /**
     * @Given /^I run a command "([^"]*)"$/
     * @Given /^(T|t)he command "([^"]*)" was executed$/
     * @When /^(T|t)he command "([^"]*)" executes$/
     */
    public function iRunACommand($command)
    {
        $commandInstance = $this->getCommand($command);
        $this->tester = new CommandTester($commandInstance);

        try {
            $this->exitCode = $this
                ->tester
                ->execute(
                    $this->getCommandParams($command),
                    $this->commandOptions
                );

            $this->commandException = null;

        } catch (\Exception $exception) {
            $this->commandException = $exception;
            $this->exitCode = $exception->getCode();
            throw $exception;
        }

        $this->runCommand = $command;
        $this->commandParameters = [];
        $this->commandOptions = [];
    }

    /**
     * @Given /^I run a command "([^"]*)" with parameters:$/
     */
    public function iRunACommandWithParameters($command, PyStringNode $parameterJson)
    {
        $this->commandParameters = json_decode($parameterJson->getRaw(), true);

        if (null === $this->commandParameters) {
            throw new \InvalidArgumentException(
                "PyStringNode could not be converted to json: ".$parameterJson->getRaw()
            );
        }

        $this->iRunACommand($command);
    }

    /**
     * @Given /^I run a command "([^"]*)" with options:$/
     */
    public function iRunACommandWithOptions($command, PyStringNode $parameterJson)
    {
        $this->commandOptions = json_decode($parameterJson->getRaw(), true);

        if (null === $this->commandOptions) {
            throw new \InvalidArgumentException(
                "PyStringNode could not be converted to json: ".$parameterJson->getRaw()
            );
        }

        $this->iRunACommand($command);
    }

    /**
     * @Then /^The command exception "([^"]*)" should be thrown$/
     */
    public function theCommandExceptionShouldBeThrown($exceptionClass)
    {
        $this->checkThatCommandHasRun();
        assertInstanceOf($exceptionClass, $this->commandException);
    }

    /**
     * @Then /^The command exit code should be (\d+)$/
     */
    public function theCommandExitCodeShouldBe($exitCode)
    {
        $this->checkThatCommandHasRun();

        assertEquals($exitCode, $this->exitCode);
    }

    /**
     * @Then /^I should see "([^"]*)" in the command output$/
     */
    public function iShouldSeeInTheCommandOutput($regexp)
    {
        $this->checkThatCommandHasRun();

        assertRegExp($regexp, $this->tester->getDisplay());
    }

    /**
     * @Then /^The command exception "([^"]*)" with message "([^"]*)" should be thrown$/
     */
    public function theCommandExceptionWithMessageShouldBeThrown($exceptionClass, $exceptionMessage)
    {
        $this->checkThatCommandHasRun();
        $this->theCommandExceptionShouldBeThrown($exceptionClass);
        assertEquals($exceptionMessage, $this->commandException->getMessage());
    }

    /**
     * assert an email was sent
     *
     * @Then /^(\d+) emails from command should be sent to "([^"]*)"$/
     */
    public function anEmailFromCommandShouldBeSentTo($arg1, $recipient)
    {
        $this->assertEmailWasSent($recipient, null, (int) $arg1);
    }

    /**
     * assert no email from command was sent
     *
     * @Then /^no email from command should be sent$/
     */
    public function noEmailFromCommandShouldBeSent()
    {
        $this->assertNoEmailSent();
    }

    /**
     * @return \Symfony\Component\Console\Application
     *
     * @throws \LogicException
     */
    public function getApplication()
    {
        if (null !== $this->application) {
            return $this->application;
        }

        /** @var Application application */
        $this->application = new $this->consoleAppClass($this->kernel);

        $this->processConsoleEventListeners();

        return $this->application;
    }

    /**
     * @param string $command
     *
     * @return \Symfony\Component\Console\Command\Command
     *
     * @throws \InvalidArgumentException
     */
    public function getCommand($command)
    {
        $command = (string) $command;

        if ($this->isLoaded($command)) {
            return $this->loadedCommands[$command];
        }

        if (!$this->isRegistered($command)) {
            throw new \InvalidArgumentException(
                sprintf('Command with name "%s" is not registered with the Context', $command)
            );
        }

        $commandInstance = new $this->registeredCommands[$command]();
        $application = $this->getApplication();

        $application->add($commandInstance);

        return $this->loadedCommands[$command] = $commandInstance;
    }

    /**
     * @param string $commandName
     * @param string $commandClass
     */
    public function registerCommand($commandName, $commandClass)
    {
        $this->checkClassIsLoaded($commandClass);

        $this->registeredCommands[(string) $commandName] = $commandClass;
    }

    /**
     * @param string $commandName
     *
     * @return bool
     */
    public function unregisterCommand($commandName)
    {
        $commandName = (string) $commandName;

        if (!isset($this->registeredCommands[$commandName])) {
            return false;
        }

        unset($this->registeredCommands[$commandName]);

        return true;
    }

    /**
     * @param string $command
     *
     * @return bool
     */
    public function isRegistered($command)
    {
        return (isset($this->registeredCommands[(string) $command]));
    }

    /**
     * @param string $command
     *
     * @return bool
     */
    public function isLoaded($command)
    {
        return (isset($this->loadedCommands[(string) $command]));
    }

    /**
     * @return array with messages
     */
    public function getMessages()
    {
        return $this->getContainer()->get('swiftmailer.plugin.messagelogger')->getMessages();
    }

    /**
     * @return int
     */
    public function getMessageCount()
    {
        return $this->getContainer()->get('swiftmailer.plugin.messagelogger')->countMessages();
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return $this->getApplication()->getKernel()->getContainer();
    }

    /**
     * @param string $class
     *
     * @throws \InvalidArgumentException
     */
    private function checkClassIsLoaded($class)
    {
        if (!class_exists((string) $class)) {
            throw new \InvalidArgumentException(
                'Class "%s" could not be found or autoloaded.'
            );
        }
    }

    /**
     * @return bool
     *
     * @throws \LogicException
     */
    private function checkThatCommandHasRun()
    {
        if (null === $this->runCommand) {
            throw new \LogicException(
                "You first need to run a command to check to use this step"
            );
        }

        return true;
    }

    /**
     * Processes the subscribers and listeners for this Application
     */
    private function processConsoleEventListeners()
    {
        if (null === $this->application) {
            return null;
        }

        if (!empty($this->listeners)) {
            $dispatcher = new EventDispatcher();

            $listeners = array_merge(
                [
                    'subscriber' => [],
                    'listener'   => [],
                ],
                $this->listeners
            );

            foreach ($listeners['listener'] as $event => $listener) {
                $priority = 0;

                if (is_array($listeners)) {
                    list($listener, $priority) = $listener;
                }

                $dispatcher->addListener($event, $listener, $priority);
            }

            foreach ($listeners['subscriber'] as $subscriber) {
                $dispatcher->addSubscriber($subscriber);
            }

            $this
                ->application
                ->setDispatcher($dispatcher);
        }
    }

    /**
     * @param string $command
     *
     * @return array
     */
    private function getCommandParams($command)
    {
        $default = [
            'command' => $command,
        ];

        return array_merge(
            $this->commandParameters,
            $default
        );
    }
}

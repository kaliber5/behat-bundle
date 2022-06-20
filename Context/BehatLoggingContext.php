<?php
/**
 * Created by PhpStorm.
 * User: andreasschacht
 * Date: 05.08.16
 * Time: 18:25
 */

namespace Kaliber5\BehatBundle\Context;

use Behat\Behat\Hook\Scope\BeforeStepScope;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class BehatLoggingContext
 *
 * This class creates log entries for each step
 *
 * @package Kaliber5\BehatBundle\Context
 */
class BehatLoggingContext
{
    /*
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


    /**
     * Creates a log message on each step
     *
     * @BeforeStep
     *
     * @param BeforeStepScope $scope
     */
    public function logStep(BeforeStepScope $scope)
    {
        $this->logger->debug('BEHATSTEP: '.$scope->getStep()->getText());
    }
}

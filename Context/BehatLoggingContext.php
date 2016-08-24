<?php
/**
 * Created by PhpStorm.
 * User: andreasschacht
 * Date: 05.08.16
 * Time: 18:25
 */

namespace Kaliber5\BehatBundle\Context;

use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class BehatLoggingContext
 *
 * This class creates log entries for each step
 *
 * @package Kaliber5\BehatBundle\Context
 */
class BehatLoggingContext implements KernelAwareContext
{

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function setKernel(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
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
        if ($this->kernel) {
            /** @var LoggerInterface $logger */
            $logger = $this->kernel->getContainer()->get('logger');
            if ($logger instanceof LoggerInterface) {
                $logger->debug('BEHATSTEP: '.$scope->getStep()->getText());
            }
        }
    }
}

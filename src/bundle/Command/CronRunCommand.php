<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\EzPlatformCronBundle\Command;

use Cron\Cron;
use Cron\Executor\Executor;
use Cron\Resolver\ArrayResolver;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;

class CronRunCommand extends ContainerAwareCommand
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct();
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setDefinition([
                new InputOption('category', null, InputOption::VALUE_REQUIRED, 'Job category to run', 'default'),
            ])
            ->setName('ezplatform:cron:run')
            ->setDescription('Perform one-time cron tasks run.')
            ->setHelp(
                <<<EOT
It's not meant to be run manually, yet it's OK to do so as it still might be useful for development purpose.

Check documentation how to setup it be called automatically.
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $registry = $this->getContainer()->get('ezplatform.cron.registry.cronjobs');

        $category = $input->getOption('category');
        $cronJobs = $registry->getCategoryCronJobs($category);

        $resolver = new ArrayResolver($cronJobs);

        $cron = new Cron();
        $cron->setExecutor(new Executor());
        $cron->setResolver($resolver);

        $reports = $cron->run();

        while ($cron->isRunning()) {
            usleep(10000);
        }

        if ($this->logger) {
            foreach ($reports->getReports() as $report) {
                $process = $report->getJob()->getProcess();
                $extraInfo = [
                    'command' => $process->getCommandLine(),
                    'exitCode' => $process->getExitCode(),
                ];
                foreach ($report->getOutput() as $reportOutput) {
                    $this->logger->info($reportOutput, $extraInfo);
                }
                if (!$report->isSuccessful()) {
                    foreach ($report->getError() as $reportError) {
                        $reportError = trim($reportError);
                        if (!empty($reportError)) {
                            $this->logger->error($reportError, $extraInfo);
                        }
                    }
                }
            }
        }
    }
}

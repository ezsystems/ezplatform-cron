<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformCronBundle\Command;

use Cron\Cron;
use Cron\Executor\Executor;
use Cron\Report\CronReport;
use Cron\Resolver\ArrayResolver;
use eZ\Bundle\EzPublishCoreBundle\Command\BackwardCompatibleCommand;
use EzSystems\EzPlatformCronBundle\Registry\CronJobsRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;

final class CronRunCommand extends Command implements BackwardCompatibleCommand
{
    /** @var \EzSystems\EzPlatformCronBundle\Registry\CronJobsRegistry */
    private $cronJobsRegistry;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger, CronJobsRegistry $cronJobsRegistry, ?string $name = null)
    {
        parent::__construct($name);

        $this->logger = $logger;
        $this->cronJobsRegistry = $cronJobsRegistry;
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputOption('category', null, InputOption::VALUE_REQUIRED, 'Job category to run', 'default'),
            ])
            ->setName('ibexa:cron:run')
            ->setAliases(['ezplatform:cron:run'])
            ->setDescription('Perform one-time cron tasks run.')
            ->setHelp(
                <<<EOT
It's not meant to be run manually, yet it's OK to do so as it still might be useful for development purpose.

Check documentation how to setup it be called automatically.
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $category = $input->getOption('category');
        $cronJobs = $this->cronJobsRegistry->getCategoryCronJobs($category);

        $resolver = new ArrayResolver($cronJobs);

        $cron = new Cron();
        $cron->setExecutor(new Executor());
        $cron->setResolver($resolver);

        $reports = $cron->run();

        while ($cron->isRunning()) {
            usleep(10000);
        }

        if ($this->logger) {
            /** @var \Cron\Report\CronReport $reports */
            $this->logReportsOutput($reports);
        }

        return 0;
    }

    private function logReportsOutput(CronReport $reports): void
    {
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

    /**
     * @return string[]
     */
    public function getDeprecatedAliases(): array
    {
        return ['ezplatform:cron:run'];
    }
}

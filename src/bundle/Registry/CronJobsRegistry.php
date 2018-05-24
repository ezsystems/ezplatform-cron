<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\EzPlatformCronBundle\Registry;

use Cron\Job\ShellJob;
use Cron\Schedule\CrontabSchedule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\PhpExecutableFinder;

class CronJobsRegistry
{
    const DEFAULT_CATEGORY = 'default';

    protected $cronJobs = [];

    protected $executable;

    protected $environment;

    public function __construct($environment)
    {
        $finder = new PhpExecutableFinder();

        $this->executable = $finder->find();
        $this->environment = $environment;
    }

    public function addCronJob(Command $command, $schedule, $category = self::DEFAULT_CATEGORY)
    {
        $command = sprintf('%s %s %s --env=%s',
            $this->executable,
            $_SERVER['SCRIPT_NAME'],
            $command->getName(),
            $this->environment
        );

        $job = new ShellJob();
        $job->setSchedule(new CrontabSchedule($schedule));
        $job->setCommand($command);

        $this->cronJobs[$category][] = $job;
    }

    public function getCategoryCronJobs($category)
    {
        if (!isset($this->cronJobs[$category])) {
            return [];
        }

        return $this->cronJobs[$category];
    }
}

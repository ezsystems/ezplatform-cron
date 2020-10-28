<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\EzPlatformCronBundle\Registry;

use Cron\Job\ShellJob;
use Cron\Schedule\CrontabSchedule;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\PhpExecutableFinder;

class CronJobsRegistry
{
    const DEFAULT_CATEGORY = 'default';

    /**
     * @var array
     */
    protected $cronJobs = [];

    /**
     * @var false|string
     */
    protected $executable;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var \eZ\Publish\Core\MVC\Symfony\SiteAccess
     */
    protected $siteaccess;

    /**
     * @var string
     */
    protected $options;

    public function __construct(string $environment, SiteAccess $siteaccess)
    {
        $finder = new PhpExecutableFinder();

        $this->executable = $finder->find();
        $this->environment = $environment;
        $this->siteaccess = $siteaccess;
    }

    public function addCronJob(Command $command, string $schedule = null, string $category = self::DEFAULT_CATEGORY, string $options = ''): void
    {
        $command = sprintf('%s %s %s %s --siteaccess=%s --env=%s',
            $this->executable,
            $_SERVER['SCRIPT_NAME'],
            $command->getName(),
            $options,
            $this->siteaccess->name,
            $this->environment
        );

        $job = new ShellJob();
        $job->setSchedule(new CrontabSchedule($schedule));
        $job->setCommand($command);

        $this->cronJobs[$category][] = $job;
    }

    /**
     * @return \Cron\Job\ShellJob[]
     */
    public function getCategoryCronJobs(string $category): array
    {
        if (!isset($this->cronJobs[$category])) {
            return [];
        }

        return $this->cronJobs[$category];
    }
}

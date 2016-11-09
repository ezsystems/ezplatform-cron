<?php

namespace EzSystems\CronBundle\DependencyInjection\Compiler;

use EzSystems\CronBundle\Registry\CronJobsRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CronJobCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('ezpublish.cron.registry.cronjobs')) {
            return;
        }

        $registry = $container->findDefinition('ezpublish.cron.registry.cronjobs');
        $cronJobs = $container->findTaggedServiceIds('ezpublish.cron.job');

        foreach ($cronJobs as $id => $tags) {
            foreach ($tags as $cronJob) {
                $reference = new Reference($id);

                if (!isset($cronJob['schedule']) || empty($cronJob['schedule'])) {
                    throw new \RuntimeException(sprintf('Invalid %s cron job configuration, schedule argument missing', $id));
                }

                $cronJob['category'] = isset($cronJob['category'])
                    ? $cronJob['category']
                    : CronJobsRegistry::DEFAULT_CATEGORY;

                $registry->addMethodCall('addCronJob', [
                    $reference,
                    $cronJob['schedule'],
                    $cronJob['category'],
                ]);
            }
        }
    }
}

# ezplatform-cron


This package exposes [cron/cron](https://github.com/Cron/Cron) package for use in eZ Platform (or just plain Symfony) via a simle command
`ezplatform:cron:run`.

This is *not* a replacement for [cron/cron-bundle](https://github.com/Cron/Symfony-Bundle) but rather a simpler alternative to it which can more esaily grow into focusing more on eZ Platform needs in the future including e.g. support for handling cron jobs across a cluster install _(separating jobs that should run on all nodes vs jobs that should only be run on one at a time and a lock system to go with it for instance)_.



## Setup system cron

Pick your systems cron / scheduling and setup `ezplatform:cron:run` command to run every minute and optionally specifying category _(default: `default`)_:

Example for Linux crontab (`crontab -e`):
```bash
* * * * * /path/to/php app/console ezplatform:cron:run [ --category=default] >/dev/null 2>&1
```
    


## Setting up own cron commands


Setting up own cron jobs is as simple as tagging services for your existing Symfony Commands.

The tag takes the following arguments:
- `name`: `ezplatform.cron.job`
- `schedule`: _Takes any kind of [format supported by cron/cron](https://github.com/Cron/Cron#crontab-syntax), which mimics linux crontab format. E.g. `* * * * *`_
- `category`: _(Optional, by default: `default`) Lets you separate cronjobs that should be run under different logic then default, e.g. infrequent jobs (NOTE: Means end user will need to setup several entries in his crontab to run all categories!)_
- `options`: _(Optional, by default: `''`) Takes custom option/s in string format which are added to the command. (E.g. '--keep=0 --status=draft' for running the cleanup versions command)_


### Example

```yml
    date_based_published.cron.publish_scheduled:
        class: EzSystems\DateBasedPublisherBundle\Command\PublishScheduledCommand
        tags:
            - { name: console.command }
            - { name: ezplatform.cron.job, schedule: '* * * * *' }
```

## Logging run command
If you want to log outputs of commands processed by run command you have to add the monolog channel `cronjob` to your configuration.

### Example
```yml
    monolog:
        channels: [...,'cronjob']
        handlers:    
            cronjob:
                bubble: false
                level: info
                type: stream
                path: '%kernel.logs_dir%/cronjob.log'
                channels: [cronjob]
```
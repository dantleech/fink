<?php

namespace DTL\Extension\Fink;

use DTL\Extension\Fink\Console\Command\CrawlCommand;
use DTL\Extension\Fink\Console\DisplayBuilder;
use DTL\Extension\Fink\Console\DisplayRegistry;
use DTL\Extension\Fink\Console\Display\MemoryDisplay;
use DTL\Extension\Fink\Console\Display\RateDisplay;
use DTL\Extension\Fink\Console\Display\StatusLineDisplay;
use DTL\Extension\Fink\Console\Display\ReportListDisplay;
use DTL\Extension\Fink\Console\Display\UptimeDisplay;
use DTL\Extension\Fink\Model\DispatcherBuilderFactory;
use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\Console\ConsoleExtension;
use Phpactor\MapResolver\Resolver;

class FinkExtension implements Extension
{
    public const SERVICE_DISPATCHER_BUILDER_FACTORY = 'fink.dispatcher_builder_factory';

    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $container->register('fink.console.command.crawl', function (Container $container) {
            return new CrawlCommand(
                $container->get(self::SERVICE_DISPATCHER_BUILDER_FACTORY),
                $container->get('fink.console.display_builder')
            );
        }, [ ConsoleExtension::TAG_COMMAND => [ 'name' => 'fink' ]]);

        $container->register('fink.console.display_registry', function (Container $container) {
            return new DisplayRegistry([
                'urllist' => new ReportListDisplay(),
                'status' => new StatusLineDisplay(),
                'rate' => new RateDisplay(),
                'memory' => new MemoryDisplay(),
                'uptime' => new UptimeDisplay(),
            ]);
        });

        $container->register('fink.console.display_builder', function (Container $container) {
            return new DisplayBuilder($container->get('fink.console.display_registry'), [
                'urllist', 'status', 'rate', 'uptime'
            ]);
        });

        $container->register(self::SERVICE_DISPATCHER_BUILDER_FACTORY, function (Container $container) {
            return new DispatcherBuilderFactory();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
    }
}

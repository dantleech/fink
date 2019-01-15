<?php

namespace DTL\Extension\Fink;

use DTL\Extension\Fink\Command\CrawlCommand;
use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\Console\ConsoleExtension;
use Phpactor\MapResolver\Resolver;

class FinkExtension implements Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $container->register('fink.command.crawl', function (Container $container) {
            return new CrawlCommand();
        }, [ ConsoleExtension::TAG_COMMAND => [ 'name' => 'crawl' ]]);
    }

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
    }
}

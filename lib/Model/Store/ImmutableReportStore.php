<?php

namespace DTL\Extension\Fink\Model\Store;

use DTL\Extension\Fink\Model\ImmutableReportStore as ImmutableReportStoreInterface;
use DTL\Extension\Fink\Model\ReportStore;
use Traversable;

class ImmutableReportStore implements ImmutableReportStoreInterface
{
    /**
     * @var ReportStore
     */
    private $innerStore;

    public function __construct(ReportStore $innerStore)
    {
        $this->innerStore = $innerStore;
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return $this->innerStore->count();
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): Traversable
    {
        return $this->innerStore->getIterator();
    }
}

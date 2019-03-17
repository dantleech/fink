<?php

namespace DTL\Extension\Fink\Console;

use DTL\Extension\Fink\Console\Exception\DisplayNotFound;

class DisplayRegistry
{
    /**
     * @var array
     */
    private $displays = [];

    public function __construct(array $displays = [])
    {
        foreach ($displays as $name => $display) {
            $this->add($name, $display);
        }
    }

    public function get(string $name)
    {
        if (!isset($this->displays[$name])) {
            throw new DisplayNotFound(sprintf(
                'Display "%s" not found, known displays "%s"',
                $name,
                implode('", "', array_keys($this->displays))
            ));
        }

        return $this->displays[$name];
    }

    private function add(string $name, Display $display)
    {
        $this->displays[$name] = $display;
    }
}

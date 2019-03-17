<?php

namespace DTL\Extension\Fink\Console;

use DTL\Extension\Fink\Console\Display\ConcatenatingDisplay;

class DisplayBuilder
{
    /**
     * @var DisplayRegistry
     */
    private $registry;

    /**
     * @var array
     */
    private $default;

    public function __construct(DisplayRegistry $registry, array $defaut = [])
    {
        $this->registry = $registry;
        $this->default = $defaut;
    }

    public function build(string $spec): Display
    {
        $units = array_filter(array_map('strtolower', array_map('trim', explode(',', $spec))));

        $units = $this->initializeDisplayNames($units);
        $units = $this->removeDisplayNames($units);

        $displays = [];
        foreach ($units as $unit) {
            if (substr($unit, 0, 1) === '+') {
                $displays[] = $this->registry->get(substr($unit, 1));
                continue;
            }

            $displays[] = $this->registry->get($unit);
        }

        return new ConcatenatingDisplay($displays);
    }

    private function initializeDisplayNames(array $units): array
    {
        $reset = array_reduce($units, function (bool $reset, string $unit) {
            if ($reset === true) {
                return $reset;
            }
            $prefix = substr($unit, 0, 1);
            return false === in_array($prefix, ['+','-']);
        }, false);
        
        if (!$reset) {
            $units = array_merge($this->default, $units);
        }
        return $units;
    }

    private function removeDisplayNames(array $units): array
    {
        $remove = array_filter(array_map(function (string $unit) {
            if (substr($unit, 0, 1) === '-') {
                return substr($unit, 1);
            }
        
            return null;
        }, $units));
        
        $units = array_filter($units, function (string $unit) use ($remove) {
            if (in_array($unit, $remove)) {
                return false;
            }

            if (substr($unit, 0, 1) === '-') {
                return false;
            }

            return true;
        });
        return $units;
    }
}

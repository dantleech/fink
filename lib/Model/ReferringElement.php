<?php

namespace DTL\Extension\Fink\Model;

use DOMElement;

class ReferringElement
{
    private $xpath = '';
    private $title = '';

    public static function none(): ReferringElement
    {
        return new self();
    }

    public static function fromDOMElement(DOMElement $element): ReferringElement
    {
        $new = new self();
        $new->xpath = $element->getNodePath();
        $new->title = $element->nodeValue;

        return $new;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function path(): string
    {
        return $this->xpath;
    }
}

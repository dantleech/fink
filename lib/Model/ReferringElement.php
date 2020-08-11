<?php

namespace DTL\Extension\Fink\Model;

use DOMNode;

class ReferringElement
{
    private $xpath = '';
    private $title = '';
    private $baseURI = '';

    public static function none(): ReferringElement
    {
        return new self();
    }

    public static function fromDOMNode(DOMNode $element): ReferringElement
    {
        $new = new self();
        $new->xpath = $element->getNodePath();
        $new->title = trim($element->nodeValue);
        $new->baseURI = $element->baseURI;

        return $new;
    }

    public function baseURI(): ?string
    {
        return $this->baseURI;
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

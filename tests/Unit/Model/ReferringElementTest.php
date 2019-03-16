<?php

namespace DTL\Extension\Fink\Tests\Unit\Model;

use DOMDocument;
use DOMNode;
use DTL\Extension\Fink\Model\ReferringElement;
use PHPUnit\Framework\TestCase;

class ReferringElementTest extends TestCase
{
    public function testReturnsPath()
    {
        $element = $this->createXmlElement('<a>Hello</a>');
        $this->assertEquals('/xml/a', ReferringElement::fromDOMNode($element)->path());
    }
    /**
     * @dataProvider provideReturnsDomElementTitle
     */
    public function testReturnsDomElementTitle(string $htmlElement, string $expectedTitle)
    {
        $element = $this->createXmlElement($htmlElement);
        $this->assertEquals($expectedTitle, ReferringElement::fromDOMNode($element)->title());
    }

    public function provideReturnsDomElementTitle()
    {
        yield 'no text' => [
            '<a></a>',
            '',
        ];

        yield 'simple element' => [
            '<a>hello</a>',
            'hello',
        ];

        yield 'simple element with nested elements' => [
            <<<'EOT'
<a>
            
    <i class="icon"></i>
            <span>Grid</span>
        </a>
EOT
        , 'Grid'
        ];
    }

    private function createXmlElement(string $string): DOMNode
    {
        $dom = new DOMDocument(1.0);
        $dom->loadXML('<xml>'.$string .'</xml>');
        return $dom->firstChild->firstChild;
    }
}

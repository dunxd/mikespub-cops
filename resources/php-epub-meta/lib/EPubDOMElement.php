<?php
/**
 * PHP EPub Meta library
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 * @author Sébastien Lucas <sebastien@slucas.fr>
 */

namespace SebLucas\EPubMeta;

use DOMElement;

class EPubDOMElement extends DOMElement
{
    /** @var array<string, string> */
    public $namespaces = [
        'n'   => 'urn:oasis:names:tc:opendocument:xmlns:container',
        'opf' => 'http://www.idpf.org/2007/opf',
        'dc'  => 'http://purl.org/dc/elements/1.1/',
    ];

    /**
     * Summary of __construct
     * @param string $name
     * @param string $value
     * @param string $namespaceURI
     */
    public function __construct($name, $value='', $namespaceURI='')
    {
        [$ns, $name] = $this->splitns($name);
        $value = htmlspecialchars($value);
        if (!$namespaceURI && $ns) {
            $namespaceURI = $this->namespaces[$ns];
        }
        parent::__construct($name, $value, $namespaceURI);
    }

    /**
     * Create and append a new child
     *
     * Works with our epub namespaces and omits default namespaces
     * @param string $name
     * @param string $value
     * @return EPubDOMElement|bool
     */
    public function newChild($name, $value='')
    {
        [$ns, $local] = $this->splitns($name);
        $nsuri = '';
        if ($ns) {
            $nsuri = $this->namespaces[$ns];
            if ($this->isDefaultNamespace($nsuri)) {
                $name  = $local;
                $nsuri = '';
            }
        }

        // this doesn't call the construcor: $node = $this->ownerDocument->createElement($name,$value);
        $node = new EPubDOMElement($name, $value, $nsuri);
        /** @var EPubDOMElement */
        $node = $this->appendChild($node);
        return $node;
    }

    /**
     * Split given name in namespace prefix and local part
     *
     * @param  string $name
     * @return array<string>  (namespace, name)
     */
    public function splitns($name)
    {
        $list = explode(':', $name, 2);
        if (count($list) < 2) {
            array_unshift($list, '');
        }
        return $list;
    }

    /**
     * Simple EPub namespace aware attribute accessor
     * @param string $attr
     * @param string|false|null $value
     * @return string|void
     */
    public function attr($attr, $value=null)
    {
        [$ns, $attr] = $this->splitns($attr);

        $nsuri = '';
        if ($ns) {
            $nsuri = $this->namespaces[$ns];
            if (!$this->namespaceURI) {
                if ($this->isDefaultNamespace($nsuri)) {
                    $nsuri = '';
                }
            } elseif ($this->namespaceURI == $nsuri) {
                $nsuri = '';
            }
        }

        if (!is_null($value)) {
            if ($value === false) {
                // delete if false was given
                if ($nsuri) {
                    $this->removeAttributeNS($nsuri, $attr);
                } else {
                    $this->removeAttribute($attr);
                }
            } else {
                // modify if value was given
                if ($nsuri) {
                    $this->setAttributeNS($nsuri, $attr, $value);
                } else {
                    $this->setAttribute($attr, $value);
                }
            }
        } else {
            // return value if none was given
            if ($nsuri) {
                return $this->getAttributeNS($nsuri, $attr);
            } else {
                return $this->getAttribute($attr);
            }
        }
    }

    /**
     * Remove this node from the DOM
     * @return void
     */
    public function delete()
    {
        $this->parentNode->removeChild($this);
    }
}

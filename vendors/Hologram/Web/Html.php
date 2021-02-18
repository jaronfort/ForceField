<?php
namespace Hologram\Web;

use DOMDocument;
use DOMElement;
use DOMComment;
use DOMText;
use DOMAttr;
use DOMNode;
use ForceField\Utility\StringWriter;
use ForceField\Utility\ArrayUtil;
use ForceField\Core\Configure;
define('HTML_AUTO_IMPORT_GET_TAG_NAME', 1);
define('HTML_MINIFY_RETURN_DOM', 2);
define('HTML_MINIFY_NO_SKIP_PRE', 3);

class Html
{

    private static $doctypes = [
        'html5' => '<!DOCTYPE html>', // Default
        'xhtml11' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
        'xhtml1-strict' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
        'xhtml1-trans' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
        'xhtml1-frame' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
        'xhtml-basic11' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">',
        'html4-strict' => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
        'html4-trans' => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
        'html4-frame' => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">',
        'mathml1' => '<!DOCTYPE math SYSTEM "http://www.w3.org/Math/DTD/mathml1/mathml.dtd">',
        'mathml2' => '<!DOCTYPE math PUBLIC "-//W3C//DTD MathML 2.0//EN" "http://www.w3.org/Math/DTD/mathml2/mathml2.dtd">',
        'svg10' => '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.0//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">',
        'svg11' => '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">',
        'svg11-basic' => '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1 Basic//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11-basic.dtd">',
        'svg11-tiny' => '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1 Tiny//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11-tiny.dtd">',
        'xhtml-math-svg-xh' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1 plus MathML 2.0 plus SVG 1.1//EN" "http://www.w3.org/2002/04/xhtml-math-svg/xhtml-math-svg.dtd">',
        'xhtml-math-svg-sh' => '<!DOCTYPE svg:svg PUBLIC "-//W3C//DTD XHTML 1.1 plus MathML 2.0 plus SVG 1.1//EN" "http://www.w3.org/2002/04/xhtml-math-svg/xhtml-math-svg.dtd">',
        'xhtml-rdfa-1' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">',
        'xhtml-rdfa-2' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.1//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-2.dtd">'
    ];

    private $data;

    private $type;

    private $dom;

    private $parent;

    private $auto_import = [];

    private $doctype;

    private $title;

    private $charset;

    private $lang;

    private $minify;

    private $obf_map;

    private $will_notify;

    const DOCUMENT = 1;

    const DOMELEMENT = 2;

    const GROUP = 3;

    const NODE = 'node';

    const TEXT = 'text';

    const ELEMENT = 'element';

    const ATTRIBUTE = 'attribute';

    const COMMENT = 'comment';

    const CHILDREN_NO_NESTED = 'children_no_nested';

    public function __construct($data = null, Html $parent = null)
    {
        $this->parent = $parent;
        if (is_string($data)) {
            
            // if (preg_match('/^([\s\n]*\<\![\s\n]*(doctype)[^\>]*\>[\s\n]*)?(.|[\n])*\<(html)/', $data)) {
            if (preg_match('/(<!doctype|<html)/i', $data)) {
                // HTML document
                $this->dom = new \DOMDocument();
                $this->dom->preserveWhiteSpace = true;
                $this->dom->formatOutput = true;
                $this->dom->strictErrorChecking = false;
                $this->data = $this->dom;
                $this->type = Html::DOCUMENT;
                @$this->dom->loadHTML($data, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            } else {
                // HTML elements
                $this->data = Html::decode($data);
                $this->type = Html::ELEMENT;
            }
        } else if ($data instanceof \DOMDocument) {
            // Document
            $this->type = Html::DOCUMENT;
            $this->data = $data;
            $this->doctype = 'html5';
        } else if ($data instanceof \DOMElement) {
            // Element
            $this->type = Html::DOMELEMENT;
            $this->data = [
                $data
            ];
        } else if (is_array($data)) {
            // Group
            $this->type = Html::GROUP;
            $this->data = $data;
        } else if (is_null($data)) {
            // Document (for templating)
            $this->type = HTML::DOCUMENT;
            $this->data = $data;
            $this->doctype = 'html5'; // Default
            $this->charset = 'utf-8'; // Default
            $this->buildTemplate();
        } else
            throw new \Exception('Invalid argument passed to Html constructor.');
        
        $this->minify = Configure::readBool('minify.html', false);
    }

    private function buildTemplate()
    {
        libxml_use_internal_errors(TRUE);
        $this->dom = new DOMDocument();
        $this->dom->preserveWhiteSpace = TRUE;
        $this->dom->formatOutput = TRUE;
        $writer = new StringWriter();
        if (array_key_exists($this->doctype, HTML::$doctypes))
            $writer->writeln(HTML::$doctypes[$this->doctype]);
        $writer->writeln(is_string($this->lang) ? '<html lang="' . $this->lang . '">' : '<html>');
        $writer->writeln('<head>');
        if ($this->charset)
            $writer->writeln('<meta charset="' . $this->charset . '" />');
        $writer->writeln('<title>' . (string) $this->title . '</title>');
        $writer->writeln('</head>');
        $writer->writeln('<body>');
        $writer->writeln('</body>');
        $writer->writeln('</html>');
        $doc = $writer->flush();
        $this->dom->loadHTML($doc);
        $this->data = $this->dom;
        return $this;
    }

    private function parseDOM(DOMDocument $dom)
    {
        
        // $this->num_parses ++;
        // foreach ($this->plugins as $p) {
        // $p->parse($this, $dom);
        // }
    }

    private function parseAttributeToken($tkn)
    {
        if (! preg_match('/^\[[a-zA-Z0-9\-_]+([\s]*[\=\^\$\|\~\*\!]{1,2}(.)*)?\]$/', $tkn))
            return [
                '',
                '',
                ''
            ];
        $attr = '';
        $op = '';
        $val = '';
        $len = strlen($tkn);
        for ($i = 0; $i < $len; $i ++) {
            $char = $tkn[$i];
            $next = $i + 1 < $len ? $tkn[$i + 1] : '';
            if ($i == 0 && $char == '[')
                continue;
            else if ($char == ']' && $i == $len - 1)
                break; // End
            else if ($char == "\n")
                continue; // Skip
            else if (! $op) {
                // Operator not yet defined
                if (preg_match('/^(\^\=|\~\=|\$\=|\*\=|\|\=)$/ ', $char . $next)) {
                    $op = $char . $next;
                    $i ++; // Skip next
                } else if ($char == '=')
                    $op = '=';
                else if (! preg_match('/^[\s]$/', $char))
                    // If not whitespace
                    $attr .= $char;
            } else
                $val .= $char;
        }
        if (preg_match('/^[\s]*".*"[\s]*$/', $val)) {
            // Trim and remove double quotes
            $val = trim($val);
            $val = substr($val, 1, strlen($val) - 2);
        } else if (preg_match('/^[\s]*\'.*\'[\s]*$/', $val)) {
            $val = trim($val);
            $val = substr($val, 1, strlen($val) - 2);
        }
        $result = array(
            $attr,
            $op,
            $val
        );
        return $result;
    }

    private static function containsText($html)
    {
        if (is_string($html))
            $html = Html::decode($html);
        else if ($html instanceof DOMNode)
            $html = array(
                $html
            );
        
        if (is_array($html)) {
            foreach ($html as $h) {
                if ($h instanceof DOMNode) {
                    foreach ($h->childNodes as $child) {
                        // If node has text that isn't whitespace
                        if ($child instanceof DOMText && preg_match('/[^\s\n]/', $child->nodeValue))
                            return true;
                        else if ($child instanceof DOMElement && ! in_array(strtolower($child->tagName), [
                            'input',
                            'textarea',
                            'select'
                        ]) && Html::containsText($child))
                            return true;
                    }
                }
            }
        }
        
        return $html;
    }

    private function loadChildren(DOMElement $element, array &$children)
    {
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $children[] = $child;
                $this->loadChildren($child, $children);
            }
        }
    }

    private function getElements($target)
    {
        if ($target instanceof DOMDocument) {
            $a = [];
            foreach ($target->getElementsByTagName('*') as $e) {
                $a[] = $e;
            }
            return $a;
        } else
            return Html::getChildren($target, Html::ELEMENT);
    }

    private function getElementsById($id, array $elements)
    {
        $a = [];
        foreach ($elements as $e) {
            if ($e->hasAttribute('id') && trim($e->getAttribute('id')) == $id)
                $a[] = $e;
        }
        return $a;
    }

    private function getElementsByTag($tag, array $elements)
    {
        $a = [];
        $tag = strtolower($tag);
        foreach ($elements as $e) {
            if (strtolower($e->tagName) == $tag)
                $a[] = $e;
        }
        return $a;
    }

    private function getElementsByClass($class, array $elements)
    {
        $a = [];
        foreach ($elements as $e) {
            if ($e->hasAttribute('class') && in_array($class, explode(' ', $e->getAttribute('class'))))
                $a[] = $e;
        }
        return $a;
    }

    private function getElementsWithAttr($attr, array $elements)
    {
        $a = [];
        foreach ($elements as $e) {
            if ($e->hasAttribute($attr))
                $a[] = $e;
        }
        return $a;
    }

    private function getElementsWithoutAttr($attr, array $elements)
    {
        $a = [];
        foreach ($elements as $e) {
            if (! $e->hasAttribute($attr))
                $a[] = $e;
        }
        return $a;
    }

    private function getElementsWithAttrVal($attr, $val, array $elements)
    {
        $a = [];
        foreach ($elements as $e) {
            if ($e->hasAttribute($attr) && trim($e->getAttribute($attr)) == $val)
                $a[] = $e;
        }
        return $a;
    }

    private function getElementChildren(DOMElement $element)
    {
        $a = [];
        foreach ($element->childNodes as $child) {
            $a[] = $child;
        }
        return $a;
    }

    private function getDecendents(array $elements)
    {
        $a = [];
        foreach ($elements as $e) {
            $this->loadChildren($e, $a);
        }
        return $a;
    }

    private function getChildElements(array $elements)
    {
        $a = [];
        foreach ($elements as $e) {
            foreach ($elements as $e) {
                foreach ($e->childNodes as $child) {
                    if ($child instanceof DOMElement)
                        $a[] = $child;
                }
            }
        }
        return $a;
    }

    private function getElementsPreceding(array $elements)
    {
        $a = [];
        foreach ($elements as $e) {
            if ($e->parentNode instanceof DOMElement) {
                $search = FALSE;
                foreach ($e->parentNode->childNodes as $child) {
                    if ($search && $child instanceof DOMElement)
                        $a[] = $child; // Append all matches
                    else if ($child === $e)
                        $search = TRUE;
                }
            }
        }
        return $a;
    }

    private function getElementImmediatelyAfter(array $elements)
    {
        $a = [];
        foreach ($elements as $e) {
            if ($e->parentNode instanceof DOMElement) {
                $search = FALSE;
                foreach ($e->parentNode->childNodes as $child) {
                    if ($search && $child instanceof DOMElement) {
                        $a[] = $child;
                        break; // Only one sibling per element
                    } else if ($child === $e)
                        $search = TRUE;
                }
            }
        }
        return $a;
    }

    private function getElementsExcept($tag_name, array $elements)
    {
        $a = [];
        foreach ($elements as $e) {
            if (strtolower($e->tagName) != strtolower($tag_name))
                $a[] = $e;
        }
        return $a;
    }

    private function getEmptyElements(array $elements)
    {
        $a = [];
        
        foreach ($elements as $e) {
            
            if (! $e->hasChildNodes()) {
                $a[] = $e;
                continue;
            }
            
            // Traverse children
            foreach ($e->childNodes as $child) {
                if ($child instanceof DOMText || $child instanceof DOMElement)
                    continue 2; // Not empty
            }
            
            $a[] = $e;
        }
        
        return $a;
    }

    private function getElementsContainingAttrVal($attr, $val, array $elements)
    {
        $a = [];
        
        foreach ($elements as $e) {
            if ($e->hasAttribute($attr) && substr_count($e->getAttribute($attr), $val) > 0)
                $a[] = $e;
        }
        
        return $a;
    }

    private function getOnlyChildElements(array $elements)
    {
        $a = [];
        
        foreach ($elements as $e) {
            
            if ($e->parentNode instanceof DOMElement) {
                
                foreach (Html::getChildren($e->parentNode, Html::ELEMENT) as $child) {
                    
                    if ($child instanceof DOMElement && $child !== $e) {
                        // Not the only child
                        continue 2;
                    }
                }
                
                // Only child
                $a[] = $e;
            }
        }
        
        return $a;
    }

    private function getOnlyTypeElements(array $elements)
    {
        $a = [];
        foreach ($elements as $e) {
            if ($e->parentNode instanceof DOMElement) {
                $type = $e->tagName;
                foreach ($e->parentNode->childNodes as $child) {
                    if ($child instanceof DOMElement && $child->tagName == $type && $child !== $e)
                        // Not the only child of type
                        continue 2;
                }
                $a[] = $e;
            }
        }
        return $a;
    }

    private function getFirstChildElements(array $elements)
    {
        $a = [];
        
        foreach ($elements as $e) {
            // if ($e->parentNode instanceof DOMElement && $e->parentNode->firstChild === $e)
            // $a[] = $e;
            if ($e->parentNode instanceof DOMElement) {
                foreach ($e->parentNode->childNodes as $child) {
                    if ($child === $e) {
                        $a[] = $e;
                    }
                    
                    // Fail
                    break;
                }
            }
        }
        return $a;
    }

    private function getFirstOfTypeElements(array $elements)
    {
        $a = [];
        foreach ($elements as $e) {
            if ($e->parentNode instanceof DOMElement) {
                $type = strtolower($e->tagName);
                $first = null;
                foreach ($e->parentNode->childNodes as $child) {
                    if ($child instanceof DOMElement && strtolower($child->tagName) == $type) {
                        $first = $child;
                        break;
                    }
                }
                if ($first === $e)
                    $a[] = $e;
            }
        }
        return $a;
    }

    private function getLastChildElements(array $elements)
    {
        $a = [];
        foreach ($elements as $e) {
            if ($e->parentNode instanceof DOMElement && $e->parentNode->lastChild === $e)
                $a[] = $e;
        }
        return $a;
    }

    private function getLastOfTypeElements(array $elements)
    {
        $a = [];
        foreach ($elements as $e) {
            if ($e->parentNode instanceof DOMElement) {
                $type = strtolower($e->tagName);
                $last = null;
                foreach ($e->parentNode->childNodes as $child) {
                    if ($child instanceof DOMElement && strtolower($child->tagName) == $type)
                        $last = $child;
                }
                if ($e === $last)
                    $a[] = $e;
            }
        }
        return $a;
    }

    private function select($tkn, $elements)
    {
        if ($tkn == '*') {
            // All elements
            return $elements;
        } else if (preg_match('/^\#[a-zA-Z0-9\-\_]+/', $tkn)) {
            // ID selector
            return $this->getElementsById(substr($tkn, 1), $elements);
        } else if (preg_match('/^\.[a-zA-Z0-9\-\_]+$/', $tkn)) {
            // Class selector
            return $this->getElementsByClass(substr($tkn, 1), $elements);
        } else if (preg_match('/^[a-zA-Z0-9]+$/', $tkn)) {
            // Element selector
            return $this->getElementsByTag($tkn, $elements);
        } else if ($tkn == ':empty')
            return $this->getEmptyElements($elements);
        else if ($tkn == ':checked')
            return $this->getElementsWithAttr('checked', $elements);
        else if ($tkn == ':unchecked')
            return $this->getElementsWithoutAttr('checked', $elements);
        else if ($tkn == ':disabled')
            return $this->getElementsWithAttr('disabled', $elements);
        else if ($tkn == ':enabled')
            return $this->getElementsWithoutAttr('disabled', $elements);
        else if ($tkn == ':first-child')
            return $this->getFirstChildElements($elements);
        else if ($tkn == ':last-child')
            return $this->getLastChildElements($elements);
        else if ($tkn == ':first-of-type')
            return $this->getFirstOfTypeElements($elements);
        else if ($tkn == ':last-of-type')
            return $this->getLastOfTypeElements($elements);
        else if ($tkn == ':only-child')
            return $this->getOnlyChildElements($elements);
        else if ($tkn == ':required')
            return $this->getElementsWithAttr('required', $elements);
        else if ($tkn == ':optional')
            return $this->getElementsWithoutAttr('required', $elements);
        else if ($tkn == ':read-only')
            return $this->getElementsWithAttr('read-only', $elements);
        else if ($tkn == ':read-write')
            return $this->getElementsWithoutAttr('read-only', $elements);
        else if ($attr = $this->parseAttributeToken($tkn)) {
            // Attribute
            $name = $attr[0];
            $op = $attr[1];
            $val = $attr[2];
            switch ($op) {
                case '=':
                    return $this->getElementsWithAttrVal($name, $val, $elements);
                // TODO Finish other selectors
                case '~=':
                
                case '$=':
                
                case '^=':
                
                case '*=':
                
                case '|=':
                
                case '!=':
                    return $this->getElementsWithoutAttrVal($name, $val, $elements);
                case '':
                    return $this->getElementsWithAttr($name, $elements);
                default:
                // Fail
            }
        }
        return null; // Fail
    }

    private function findFunc($selector, $target)
    {
        if ($tokens = Css::tokenize($selector)) {
            
            $elements = $this->getElements($target); // Get all elements
            $result = [];
            $prev = null;
            $size = count($tokens);
            
            for ($i = 0; $elements && $i < $size; $i ++) {
                $tkn = $tokens[$i];
                
                switch ($tkn) {
                    case ',':
                        if ($i + 1 >= $size)
                            return null; // Fail
                        $result = array_merge($result, $elements);
                        $elements = $this->getElements($target); // Get all elements (reset)
                        break;
                    case ' ': // Decendent
                        if ($i + 1 >= $size)
                            return null; // Fail
                        $elements = $this->getDecendents($elements);
                        break;
                    case '>': // Children
                        if ($i + 1 >= $size)
                            return null; // Fail
                        $elements = $this->getChildElements($elements);
                        $i ++; // To expected selector
                        $tkn = $tokens[$i];
                        $elements = $this->select($tkn, $elements);
                        break;
                    case '~': // All B preceding A
                        if ($i + 1 >= $size)
                            return null; // Fail
                        $i ++; // To expected selector
                        $tkn = $tokens[$i];
                        $elements = $this->getElementsPreceding($elements);
                        $elements = $this->select($tkn, $elements);
                        break;
                    case '+': // B immediately after A
                        if ($i + 1 >= $size)
                            return null; // Fail
                        $i ++; // To expected selector
                        $tkn = $tokens[$i];
                        // print( count( $elements ) . "\n" );
                        $elements = $this->getElementImmediatelyAfter($elements);
                        // print( count( $elements ) . "\n" );
                        $elements = $this->select($tkn, $elements);
                        // print( count( $elements ) . "\n" );
                        break;
                    default:
                        // Element, ID, and class selectors
                        $elements = $this->select($tkn, $elements);
                }
                $prev = $tkn;
            }
            $result = array_merge($result, $elements); // Merge remaining elements
            return $result;
        } else
            return null;
    }

    private function htmlString($glue = '', $process = false)
    {
        if (! $this->dom) {
            // Handle group or element
            $doc = $this->ownerDocument();
            
            if (! $doc)
                $doc = new \DOMDocument();
            
            $html = '';
            
            // Elements
            foreach ($this->data as $element) {
                if ($html)
                    $html .= $glue;
                
                if ($this->minify)
                    $html .= Html::minify($element);
                else
                    $html .= $doc->saveHTML($element);
            }
            
        } else {
            if ($this->minify) {
                $html = Html::minify($this->dom);
            } else {
                $dom = $this->dom;
                $html = $dom->saveHTML();
            }
        }
        
        return trim($html);
    }

    private function doNotify()
    {
        if ($this->will_notify === TRUE || (is_int($this->will_notify) && $this->will_notify -- > 0))
            $this->html->update($this->elements);
    }

    public static function decode($html_string, $encoding = 'utf-8')
    {
        $uid = uniqid('htmldecode_');
        $len = strlen($html_string);
        // Prevent automatic wrapping
        $html_string = '<div id="' . $uid . '">' . $html_string . '</div>';
        libxml_use_internal_errors(TRUE);
        $dom = new DOMDocument();
        $dom->strictErrorChecking = FALSE;
        $dom->loadHTML("<?xml encoding='" . $encoding . "' ?>" . $html_string, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $root = $dom->getElementById($uid);
        if ($root) {
            $a = [];
            foreach ($root->childNodes as $child) {
                $a[] = $child;
            }
            return $a;
        } else
            return null; // Fail
    }

    public static function htmlToText($html)
    {
        if (is_string($html))
            $html = Html::decode($html);
        else if ($html instanceof DOMNode)
            $html = [
                $html
            ];
        else
            return null; // Invalid argument
        $text = '';
        foreach ($html as $node) {
            $text .= $node->textContent;
        }
        return $text;
    }

    public static function getChildren(DOMNode $node, $filter_type = Html::NODE)
    {
        $options = array_slice(func_get_args(), 1);
        $a = [];
        if (@$node->hasChildNodes()) {
            foreach (@$node->childNodes as $child) {
                if ($child instanceof DOMText && in_array(Html::TEXT, $options) || $child instanceof DOMAttr && in_array(Html::ATTRIBUTE, $options) || $child instanceof DOMElement && in_array(Html::ELEMENT, $options) || $child instanceof DOMComment && in_array(Html::COMMENT, $options) || $child instanceof DOMNode && in_array(Html::NODE, $options)) {
                    $a[] = $child;
                    if (! in_array(Html::CHILDREN_NO_NESTED, $options)) {
                        $a = array_merge($a, call_user_func_array([
                            'Hologram\Web\Html',
                            'getChildren'
                        ], array_merge([
                            $child
                        ], $options)));
                    }
                }
            }
        }
        
        return $a;
    }

    public static function minify($html)
    {
        $options = array_slice(func_get_args(), 1);
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->strictErrorChecking = false;
        $dom->formatOutput = false;
        $dom->standalone = true;
        $uid = null;
        libxml_use_internal_errors(true);
        
        if (is_string($html) && in_array(HTML_MINIFY_RETURN_DOM, $options))
            @$dom->loadHTML($html);
        else if (is_string($html)) {
            if (! preg_match('/^[\s\n]*\<!DOCTYPE.*\>/', $html) && ! preg_match('/^[\s\n]*\<html.*\>/', $html)) {
                // Value is not a document so apply hack
                $uid = uniqid('html.min_');
                $html = '<div id="' . $uid . '">' . $html . '</div>'; // Remove div later
            }
            @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } else if ($html instanceof DOMDocument)
            @$dom->loadHTML($html->saveHTML()); // Clone document
        else if ($html instanceof DOMElement)
            $dom->importNode($html->cloneNode(true), true);
        
        // Remove comments
        foreach ($dom->getElementsByTagName('*') as $element) {
            foreach ($element->childNodes as $child) {
                if ($child instanceof DOMComment) {
                    // Only remove whitespace not associated with an IE query
                    if (preg_match('/^[\s\n]*\[if/', $child->nodeValue)) {
                        // Minify comment value (assume it is html)
                        // TODO Clean up and minify properly
                        $child->nodeValue = trim(preg_replace('/[\n]+/', '', $child->nodeValue));
                        // $child->nodeValue = Html::minify( trim( $child->nodeValue ) );
                    } else
                        $child->parentNode->removeChild($child);
                }
            }
        }
        
        // Remove whitespace
        foreach ($dom->getElementsByTagName('*') as $element) {
            foreach ($element->childNodes as $child) {
                if ($child instanceof DOMElement) {
                    //if ($child->hasAttribute('style'))
                    //    $child->setAttribute('style', Css::minify($child->getAttribute('style')));
                    continue;
                } else if ($child instanceof DOMText) {
                    if ($child->parentNode instanceof DOMElement) {
                        switch ($child->parentNode->tagName) {
                            case 'pre':
                                // Minimize whitespace if parent is not a <pre> tag
                                if (! in_array(HTML_MINIFY_NO_SKIP_PRE, $options))
                                    continue;
                            case 'style':
                                // Minify style content (CSS)
                                //if ($child->parentNode->getAttribute('data-html-minify') == 'true') {
                                //    $child->nodeValue = CSS::minify($child->nodeValue);
                                //    $child->parentNode->removeAttribute('data-html-minify');
                                //}
                                continue;
                            case 'script':
                                // Minify script content if JSON or JavaScript
                                //if (preg_match('/(javascript|json)/', $child->parentNode->getAttribute('type')))
                                    //$child->nodeValue = JavaScript::minify($child->nodeValue);
                                continue;
                            default:
                            // Do nothing
                        }
                        $val = trim($child->nodeValue, "\n");
                        $first_char = $child->nodeValue ? $child->nodeValue[0] : '';
                        $last_char = $child->nodeValue ? $child->nodeValue[strlen($child->nodeValue) - 1] : '';
                        // Assume element contains text but minimize whitespace
                        $child->nodeValue = implode(' ', ArrayUtil::remove('', preg_split('/[\s\n]+/m', $child->nodeValue)));
                        // Perserve whitespace on elements with nested text
                        if ($first_char == ' ' && preg_match('/[^\s\n]/', $child->nodeValue) && $child->previousSibling instanceof DOMElement && Html::containsText($child->previousSibling))
                            $child->nodeValue = ' ' . $child->nodeValue; // Restore intended whitespace
                        if ($last_char == ' ' && $child->nextSibling instanceof DOMElement && (! in_array(strtolower($child->nextSibling->tagName), array(
                            'input',
                            'select',
                            'textarea'
                        )) && Html::containsText($child->nextSibling)))
                            $child->nodeValue .= ' '; // Restore intended whitespace
                    }
                }
            }
        }
        if ($uid) {
            $root = $dom->getElementById($uid);
            $html = '';
            foreach ($root->childNodes as $node) {
                $html .= $dom->saveHTML($node);
            }
            return $html; // Minified html
        }
        
        if (in_array(HTML_MINIFY_RETURN_DOM, $options))
            return $dom;
        
        $html = $dom->saveHTML();
        
        if (preg_match('/^\<\!doctype(.)*\>[\n]+/i', $html)) {
            // Remove linefeed after HTML <!DOCTYPE>
            $html = preg_replace('/[\n]+/', '', $html, 1);
        }
        
        trim($html);
        return $html;
    }

    /**
     * Loads a file assumed to be HTML.
     * Optionally parses variables in the loaded HTML file.
     *
     * @param string $src
     * @param array $data
     * @return HTML
     */
    public function loadFile($src, array $data = null)
    {
        return $this->loadFunc($src, $data);
    }

    public function loadFileOn($selector, $src, array $data = null)
    {
        $html_string = $this->loadFunc($src, $data);
        return $this->loadOn($selector, $html_string, $data);
    }

    public function load($html_string, array $data = null)
    {
        $options = array_slice(func_get_args(), 2);
        if (! is_string($html_string))
            throw new \Exception('Argument one is expected to be a string.');
        if ($data)
            $html_string = TemplateParser::parse($html_string, $data);
        // Decode html string for parsing
        $nodes = HTML::decode($html_string);
        // Import nodes
        $this->autoImport($nodes);
        $this->parseDOM($this->dom);
        return $this;
    }

    public function loadOn($selector, $html_string, array $data = null)
    {
        $grp = $this->find($selector);
        $options = array_slice(func_get_args(), 2);
        if ($data)
            $html_string = TemplateParser::parse($html_string, $data);
        // Decode html string for parsing
        $nodes = HTML::decode($html_string);
        // Import nodes
        foreach ($nodes as $node) {
            $grp->append($node);
        }
        $this->parseDOM($this->dom);
        return $this;
    }

    public function save($glue = '')
    {
        return $this->htmlString($glue);
    }

    public function willAutoImport($tag_name)
    {
        $options = array_slice(func_get_args(), 1);
        foreach (array_keys($this->auto_import) as $parent_tag) {
            if (HTML::isValidImportTag($parent_tag)) {
                $val = $this->auto_import[$parent_tag];
                if ($val == '*' || (is_array($val) && in_array($tag_name, $val)))
                    return in_array(HTML_AUTO_IMPORT_GET_TAG_NAME, $options) ? $parent_tag : TRUE;
            }
        }
        return FALSE;
    }

    public function autoImport($node)
    {
        if ($node instanceof DOMNode)
            $node = array(
                $node
            );
        if (is_array($node)) {
            $arr = $node;
            foreach ($arr as $node) {
                if ($node instanceof DOMElement) {
                    $tag_name = $this->willAutoImport($node->tagName, HTML_AUTO_IMPORT_GET_TAG_NAME);
                    if ($tag_name && $this->contains('head'))
                        $this->find($tag_name)->append($node);
                    else
                        $this->find('body')->append($node);
                } else if ($this->contains('body'))
                    $this->find('body')->append($node);
                else if ($this->dom->documentElement) {
                    $node = $this->dom->importNode($node, TRUE);
                    $this->dom->documentElement->appendChild($node);
                }
            }
        }
        return $this;
    }

    public function find($selector)
    {
        if (is_array($this->data)) {
            $result = [];
            foreach ($this->data as $element) {
                $tokens = $this->findFunc($selector, $element);
                if ($tokens)
                    $results = array_merge($result, $tokens);
            }
        } else if ($this->data instanceof DOMDocument || $this->data instanceof DOMElement)
            $result = $this->findFunc($selector, $this->data);
        else
            $result = []; // Fail
        
        return new Html($result, $this);
    }

    public function contains($selector)
    {
        return count($this->find($selector)->$data) > 0;
    }

    /**
     * Returns the DOMDocument associated with this Html instance.
     *
     * @return unknown|boolean|null
     */
    public function ownerDocument()
    {
        if (is_array($this->data)) {
            foreach ($this->data as $element) {
                if ($element->ownerDocument)
                    return $element->ownerDocument;
                else
                    break; // Fail
            }
        } else if ($this->data instanceof DOMElement)
            return $this->data->ownerDocument;
        else if ($this->data instanceof DOMDocument)
            return FALSE;
        return null;
    }

    public function owner()
    {
        $parent = $this->parent;
        
        while ($parent && $parent->parent)
            $parent = $parent->parent;
        
        return $parent;
    }

    public function parent()
    {
        return $this->parent;
    }

    /**
     * Returns the Html instance representing the <code>html</code> element.
     *
     * @return \Hologram\Web\Html|\Hologram\Web\HTML
     */
    public function document()
    {
        return $this->type == Html::DOCUMENT ? $this->find('html') : new Html(null, $this);
    }

    public function lang($lang = null)
    {
        if (func_num_args() > 0) {
            $this->lang = (string) $lang;
            return $this;
        }
        
        return $this->lang;
    }

    public function head()
    {
        return $this->type == Html::DOCUMENT ? $this->find('html > head') : new Html(null, $this);
    }

    public function body()
    {
        return $this->type == Html::DOCUMENT ? $this->find('html > body') : new Html(null, $this);
    }

    public function charset($charset = null)
    {
        if (func_num_args() == 0)
            return $this->find('html > head > meta[charset]')->attr('charset');
        else {
            if (is_string($charset)) {
                if (! $this->contains('html > head > meta[charset]'))
                    $this->find('html > head')->prepend('<meta charset="' . $charset . '" />');
                else
                    $this->find('html > head > meta[charset]')->attr('charset', $charset);
            }
        }
        
        return $this;
    }

    public function meta($name = null, $content = null)
    {
        if ($name == 'charset')
            return ! is_null($content) ? $this->charset($content) : $this->charset();
        
        if (func_num_args() > 1) {
            $tag = $this->find('html > head > meta[name=' . $name . ']');
            
            if ($tag->numMembers() == 0) {
                
                $meta = $this->find('html > head > meta');
                
                if ($meta->numMembers() > 0) {
                    // Insert after last meta tag
                    $meta->last()->insertAfter('<meta name="' . $name . '" content="' . $content . '" />');
                } else {
                    
                    $title = $this->find('html > head > title');
                    
                    if ($title->numMembers() > 0) {
                        // Insert before document title
                        $title->insertBefore('<meta name="' . $name . '" content="' . $content . '" />');
                    } else {
                        // Append to document head (default)
                        $this->find('html > head')->append('<meta name="' . $name . '" content="' . $content . '" />');
                    }
                }
            } else {
                // Update value
                $tag->attr('content', $content);
            }
            
            return $this;
        } else if (func_num_args() == 1) {
            return $this->find('html > head > meta[name=' . $name . ']')->attr('content');
        } else {
            return $this->find('html > head > meta');
        }
    }

    public function keywords($keywords = null)
    {
        if (func_num_args() > 0) {
            $args = [];
            
            foreach (func_get_args() as $a) {
                $t = explode(',', $a);
                foreach ($t as $argument) {
                    $argument = trim($argument);
                    if (! in_array($argument, $args))
                        $args[] = $argument;
                }
            }
            
            $this->meta('keywords', implode(',', $args));
            return $this;
        } else {
            $val = $this->meta('keywords');
            
            if ($val)
                return explode(',', $val);
            else
                return [];
        }
    }

    public function description($description = null)
    {
        if (func_num_args() > 0) {
            $this->find('html > head > meta[name=description]')->attr('content', $description);
            return $this;
        }
        
        return $this->find('html > head > meta[name=description]')->attr('content');
    }

    public function title($title = null)
    {
        if (func_num_args() == 0) {
            if ($this->dom) {
                // TODO Get meta charset tag
                return $this->find('html > head > title')->text();
            }
            return $this->title;
        } else {
            if (is_string($title)) {
                if ($this->dom) // Update title
{
                    $tag = $this->find('html > head > title');
                    
                    // Update title tag or create title tag if it does not exist
                    if ($tag->numMembers() > 0)
                        $tag->text($title);
                    else
                        $this->find('html > head')->append('<title>' . $title . '</title>');
                }
                
                $this->title = $title;
            }
            
            return $this;
        }
    }

    public function robotsIndex($index = null)
    {
        $robots = $this->find('html > meta[name=robots]');
        
        if (func_num_args() > 0) {
            if ($robots->numMembers() == 0) {
                $this->meta('robots', $index ? 'index' : '');
            }
            
            $content = $robots->attr('content');
            if ($robots)
                return $this;
        }
        
        $value = FALSE;
        
        if ($robots->numMembers() > 0) {
            $content = $robots->attr('content');
            $value = substr_count('index', $content) > 0;
        }
        
        return $value;
    }

    public function id($id = null)
    {
        if (is_array($this->data)) {
            
            if (func_num_args() == 0) {
                
                foreach ($this->data as $e) {
                    return $e->getAttribute('id');
                }
                
                return null;
            } else {
                
                foreach ($this->data as $e) {
                    $e->setAttribute('id', $id);
                }
                
                $this->doNotify();
            }
        }
        return $this;
    }

    public function name($name)
    {
        if (is_array($this->data)) {
            
            if (func_num_args() == 0) {
                
                foreach ($this->data as $e) {
                    
                    return $e->getAttribute('name');
                }
                
                return null;
            } else {
                
                foreach ($this->data as $e) {
                    $e->setAttribute('name', $name);
                }
                
                $this->doNotify();
            }
        }
        return $this;
    }

    public function attr($attr = null, $val = null)
    {
        if (is_array($this->data)) {
            
            if (func_num_args() == 1) {
                
                foreach ($this->data as $e) {
                    
                    return $e->getAttribute($attr);
                }
                
                return null;
            } else if (func_num_args() > 1) {
                
                foreach ($this->data as $e) {
                    
                    $e->setAttribute($attr, $val);
                }
                
                return $this;
            } else {
                
                $a = [];
                
                foreach ($this->data as $e) {
                    
                    foreach ($e->attributes as $attr) {
                        $name = $attr->name;
                        
                        if (! array_key_exists($name, $a))
                            $a[$name] = $attr->value;
                    }
                }
                
                return $a;
            }
        }
        
        return $this;
    }

    public function hasAttr($attr)
    {
        if (is_array($this->data)) {
            
            foreach ($this->data as $e) {
                
                return $e->hasAttribute($attr);
            }
        }
        
        return false;
    }

    public function removeAttr($attr)
    {
        if (is_array($this->data)) {
            
            $attributes = func_get_args();
            
            foreach ($attributes as $attr) {
                
                foreach ($this->data as $e) {
                    
                    $e->removeAttribute($attr);
                }
            }
            
            $this->doNotify();
        }
        
        return $this;
    }

    public function data($name, $value = null)
    {
        if (func_num_args() > 1) {
            $this->attr('data-' . $name, $value);
            return $this;
        } else {
            return $this->attr('data-' . $name);
        }
    }

    public function addClass($class)
    {
        if (is_array($this->data)) {
            
            $classes = func_get_args();
            
            foreach ($this->data as $e) {
                
                $a = explode(' ', $e->getAttribute('class'));
                
                foreach ($classes as $class) {
                    if (! in_array($class, $a))
                        $a[] = $class;
                }
                
                $a = ArrayUtil::remove('', $a);
                $e->setAttribute('class', implode(' ', $a));
            }
            
            $this->doNotify();
        }
        
        return $this;
    }

    public function hasClass($class)
    {
        if (is_array($this->data)) {
            
            if (! $class)
                return FALSE;
            foreach ($this->data as $e) {
                return in_array($class, explode(' ', $e->getAttribute('class')));
            }
        }
        return FALSE;
    }

    public function swapClass($class)
    {
        if (is_array($this->data)) {
            
            // TODO Support variable args
            if (! $class)
                return $this;
            foreach ($this->data as $e) {
                $a = explode(' ', $e->getAttribute('class'));
                if (in_array($class, $a))
                    $a = ArrayUtil::remove($class, $a);
                else
                    $a[] = $class;
                $a = ArrayUtil::remove('', $a);
                $e->setAttribute('class', implode(' ', $a));
            }
            $this->doNotify();
        }
        return $this;
    }

    public function removeClass($class)
    {
        if (is_array($this->data)) {
            
            $classes = func_get_args();
            foreach ($this->data as $e) {
                $a = explode(' ', $e->getAttribute('class'));
                foreach ($classes as $class) {
                    if (in_array($class, $a))
                        $a = ArrayUtil::remove($class, $a);
                }
                $a = ArrayUtil::remove('', $a);
                $e->setAttribute('class', implode(' ', $a));
            }
            $this->doNotify();
        }
        
        return $this;
    }

    public function val($val = null)
    {
        if (! is_array($this->data))
            return null;
        
        if (func_num_args() > 0) {
            $val = ! is_null($val) ? (string) $val : null;
            foreach ($this->data as $e) {
                $tag_name = strtolower($e->tagName);
                switch ($tag_name) {
                    case 'select':
                        $children = Html::getChildren($e, Html::ELEMENT);
                        foreach ($children as $child) {
                            if (strtolower($child->tagName) == 'option') {
                                if (! is_null($val) && $child->hasAttribute('value') && $child->getAttribute('value') == $val)
                                    $child->setAttributeNode(new DOMAttr('selected'));
                                else
                                    $child->removeAttribute('selected');
                            }
                        }
                        break;
                    case 'textarea':
                        $e->nodeValue = $val ? $val : ''; // Null removes inner text
                        break;
                    case 'button':
                    case 'input':
                    default:
                        if (! is_null($val))
                            $e->setAttribute('value', $val);
                        else
                            $e->removeAttribute('value'); // null removes attribute
                }
            }
            $this->doNotify();
            return $this;
        } else {
            foreach ($this->data as $e) {
                $tag_name = strtolower($e->tagName);
                switch ($tag_name) {
                    case 'select':
                        $children = Html::getChildren($e, Html::ELEMENT);
                        foreach ($children as $child) {
                            if (strtolower($child->tagName) == 'option') {
                                if ($child->hasAttribute('selected'))
                                    return $child->getAttribute('value');
                            }
                        }
                        // Try again but this time assume the first option is the one with is selected
                        foreach ($children as $child) {
                            if (strtolower($child->tagName) == 'option') {
                                return $child->getAttribute('value');
                            }
                        }
                        return null;
                    case 'textarea':
                        return $e->nodeValue;
                    case 'button':
                    case 'input':
                    default:
                        return $e->hasAttribute('value') ? $e->getAttribute('value') : null;
                }
                return null; // Fail
            }
        }
        
        // Unreachable
    }

    public function required($val = null)
    {
        if (! is_array($this->data))
            return FALSE;
        
        if (func_num_args() > 0) {
            
            foreach ($this->data as $e) {
                if (! $e->hasAttribute('required'))
                    $e->setAttributeNode(new DOMAttr('required'));
            }
            
            return $this;
        } else {
            
            foreach ($this->data as $e) {
                return $e->hasAttribute('required');
            }
            
            return FALSE;
        }
    }

    public function multiple($val = null)
    {
        if (is_array($this->data))
            return FALSE;
        
        if (func_num_args() > 0) {
            foreach ($this->data as $e) {
                if (! $e->hasAttribute('multiple'))
                    $e->setAttributeNode(new DOMAttr('multiple'));
            }
            return $this;
        } else {
            foreach ($this->data as $e) {
                return $e->hasAttribute('multiple');
            }
            return FALSE;
        }
    }

    public function checked($val = null)
    {
        if (! is_array($this->data))
            return FALSE;
        
        if (func_num_args() > 0) {
            if (! is_array($val) && (is_bool($val) || is_null($val) || ! is_string($val))) {
                foreach ($this->data as $e) {
                    $tag_name = strtolower($e->tagName);
                    $type = strtolower(trim($e->getAttribute('type')));
                    if ($tag_name == 'input' && ($type == 'radio' || $type == 'checkbox')) {
                        if ($val)
                            $e->setAttributeNode(new DOMAttr('checked'));
                        else
                            $e->removeAttribute('checked'); // Uncheck
                    }
                }
            } else {
                // Value is string or array (check all in group that contain the following value and uncheck all that do not)
                if (is_string($val)) {
                    foreach ($this->data as $e) {
                        $tag_name = strtolower($e->tagName);
                        $type = strtolower(trim($e->getAttribute('type')));
                        if ($tag_name == 'input' && ($type == 'radio' || $type == 'checkbox')) {
                            if ($e->getAttribute('value') == $val)
                                $e->setAttributeNode(new DOMAttr('checked'));
                            else
                                $e->removeAttribute('checked'); // Uncheck
                        }
                    }
                } else if (is_array($val)) {
                    foreach ($this->data as $e) {
                        $tag_name = strtolower($e->tagName);
                        $type = strtolower(trim($e->getAttribute('type')));
                        if (! $input_only || ($tag_name == 'input' && ($type == 'radio' || $type == 'checkbox'))) {
                            foreach ($val as $v) {
                                if ($e->getAttribute('value') == (string) $v)
                                    $e->setAttributeNode(new DOMAttr('checked'));
                                else
                                    $e->removeAttribute('checked'); // Uncheck
                            }
                        }
                    }
                }
            }
            return $this;
        } else {
            foreach ($this->data as $e) {
                return $e->hasAttribute('checked');
            }
            return FALSE; // Default
        }
    }

    /**
     *
     * @param mixed $val
     * @return Html|number|null
     */
    public function width($val = null)
    {
        if (! is_array($this->data))
            return FALSE;
        
        if (func_num_args() > 0) {
            foreach ($this->data as $e) {
                $e->setAttribute('width', (string) $val);
            }
            return $this;
        } else {
            foreach ($this->data as $e) {
                if ($e->hasAttribute('width'))
                    return is_numeric($e->getAttribute('width')) ? (double) $e->getAttribute('width') : $e->getAttribute('width');
            }
            return null;
        }
    }

    /**
     *
     * @param mixed $val
     * @return Html|number|null
     */
    public function height($val = null)
    {
        if (! is_array($this->data))
            return FALSE;
        
        if (func_num_args() > 0) {
            foreach ($this->data as $e) {
                $e->setAttribute('height', (string) $val);
            }
            return $this;
        } else {
            foreach ($this->data as $e) {
                if ($e->hasAttribute('height'))
                    return is_numeric($e->getAttribute('height')) ? (double) $e->getAttribute('height') : $e->getAttribute('height');
            }
            return null;
        }
    }

    /**
     * Removes elements from their parents.
     *
     * @return Html
     */
    public function remove()
    {
        if (is_array($this->data)) {
            foreach ($this->data as $e) {
                if ($e->parentNode instanceof DOMElement) {
                    foreach ($e->childNodes as $child) {
                        $e->removeChild($child);
                    }
                    $e->parentNode->removeChild($e);
                }
            }
            $this->data = [];
            $this->doNotify();
        }
        return $this;
    }

    public function insertBefore($val)
    {
        if (is_array($this->data)) {
            if (is_string($val))
                $val = Html::decode($val);
            else if ($val instanceof DOMNode)
                $val = array(
                    $val
                );
            else if ($val instanceof Html)
                $val = $val->data;
            
            if (is_array($val)) {
                // $val = array_reverse( $val ); // Reverse order
                foreach ($this->data as $e) {
                    // Insert nodes before
                    if ($e->parentNode instanceof DOMElement) {
                        $first = null;
                        foreach ($val as $node) {
                            $clone = $e->ownerDocument ? $e->ownerDocument->importNode($node, TRUE) : $node->cloneNode(TRUE);
                            // Insert before nodes
                            if ($first) {
                                // if ( $first->nextSibling )
                                $first->parentNode->insertBefore($clone, $first->nextSibling);
                                // else
                                // $first->parentNode->appendChild( $clone );
                            } else {
                                $first = $clone;
                                $e->parentNode->insertBefore($clone, $e);
                            }
                        }
                    }
                }
            }
        }
        
        return $this;
    }

    public function insertAfter($val)
    {
        if (is_array($this->data)) {
            if (is_string($val))
                $val = Html::decode($val);
            else if ($val instanceof DOMNode)
                $val = array(
                    $val
                );
            else if ($val instanceof Html)
                $val = $val->data;
            
            if (is_array($val)) {
                foreach ($this->data as $e) {
                    // Insert nodes after
                    if ($e->parentNode instanceof DOMElement) {
                        foreach ($val as $node) {
                            $clone = $e->ownerDocument ? $e->ownerDocument->importNode($node, TRUE) : $node->cloneNode(TRUE);
                            if ($e->nextSibling)
                                // Element has node after
                                $e->parentNode->insertBefore($clone, $e->nextSibling);
                            else
                                // Element is last in child node list
                                $e->parentNode->appendChild($clone);
                        }
                    }
                }
            }
        }
        
        return $this;
    }

    /**
     *
     * @param string|DOMNode|array $val
     * @return Html
     */
    public function append($val, $return_new_elements = FALSE)
    {
        if (is_array($this->data)) {
            
            if (is_string($val))
                $val = Html::decode($val);
            else if ($val instanceof DOMNode)
                $val = [
                    $val
                ];
            else if ($val instanceof Html)
                $val = $val->data;
            
            if (is_array($val)) {
                foreach ($this->data as $e) {
                    // Append nodes
                    foreach ($val as $node) {
                        $clone = $e->ownerDocument ? $e->ownerDocument->importNode($node, TRUE) : $node->cloneNode(TRUE);
                        $e->appendChild($clone);
                    }
                }
            }
        }
        
        return $this;
    }

    /**
     *
     * @param string|DOMNode|array $val
     * @return Html
     */
    public function prepend($val)
    {
        if (is_array($this->data)) {
            
            if (is_string($val))
                $val = Html::decode($val); // Returns an array
            else if ($val instanceof \DOMNode)
                $val = array(
                    $val
                );
            else if ($val instanceof Html)
                $val = $val->data;
            
            if (is_array($val)) {
                foreach ($this->data as $e) {
                    $a = [];
                    // Remove but save children in order
                    foreach ($e->childNodes as $child) {
                        $a[] = $child;
                        $e->removeChild($child);
                    }
                    // Prepend nodes
                    foreach ($val as $node) {
                        $clone = $e->ownerDocument ? $e->ownerDocument->importNode($node, TRUE) : $node->cloneNode(TRUE);
                        $e->appendChild($clone);
                    }
                    // Add childs back in
                    foreach ($a as $child) {
                        $clone = $e->ownerDocument ? $e->ownerDocument->importNode($child, TRUE) : $child->cloneNode(TRUE);
                        $e->appendChild($clone);
                    }
                }
            }
        }
        
        return $this;
    }

    /**
     *
     * @param string|DOMNode|array $val
     * @return Html
     */
    public function html($val)
    {
        if (is_array($this->data)) {
            $this->removeChildren();
            $this->append($val);
        }
        
        return $this;
    }

    public function tag()
    {
        if (is_array($this->data)) {
            foreach ($this->data as $element) {
                return $element->tagName;
            }
        }
        
        return null;
    }

    /**
     *
     * @param string $val
     * @return string|Html
     */
    public function text($val = null)
    {
        if (is_array($this->data)) {
            
            if (func_num_args() == 0) {
                foreach ($this->data as $e) {
                    return $e->nodeValue;
                }
                return null;
            } else if (is_string($val)) {
                
                foreach ($this->data as $e) {
                    // Remove children
                    if ($e->childNodes->length > 0) {
                        foreach ($e->childNodes as $child) {
                            $e->removeChild($child);
                        }
                    }
                    
                    $e->nodeValue = $val; // Automatically encoded
                }
            }
        }
        
        return $this;
    }

    /**
     *
     * @return string
     */
    public function textContent()
    {
        if (is_array($this->data)) {
            foreach ($this->data as $e) {
                return $e->textContent;
            }
        }
        return null;
    }

    public function minified($minified = null)
    {
        if (func_num_args() > 0) {
            $this->minify = $minified;
            return $this;
        }
        
        return $this->minify;
    }

    /**
     *
     * @param string $property
     * @param string $val
     * @return string|Html
     */
    public function style($property = null, $val = null)
    {
        if (is_array($this->data)) {
            
            if (func_num_args() == 1 || is_array($property)) {
                
                foreach ($this->data as $e) {
                    // Override style attribute
                    if (is_string($property)) {
                        $e->setAttribute('style', $property);
                    } else if (is_array($property)) {
                        $a = $e->hasAttribute('style') ? explode(';', preg_replace('/\;+/', ';', $e->getAttribute('style'))) : [];
                        
                        foreach ($property as $key => $val) {
                            // Append style
                            $rule = $key . ':' . $val;
                            $a[] = $rule;
                        }
                        
                        $e->setAttribute('style', implode(';', $a));
                    } else
                        break; // Invalid argument
                }
            } else if (func_num_args() > 1) {
                
                foreach ($this->data as $e) {
                    $a = $e->hasAttribute('style') ? explode(';', preg_replace('/\;+/', ';', $e->getAttribute('style'))) : [];
                    // Append style
                    $rule = $property . ':' . $val;
                    $a[] = $rule;
                    $e->setAttribute('style', implode(';', $a));
                }
            } else {
                
                foreach ($this->data as $e) {
                    
                    return $e->hasAttribute('style') ? $e->getAttribute('style') : '';
                }
                return null;
            }
        }
        
        return $this;
    }

    public function each(callable $callback)
    {
        if ($this->dom)
            $callback($this);
        else if (is_array($this->data)) {
            foreach ($this->data as $element) {
                $callback(new Html($element));
            }
        }
    }

    public function removeChildren()
    {
        foreach ($this->data as $e) {
            foreach ($e->childNodes as $child) {
                $e->removeChild($child);
            }
        }
        return $this;
    }

    public function isEmpty()
    {
        return is_array($this->data) ? count($this->data) == 0 : FALSE;
    }

    public function hasElementChildren()
    {
        if (is_array($this->data)) {
            
            foreach ($this->data as $e) {
                
                foreach ($e->childNodes as $e) {
                    
                    return TRUE;
                }
                
                return TRUE; // Only check first element (single instance only)
            }
        }
        
        return FALSE;
    }

    public function childElements()
    {
        $a = [];
        
        if ($this->dom) {
            foreach ($this->dom->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    if (! in_array($child, $a, true))
                        $a[] = $child;
                }
            }
        } else if (is_array($this->data)) {
            foreach ($this->data as $child) {
                if (! in_array($child, $a, true))
                    $a[] = $child;
            }
        }
        
        if ($group)
            return new Html($a);
    }

    public function hasChildren()
    {
        if (is_array($this->data)) {
            foreach ($this->data as $e) {
                return ! $e->hasChildNodes();
            }
        }
        
        return FALSE;
    }

    public function wrap($val)
    {
        if (is_array($this->data)) {
            
            if (is_string($val))
                $val = Html::decode($val);
            else if ($val instanceof DOMNode)
                $val = array(
                    $val
                );
            else if ($val instanceof Html)
                $val = $val->data;
            
            if (is_array($val)) {
                foreach ($this->data as $e) {
                    if ($e->parentNode) {
                        foreach ($val as $node) {
                            if ($node instanceof DOMAttr || $node instanceof DOMDocument)
                                continue;
                            $clone = $e->ownerDocument ? $e->ownerDocument->importNode($node) : $node->cloneNode(TRUE);
                            $e->parentNode->replaceChild($clone, $e);
                            if ($clone instanceof DOMComment || $clone instanceof DOMText)
                                $clone->nodeValue .= new Html($e, $this); // TODO ?
                            else
                                $clone->appendChild($e);
                        }
                    }
                }
            }
        }
        
        return $this;
    }

    /**
     *
     * @param DOMNode|string|array $element
     * @return Html
     */
    public function wrapAll($element)
    {
        // TODO Fix method or deprecate
        if (is_array($this->data)) {
            if (is_string($element))
                $element = Html::decode($element);
            if ($element instanceof DOMNode)
                $element = array(
                    $element
                );
            else if ($element instanceof Html && $element->element())
                $element = [
                    $element->element()
                ];
            
            if (is_array($element)) {
                $size = count($this->data);
                $clone = $element->cloneNode(TRUE);
                $replaced = FALSE;
                for ($i = 0; $i < $size; $i ++) {
                    $e = $this->data[$i];
                    if (! $replaced && $e->parentNode) {
                        // Set clone as parent
                        $replaced = TRUE;
                        $e->parentNode->replaceChild($clone, $e);
                    }
                    $clone->appendChild($e);
                }
            }
        }
        
        return $this;
    }

    public function index($index)
    {
        if (! is_array($this->data))
            return new Html([]);
        
        return is_int($index) && $index > - 1 && $index < count($this->data) ? new Html($this->data[$index], $this) : null;
    }

    public function first()
    {
        return $this->index(0);
    }

    public function last()
    {
        if (! is_array($this->data))
            return new Html([]);
        
        $count = count($this->data);
        return new Html($this->data[$count - 1]);
    }

    /**
     *
     * @param DOMElement $element
     * @return boolean
     */
    public function in(DOMElement $element)
    {
        return is_array($this->data) ? in_array($element, $this->data, TRUE) : FALSE;
    }

    public function htmlObjectType()
    {
        return $this->type;
    }

    public function isDocument()
    {
        return $this->type == Html::DOCUMENT;
    }

    public function isElement()
    {
        return $this->type == Html::DOMELEMENT;
    }

    public function isGroup()
    {
        return $this->type == Html::GROUP;
    }

    public function numMembers()
    {
        return is_array($this->data) ? count($this->data) : - 1;
    }

    public function members()
    {
        if (! is_array($this->data))
            return [];
        
        $a = [];
        
        foreach ($this->data as $element) {
            $a[] = new Html($element);
        }
        
        return $a;
    }

    public function element()
    {
        if ($this->dom)
            return $this->dom->documentElement;
        else if (is_array($this->data)) {
            
            foreach ($this->data as $element) {
                return $element;
            }
        }
        
        return null;
    }

    public function __toString()
    {
        return $this->htmlString('');
    }
}


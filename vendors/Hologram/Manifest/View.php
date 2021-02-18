<?php
namespace Hologram\Manifest;

use Hologram\Web\Css;
use Hologram\Web\Html;
use Hologram\Web\Form;
use ForceField\Core\Configure;
use Hologram\Obfuscate\Obfuscator;
use ForceField\Utility\StringUtil;
use ForceField\l10n\Lang;

class View
{

    private static $current;

    private $html;

    private $css;

    private $forms;

    public function __construct($contents = NULL)
    {
        View::$current = $this;
        $this->html = new Html($contents);
        $this->init();
    }

    protected function init()
    {
        // Abstract
    }

    protected function run(array $args)
    {
        // Validate forms
        if ($this->loadForms()) { // && ((array_key_exists('form:validate',$args) && $args['form:validate'] !== false) || (!array_key_exists('form:validate', $args) && Configure::readBool('form.autoValidate', false)))) {
            
            $name = array_key_exists('form:submit', $args) ? $args['form:submit'] : null;
            
            foreach ($this->forms as $form) {
                
                if (! $form->validated() && $form->submitted($name)) {
                    
                    if ($form->validate()) {
                        
                        // Handle success
                    } else {
                        // Display errors
                        $this->displayFormErrors($form);
                    }
                } else {
                    $this->displayFormErrors($form);
                }
            }
        }
        
        $prefix = 'data-hologram-';
        
        // Process language keys
        $lang = $this->html->lang();
        
        if (! $lang)
            $lang = Configure::read('lang.default', 'en');
        
        $targets = $this->find('[data-hologram-lang]');
        
        foreach ($targets->members() as $e) {
            $key = $e->attr($prefix . 'lang');
            $val = Lang::read($key);
            
            if ($val)
                $e->text($val);
        }
        
        $all = $this->find('*');
        
        foreach ($all->members() as $element) {
            if ($element->hasAttr('id')) {
                $element->attr('id', Obfuscator::obscure(trim($element->attr('id'))));
            }
            
            if ($element->hasAttr('class')) {
                $classes = explode(' ', trim($element->attr('class')));
                $result = [];
                
                foreach ($classes as $cls) {
                    $result[] = Obfuscator::obscure($cls);
                }
                
                $element->attr('class', implode(' ', $result));
            }
        }
        
        // Remove empty elements
        $this->find('[data-hologram-empty=remove]:empty')->remove();
        
        // Attribute processor
        foreach ($all->attr() as $attr => $val) {
            
            if (StringUtil::startsWith($prefix, $attr)) {
                // Remove hologram-* attributes
                $all->removeAttr($attr);
            }
        }
        
        return $this;
    }

    private function isFormRegistered(\DOMElement $form)
    {
        if ($this->forms) {
            foreach ($this->forms as $f) {
                if (($html = $f->html()) && $html->element() === $f || $form->getAttribute('name') == $f->name())
                    return true;
            }
        }
        
        return false;
    }

    private function loadForms()
    {
        if (! is_array($this->forms))
            $this->forms = []; // Initialize
        
        foreach ($this->find('form')->members() as $f) {
            if (! $this->isFormRegistered($f->element())) {
                // Register form
                $this->forms[] = new Form($f);
            }
        }
        
        return $this->forms;
    }

    private function displayFormErrors(Form $form)
    {
        // Display errors
        foreach ($form->errors() as $field_name => $errors) {
            $container = $this->find("[data-hologram-form-error={$field_name}]");
            
            if ($container->numMembers() > 0) {
                foreach ($errors as $rule => $text) {
                    $container->append($text);
                }
            }
        }
        
        return $this;
    }

    public static function current()
    {
        return View::$current;
    }
    
    public function loadPageData($page, $keywords = null)
    {
        $title = lang('meta.' . $page . '.title', '');
        $description = lang('meta.' . $page . '.description', '');
        $this->html->title($title);
        $this->html->description($description);
        $this->html->keywords($keywords ? $keywords : '');
        
        return $this;
    }
    
    public function link($href, $type)
    {
        $this->find('html > head')->append('<link href="' . $href . '" type="' . $type . '" />');
        return $this;
    }

    public function style($src, $type = 'text/css')
    {
        $this->find('html > head')->append('<link href="' . $src . '" type="' . $type . '" />');
        return $this;
    }

    public function css($css, $selector = 'html > head')
    {
        $stylesheet = Css::parse(file_get_contents(LIBPATH . '/css/' . $css . '.css'));
        $this->find($selector)->append('<style type="text/css">' . $stylesheet . '</style>');
        return $this;
    }

    public function find($selector)
    {
        return $this->html->find($selector);
    }

    public function ajax($selector)
    {
        return AJAX ? $this->find($selector) : $this;
    }

    public function meta($name = null, $content = null)
    {
        if (! is_null($name)) {
            if (! is_null($content)) {
                $this->html->meta($name, $content);
                return $this;
            }
            
            return $this->html->meta($name);
        }
        
        return $this;
    }

    public function keywords($keywords = null)
    {
        if (! is_null($keywords)) {
            call_user_func_array([
                $this->html,
                'keywords'
            ], func_get_args());
            return $this;
        }
        
        return $this->html->keywords();
    }

    public function lang($lang = null)
    {
        if (is_string($lang)) {
            $this->html->lang($lang);
            return $this;
        }
        
        return $this->html->lang();
    }

    /**
     *
     * @param Form|string|int $form
     * @param object $data
     * @param string $selector
     * @return unknown
     */
    public function form($form = null, $data = null, $selector = 'html > body')
    {
        $this->loadForms();
        
        // Look for form
        $target = null;
        $exists = false;
        
        $i = 0;
        
        foreach ($this->forms as $f) {
            if ((is_string($form) && $f->attr('name') == $form) || (is_int($form) && $form == $i) || ($form instanceof Form && $form->compare($f)) || is_null($form)) {
                // Form found
                $exists = true;
                $target = $f;
            }
            
            $i ++;
        }
        
        if (! $target && $form instanceof Form)
            $target = $form;
        
        if ($target && ! $exists) {
            $this->forms[] = $target;
            $this->html->find($selector)->append($target->html());
        }
        
        return $target;
    }

    public function remove($selector)
    {
        $this->html->find($selector)->remove();
        return $this;
    }

    public function html()
    {
        return $this->html;
    }

    public function head()
    {
        return $this->html->head();
    }

    public function body()
    {
        return $this->html->body();
    }

    public function title($title = NULL)
    {
        if (! is_null($title)) {
            $this->html->title($title);
            return $this;
        }
        
        return $this->html->title();
    }

    public function __invoke(array $args = null)
    {
        $this->run(is_array($args) ? $args : []);
        $callbacks = [];
        $callbacks[] = Configure::readCallable('view.callbacks.onRender');
        
        foreach ($callbacks as $plug) {
            if ($plug)
                $plug($this);
        }
        
        return (string) $this->html;
    }

    public function __toString()
    {
        return $this();
    }
}


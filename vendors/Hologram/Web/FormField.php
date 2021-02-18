<?php
namespace Hologram\Web;

use ForceField\Core\Input;
use ForceField\l10n\Lang;
use ForceField\Core\Configure;

class FormField
{

    private $form;

    private $name;

    private $type;

    private $label;

    private $rules;

    private $value;

    private $required;

    private $min_length;

    private $maxlength;

    private $pattern;

    private $step;

    private $multiple;

    private $min;

    private $max;

    private $errors;

    private $label_element;

    private $field_element;

    private $option_elements;

    private $values;

    private $validation_interrupted;

    private $element_type;

    const TEXT = 'text';

    const EMAIL = 'email';

    const BUTTON = 'button';

    const COLOR = 'color';

    const SUBMIT = 'submit';

    const RESET = 'reset';

    const RADIO = 'radio';

    const CHECKBOX = 'checkbox';

    const DATE = 'date';

    const DATETIME_LOCAL = 'datetime-local';

    const MONTH = 'month';

    const NUMBER = 'number';

    const RANGE = 'range';

    const SEARCH = 'search';

    const TELEPHONE = 'tel';

    const TIME = 'time';

    const URL = 'url';

    const WEEK = 'week';

    const TEXTAREA = 'textarea';

    const SELECT = 'select';

    public function __construct(Form $form, $name, $element = NULL)
    {
        $this->form = $form;
        $this->name = $name;
        $this->label = NULL;
        $this->rules = [];
        $this->required = $this->multiple = FALSE;
        $this->errors = [];
        $this->values = [];
        
        if ($element instanceof \DOMElement)
            $this->parseElement($element);
        else if (is_array($element))
            $this->parseMultiple($element);
        else {
            switch ($element) {
                case 'text':
                case 'email':
                case 'password':
                case 'submit':
                case 'reset':
                case 'radio':
                case 'checkbox':
                case 'button':
                case 'color':
                case 'date':
                case 'datetime-local':
                case 'month':
                case 'number':
                case 'range':
                case 'search':
                case 'tel':
                case 'time':
                case 'url':
                case 'week':
                case 'textarea':
                case 'select':
                case 'file':
                    $this->element_type = $element;
                    break;
                case 'text':
                default:
                    $this->element_type = 'text';
            }
        }
    }

    private static function parseRule($rule_string)
    {
        if (preg_match('/^#?[a-zA-Z0-9\_\-]+(\(.*\))?$/', $rule_string)) {
            if ($rule_string[0] == '#')
                $rule_string = substr($rule_string, 1);
            
            $id = '';
            $val = null;
            $len = strlen($rule_string);
            $char = $prev = null;
            
            for ($i = 0; $i < $len; $i ++) {
                
                $prev = $char;
                $char = $rule_string[$i];
                
                if (is_string($val)) {
                    
                    if ($char == ')' && $i + 1 == $len)
                        break; // End
                    else if (($char == '(' || $char == ')') && $prev == '\\')
                        $val[strlen($val) - 1] = $char; // Escaped )
                    else if ($char == ')' || $char == '(')
                        return false; // Fail (Unexpected parenthesis)
                    else
                        $val .= $char;
                } else if ($char == '(') {
                    $val = ''; // Value exists
                    continue;
                } else
                    $id .= $char;
            }
            
            if (is_string($val))
                $val = trim($val);
            
            return [
                'id' => $id,
                'val' => $val
            ];
        }
        
        // Syntax error
        return false;
    }

    private function parseElement(\DOMElement $element)
    {
        $tag_name = strtolower($element->tagName);
        $this->field_element = $element;
        $html = new Html($element);
        $this->type = strtolower(trim($html->attr('type')));
        
        if ($tag_name)
            if ($tag_name == 'select') {
                $this->element_type = 'select';
                $this->type = 'select';
                
                foreach($element->childNodes as $child)
                {
                    if($child instanceof \DOMElement && $child->tagName == 'option')
                    {
                        $this->parseOption($child);
                    }
                }
            } else if ($tag_name == 'textarea') {
                $this->element_type = 'textarea';
                $this->type = 'textarea';
            } else if ($tag_name == 'input' || $tag_name == 'button') {
                $this->element_type = $this->type ? $this->type : 'text';
            }
        
        $name = $html->hasAttr('name') ? $html->attr('name') : null;
        
        // TODO Confirm HTML ignore whitespace in id and name attributes
        if ($name)
            $name = trim($name);
        $id = $html->hasAttr('id') ? $html->attr('id') : null;
        if (! $id && $this->form->autoInsertId()) {
            $id = $name;
            if ($name)
                $id = $name;
            $html->attr('id', $id);
        } else if ($id) {
            $id = trim($id);
            $html->attr('id', $id);
        }
        
        // Required
        if ($html->hasAttr('required')) {
            $this->required = true;
            $this->appendRule('required', $html->attr('required'));
        }
        
        // Multiple
        if ($html->hasAttr('multiple')) {
            $this->multiple = true;
            $this->appendRule('multiple', $html->attr('multiple'));
        }
        
        // Min
        if ($html->hasAttr('min')) {
            $val = $html->attr('min');
            
            if (filter_var($val, FILTER_VALIDATE_INT))
                $val = ((int) $val);
            else if (filter_var($val, FILTER_VALIDATE_FLOAT))
                $val = ((double) $val);
            else
                $val = NaN;
            
            $this->min = $val;
            $this->appendRule('min', $val);
        }
        
        // Max
        if ($html->hasAttr('max')) {
            $val = $html->attr('max');
            
            if (filter_var($val, FILTER_VALIDATE_INT))
                $val = ((int) $val);
            else if (filter_var($val, FILTER_VALIDATE_FLOAT))
                $val = ((double) $val);
            else
                $val = NaN;
            
            $this->max = $val;
            $this->appendRule('max', $val);
        }
        
        // Max Length
        if ($html->hasAttr('maxlength')) {
            $val = $html->attr('maxlength');
            
            if (filter_var($val, FILTER_VALIDATE_INT))
                $val = ((int) $val);
            else if (filter_var($val, FILTER_VALIDATE_FLOAT))
                $val = ((double) $val);
            else
                $val = NaN;
            
            $this->maxlength = $val;
            $this->appendRule('maxlength', $val);
        }
        
        if ($html->hasAttr('pattern')) {
            $val = $html->attr('pattern');
            $this->pattern = $val;
            $this->appendRule('pattern', $val);
        }
        
        $this->value = $html->val(); // Get initial value
        
        if ($tag_name == 'select' && $element->hasAttribute('multiple')) {
            $this->parseMultiple($element->childNodes);
        }
        
        $rules_attr = Configure::readString('form.validation.attribute', 'data-validation');
        
        if ($rule_string = $html->attr($rules_attr)) {
            
            $a = explode('|', $rule_string);
            $rules = [];
            
            foreach ($a as $raw) {
                $rule = trim(str_replace("\n", '', $raw));
                
                if ($rule) {
                    $rules[] = $rule;
                    
                    $rule_data = FormField::parseRule($rule);
                    
                    if ($rule_data)
                        $this->appendRule($rule_data['id'], $rule_data['val']);
                    else
                        throw new Exception('Encountered invalid rule value, "' . $rule . '", on form field "' . $name . '".');
                }
            }
            
            // Client side form validation (leave attribute for JavaScript validation)
            $client_rules = [];
            
            foreach ($rules as $r) {
                if ($r[0] == '#')
                    $client_rules[] = substr($r, 1);
            }
            
            if ($client_rules)
                $html->attr($rules_attr, implode('|', $client_rules));
            else
                $html->removeAttr($rules_attr);
        }
    }

    private function parseMultiple($elements)
    {
        $this->value = []; // Assume multiple values
        $this->option_elements = [];
        
        if (count($elements) > 0) {
            
            $name = $elements[0]->getAttribute('name');
            
            if (! $name)
                return; // Parse failure
            
            foreach ($elements as $e) {
                if ($e->tagName == 'option') {
                    
                    $this->option_elements[] = $e;
                    
                    if ($e->hasAttribute('checked')) {
                        
                        $val = $e->getAttribute('value');
                        $this->value[] = $val;
                    }
                }
            }
        }
    }
    
    private function parseOption(\DOMElement $option)
    {
        $val = $option->getAttribute('value');
        
        if(!in_array($val, $this->values))
            $this->values[] = $val;
        
        if($option->getAttribute('selected') || !$this->values)
            $this->value = $option->nodeValue;
    }

    private function fail($rule_id)
    {
        $text = '';
        $rule = $this->rule($rule_id);
        $rule_arg = $rule ? $rule->arg() : '';
        $validation = Configure::readString('form.validation.langFile', 'validation');
        
        if ($this->name) {
            $label = lang('validation.@' . $this->name . '.label', '');
            
            $text = lang("{$validation}.@{$this->name()}.{$rule_id}", '', [
                'label' => $label ? $label : $this->name,
                'field' => $this->name,
                'value' => $this->val(),
                'arg' => $rule_arg
            ]);
        } else
            $label = $this->label();
        
        if (! $text)
            $text = lang("{$validation}." . $rule_id, $this->name . '-error:' . $rule_id, [
                'label' => $label ? $label : $this->name,
                'field' => $this->name,
                'value' => $this->val(),
                'arg' => $rule_arg
            ]);
        
        $this->errors[$rule_id] = $text;
        return $this;
    }

    public function interrupt()
    {
        $this->validation_interrupted = true;
        return $this;
    }

    public function validate()
    {
        if ($this->type == 'submit' || $this->type == 'reset' || $this->type == 'button')
            return true;
        
        // Traverse rules
        if ($this->hasVal() || $this->hasRule('required')) {
            
            // $this->errors = []; // Reset errors
            $this->validation_interrupted = false;
            $result = false;
            
            if ($this->hasRule('required')) {
                $result = $this->rule('required')->validate();
                
                if ($result === false) {
                    $this->fail('required');
                    return false;
                }
            }
            
            if(is_array($this->values) && ($this->element_type == 'select' || $this->element_type == 'checkbox' || $this->element_type == 'radio'))
            {
                if(!in_array($this->val(), $this->values))
                {
                    $this->fail('value');
                    return false;
                }
            }
            
            $valid = true;
            
            foreach ($this->rules as $rule_id => $rule) {
                
                switch ($rule_id) {
                    case 'required':
                    case 'integer':
                    case 'number':
                        // Skip already handled rules
                        continue;
                    default:
                    // Do nothing
                }
                
                if ($rule_id == 'required')
                    continue; // Already validated
                else if ($this->validation_interrupted)
                    break; // Halt
                
                $result = $rule->validate();
                
                if (is_bool($result)) {
                    if ($result === true)
                        continue;
                    else {
                        // Validation error
                        $valid = false;
                        $this->fail($rule_id);
                    }
                } else {
                    // Update value
                    if (is_null($result))
                        $result = '';
                    
                    $this->value = $result;
                }
            }
            
            return $valid;
        }
        
        return true;
    }

    public function name()
    {
        return $this->name;
    }

    public function label($label = NULL)
    {
        if (func_num_args() > 0) {
            if (is_string($label)) {
                
                $this->label = $label;
                
                if ($this->label_element)
                    (new Html($this->label_element))->html($label);
            } else if ($label instanceof \DOMElement && strtolower($label->tagName) == 'label') {
                $this->label = (new Html($label))->text();
                $this->label_element = $label;
            }
            return $this;
        }
        return $this->label;
    }

    public function appendRule($rule_id, $rule_argument = null)
    {
        // Append new rule or override existing rule
        $this->rules[$rule_id] = new FormRule($this, $rule_id, $rule_argument);
        return $this;
    }

    public function rule($rule_id)
    {
        return array_key_exists($rule_id, $this->rules) ? $this->rules[$rule_id] : null;
    }

    public function hasRule($rule_id)
    {
        return array_key_exists($rule_id, $this->rules);
    }

    public function removeRule($rule_id)
    {
        if (array_key_exists($rule_id, $this->rules))
            unset($this->rules[$rule_id]);
        
        return $this;
    }

    public function clear()
    {
        $this->value = NULL;
        $this->values = [];
        return $this;
    }

    public function form()
    {
        return $this->form;
    }

    public function required($required = NULL)
    {
        if (func_num_args() > 0) {
            $this->required = $required;
            return $this;
        }
        return $this->required;
    }

    public function maxlength($maxlength = NULL)
    {
        if (func_num_args() > 0) {
            
            if (is_int($maxlength))
                $this->maxlength = $maxlength;
            
            return $this;
        }
        
        return $this->maxlength;
    }

    public function pattern($pattern = NULL)
    {
        if (func_num_args() > 0) {
            if (is_string($pattern))
                $this->pattern = $pattern;
            return $this;
        }
        return $this->pattern;
    }

    public function step($step = NULL)
    {
        if (func_num_args() > 0) {
            if (is_double($step))
                $this->step = $step;
            return $this;
        }
        return $this->step;
    }

    public function multiple($multiple = NULL)
    {
        if (is_bool($multiple))
            $this->multiple = $multiple;
        return func_num_args() == 0 ? $this->multiple : $this;
    }

    public function min($val = NULL)
    {
        if (is_int($val) || is_float($val) || is_string($val))
            $this->min = $val;
        return func_num_args() == 0 ? $this->min : $this;
    }

    public function max($val = NULL)
    {
        if (is_int($val) || is_float($val) || is_string($val))
            $this->max = $val;
        return func_num_args() == 0 ? $this->max : $this;
    }

    public function values()
    {
        // All valid values in a list of options or multiple values
        if (func_num_args() > 0) {
            if (! $this->values)
                $this->values = [];
            foreach (func_get_args() as $val) {
                $this->values[] = $val;
            }
            return $this;
        } else {
            if ($this->values) {
                $a = [];
                foreach ($this->values as $v) {
                    $a[] = $v;
                }
                return $a;
            }
            return [];
        }
    }

    public function val($val = NULL)
    {
        if (func_num_args() == 0)
            return $this->value;
        
        if (is_array($this->value)) {
            // Checkbox or radio value
            $this->value = func_get_args(); // Accept a list of values
            if ($this->field_element) {
                foreach ($this->field_element as $input) {
                    $input->removeAttribute('checked');
                }
                foreach ($this->option_elements as $input) {
                    foreach ($this->value as $val) {
                        $val = (string) $val;
                        
                        if ($input->hasAttribute('value') && $input->getAttribute('value') == $val)
                            $input->setAttributeNode(new \DOMAttr('checked'));
                    }
                }
            }
        } else {
            $this->value = $val;
            if ($this->field_element && ! $this->isPassword())
                // Password fields are not populated to avoid sending sensitive data to the browser
                (new Html($this->field_element))->val($val);
        }
        return $this;
    }

    public function isPassword()
    {
        if ($this->field_element)
            return ! is_array($this->field_element) && strtolower($this->field_element->getAttribute('type')) == 'password';
        else {
            // TODO Get field type (integer)
        }
        return FALSE;
    }

    public function hasVal()
    {
        return $this->value != null && $this->value != '';
    }

    public function hasVisibleVal()
    {
        return $this->value != null && trim($this->value) != '';
    }

    public function exists()
    {
        return $this->value != null;
    }

    public function valGet()
    {
        $this->value = Input::get($this->field_name);
        return $this;
    }

    public function valPost()
    {
        $this->value = Input::post($this->field_name);
        return $this;
    }

    public function valGetPost()
    {
        $this->value = Input::getPost($this->field_name);
        return $this;
    }

    public function valPostGet()
    {
        $this->value = Input::postGet($this->field_name);
        return $this;
    }

    public function error($rule_id = null, $error_text = null)
    {
        if (func_num_args() == 1) {
            // Get error by id
            return array_key_exists($rule_id, $this->errors) ? $this->errors[$rule_id] : null;
        } else if (func_num_args() > 1) {
            // Set error message for a specific rule
            $this->errors[$rule_id] = $error_text;
            return $this;
        }
        
        return $this->errors;
    }

    public function elementType()
    {
        return $this->element_type;
    }

    public function element()
    {
        return $this->field_element ? new Html([
            $this->field_element
        ]) : null;
    }
}


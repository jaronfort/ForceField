<?php
namespace Hologram\Web;

use ForceField\Core\Configure;

class FormValidator
{

    private static $instance;

    protected $field;

    protected $form;

    protected $argument;

    public function __construct()
    {
        if (FormValidator::$instance)
            throw new \Exception('FormValidator has already been initialized.');
        FormValidator::$instance = $this;
    }

    private function run($rule_id, $rule_arg, FormField $field, Form $form)
    {
        $this->argument = $rule_arg;
        $this->field = $field;
        $this->form = $form;
        if(method_exists($this, $rule_id))
            return $this->$rule_id($field->val(), $field, $form);
        else
        {
            if($this->argument != null)
                return call_user_func($rule_id, $this->argument);
            
            return call_user_func($rule_id);
        }
    }

    public static function validate(FormRule $rule)
    {
        if (! FormValidator::$instance) {
            $instance = load(Configure::readString('form.validator', 'Hologram\Web\FormValidator'));
            if (! $instance instanceof FormValidator)
                die('Error: Form validator does not extend the Hologram\Web\FormValidator class.');
        }
        
        return FormValidator::$instance->run($rule->id(), $rule->arg(), $rule->field(), $rule->form());
    }

    public function required($val)
    {
        if ($val != null && strlen($val)) {
            // Value submitted
            return true;
        }
        
        return false;
    }

    public function integer($val)
    {
        return filter_var($val, FILTER_VALIDATE_INT);
    }

    public function number($val)
    {
        return filter_var($val, FILTER_VALIDATE_FLOAT);
    }

    public function email($val)
    {
        return filter_var($val, FILTER_VALIDATE_EMAIL);
    }

    public function pattern($val)
    {
        if ($pattern = filter_var('/^' . $this->argument . '$/', FILTER_VALIDATE_REGEXP)) {
            // Validate regular expression pattern
            if (is_string($val) && preg_match($pattern, $val))
                return true;
        }
        
        return false;
    }

    public function url($val)
    {
        return filter_var($val, FILTER_VALIDATE_URL);
    }

    public function maxlength($val)
    {
        if (! is_numeric($this->argument))
            return false;
        
        return strlen((string) $val) <= ((double) $this->argument);
    }

    public function min($val)
    {
        if (! is_numeric($this->argument))
            return false;
        
        if (is_numeric($val))
            return ((double) $val) >= ((double) $this->argument);
        else
            return strlen($val) >= ((double) $this->argument);
    }

    public function max($val)
    {
        if (! is_numeric($this->argument))
            return false;
        
        if (is_numeric($val))
            return ((double) $val) <= ((double) $this->argument);
        else
            return strlen($val) <= ((double) $this->argument);
    }

    public function trim($val)
    {
        return trim($val, $this->argument);
    }

    public function upper($val)
    {
        return strtoupper((string) $val);
    }

    public function lower($val)
    {
        return strtolower((string) $val);
    }
}


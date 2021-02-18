<?php
namespace Hologram\Web;

final class FormRule
{
    
    private $field;
    
    private $rule_id;
    
    private $rule_arg;
    
    public function __construct(FormField $field, $id, $arg = NULL)
    {
        $this->field = $field;
        $this->rule_id = $id;
        $this->rule_arg = $arg;
    }
    
    protected function appendFieldError($error_text)
    {
        $this->field->error($this->rule_id, $error_text);
    }
    
    public function validate()
    {
        return FormValidator::validate($this);
    }
    
    public function field()
    {
        return $this->field;
    }
    
    public function form()
    {
        return $this->field->form();
    }
    
    public function id($id = NULL)
    {
        if (func_num_args() > 0) {
            $this->rule_id = $id;
            return $this;
        }
        return $this->rule_id;
    }
    
    public function arg($arg = NULL)
    {
        if (func_num_args() > 0) {
            $this->rule_val = $arg;
            // Update standard (built-in) validation properties
            switch ($this->rule_id) {
                case 'required':
                    $this->field->required($arg);
                    break;
                case 'min':
                    $this->field->min($arg);
                    break;
                case 'max':
                    $this->field->max($arg);
                    break;
                case 'max_length':
                    $this->field->maxLength($arg);
                    break;
                case 'pattern':
                    $this->field->pattern($arg);
                    break;
                default:
                    // Do nothing
            }
            return $this;
        } else {
            /*switch ($this->rule_id) {
                case 'required':
                    $this->rule_arg = $this->field->required();
                    break;
                case 'min':
                    $this->rule_arg = $this->field->min();
                    break;
                case 'max':
                    $this->rule_arg = $this->field->max();
                    break;
                case 'max_length':
                    $this->rule_arg = $this->field->maxLength();
                    break;
                case 'pattern':
                    $this->rule_arg = $this->field->pattern();
                    break;
                default:
                    // Do nothing
            }*/
            return $this->rule_arg;
        }
    }
}

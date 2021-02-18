<?php
namespace Hologram\Web;

use ForceField\Core\Configure;
use ForceField\Core\Input;
use ForceField\Security\CsrfToken;
use ForceField\Network\Url;
use Hologram\Authenticate\Auth;
use ForceField\Utility\StringUtil;

class Form
{

    private $data;

    private $element;

    private $csrf;

    private $auto_insert_id;

    private $form;

    private $html;

    private $fields;

    private $name;

    private $validated;

    private $errors;

    private $success_callbacks;

    private $error_callbacks;

    // Non-form related errors (user-defined)
    public function __construct($value = null, $auto_insert_id = true)
    {
        $this->data = [];
        $this->auto_insert_id = $auto_insert_id;
        $this->validated = true;
        $this->errors = [];
        $this->success_callbacks = [];
        $this->error_callbacks = [];
        
        if (is_array($value)) {
            // Explicit form
        } else if ($value instanceof Html) {
            if (($tag = strtolower($value->tag())) == 'form') {
                $this->html = $value;
                $this->parseForm($value->element());
            } else
            {
                throw new \Exception("HTML does not represent a form element. The '{$tag}' tag is not valid.");
            }
        } else if (is_null($value)) {
            // Do nothing
        }
    }

    private function parseForm(\DOMElement $form)
    {
        if ($form->tagName == 'form') {
            
            $this->element = $form;
            $this->action = $form->getAttribute('action');
            $this->name = $form->getAttribute('name');
            
            if (trim($this->action) == '@current') {
                $url = Url::current();
                $this->action = (string) $url;
                $form->setAttribute('action', $this->action);
            }
            
            $children = Html::getChildren($form, Html::ELEMENT);
            
            foreach ($children as $child) {
                if ($child instanceof \DOMAttr) {
                    $attr = $child;
                    $this->attributes[$attr->name] = $attr->value ? $attr->value : null;
                } else if ($child instanceof \DOMElement)
                    $this->parseElement($child);
            }
            
            if ($form->hasAttribute('method')) {
                
                $method = strtolower((string) $form->getAttribute('method'));
                
                switch ($method) {
                    case 'get':
                    case 'post':
                        break;
                    default:
                        throw new \Exception('Unsupport request method.');
                }
                
                $this->method = $method;
                // $this->initLabels($form);
            } else {
                $this->method = 'get'; // Default
            }
        } else
            throw new \Exception('Element is not a form.');
    }

    private function parseElement(\DOMElement $element)
    {
        $tag_name = strtolower($element->tagName);
        if (($tag_name == 'input' || $tag_name == 'select' || $tag_name == 'textarea' || $tag_name == 'button') && $element->getAttribute('name')) {
            
            $name = $element->getAttribute('name');
            $type = $element->hasAttribute('type') ? strtolower(trim($element->getAttribute('type'))) : '';
            
            if (array_key_exists($name, $this->data))
                return; // Skip duplicates and already parsed radio and checkbox elements
            
            if ($tag_name == 'input' && ($type == 'radio' || $type == 'checkbox')) {
                $html = new Html($this->element);
                $options = $html->find('input[name="' . $name . '"][type="' . $type . '"]');
                
                if (StringUtil::endsWith('[]', $name))
                    $name = substr($name, 0, strlen($name) - 2);
                
                $field = new FormField($this, $name, $options->members());
            } else
                $field = new FormField($this, $name, $element);
            
            $this->data[$name] = $field;
        } else if ($element->tagName == 'form') {
            // Skip nested form (invalid)
        } else {
            // Loop element
            foreach ($element->childNodes as $child) {
                if ($child instanceof DOMElement)
                    $this->parseElement($child);
            }
        }
    }

    private function parseSelect(DOMElement $select)
    {
        $a = [];
        
        foreach ($select->childNodes as $option) {
            
            if ($option instanceof DOMElement && $option->tagName == 'option' && $option->hasAttribute('value')) {
                // $a[ $option->getAttribute( 'value' ) ] = $option->nodeValue;
                $a[] = $option->getAttribute('value');
            }
        }
        
        return $a;
    }

    private function parseMultiple(array $elements)
    {
        $this->value = []; // Assume multiple values
        $this->field_element = [];
        
        if (count($elements) > 0) {
            
            $name = $elements[0]->getAttribute('name');
            
            if (! $name)
                return; // Parse failure
            
            foreach ($elements as $e) {
                
                $this->field_element[] = $e;
                
                if ($e->hasAttribute('checked')) {
                    $val = $e->getAttribute('value');
                    $this->value[] = $val;
                }
            }
        }
    }

    /**
     *
     * @param string $name
     */
    private function initDataFromMethod($name = NULL)
    {
        switch ($this->method) {
            case 'post':
                $data = Input::post($name);
                break;
            case 'get':
            default:
                $data = Input::get($name);
        }
        
        foreach ($data as $name => $val) {
            
            if ($this->exists($name)) {
                
                if (is_array($val))
                    call_user_func_array(array(
                        $this->data[$name],
                        'val'
                    ), $val);
                else
                    $this->data[$name]->val($val);
            }
        }
    }

    /**
     * Gets a form field.
     *
     * @param string $name
     * @return FormField
     */
    public function field($name)
    {
        if (! $this->exists($name))
            return null;
        
        return $this->data[$name];
    }

    /**
     * Specifies whether or not a field is contained within the form.
     *
     * @param string $name
     * @return boolean
     */
    public function exists($name)
    {
        return array_key_exists($name, $this->data);
    }

    public function csrf($token = NULL)
    {
        $nargs = func_num_args();
        
        if ($nargs > 1) {
            $this->removeCsrf();
            $name = $token;
            $value = func_get_arg(1);
            $this->csrf = new CsrfToken($name, $value);
            // TODO Insert csrf
        } else if ($nargs == 1) {
            $this->removeCsrf();
            $name = ff_config('security.csrf.tokenName', 'token');
            $value = $token;
            $this->csrf = new CsrfToken($name, $value);
            // TODO Insert csrf
        } else {
            //
            return $this->csrf;
        }
        
        return $this;
    }

    public function removeCsrf()
    {
        if ($this->csrf) {
            // TODO Remove from HTML form if exists
            $this->csrf = NULL;
        }
    }

    public function validate($name = null)
    {
        die("validate");
        if (is_string($name)) {
            // Validate single field
            if (! is_array($this->validated))
                $this->validated = [];
            
            $this->initDataFromMethod($name);
            
            if ($this->exists($name)) {
                $result = $this->data[$name]->validate();
                
                if ($result === true)
                    $this->validated[] = $name;
                
                // $this->runTranslation($this->lang->lang());
                return $this->data[$name]->val();
            } else
                return false; // Fail (field doesn't exist)
        } else {
            $this->validated = [];
            $this->initDataFromMethod();
            $errors = false;
            
            foreach ($this->data as $name => $field) {
                
                if (! $field->validate())
                {
                    $errors = true;
                    $this->errors[$name] = $field->error();
                }
                
                $this->validated[] = $name;
            }
            
            // $this->runTranslation($this->lang->lang());
            
            if (! $errors) {
                $data = $this->data();
                
                foreach ($this->success_callbacks as $callback) {
                    $callback($result, $data);
                }
                
                return $data;
            } else {
                
                $errors = $this->errors();
                
                foreach ($this->error_callbacks as $callback) {
                    $callback($errors, $this);
                }
                
                return false;
            }
        }
    }

    public function validateIfSubmitted($submit_field = null)
    {
        if ($this->submitted($submit_field))
            return $this->validate();
        
        return false;
    }

    public function login($user_field = 'email', $password_field = 'password', $submit_field = null, $remember_me_field = 'remember_me')
    {
        if ($submit_field === true || $this->submitted($submit_field)) {
            if ($data = $this->validate()) {
                
                if ($user = Auth::login($data[$user_field], $data[$password_field])) {
                    // Do something on login
                    return $user;
                } else {
                    // Error: Authentication failed
                    $validation = Configure::readString('form.validation.langFile', 'validation');
                    $label = lang('validation.@' . $this->name . '.label', '');
                    $name = $this->name ? $this->name : 'login';
                    
                    $text = lang("{$validation}.@{$name}.auth", '', [
                        'label' => $name,
                        'field' => $name,
                        'value' => $data[$user_field],
                        'arg' => ''
                    ]);
                    
                    if (! $text)
                        $text = lang("{$validation}.auth", $this->name . '-error:auth', [
                            'label' => $name,
                            'field' => $name,
                            'value' => $data[$user_field],
                            'arg' => ''
                        ]);
                    
                    $this->errors($name, 'auth', $text);
                }
            }
        }
        
        return false;
    }

    public function name()
    {
        return $this->name;
    }

    public function value($name)
    {
        if (array_key_exists($name, $this->data))
            return $this->data[$name]->val();
        
        return null;
    }

    public function autoInsertId()
    {
        return $this->auto_insert_id;
    }

    /**
     * Sets the <code>method</code> property.
     *
     * @param string $method
     * @return Form|string
     */
    public function method($method = NULL)
    {
        if (is_string($method)) {
            
            $method = strtolower($method);
            
            switch ($method) {
                case 'get':
                case 'post':
                    // Do nothing
                    break;
                default:
                    throw new \Exception('Invalid form method.');
            }
            
            $this->method = $mehtod;
            
            if ($this->element) {
                $this->element->setAttribute('method', $method);
            }
        }
        return func_num_args() == 0 ? $this->method : $this;
    }

    /**
     *
     * @param mixed $action
     * @return Form|NULL|string
     */
    public function action()
    {
        if (func_num_args() > 0) {
            $this->attr('action', ff_get_array(func_get_args()));
            return $this;
        } else
            return $this->action;
    }

    /**
     * The number of fields contained in this form.
     *
     * @return int
     */
    public function numFields()
    {
        return count(array_keys($this->data));
    }

    /**
     * The number of errors found after validation has occurred.
     *
     * @return int
     */
    public function numErrors()
    {
        $n = 0;
        
        foreach (array_keys($this->data) as $name) {
            $field = $this->data[$name];
            $count = count(array_keys($field->error()));
            $n += $count;
        }
        
        return $n;
    }

    public function errors($name = null, $rule_id = null, $error = null)
    {
        if (is_string($name)) {
            if ($this->exists($name)) {
                if (func_num_args() == 1)
                    return $this->data[$name]->error();
                else {
                    // Add custom error to existing field
                    $this->data[$name]->error($error);
                    return $this;
                }
            } else if (func_num_args() > 1) {
                // Add error to custom field
                $this->errors[$name][$rule_id] = $error;
            }
        } else {
            return $this->errors;
            $a = $this->errors;
            
            foreach ($this->data as $name => $field) {
                $errors = $field->error(); // Returns an associative array of errors
                
                if (count(array_keys($errors)) == 0)
                    continue;
                
                foreach ($errors as $rule_id => $msg) {
                    $a[$name][$rule_id] = $msg;
                }
            }
            
            return $a;
        }
    }

    public function successCallback(callable $callback)
    {
        $this->success_callbacks[] = $callback;
    }

    public function errorCallback(callable $callback)
    {
        $this->error_callbacks[] = $callback;
    }

    public function displayErrors($name = NULL, $delimiter_start = '<p>', $delimeter_end = '</p>')
    {
        $result = '';
        
        if ($name == true) {
            
            $errors = $this->errors($name);
            foreach ($errors as $name => $err) {
                if ($result)
                    $result .= "\n";
                $result .= $delimiter_start . $err . $delimeter_end;
            }
        } else if (is_string($name)) {
            $errors = $this->errors($name);
            
            foreach ($errors as $err) {
                if ($result)
                    $result .= "\n";
                $result .= $delimiter_start . $err . $delimeter_end;
            }
        }
        return $result;
    }

    public function insertErrors($selector = '.validation-errors', $delimiter_start = '<p>', $delimiter_end = '</p>')
    {
        if ($this->element instanceof \DOMElement) {
            $target = (new Html($this->element))->html($this->displayErrors($name, $delimiter_start, $delimiter_end));
        }
        return $this;
    }

    /**
     * Specifies whether or not the form has been validated via a call to the <code>validated()</code> method.
     *
     * @param
     *            name
     * @return boolean
     */
    public function validated($name = null)
    {
        if (is_string($name) && is_array($this->validated))
            return in_array($name, $this->validated);
        
        return is_array($this->validated) ? count($this->validated) > 0 : false;
    }

    /**
     * Specifies if the form validation has ran and zero errors were encountered.
     *
     * @return boolean Returns <code>true</code> if validation has ran and zero errors were encountered. Otherwise, returns <code>false</code>.
     */
    public function success()
    {
        return $this->validated() && $this->numErrors() == 0;
    }

    /**
     *
     * @param array $data
     * @return mixed
     */
    public function data($name = null)
    {
        if ($name) {
            return $this->validated($name) ? $this->data[$name]->val() : false;
        } else {
            
            $a = [];
            
            foreach ($this->validated as $name) {
                $a[$name] = $this->data[$name]->val();
            }
            
            return $a;
        }
    }

    /**
     * Populates the form using the supplied data.
     *
     * @param array $data
     * @return Form
     */
    public function populate(array $data)
    {
        foreach ($data as $name => $val) {
            
            if ($this->exists($name)) {
                if (is_array($val))
                    call_user_func_array(array(
                        $this->data[$name],
                        'val'
                    ), $val);
                else
                    $this->data[$name]->val($val);
            }
        }
        
        return $this;
    }

    /**
     *
     * @return DOMElement
     */
    public function element()
    {
        return $this->element;
    }

    /**
     * A list of field names contained in the form.
     *
     * @return array
     */
    public function fields()
    {
        return array_keys($this->data);
    }

    public function submitted($name = null)
    {
        switch ($this->method) {
            case 'get':
                $data = Input::get($name);
                break;
            case 'post':
            default:
                $data = Input::post($name);
        }
        
        if (is_array($data)) {
            
            foreach ($data as $name => $value) {
                if ($this->exists($name) && $this->data[$name]->elementType() == 'submit') {
                    // Submit field received
                    return true;
                }
            }
        } else if (is_string($name) && $this->exists($name) && $this->data[$name]->elementType() == 'submit')
            return true;
        
        return false;
    }

    public function html()
    {
        return $this->html;
    }

    public function attr($name = NULL, $value = NULL)
    {
        if($this->html)
        {
            if(func_num_args() > 1)
                return $this->html->attr($name, $value);
            else if(func_num_args() == 1)
                return $this->html->attr($name);
            else
                return $this->html->attr();
        }
        return NULL;
    }

    public function compare(Form $form)
    {
        if ($form != $this) {
            
            foreach ($this->data as $name => $field) {
                if (! $form->field($name))
                    return false; // Fail (fields must match)
            }
        }
        
        // Success!
        return true;
    }

    public function __toString()
    {
        return (string) $this->html;
    }
}


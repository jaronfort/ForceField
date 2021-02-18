<?php
namespace ForceField\Utility;

class ArrayList implements \ArrayAccess
{

    private $data = array();

    private $size = 0;

    public function add($element)
    {
        $this->size ++;
        $this->data[] = $element;
        return $this->size - 1; // New element index
    }

    public function addAt($element, $index)
    {
        if (is_int($index) && $index > - 1 && $index < $this->size) {
            $i = - 1;
            foreach ($this->data as $e) {
                $i ++;
                if ($i == $index)
                    $a[] = $element;
                $a[] = $e;
            }
            $this->data = $a;
            $this->size ++;
            return $index;
        } else
            return - 1;
    }

    public function contains($element)
    {
        return in_array($element, $this->data);
    }

    public function remove($element)
    {
        $a = array(); // New array
        for ($i = 0; $i < $this->size; $i ++) {
            if ($this->data[$i] != $element)
                $a[] = $element;
        }
        $this->data = $a;
        $this->size = count($this->data);
        return $element;
    }

    public function removeAt($index)
    {
        if (is_int($index) && $index > - 1 && $index < $this->size) {
            $element = NULL;
            $a = array(); // New array
            for ($i = 0; $i < $this->size; $i ++) {
                if ($i == $index)
                    $element = $this->data[$i];
                else
                    $a[] = $this->data[$i];
            }
            $this->size --;
            $this->data = $a;
            return $index;
        }
        return - 1;
    }

    public function swap($element, $index)
    {
        if (is_int($index) && $index > - 1 && $index < $this->size) {
            $this->data[$index] = $element;
            return $index;
        } else
            return - 1;
    }

    public function get($index = NULL)
    {
        if (is_int($index)) {
            if ($index > - 1 && $index < $this->size)
                return $this->data[$index];
        } else if (is_null($index)) {
            $a = array(); // Safe array
            for ($i = 0; $i < $this->size; $i ++) {
                $a[] = $this->data[$i];
            }
            return $a;
        }
    }

    public function clear()
    {
        $this->data = array();
    }

    public function size()
    {
        return $this->size;
    }

    public function back()
    {
        return $this->size > 0 ? $this->data[$size - 1] : FALSE;
    }

    public function pop()
    {
        return pop_array($this->data);
    }

    public function isEmpty()
    {
        return $this->size == 0;
    }

    public function offsetExists($offset)
    {
        return is_int($offset) && $offset > - 1 && $offset < $this->size;
    }

    public function offsetGet($offset)
    {
        return is_int($offset) ? $this->get($offset) : NULL;
    }

    public function offsetSet($offset, $value)
    {
        $this->swap($offset, $value);
    }

    public function offsetUnset($offset)
    {
        if (is_int($offset) && $offset > - 1 && $offset < $this->size)
            unset($this->data[$offset]);
    }
    
}
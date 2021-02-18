<?php

class Collection
{

    private $data = [];

    private $size = 0;

    public function __construct()
    {}

    public function add($row)
    {
        $data[] = $row;
        $this->size ++;
    }

    public function first()
    {
        return $this->index(0);
    }

    public function index($index)
    {
        return $index > - 1 && $index < $size ? $data[$index] : NULL;
    }

    public function last()
    {
        return $this->index($size - 1);
    }
    
    public function clear()
    {
        $this->data = [];
        $this->size = 0;
    }
    
    public function each(callable $callable)
    {
        foreach($this->data as &$row)
        {
            $row = $callable($row);
        }
    }
}
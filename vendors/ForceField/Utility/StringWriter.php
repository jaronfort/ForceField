<?php
namespace ForceField\Utility;

class StringWriter
{

    private $tab_count;

    public function __construct()
    {
        $this->_value = '';
        $this->tab_count = 0;
    }

    private function write_tab()
    {
        $this->_value .= $this->tab_string();
    }

    private function tab_string()
    {
        $tab = '';
        for ($i = 0; $i < $this->tab_count; $i ++) {
            $tab .= "\t";
        }
        return $tab;
    }

    public function clear()
    {
        $this->_value = '';
    }

    public function write($value)
    {
        $this->write_tab();
        $this->_value .= (string) $value;
    }

    public function writeln($value = '')
    {
        $this->write_tab();
        $this->_value .= (string) $value . "\n";
    }

    public function tab()
    {
        $this->write_tab();
    }

    public function tabln()
    {
        $this->write_tab();
        $this->writeln('');
    }

    public function tab_up()
    {
        $this->tab_count ++;
    }

    public function tab_down()
    {
        if ($this->tab_count > 0)
            $this->tab_count --;
    }

    public function value()
    {
        return $this->_value;
    }

    public function num_tabs()
    {
        return $this->tab_count;
    }

    public function flush()
    {
        $value = $this->_value;
        $this->_value = ''; // Clear
        return $value;
    }
}
<?php

abstract class Entity
{
    protected $m_lineNum = 0;

    abstract public function convert();
    abstract public function neatPrint($indentNum);

    public function __toString()
    {
        return $this->neatPrint(0);
    }
    
    public function setLnNum($num)
    {
        $this->m_lineNum = $num;
    }
    
    public function getLnNum()
    {
        return $this->m_lineNum;
    }
    
}

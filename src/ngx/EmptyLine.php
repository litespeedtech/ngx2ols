<?php

class EmptyLine extends Entity
{
    public function __construct($lnNum)
    {
        $this->setLnNum($lnNum);
    }

    public static function mkInstByContent(Content $confContent)
    {
        $confContent->gotoNextEol();
        return new self($confContent->getGlnNum());
    }
    public function convert()
    {
        return null;
    }
    
    public function neatPrint($indentNum)
    {
        return "\n";
    }
}

<?php

class Comment extends Entity
{
    private $m_comment = null;

    public function __construct($content = null, $lnNum = 0)
    {
        $this->m_comment      = $content;
        $this->setLnNum($lnNum);
    }

    public static function mkInstByContent(Content $contentObj)
    {
        $content = '';
        while ((false === $contentObj->isEof()) && (false === $contentObj->isEol()))
        {
            $content .= $contentObj->getContentChar();
            $contentObj->movePos();
        }
        return new Comment(ltrim($content, "# "), $contentObj->getGlnNum());
    }

    public function isEmpty()
    {
        return ((is_null($this->m_comment)) || ('' === $this->m_comment));
    }

    public function isLines()
    {
        return (false !== strpos(rtrim($this->m_comment), "\n"));
    }

    public function setContent($content)
    {
        $this->m_comment = $content;
    }

    public function neatPrint($indentNum)
    {
        if (true === $this->isEmpty())
        {
            return '';
        }

        $indent = str_repeat('    ', $indentNum);
        $content = $indent . "# " . rtrim($this->m_comment);

        if (true === $this->isLines())
        {
            $content = preg_replace("#\r{0,1}\n#", PHP_EOL . $indent . "# ", $content);
        }

        return $content . "\n";
    }
    
    public function convert()
    {
        return null;
    }
}

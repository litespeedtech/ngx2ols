<?php

class Content
{
    static private $m_gLineNum  = 0;
    const DEFAULT_POS = -1;
    private $m_content;
    private $m_pos;
    private $m_isSkipChar;

    public function __construct($data)
    {
        $this->m_pos = 0;
        $this->m_content = $data;
        $this->m_isSkipChar = false;
        
    }
    
    static public function incGlnNum()
    {
        self::$m_gLineNum++;
    }
    
    static public function getGlnNum()
    {
        return self::$m_gLineNum;
    }
    
    public function isEscape()
    {
        return $this->m_isSkipChar;
    }
    
    public function escapeChar($char)
    {
        if($char === '\'')
        {
            $this->m_isSkipChar = !$this->m_isSkipChar;
        }
    }
    
    public function getContentChar($pos = self::DEFAULT_POS)
    {
        $position = $this->useDefaultPos($pos);

        if (!is_int($position))
        {
            die("ERROR: getContentChar, invalid position\n");
        }

        if ($this->isEof())
        {
            die("ERROR: getContentChar, Position {$position} index out of range\n");
        }

        return $this->m_content[$position];
    }

    public function getRestOfTheLine($pos = self::DEFAULT_POS)
    {
        $position = $this->useDefaultPos($pos);
        $content = '';
        while ((false === $this->isEof($position)) && (false === $this->isEol($position)))
        {
            $content .= $this->getContentChar($position);
            $position++;
        }
        return $content;
    }

    public function isEol($position = self::DEFAULT_POS)
    {
        return ("\r" === $this->getContentChar($position)) || ("\n" === $this->getContentChar($position));
    }

    public function isEmptyLine($pos = self::DEFAULT_POS)
    {
        $line = $this->getCurrLine($pos);
        return (0 === strlen(trim($line)));
    }

    public function getCurrLine($pos = self::DEFAULT_POS)
    {
        $position = $this->useDefaultPos($pos);

        $offset = $this->lastEolPos($position);
        $length = $this->nextEolPos($position) - $offset;
        return substr($this->m_content, $offset, $length);
    }

    public function isEof($pos = self::DEFAULT_POS)
    {
        $position = $this->useDefaultPos($pos);
        return (!isset($this->m_content[$position]));
    }

    public function movePos($inc = 1)
    {
        if($this->isEol())
        {
            self::incGlnNum();
        }
        $this->m_pos += $inc;
    }

    public function gotoNextEol($pos = self::DEFAULT_POS)
    {
        $position = $this->useDefaultPos($pos);
        $this->m_pos = $this->nextEolPos($position);
    }
    
    private function useDefaultPos($position)
    {
        if (self::DEFAULT_POS === $position)
        {
            $position = $this->m_pos;
        }
        return $position;
    }

    private function lastEolPos($pos = self::DEFAULT_POS)
    {
        $position = $this->useDefaultPos($pos);
        return strrpos(substr($this->m_content, 0, $position), "\n", 0);
    }

    private function nextEolPos($pos = self::DEFAULT_POS)
    {
        $position = $this->useDefaultPos($pos);

        $eolPos = strpos($this->m_content, "\n", $position);
        if (false === $eolPos)
        {
            $eolPos = strlen($this->m_content) - 1;
        }

        return $eolPos;
    }
}

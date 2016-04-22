<?php

class Brackets extends Entity
{
    private $m_parentDirective  = null;
    private $m_directives       = array();
    private $m_entities         = array();

    public function serialize($filePath)
    {
        $fHandle = @fopen($filePath, 'w');
        if (false === $fHandle)
        {
            die("ERROR: serialize, Cannot write to {$filePath}");
        }

        $ret = @fwrite($fHandle, (string)$this);
        if (false === $ret)
        {
            fclose($fHandle);
            die("ERROR: serialize, Cannot write to {$filePath}");
        }

        fclose($fHandle);
    }

    public static function mkInst()
    {
        return new self();
    }

    public static function mkInstByContent(Content $content)
    {
        $brackets = new Brackets();
        while (false === $content->isEof())
        {
            if (true === $content->isEmptyLine())
            {
                $brackets->addEntity(EmptyLine::mkInstByContent($content));
            }

            $char = $content->getContentChar();
            
            if ('#' === $char)
            {
                $brackets->addEntity(Comment::mkInstByContent($content));
            }
            else if (('a' <= $char) && ('z' >= $char))
            {
                $brackets->addDirective(Directive::mkInstByContent($content));
            }
            else if ('}' === $char)
            {
                break;
            }
            else
            {
                $content->movePos();
            }
        }
        return $brackets;
    }
    
    public static function instFromFile($fileName) {
        $fileContents = @file_get_contents($fileName);

        if (false === $fileContents) {
            die("ERROR:  instFromFile, failed to read file {$fileName}");
        }

        return self::mkInstByContent(new Content($fileContents));
    }

    public function toCtxKey() {
        $rNgCnfDict = Array();
        $this->traverse($this->convert(), '', $rNgCnfDict);
        return $rNgCnfDict;
    }

    private function traverse($obj, $parentKey, &$ret)
    {
        $lnNum = 0;
        foreach($obj as $elem)
        {
            if(is_array($elem))
            {
                $key0   = $elem[0];
                $key    = "{$parentKey}.$key0";
                $lnNum  =  $elem['LINENUM_VAL'];
                array_push($ret, array($key, $elem[$key0], $lnNum ));
                //$ret[ $key ] = $elem[$key0]; 
                if (count($elem) > 3 && is_array($elem[1]))
                {    
                    $this->traverse($elem[1], $parentKey . "." . $key0, $ret); 
                }
            }
            else
            {
                print "error\n";
            }
        }
    }

    public function addDirective(Directive $directive)
    {
        if ($directive->getParentBrackets() !== $this)
        {
            $directive->setParentBrackets($this);
        }

        $this->m_directives[] = $directive;
        $this->addEntity($directive);

        return $this;
    }

    public function getParentDirective()
    {
        return $this->m_parentDirective;
    }

    public function setParentDirective(Directive $parentDirective)
    {
        $this->m_parentDirective = $parentDirective;

        if ($parentDirective->getChildBrackets() !== $this)
        {
            $parentDirective->setChildBrackets($this);
        }
        return $this;
    }

    public function neatPrint($indentNum)
    {
        $resultString = "";
        foreach ($this->m_entities as $entity)
        {
            $resultString .= $entity->neatPrint($indentNum + 1);
        }
        return $resultString;
    }

    public function __toString()
    {
        return $this->neatPrint(-1);
    }

    public function convert()
    {
        $ret = array();
        foreach ($this->m_entities as $brackets)
        {
            $tmp = $brackets->convert();
            if (!is_null($tmp) )
            {
                array_push($ret, $tmp);
            }
        }
        return $ret;
    }
    private function addEntity(Entity $entity)
    {
        $this->m_entities[] = $entity;
    }
   
}

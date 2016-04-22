<?php

class Directive extends Entity
{
    private $m_name;
    private $m_value;

    private $m_childBrackets = null;
    private $m_parentBrackets = null;
    private $m_comment = null;

    public function __construct(
        $lnNum,
        $name,
        $value = null,
        Brackets $childBrackets = null,
        Brackets $parentBrackets = null,
        Comment $comment = null ) 
    {
        $this->m_name   = $name;
        $this->m_value  = $value;
        $this->setLnNum($lnNum);

        if (!is_null($childBrackets))
        {
            $this->setChildBrackets($childBrackets);
        }
        if (!is_null($parentBrackets))
        {
            $this->setParentBrackets($parentBrackets);
        }
        if (!is_null($comment))
        {
            $this->setComment($comment);
        }
    }
    
    public function convert()
    {
        $ret = array(0 => $this->m_name, $this->m_name => $this->m_value, 'LINENUM_VAL' => $this->m_lineNum);
        if (!is_null($this->getChildBrackets()))
        {
            $arr = $this->m_childBrackets->convert();
            array_push($ret, $arr);
        }
        return $ret;
    }

    public static function mkInst(
        $name,
        $value = null,
        Brackets $childBrackets = null,
        Brackets $parentBrackets = null,
        Comment $comment = null)
    {
        return new self(0, $name, $value, $childBrackets, $parentBrackets, $comment);
    }

    public static function mkInstByContent(Content $confContent)
    {
        $text = '';
        while (false === $confContent->isEof())
        {
            $char = $confContent->getContentChar();
            $confContent->escapeChar($char);

            if ('{' === $char && !$confContent->isEscape())
            {
                return self::newDirectiveHasBrackets($text, $confContent);
            }
            if (';' === $char && !$confContent->isEscape())
            {
                return self::newDirectiveNoBrackets($text, $confContent);
            }
            $text .= $char;
            $confContent->movePos();
        }
        die("ERROR: mkInstByContent, failed to create directive\n");
    }

    private static function newDirectiveHasBrackets(
        $nameString,
        Content $contentText) 
    {
        $contentText->movePos();
        list($name, $value) = self::parseContent($nameString);
        $directive = new Directive($contentText->getGlnNum(), $name, $value);

        $comment = self::scanCommentInLineLeft($contentText);
        if (false !== $comment)
        {
            $directive->setComment($comment);
        }

        $childBrackets = Brackets::mkInstByContent($contentText);
        $childBrackets->setParentDirective($directive);
        $directive->setChildBrackets($childBrackets);

        $contentText->movePos();

        $comment = self::scanCommentInLineLeft($contentText);
        if (false !== $comment)
        {
            $directive->setComment($comment);
        }

        return $directive;
    }

    private static function newDirectiveNoBrackets(
        $nameString,
        Content $confContent)
    {
        $confContent->movePos();
        list($name, $value) = self::parseContent($nameString);
        $directive = new Directive($confContent->getGlnNum(), $name, $value);

        $comment = self::scanCommentInLineLeft($confContent);
        if (false !== $comment)
        {
            $directive->setComment($comment);
        }

        return $directive;
    }

    private static function scanCommentInLineLeft(Content $confContent)
    {
        $restOfTheLine = $confContent->getRestOfTheLine();
        if (1 !== preg_match('/^\s*#/', $restOfTheLine))
        {
            return false;
        }

        $commentPosition = strpos($restOfTheLine, '#');
        $confContent->movePos($commentPosition);
        return Comment::mkInstByContent($confContent);
    }

    private static function parseContent($content)
    {
        $result = self::regxKeyValue($content);
        if (is_array($result))
        {
            return $result;
        }
        $result = self::regxKey($content);
        if (is_array($result))
        {
            return $result;
        }
        return array($content, null);
    }

    public function getParentBrackets()
    {
        return $this->m_parentBrackets;
    }

    public function getChildBrackets()
    {
        return $this->m_childBrackets;
    }

    public function getComment()
    {
        if (is_null($this->m_comment))
        {
            $this->m_comment = new Comment();
        }
        return $this->m_comment;
    }

    public function existsComment()
    {
        return (!$this->getComment()->isEmpty());
    }

    public function setParentBrackets(Brackets $parentBrackets)
    {
        $this->m_parentBrackets = $parentBrackets;
        return $this;
    }

    public function setChildBrackets(Brackets $childBrackets)
    {
        $this->m_childBrackets = $childBrackets;

        if ($childBrackets->getParentDirective() !== $this) {
            $childBrackets->setParentDirective($this);
        }

        return $this;
    }

    public function setComment(Comment $comment)
    {
        $this->m_comment = $comment;
        return $this;
    }

    public function setCommentText($text)
    {
        $this->getComment()->setContent($text);
        return $this;
    }

    public function neatPrint($indentNum)
    {
        $indent = str_repeat("    ", $indentNum);

        $resultString = $indent . $this->m_name;
        if (!is_null($this->m_value))
        {
            $resultString .= "    " . $this->m_value;
        }

        if (is_null($this->getChildBrackets())) {
            $resultString .= "";
        } else {
            $resultString .= " {";
        }

        if (false === $this->existsComment())
        {
            $resultString .= "\n";
        } 
        else
        {
            if (false === $this->getComment()->isLines())
            {
                $resultString .= " " . $this->m_comment->neatPrint(0);
            } 
            else
            {
                $comment = $this->getComment()->neatPrint($indentNum);
                $resultString = $comment . $resultString;
            }
        }

        if (!is_null($this->getChildBrackets()))
        {
            $resultString .= "" . $this->m_childBrackets->neatPrint($indentNum) . $indent . "}\n";
        }

        return $resultString;
    }
    
    private static function regxKeyValue($content)
    {
        $matches = null;
        if (1 === preg_match('!^([a-z][a-z0-9._/+-]*)\s+([^;{]+)$!', $content, $matches))
        {
            return array($matches[1], rtrim($matches[2]));
        }
        return false;
    }

    private static function regxKey($content)
    {
        $matches = null;
        if (1 === preg_match('!^([a-z][a-z0-9._/+-]*)\s*$!', $content, $matches))
        {
            return array($matches[1], null);
        }
        return false;
    }
}

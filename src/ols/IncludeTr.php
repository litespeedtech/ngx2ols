<?php 
include ('Util.php');

function writeMimeTypes($olsRoot, &$rNgIncludeArr)
{
    $scope = Brackets::mkInst();
    $key = null;
    $val = null;
    $typeLen = strlen('.types.');
    foreach($rNgIncludeArr as $elem)
    {
        $key = join(', ', preg_split('/\s+/', $elem[1]));
        $neatKey = Util::neatKey($key);
        $len = strlen($elem[0]);
        $val = substr($elem[0], $typeLen, $len - $typeLen);
        $scope->addDirective(Directive::mkInst($neatKey . "=", $val)); 
    }
    $output = "{$olsRoot}/conf/mine.properties";
    $scope->serialize($output);
    return array('type' => 'mime', 'ret'=> $output) ;
}

function transIncludeLayer($includeFile, $olsRoot)                    
{
    $brackets = Brackets::instFromFile($includeFile);
    $rNgIncludeArr = $brackets->toCtxKey();
    
    if ( !is_null($rNgIncludeArr[0]) &&  !is_null($rNgIncludeArr[0][0]) )
    {
        if ( $rNgIncludeArr[0][0] == '.types') //mime type 
        {
            unset($rNgIncludeArr[0]);
            return writeMimeTypes($olsRoot, $rNgIncludeArr);
        }
        else //otherwise taken as virtualhost
//        if ( $rNgIncludeArr[0][0] == '.server' || $rNgIncludeArr[0][0] == '.upstream')
        {
            return array('type' => 'subFile', 'ret'=> $rNgIncludeArr) ;
        }
    }    
}

?>

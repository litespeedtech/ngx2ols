<?php 
include('OlsTmpl.php');

//vHostPath: $SERVER_ROOT/conf/vhosts/
class OlsMaker
{
    public function createOlsCnf(&$rOlsCnfDict, $serverRoot)
    {
        $brackets = Brackets::mkInst();
        //1)create default
        $vHostDefault = array();
        OlsTmpl::loadDefaultVhostCnf($vHostDefault);
        $this->writeCnfEntry($brackets, $rOlsCnfDict, $vHostDefault, "{$serverRoot}/conf/vhosts");
        $brackets->serialize("{$serverRoot}/conf/httpd_config.conf");
    }
    
    private function writeCnfEntry(&$brackets, &$rOlsCnfDict, &$vHostDefault, $vHostPath) 
    {
        $vHostCnf = null ;
        foreach($rOlsCnfDict as $key => $val)
        {
            if ($key === 'serverMapVhost') // listener=>, virtualhost=>, context=>...
            {
                $ratherVhost = array();
                $this->writeOlsVhost($val, $vHostDefault, $ratherVhost, $vHostPath);
                foreach ($ratherVhost as $vhostKey => $vhostVal)
                {
                    foreach ($vhostVal as $elem)
                    {
                        $this->trans2LayerArrToBrackets($brackets, $vhostKey, $elem);
                    }
                }
            }
            else 
            {
                $this->trans2LayerArrToBrackets($brackets, $key, $val);
            }

        }
    }

    /*
    [serverMapVhost] => Array
        (
            [0] => Array
                (
                    [listener] => Array
                    [root] => /var/www
                    [index] => index.php index.html index.htm
                    [virtualhost] => ...
             [1]            
     */
    private function transVhostMap(&$serverMapVhost, &$vHostDefault, &$ratherVhost)
    {
        $vHostCnf = array();
        foreach($serverMapVhost as $mapVhost)
        {
            $eachVhostCnf = $vHostDefault;
            foreach( $mapVhost as $key => $val)
            {
                if($key == 'context')
                {
                    foreach ($val as $subCxtKey => $subCxtVal)
                    {
                        $eachVhostCnf[$subCxtKey] = $subCxtVal;
                    }
                }
                else if ($key == 'index')
                {
                    $eachVhostCnf['index']['indexFiles'] = $val;
                }
                else if ($key == 'root')
                {
                    $eachVhostCnf['docRoot'] = $val;
                }
                else
                {
                    $ratherVhost[$key][] = $val;
                }
                unset($mapVhost[$key]);

            }
            array_push($vHostCnf, $eachVhostCnf);
        }
        return $vHostCnf;
    }

    //FIXME: need to improve
    private function transVhostCnf(&$scope, &$eachVhostCnf)
    {
        foreach($eachVhostCnf as $vHostKey => $vHostVal)
        {
            if ($vHostKey == 'context' || $vHostKey == 'errorPage')
            {
                foreach ( $vHostVal as $subKey => $subVal)
                {
                    $neatKey = Util::neatKey($vHostKey) . "$subKey";
                    $this->trans2LayerArrToBrackets($scope, $neatKey, $subVal);
                }
            }
            else
                $this->trans2LayerArrToBrackets($scope, $vHostKey, $vHostVal); 
        }
    }

    //get and write vhost context { location=>, errorpage=> ...}
    //vHostPath: $SERVER_ROOT/conf/vhosts/
    private function writeOlsVhost(&$serverMapVhost, &$vHostDefault, &$ratherVhost, $vHostPath)
    {
        if (is_null($serverMapVhost))
        {   return; }
        $vHostCnf = $this->transVhostMap($serverMapVhost, $vHostDefault, $ratherVhost);

        if (is_null($vHostCnf))
        {   return; }

        $vHostIdx = 0;
        foreach ($vHostCnf as $eachVhostCnf)
        {
            $brackets = Brackets::mkInst();
            $this->transVhostCnf($brackets, $eachVhostCnf);
            $vPath = "{$vHostPath}/VirtualHost_" . $vHostIdx;
            if (!file_exists($vPath))
            {
                mkdir($vPath, 0755, true);
            }
            $brackets->serialize("{$vPath}/vhconf.conf");
            $vHostIdx ++;
        }
    }
    
    function trans2LayerArrToBrackets(&$brackets, $key, &$val)
    {
        if(is_array($val))
        {
            $childBrackets = Brackets::mkInst();
            $directiveVal = null;
            foreach($val as $subKey => $subVal)
            {
                if (is_int($subKey))
                {   
                    $childBrackets->addDirective(Directive::mkInst($subVal));
                } //remove array index
                else if ($subKey === 'DIRECTIVE_VAL')      //subkey is DIRECTIVE_VAL
                {   
                    $directiveVal = $subVal;
                }
                else
                {   
                    $childBrackets->addDirective(
                            Directive::mkInst(Util::neatKey($subKey), $subVal));
                }
            }
            if (is_null($directiveVal))
            {   
                $brackets->addDirective(
                    Directive::mkInst($key)->setChildBrackets($childBrackets));  
            }
            else
            {   $brackets->addDirective(
                    Directive::mkInst(Util::neatKey($key), 
                            $directiveVal)->setChildBrackets($childBrackets));
            }
        }
        else
        {
            $brackets->addDirective(Directive::mkInst(Util::neatKey($key), $val)); 
        }
    }

    function trans3LayerArrToBrackets(&$scope, $key, &$val)
    {
        if(is_array($val))
        {
            $childBrackets = Brackets::mkInst();
            $directiveVal = null;
            foreach($val as $subKey2 => $subVal2)
            {
                $this->trans2LayerArrToBrackets($childBrackets, $subKey2, $subVal2 );
            }
            $scope->addDirective(Directive::mkInst(Util::neatKey($key), $directiveVal)->setChildBrackets($childBrackets));
        }
        else
        {
            $scope->addDirective(Directive::mkInst(Util::neatKey($key), $val)); 
        }
    }    

}


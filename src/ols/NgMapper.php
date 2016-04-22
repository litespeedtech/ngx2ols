<?php 
/*
 * example dict input
 * 
.user=>www www
.worker_processes=>5
.error_log=>logs/error.log
.pid=>logs/nginx.pid
.worker_rlimit_nofile=>8192
.events=>
.events.worker_connections=>4096
.http=>
.http.include=>/etc/nginx/fastcgi.conf
.http.index=>index.html index.htm index.php
.http.default_type=>application/octet-stream
.http.log_format=>main '$remote_addr - $remote_user [$time_local]  $status '
    '"$request" $body_bytes_sent "$http_referer" '
    '"$http_user_agent" "$http_x_forwarded_for"'
.http.access_log=>logs/access.log  main
.http.sendfile=>on
.http.tcp_nopush=>on
.http.server_names_hash_bucket_size=>128
.http.server=>
.http.server.listen=>80
.http.server.server_name=>big.server.com
.http.server.access_log=>logs/big.server.access.log main
.http.server.root=>html
.http.server.location=>/
.http.server.location.fastcgi_pass=>127.0.0.1:1025
.http.server.location.root=>/var/www/virtual/big.server.com/htdocs
.http.server.location.expires=>30d
.http.server.location.proxy_pass=>http://big_server_com
.http.upstream=>big_server_com
.http.upstream.server=>192.168.0.1:8001
 */
include('IncludeTr.php');

class NgMapper
{
    private $m_failLnNums;
    private $m_allFileNames;
    private $m_nginxRoot;
    private $m_olsRoot;
    
    public function __construct($nginxRoot, $olsRoot, $mainConf)
    {
        $this->m_nginxRoot      = $nginxRoot;
        $this->m_olsRoot        = $olsRoot;
        $this->m_failLnNums     = Array();
        $this->m_allFileNames   = Array();
        array_push($this->m_allFileNames , $mainConf);
    }
    
    public function getAllFileNames()
    {
        return $this->m_allFileNames;
    }
    public function getFailLnNums()
    {
        return $this->m_failLnNums;
    }
     
    public function transfrom(&$ngCnfDict, &$olsCnfDict)
    {
        $moreFileObjs = Array();
    //    transRecurLayer ($nginxRoot, $olsRoot, $ngCnfDict, $olsCnfDict, $fails, $moreFiles, $rAllFname);

        $this->coreMap($ngCnfDict, $olsCnfDict, $moreFileObjs);
        if(!is_null($moreFileObjs) && count($moreFileObjs) > 0)
        {
            foreach($moreFileObjs as $subNg)
            {
                $moreFileObjs2 = Array();
                $this->coreMap($subNg, $olsCnfDict, $moreFileObjs2);
                if(!is_null($moreFileObjs2) && count($moreFileObjs2) > 0)
                {
                    foreach($moreFileObjs2 as $subNg2)
                    {
                        $moreFileObjs3 = Array();
                        $this->coreMap($subNg2, $olsCnfDict, $moreFileObjs3);
                    }
                }
            }
        }
    }
    
    public function printStat()
    {
        $failIdx    = 0;
        $lnNum      = 0;
        foreach($this->m_allFileNames as $fname)
        {
            echo "=================== {$fname} (lines leading with ??? are not converted) ===================\n";
            foreach(file($fname) as $line)
            {
                if($failIdx < count($this->m_failLnNums) && $lnNum === $this->m_failLnNums[$failIdx])
                {
                    echo "??? {$line}";
                    $failIdx++;
                }
                else
                {    echo "{$line}"; }
                $lnNum++;
            }
            echo "--------------------convert done ({$fname})------------------------------------------------\n\n\n";
        }    
    }    
    
    private function parseUser(&$olsCnfDict, $val)
    {
        $valArr = preg_split('/\s+/', $val);
        $olsCnfDict['user'] =  $valArr[0];
        if ( count($valArr) > 1)
        {
            $olsCnfDict['group'] =  $valArr[1];
        }
    }

    private function parseHttpInclude(&$olsCnfDict, $includeFile, &$moreFileObj)
    {
        $includeType    = null;
        $includeData    = null;
        $includeRet     = array();
        if ('/' !== $includeFile[0] && '~' !== $includeFile[0])
        {    
            $includeFile = "{$this->m_nginxRoot}/$includeFile";
        }
        foreach (glob($includeFile) as $eachFile)
        {
            array_push($this->m_allFileNames, $eachFile);
            $includeRet = transIncludeLayer($eachFile, $this->m_olsRoot);
            $includeType = $includeRet['type'];
            $includeData = $includeRet['ret'];
            if ($includeType == 'mime')  //mime type
            {
            }
            else if ($includeType == 'subFile' && !is_null($includeData)) //i.g. conf.d/*.conf
            {
                array_push($moreFileObj, $includeData); 
            }

        }
        //include type is not parsed, add it to main conf
        if ($includeType == null)
        {
            if (array_key_exists('include', $olsCnfDict))
            {
                $olsCnfDict['include'][] = $includeFile;
            }
            else
            {
                $olsCnfDict['include'] = array($includeFile);
            }
        }

    }

    private function parseHttpSendfile(&$olsCnfDict, $val)
    {
        if ($val === 'on')
        {    $olsCnfDict['tuning']['useSendfile'] = 1;}
        else
        {   $olsCnfDict['tuning']['useSendfile'] = 0;}

    }

    private function parseHttpGzip(&$olsCnfDict, $val)
    {
        if ($val === 'on')
        {   $olsCnfDict['tuning']['enableGzipCompress'] = 1; }
        else
        {   $olsCnfDict['tuning']['enableGzipCompress'] = 0; }
    }

    private function parseHttpServerLstnr($val, &$currVhost)
    {
        static $lstnrNum = 0;
        $lstnrName = 'Listener_' . $lstnrNum;
        $lstnrNum++;
        $currVhost['listener']['DIRECTIVE_VAL'] = $lstnrName;
        $currVhost['listener']['address']  = $val;
        $currVhost['listener']['secure']   = 0;
        $currVhost['listener']['map']      = null;

        return $currVhost['listener'];
    }

    private function parseHttpServerSvrName($val, &$currVhost)
    {
        static $vHostNum = 0;
        $name = 'VirtualHost_' . $vHostNum;
        $vHostNum++;
        //create new virtual host
        $currVhost['virtualhost']['DIRECTIVE_VAL']  = $name;
        $currVhost['virtualhost']['vhRoot']         = '$SERVER_ROOT/' . "{$name}/";
        $currVhost['virtualhost']['configFile']     = '$SERVER_ROOT/conf/vhosts/' . "{$name}/vhconf.conf";
        $currVhost['virtualhost']['allowSymbolLink']= 1;
        $currVhost['virtualhost']['enableScript']   = 1;
        $currVhost['virtualhost']['restrained']     = 0;
        $currVhost['virtualhost']['setUIDMode']     = 0;
        //associate virtual and listener
        $currVhost['listener']['map']      = "{$name} {$val}";
    }

    private function parseHttpServerSslOn($val, &$currVhost)
    {
        $currVhost['listener']['secure'] = ($val == 'on'? 1: 0);
    }     

    private function parseHttpServerSslCert($val, &$currVhost)
    {
        $currVhost['listener']['certFile'] = $val;
    }

    private function parseHttpServerSslKey($val, &$currVhost)
    {
        $currVhost['listener']['keyFile'] = $val;
    }

    private function parseHttpServerIndex($val, &$currVhost)
    {
        $cnt = count($currVhost['serverMapVhost']);
        $currVhost['serverMapVhost'][$cnt-1]['index'] = $val;
    }

    private function parseHttpServerRoot($val, &$currVhost)
    {
        $cnt = count($currVhost['serverMapVhost']);
        $currVhost['serverMapVhost'][$cnt-1]['root'] = $val;
    }

    private function parseHttpServerLocation($val, &$currVhost, &$currCxt)
    {
        $cxtElem = Array();
        $cxtElem['allowBrowse'] = 1;
        $cxtElem['location']    = '$VH_ROOT' . $val;
        $currCxt['context'][$val] = $cxtElem;
        $currVhost['context']   = $currCxt;
    }

    private function parseHttpServerLocRoot($val, &$hslocRoot)
    {
        if (!is_null($hslocRoot))
        {   $hslocRoot['doctRoot'] = $val;    }
    }

    private function parseHttpServerLocDeny($val, &$hslocRoot)
    {
        if (!is_null($hslocRoot))
        {   $hslocRoot['deny'] = $val;    }
    }

    private  function parseHttpServerLocAddHeader($val, &$hslocRoot)
    {
        if (!is_null($hslocRoot))
        {   $hslocRoot['add_header'] = $val;    }
    }

    //error_page  501 502 503 /504.html;
    private function parseHttpServerErrPage(&$olsCnfDict, $val, &$currCxt)
    {
        $valArr = preg_split('/\s+/', $val);
        $cnt = count($valArr);
        if (is_null($val) || $cnt < 2)
            return ;

        for( $idx = 0; $idx < $cnt - 1; $idx++)
        {
            $errEntry = array('url' => $valArr[$cnt -1 ]);
            $currCxt['errorPage'][$valArr[$idx]] = $errEntry;
        }
    }

    private function parseHttpServer(&$olsCnfDict)
    {
        $currVhost = array();
        $olsCnfDict['serverMapVhost'][] = &$currVhost; 
        return count($olsCnfDict['serverMapVhost']) - 1;
    }


    private function coreMap(&$ngCnfDict, &$olsCnfDict,&$moreFileObj)
    {   
        $currLstnr  =   null;
        $currVhost  =   null;
        $currCxt    =   null;
        $cxtElem    =   null; // location=>( (conext=> xxx, type=> xxx, allow=>xx)  )
        foreach ($ngCnfDict as $elem)
        {
            $key    = $elem[0];
            $val    = $elem[1];
            $lnNum  = $elem[2];

            switch ($key)
            {

                case '.user':
                    $this->parseUser($olsCnfDict, $val);
                    break;

                case '.events.worker_connections':
                    $olsCnfDict['tuning']['maxConnections'] =  $val;
                    break;

                case '.http.include':
                case '.server.include':
                case '.include':    
                    $this->parseHttpInclude($olsCnfDict, $val, $moreFileObj);    
                    break;
                case '.http.access_log':
                    break;
                case '.http.sendfile':
                case '.sendfile':    
                    $this->parseHttpSendfile($olsCnfDict, $val);
                    break;
                case '.http.keepalive_timeout':
                    $olsCnfDict['tuning']['connTimeout'] = $val;
                    break;

                case '.http.gzip':
                case '.gzip':
                    $this->parseHttpGzip($olsCnfDict, $val);   
                    break;

                case '.http.server':
                case '.server':    
                    $currLstnr  = null;
                    $currCxt    = null;
                    $cxtElem    = null;
                    $idx        = $this->parseHttpServer($olsCnfDict);
                    $currVhost  = &$olsCnfDict['serverMapVhost'][$idx];
                    break;
                case '.http.server.listen':
                case '.server.listen':    
                    $currLstnr = $this->parseHttpServerLstnr($val, $currVhost); 
                    break;
                case '.http.server.server_name':
                case '.server.server_name':    
                    $this->parseHttpServerSvrName($val, $currVhost);
                    break;
                case '.http.server.ssl':
                case '.server.ssl':
                    $this->parseHttpServerSslOn($val, $currVhost);
                    break;

                case '.http.server.ssl_certificate':
                case '.server.ssl_certificate':
                    $this->parseHttpServerSslCert($val, $currVhost);
                    break;

                case '.http.server.ssl_certificate_key':
                case '.server.ssl_certificate_key':
                    $this->parseHttpServerSslKey($val, $currVhost);
                    break;

                case '.http.server.root':
                case '.server.root':
                case '.root':    
                    $this->parseHttpServerRoot($val, $olsCnfDict);
                    break;
                case '.http.server.index':
                case '.server.index':
                case '.index':    
                    $this->parseHttpServerIndex($val,$olsCnfDict);
                    break;

                case '.http.server.location':
                case '.server.location':
                case '.location':    
                    $this->parseHttpServerLocation($val, $currVhost, $currCxt);
                    $hslocRoot = &$currVhost['context']['context'][$val]; 
                    break;

                case '.http.server.location.root':
                case '.server.location.root':
                case '.location.root':    
                    $this->parseHttpServerLocRoot($val,  $hslocRoot);
                    break;

                case '.http.server.location.deny':
                case '.server.location.deny':
                case '.location.deny':    
                    $this->parseHttpServerLocDeny($val,  $hslocRoot);
                    break;

                case '.http.server.location.add_header':
                case '.server.location.add_header':
                case '.location.add_header':    
                    $this->parseHttpServerLocAddHeader($val,  $hslocRoot);
                    break;

                case '.http.server.error_page':
                case '.server.error_page':
                    $this->parseHttpServerErrPage($olsCnfDict, $val, $currVhost['context']); 
                    break;
                case '.worker_processes':
                case '.error_log':
                case '.events':
                case '.events.use':
                case '.http':
                case '.http.server.location.index':
                    break;

                default:
                    $this->printFailKey($key, $lnNum);
                    array_push($this->m_failLnNums, $lnNum); 
            }
        }

    }

    private function printFailKey(&$key, $lnNum)
    {
        if ($GLOBALS['debug'])
        {
            echo  Util::neatKey("{$key}:{$lnNum}", 60) . "not converted \n";
        }
    }

/*
function transRecurLayer ($nginxRoot, $olsRoot, &$ngCnfDict, &$olsCnfDict, &$fails, &$moreFileObj, &$moreFileName)
{
    coreMap($nginxRoot, $olsRoot, $ngCnfDict, $olsCnfDict, $fails, $moreFileObj, $moreFileName);
    if(!is_null($moreFileName) && count($moreFileName) > 0)
    {
        foreach($moreFiles as $subNg)
        {
            $moreFiles = Array();
            transRecurLayer($nginxRoot, $olsRoot, $subNg, $olsCnfDict, $fails, $moreFiles, $rAllFname);
        }
    }    
}*/
}
?>

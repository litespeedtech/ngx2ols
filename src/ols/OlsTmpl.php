<?php

class OlsTmpl
{
    static private function loadDefaultOlsGlobal(&$olsCnfDict) 
    {
        $olsGlbal = Array(
            'serverName'    => 'lshttpd',
            'user'          => 'nobody',                      
            'group'         => 'nobody',                     
            'priority'      => 0,
            'inMemBufSize'  => '60M',              
            'swappingDir'   => '/tmp/lshttpd/swap',
            'autoFix503'    => 1,
            'gracefulRestartTimeout'=>     300,    
            'mime'          => '$SERVER_ROOT/conf/mime.properties',
            'showVersionNumber'=>  0,
            'adminEmails'   => 'root@localhost',
            'adminRoot'     => '$SERVER_ROOT/admin/',
            'indexFiles'    => 'index.html',
            'uploadTmpDir'  => 'tmp',
            'uploadTmpFilePermission'=>     0644,
            'uploadPassByPath'=> 1    
        );
        foreach($olsGlbal as $key => $val)
        {
            $olsCnfDict[$key] = $val;
        }

        $olsCnfDict['accesslog']['DIRECTIVE_VAL']   =     '$SERVER_ROOT/logs/access.log';
        $olsCnfDict['accesslog']['rollingSize']     =     '10M';
        $olsCnfDict['accesslog']['keepDays']        =     30;
        $olsCnfDict['accesslog']['compressArchive'] =     0;

        $olsCnfDict['errorlog']['DIRECTIVE_VAL']    =     '$SERVER_ROOT/logs/error.log';
        $olsCnfDict['errorlog']['logLevel']         =     'DEBUG';
        $olsCnfDict['errorlog']['debugLevel']       =     10;
        $olsCnfDict['errorlog']['rollingSize']      =     '10M';
        $olsCnfDict['errorlog']['enableStderrLog']  =    1;
        $olsCnfDict['serverMapVhost']               =     null;  
    }

    static private function loadDefaultOlsTuning(&$olsCnfDict)
    {
        $olsDefault = Array(
            'eventDispatcher'   => 'best',
            'SSLCryptoDevice'   => 'null',
            'maxConnections'    => 300,
            'maxSSLConnections' => 200,
            'connTimeout'       => 30000,
            'maxKeepAliveReq'   => 1000,
            'smartKeepAlive'    => 0,
            'keepAliveTimeout'  => 5,
            'sndBufSize'        => 0,
            'rcvBufSize'        => 0,
            'maxReqURLLen'      => 8192,
            'maxReqHeaderSize'  => 16380,
            'maxReqBodySize'    => '6000M',
            'maxDynRespHeaderSize'=> 8192,
            'maxDynRespSize'    => '2047M',
            'maxCachedFileSize' => 4096,
            'totalInMemCacheSize'=>'20M',
            'maxMMapFileSize'   => '256K',
            'totalMMapCacheSize'=> '40M',
            'useSendfile'       => 1,
            'fileETag'          => 28,
            'enableGzipCompress'=> 1,
            'enableDynGzipCompress'=> 1,
            'gzipCompressLevel' => 6,
            'compressibleTypes' => 'text/*,application/x-javascript,application/javascript,application/xml, image/svg+xml',
            'gzipAutoUpdateStatic'=> 1,
            'gzipStaticCompressLevel'=> 6,
            'gzipMaxFileSize'   => '1M',
            'gzipMinFileSize'   => 300     
        );
        foreach ($olsDefault as $key => $val)
        {
            $olsCnfDict['tuning'][$key] = $val;
        }
    }

    static public function loadDefaultVhostCnf(&$vHostCnf)
    {
        $vHostCnf['enableGzip'] = 1;
        $vHostCnf['expires']['enableExpires']   = 1;
        $vHostCnf['accessControl']['deny']      = null;
        $vHostCnf['accessControl']['allow']     = '*';

        $vHostCnf['htAccess']['accessFileName'] = '.htaccess';
        $vHostCnf['htAccess']['allowOverride']  = 0;

        $vHostCnf['rewrite']['enable']      = 0;
        $vHostCnf['rewrite']['logLevel']    = 0;
        $vHostCnf['rewrite']['rules']       = '<<<END_rules';
        $vHostCnf['rewrite']['RewriteRule'] = '^index\.php$ - [L]';
        $vHostCnf['rewrite']['RewriteCond'] = '%{REQUEST_FILENAME} !-f';
        $vHostCnf['rewrite']['RewriteCond '] = '%{REQUEST_FILENAME} !-d';
        $vHostCnf['rewrite']['RewriteRule '] = '. /index.php [L]';
        $vHostCnf['rewrite']['END_rules']   = null;

        $logDefault = Array(
            'DIRECTIVE_VAL' => '$VH_ROOT/logs/error.log',
            'logLevel'      => 'DEBUG',
            'rollingSize'   =>'10M',
            'useServer'     => 0
        );
        foreach ($logDefault as $key => $val)
        {
            $vHostCnf['errorlog'][$key] = $val;
        }

        $accessDefault = Array(
            'DIRECTIVE_VAL'     => '$VH_ROOT/logs/access.log',
            'compressArchive'   => 0,
            'logReferer'        => 1,
            'keepDays'          => 30,
            'rollingSize'       => '10M',
            'logUserAgent'      => 1,
            'useServer'         => 0
        );
        foreach ($accessDefault as $key => $val)
        {
            $vHostCnf['accessLog'][$key] = $val;
        }    
    }

    static public function loadDefaultOlsCnf(&$olsCnfDict)
    {
        self::loadDefaultOlsGlobal($olsCnfDict);
        self::loadDefaultOlsTuning($olsCnfDict);
    }

    static private function getMapKeyValue(&$dict, $key, $defVal)
    {
        if (array_key_exists($key, $dict)) {
            return $dict[$key];
        }
        return $defVal;    
    }

    static private function createOlsDefault(&$scope, &$defaultDict)
    {
        $olsDefault = Array(
            'serverName'	=> 'lshttpd',
            'user'          => 'nobody',
            'group'         => 'nobody',
            'priority'      => 0,
            'autoRestart'	=> 1,
            'chrootPath'	=> '/',
            'enableChroot'	=> 0,
            'inMemBufSize'	=> '60M',
            'swappingDir'	=> '/tmp/lshttpd/swap',
            'autoFix503'	=> 1,
            'gracefulRestartTimeout'=>300,
            'mime'		=> '$SERVER_ROOT/conf/mime.properties',
            'showVersionNumber'=>0,
            'adminEmails'	=> 'root@localhost',
            'adminRoot'     => '$SERVER_ROOT/admin/',
            'indexFiles'	=> 'index.html'
        );

        foreach($olsDefault as $key => $val)
        {
            $scope->addDirective(Directive::mkInst($key, getMapKeyValue($defaultDict,$key, $val)));
        }
    }
}
?>

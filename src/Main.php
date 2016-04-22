<?php
error_reporting(-1);
set_include_path(getHomeDir($argv));

include('ngx/Entity.php');
include('ngx/EmptyLine.php');
include('ngx/Directive.php');
include('ngx/Comment.php');
include('ngx/Content.php');
include('ngx/Brackets.php');
include('ols/NgMapper.php');
include('ols/OlsMaker.php');


/*
 * $ngCnfDict : ( ('.usre','nobdy'), ('location','/abc') ...) 
*/
function convertMain($nginxRoot, $confFile, $olsRoot)
{
    //1)load conf into memory ;
    $rOlsCnfDict = Array();
    $brackets    = Brackets::instFromFile($confFile);
    $rNgCnfDict  = $brackets->toCtxKey();
    
    //2) transfrom
    OlsTmpl::loadDefaultOlsCnf($rOlsCnfDict);
    
    $mapper = new NgMapper($nginxRoot, $olsRoot, $confFile);
    $mapper->transfrom($rNgCnfDict, $rOlsCnfDict);
    //3)create ols conf
    $olsMaker = new OlsMaker();
    $olsMaker->createOlsCnf($rOlsCnfDict, $olsRoot);
    
    $mapper->printStat();
}

function getAppType($root)
{
    $directories = shell_exec("find $root -type d -name wp-admin -print") ;
    if (is_array($directories))
    {
        return 'wp';
    }
}

function printHelp()
{
    echo "php Main.cpp -d <Nginx configure path> -f <configure file > -o <LitSpeed Server Root>\n";
    echo "-d : Nginx configure path\n";
    echo "-f : Nginx configure file \n";
    echo "-o : SERVER_ROOT path\n";
}

function getHomeDir(&$argv)
{
    $len = strlen('/Main.php');
    if ($argv[0][0] == '/' || $argv[0][0] == '~') //absoulte
    {
        $home = substr($argv[0], 0, strlen($argv[0]) - $len);
    }
    else
    {
        $home = getcwd() . '/' . $argv[0];
        $home = substr($home, 0, strlen($home) - $len);
    }
    return $home;
}

function main()
{
    $options    = getopt("n:f:o:d");
    $nginxRoot  = $options['n'];
    $nginxFile  = $options['f'];
    $olsRoot    = $options['o'];
    $GLOBALS['debug'] = key_exists('d', $options);
    
    if (is_null($nginxRoot) || is_null($nginxFile) || is_null($olsRoot))
    {
        printHelp();
        return ;
    }
    //validate nginx folder
    if(!file_exists("{$nginxRoot}"))
    {
        die("ERROR: The folder {$nginxRoot} does not exist\n");
    }
    //validate nginx conf file
    $ngFoundFile = null;
    if (file_exists($nginxFile))
    {
        $ngFoundFile = $nginxFile;
    }
    else
    {
        if (file_exists("{$nginxRoot}/{$nginxFile}"))
        {   $ngFoundFile = "{$nginxRoot}/{$nginxFile}";    }
    }

    if ($ngFoundFile == null)
    {
        die("ERROR: The file {$nginxFile} does not exist\n");
    }

    //create output folder
    if (!file_exists("{$olsRoot}/conf") || !file_exists("{$olsRoot}/conf/vhosts"))
    {
        mkdir("{$olsRoot}/conf/vhosts", 0755, true);
    }
    convertMain($nginxRoot, $ngFoundFile, $olsRoot);
}

//////////////////////////////////////////////// MAIN ///////////////////////////////////////
                                                 main();
/////////////////////////////////////////////////////////////////////////////////////////////
                    
?>

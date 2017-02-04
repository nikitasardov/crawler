<?php
ini_set('log_errors', 'On');
ini_set('error_log', './php_errors.log');

function getSites ($fileAddr) {
    global $argv;
    echo PHP_EOL."=====================================================";
    echo PHP_EOL."checkspell gets sites from $argv[1]:";
    echo PHP_EOL."=====================================================";
    $siteslist = file_get_contents($fileAddr);
    if ($siteslist == false) {
        return false;
    }
 //   echo PHP_EOL.$siteslist;
    $siteslist = explode("\n", strip_tags($siteslist));
    foreach ($siteslist as $s) {
        if (strpos(trim($s),'http') !== false) {
            $urls[] = $s;
        }
    }
    $c = count($urls);
    shell_exec('clear');
    echo PHP_EOL.PHP_EOL."===============================";
    echo PHP_EOL.$c.' sites found';
    echo PHP_EOL."===============================".PHP_EOL;
    return $urls;
}

function getSitePages ($siteAddr) {
    global $argv;
    echo PHP_EOL."=====================================================";
    echo PHP_EOL."checkspell gets sitemap.xml from $siteAddr:";
    echo PHP_EOL."=====================================================";
    $sitemap = file_get_contents($siteAddr.'/sitemap.xml');
    if ($sitemap == false) {
        return false;
    }
 //   echo PHP_EOL.$sitemap;
    $sitemap = explode("\n", strip_tags($sitemap));
    foreach ($sitemap as $s) {
        if (strpos(trim($s),'http') !== false) {
            $urls[] = $s;
        }
    }
    $c = count($urls);
    shell_exec('clear');
    echo PHP_EOL.PHP_EOL."===============================";
    echo PHP_EOL.$c.' urls found';
    echo PHP_EOL."===============================".PHP_EOL;
    return $urls;
}

function spellCheck ($urls) {
    global $argv;
    global $m;
    global $logFile;
    global $sUrl;
    global $rdir;
    echo PHP_EOL,"=====================================================";
    echo PHP_EOL."start checking found urls:";
    echo PHP_EOL,"=====================================================";
    $n = 0;
    $c = count($urls);
    $errurls = 0;

    if (isset($argv[2])) {
        $logFile = $argv[2];
    } else $logFile =  'log__'.$rdir.'.txt';
    echo PHP_EOL;
    foreach ($urls as $u) {
        $n++;
        echo '-----'.PHP_EOL;
        echo "$n из $c: ";
        if (strpos(trim($u),'http') != 0) {
            echo PHP_EOL.'Invalid url skipped'.PHP_EOL;
            `echo "ERROR! Invalid url skipped $u" >> results/$rdir/$logFile`;
            $errurls++;
            continue;
        }
        if (addrAllowed($u) == false) {
            echo PHP_EOL.'Address skipped: '.$u.PHP_EOL.PHP_EOL.PHP_EOL;
            `echo "Address skipped: $u" >> results/$rdir/$logFile`;
            $errurls++;
            continue;
        } else {
            //echo PHP_EOL.'Address ok: '.$u.PHP_EOL;
        }
        echo $u.PHP_EOL;

        `yaspeller --report console,html --find-repeat-words --ignore-latin $u 2>&1 | tee -a results/$rdir/$logFile`;

        $nn = prepareFName($u);
        $fn = "results/$rdir/yasp_$nn.html";
        `mv yaspeller_report.html $fn`;
    }
    echo PHP_EOL."===============================";
    echo PHP_EOL."spellcheck of $argv[1] completed";
    echo PHP_EOL."===============================";
    echo PHP_EOL;
    echo PHP_EOL."Urls found in $argv[1]/sitemap.xml: $c";
    echo PHP_EOL."Error urls skipped: $errurls";
    echo PHP_EOL."Results saved in folder .results/$rdir/";
    echo PHP_EOL . "Log file is here .results/$rdir/$logFile";
    echo PHP_EOL.PHP_EOL;
}

function prepareFName($u) {
    $repl = array(
        '/https:\/\//',
        '/http:\/\//',
        '/\//',
        '/\./');
    return trim(preg_replace($repl, "_", $u));
}

function addrAllowed($u) {
    $ext = array (
        '.zip',
        '.jpg',
        '.png',
        '.gif',
        '.doc',
        '.docx',
        '.pdf'
    );
    $err = 0;
    $errstr = '';
    foreach ($ext as $e) {
        global $errstr;
        if (strpos($u,$e) > 0) {
            $err = 1;
            $errstr = $e;
        } else {
            $err = 0;
            $errstr = '';
        }
        if ($err == 1) {
            echo PHP_EOL."BAD ADDR, $errstr found in: ".$u;
            return false;
        }
        }
    if ($err == 0) {
        //echo PHP_EOL."ADDR OK, error extensions not found in: ".$u;
        return true;
    }
}

function checkSite($siteurl) {
    global $m;
    global $m_errors;
    global $sUrl;
    global $rdir;
    global $logFile;
    $sUrl = $siteurl;
    $urls = getSitePages($siteurl);
    mkResultsDir();
    if ($urls == false) {
        echo PHP_EOL . "=====================================================";
        echo PHP_EOL . "ERROR! no $siteurl/sitemap.xml found";
        echo PHP_EOL . "=====================================================";
        echo PHP_EOL . "spellcheck stopped";
        echo PHP_EOL . PHP_EOL;
        if ($m == 1) {
            $m_errors[] = "ERROR: no sitemap found: $siteurl";
        }
        $logFile =  'log__'.$rdir.'.txt';
        `echo "ERROR! sitemap.xml not found in $siteurl" >> results/$rdir/$logFile`;
    } else {
        spellCheck($urls);
    }
}

function checkSites($sitelist) {
    global $rdir;
    global $logFile;
    global $m_errors;
    global $sUrl;
    $sites = getSites($sitelist);
    $rdir = 'results/'.prepareFName($sUrl);
    if ($sites == false) {
        echo PHP_EOL . "=====================================================";
        echo PHP_EOL . "ERROR! no sites found in $sitelist";
        echo PHP_EOL . "=====================================================";
        echo PHP_EOL . "spellcheck stopped";
        echo PHP_EOL . PHP_EOL;
    } else {
        foreach ($sites as $s) {
            $sUrl = $s;
            checkSite($s);
        }
    }
    echo PHP_EOL."===============================";
    echo PHP_EOL."Finished checking sites found in $sitelist";
    echo PHP_EOL."===============================";
    echo PHP_EOL;
    if (isset($m_errors)) {
        echo PHP_EOL . "=====================================================";
        foreach ($m_errors as $e) {
            echo PHP_EOL . $e;
        }
        echo PHP_EOL . "=====================================================";
        echo PHP_EOL.PHP_EOL;
    }
    echo PHP_EOL . "Log file is here .results/$rdir/$logFile";
    echo PHP_EOL.PHP_EOL;
}

function mkResultsDir() {
    global $rdir;
    global $logFile;
    global $sUrl;
    $rdir = prepareFName($sUrl);
    `mkdir results`;
    //`cd results/`;
    `mkdir results/$rdir`;
    //`cd ../`;
}

system('clear');

if (!isset($argv[1])) {
    echo PHP_EOL . "=====================================================";
    echo PHP_EOL . "no sites to check";
    echo PHP_EOL . "=====================================================";
    echo PHP_EOL . "command should be like this:";
    echo PHP_EOL . "1. php checkspell.php http://sitename.com  #to check single site";
    echo PHP_EOL . "2. php checkspell.php listOfSites.txt result.txt -m  #to check all sites in the list";
    echo PHP_EOL;
    echo PHP_EOL . "checkspell stopped";
    echo PHP_EOL . PHP_EOL;
    die;
}

if (isset($argv[3])&&$argv[3] == '-m') {
    $m = 1; //check a more than 1 site
    echo '$m = '.$m;
    checkSites($argv[1]);
} else {
    $m = 0; //check just 1 site
    checkSite($argv[1]);
}
?>
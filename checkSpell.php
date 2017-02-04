<?php
function getUrls ($siteAddr) {
    global $argv;
    $sitemap = file_get_contents($siteAddr.'/sitemap.xml');
    echo $sitemap;
    $sitemap = explode("\n", strip_tags($sitemap));
    foreach ($sitemap as $s) {
        if (strpos(trim($s),'http') !== false) {
            $urls[] = $s;
        }
    }
    $c = count($urls);
    shell_exec('clear');
    echo $c.' urls found'.PHP_EOL;
    return $urls;
}

function spellCheck ($urls) {
    global $argv;
    $n = 0;
    $c = count($urls);
    $resultFile =  'r__'.$argv[2];
    $rdir = prepareFName($argv[1]);
    echo PHP_EOL;
    `mkdir $rdir`;
    foreach ($urls as $u) {
        $n++;
        echo '-----'.PHP_EOL;
        echo "$n из $c: $u".PHP_EOL;

        if (addrAllowed($u) == false) {
            echo PHP_EOL.'Address skipped: '.$u.PHP_EOL;
            continue;
        } else {
            //echo PHP_EOL.'Address ok: '.$u.PHP_EOL;
        }

        `yaspeller --report console,html --find-repeat-words --ignore-latin $u 2>&1 | tee -a $rdir/$resultFile`;
        $nn = prepareFName($u);
        $fn = "$rdir/yasp_$nn.html";
        `mv yaspeller_report.html $fn`;
    }
    $raddr = `pwd`;
    echo PHP_EOL,"Results saved in $raddr/$rdir/";
    echo PHP_EOL,"===============================";
    echo PHP_EOL,"spellCheck completed";
    echo PHP_EOL,"===============================";
    echo PHP_EOL;
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

system('clear');

echo PHP_EOL,"=====================================================";
echo PHP_EOL."checkspell gets sitemap.xml from $argv[1]:";
echo PHP_EOL,"=====================================================";

$urls = getUrls($argv[1]);

echo PHP_EOL,"=====================================================";
echo PHP_EOL."start checking found urls:";
echo PHP_EOL,"=====================================================";

spellCheck ($urls);
?>
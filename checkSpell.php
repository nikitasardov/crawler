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
    echo $c.' урлов в обнаружено'.PHP_EOL;
    return $urls;
}

function spellCheck ($urls) {
    global $argv;
    $n = 0;
    $c = count($urls);
    $resultFile =  'r__'.$argv[2];
    $rdir = prepareFName($argv[1]);
    `mkdir $rdir`;
    foreach ($urls as $u) {
        $n++;
        echo '-----'.PHP_EOL;
        echo "$n из $c: $u".PHP_EOL;
        `yaspeller --report console,html,custom_report --find-repeat-words $u 2>&1 | tee -a $rdir/$resultFile`;
        $nn = prepareFName($u);
        $fn = "$rdir/yasp_$nn.html";
        `mv yaspeller_report.html $fn`;
    }
    echo PHP_EOL,"Results saved in $rdir/ here: ";
    echo `pwd`;
    echo PHP_EOL,"spellCheck completed";
    echo PHP_EOL;
}

function prepareFName($u) {
    $repl = array('/https:\/\//','/http:\/\//','/\//','/\./');
    return trim(preg_replace($repl, "_", $u));
}

$urls = getUrls($argv[1]);
spellCheck ($urls);
?>
<?php

class Cloaker
{
    protected $REDIRECT_URL = 'https://google.co.id/';

    protected $BLOCKED_COUNTRY_CODES = ['ID'];

    protected $BLOCKED_USER_AGENTS = [
        'Googlebot', 'Googlebot-Image', 'Googlebot-News', 'Googlebot-Video', 'Storebot-Google', 'GoogleOther', 'APIs-Google', 'AdsBot-Google-Mobile', 'AdsBot-Google', 'Mediapartners-Google', 'Mediapartners-Google', 'FeedFetcher-Google', 'facebookexternalhit', 'Facebot'
    ];

    protected $blocked = false;

    protected $errors = [];

    public function isBlocked()
    {
        return $this->blocked;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getRedirectUrl()
    {
        return $this->REDIRECT_URL;
    }

    public function getBlockedUserAgents()
    {
        $search = '';
        if (count($this->BLOCKED_USER_AGENTS)) {
            $search = implode('|', $this->BLOCKED_USER_AGENTS);
            $search = preg_quote($search) . '|';
        }
        $search = $search . 'googlebot|bot|Googlebot-Mobile|Googlebot-Image|Google favicon|Mediapartners-Google|bingbot|slurp|java|wget|curl|Commons-HttpClient|Python-urllib|libwww|httpunit|nutch|phpcrawl|msnbot|jyxobot|FAST-WebCrawler|FAST Enterprise Crawler|biglotron|teoma|convera|seekbot|gigablast|exabot|ngbot|ia_archiver|GingerCrawler|webmon |httrack|webcrawler|grub.org|UsineNouvelleCrawler|antibot|netresearchserver|speedy|fluffy|bibnum.bnf|findlink|msrbot|panscient|yacybot|AISearchBot|IOI|ips-agent|tagoobot|MJ12bot|dotbot|woriobot|yanga|buzzbot|mlbot|yandexbot|purebot|Linguee Bot|Voyager|CyberPatrol|voilabot|baiduspider|citeseerxbot|spbot|twengabot|postrank|turnitinbot|scribdbot|page2rss|sitebot|linkdex|Adidxbot|blekkobot|ezooms|dotbot|Mail.RU_Bot|discobot|heritrix|findthatfile|europarchive.org|NerdByNature.Bot|sistrix crawler|ahrefsbot|Aboundex|domaincrawler|wbsearchbot|summify|ccbot|edisterbot|seznambot|ec2linkfinder|gslfbot|aihitbot|intelium_bot|facebookexternalhit|yeti|RetrevoPageAnalyzer|lb-spider|sogou|lssbot|careerbot|wotbox|wocbot|ichiro|DuckDuckBot|lssrocketcrawler|drupact|webcompanycrawler|acoonbot|openindexspider|gnam gnam spider|web-archive-net.com.bot|backlinkcrawler|coccoc|integromedb|content crawler spider|toplistbot|seokicks-robot|it2media-domain-crawler|ip-web-crawler.com|siteexplorer.info|elisabot|proximic|changedetection|blexbot|arabot|WeSEE:Search|niki-bot|CrystalSemanticsBot|rogerbot|360Spider|psbot|InterfaxScanBot|Lipperhey SEO Service|CC Metadata Scaper|g00g1e.net|GrapeshotCrawler|urlappendbot|brainobot|fr-crawler|binlar|SimpleCrawler|Livelapbot|Twitterbot|cXensebot|smtbot|bnf.fr_bot|A6-Indexer|ADmantX|Facebot|Twitterbot|OrangeBot|memorybot|AdvBot|MegaIndex|SemanticScholarBot|ltx71|nerdybot|xovibot|BUbiNG|Qwantify|archive.org_bot|Applebot|TweetmemeBot|crawler4j|findxbot|SemrushBot|yoozBot|lipperhey|y!j-asr|Domain Re-Animator Bot';
        $search = '/' . $search . '/i';

        return $search;
    }

    protected function getIpAddress()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    protected function getIpApi($ip)
    {
        if (!empty($ip)) {
            try {
                $response = json_decode(file_get_contents('http://ip-api.com/json/'.$ip.'?fields=status,message,country,countryCode,mobile,proxy,hosting,query'), true);

                if ($response && $response['status'] == "success") {
                    $ipapi = $response;
                    return $ipapi;
                }
            } catch (Exception $e) {
                    $this->errors[] = $e->getMessage();
                    return false;
            }
        } else {
            return false;
        }
    }
    
    protected function getIpShodan($ip)
    {
        if (!empty($ip)) {
            try {
                $response = json_decode(file_get_contents('https://internetdb.shodan.io/'.$ip), true);

                if ($response['detail'] && $response['detail'] == "No information available") {
                    return false;
                } else {
                    return $response['tags'];
                }
            } catch (Exception $e) {
                    $this->errors[] = $e->getMessage();
                    return false;
            }
        } else {
            return false;
        }
    }

    public function checkUserAgent()
    {
        $search = $this->getBlockedUserAgents();
        if (preg_match($search, $_SERVER['HTTP_USER_AGENT'])) {
            return 'Bot';
        }
        else {
            return 'Human';
        }
    }

    public function checkIpAddress()
    {
        $ip = $this->getIpAddress();
        $ipapi = $this->getIpApi($ip);

        if ($ipapi) {
            if (in_array($ipapi['countryCode'], $this->BLOCKED_COUNTRY_CODES)) {
                if ($ipapi['hosting']) {
                    return 'IpHosting';
                } 
                elseif ($ipapi['proxy']) {
                    return 'IpBlocked';
                }
                else {
                    return 'IpBlocked';
                }
            } 
            else {
                return 'IpOther';
            }
        } else {
            return 'IpApiError';
        }
    }

    public function check()
    {
        $agent = $this->checkUserAgent();
        $IpAdress = $this->checkIpAddress();

        if ($agent == 'Human' && $IpAdress == 'IpBlocked') {
            return 'Redirect';
        } else {
            return 'Stay';
        }
    }
}

// Create new check instance
$cloaker = new Cloaker();
// Run the checks
$check = $cloaker->check();

if ($check == 'Redirect') { 
    header("Location: ".$cloaker->getRedirectUrl(), true, 301);
    exit();
} else {
    header("Location: https://mercusuar.uzone.id", true, 301);
    exit();
}

?>

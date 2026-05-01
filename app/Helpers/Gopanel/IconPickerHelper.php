<?php

namespace App\Helpers\Gopanel;

use Illuminate\Support\Facades\Cache;

/**
 * Parses /public/assets/gopanel/css/icons.min.css and returns icon class lists
 * for the global icon picker modal (Font Awesome, Boxicons, Material Design,
 * Dripicons). Result is cached for 7 days because the CSS is static.
 */
class IconPickerHelper
{
    private const CACHE_KEY = 'gopanel.icon_picker.list.v2';
    private const CACHE_TTL = 60 * 60 * 24 * 7;

    /**
     * Subset of Font Awesome 5.13 Free Brands icon names. Used to decide
     * whether a `fa-xxx` class should be rendered with the `fab` or `fas`
     * prefix in the picker.
     */
    private const FA_BRAND_NAMES = [
        '500px','accessible-icon','accusoft','acquisitions-incorporated','adn','adobe','adversal','affiliatetheme','airbnb','algolia',
        'alipay','amazon','amazon-pay','amilia','android','angellist','angrycreative','angular','app-store','app-store-ios','apper','apple',
        'apple-pay','artstation','asymmetrik','atlassian','audible','autoprefixer','avianex','aviato','aws','bandcamp','battle-net','behance',
        'behance-square','bimobject','bitbucket','bitcoin','bity','black-tie','blackberry','blogger','blogger-b','bluetooth','bluetooth-b',
        'bootstrap','btc','buffer','buromobelexperte','buy-n-large','buysellads','canadian-maple-leaf','cc-amazon-pay','cc-amex','cc-apple-pay',
        'cc-diners-club','cc-discover','cc-jcb','cc-mastercard','cc-paypal','cc-stripe','cc-visa','centercode','centos','chrome','chromecast',
        'cloudflare','cloudscale','cloudsmith','cloudversify','codepen','codiepie','confluence','connectdevelop','contao','cotton-bureau',
        'cpanel','creative-commons','creative-commons-by','creative-commons-nc','creative-commons-nc-eu','creative-commons-nc-jp','creative-commons-nd',
        'creative-commons-pd','creative-commons-pd-alt','creative-commons-remix','creative-commons-sa','creative-commons-sampling','creative-commons-sampling-plus',
        'creative-commons-share','creative-commons-zero','critical-role','css3','css3-alt','cuttlefish','d-and-d','d-and-d-beyond','dailymotion',
        'dashcube','deezer','delicious','deploydog','deskpro','dev','deviantart','dhl','diaspora','digg','digital-ocean','discord','discourse',
        'dochub','docker','draft2digital','dribbble','dribbble-square','dropbox','drupal','dyalog','earlybirds','ebay','edge','edge-legacy',
        'elementor','ello','ember','empire','envira','erlang','ethereum','etsy','evernote','expeditedssl','facebook','facebook-f','facebook-messenger',
        'facebook-square','fantasy-flight-games','fedex','fedora','figma','firefox','firefox-browser','first-order','first-order-alt','firstdraft',
        'flickr','flipboard','fly','font-awesome','font-awesome-alt','font-awesome-flag','fonticons','fonticons-fi','fort-awesome','fort-awesome-alt',
        'forumbee','foursquare','free-code-camp','freebsd','fulcrum','galactic-republic','galactic-senate','get-pocket','gg','gg-circle','git',
        'git-alt','git-square','github','github-alt','github-square','gitkraken','gitlab','gitter','glide','glide-g','gofore','goodreads',
        'goodreads-g','google','google-drive','google-pay','google-play','google-plus','google-plus-g','google-plus-square','google-wallet',
        'gratipay','grav','gripfire','grunt','guilded','gulp','hacker-news','hacker-news-square','hackerrank','hips','hire-a-helper','hive',
        'hooli','hornbill','hotjar','houzz','html5','hubspot','ideal','imdb','innosoft','instagram','instagram-square','instalod','intercom',
        'internet-explorer','invision','ioxhost','itch-io','itunes','itunes-note','java','jenkins','jira','joget','joomla','js','js-square',
        'jsfiddle','kaggle','keybase','keycdn','kickstarter','kickstarter-k','korvue','laravel','lastfm','lastfm-square','leanpub','less',
        'line','linkedin','linkedin-in','linode','linux','lyft','magento','mailchimp','mandalorian','markdown','mastodon','maxcdn','mdb',
        'medapps','medium','medium-m','medrt','meetup','megaport','mendeley','microblog','microsoft','mix','mixcloud','mixer','mizuni',
        'modx','monero','napster','neos','nimblr','node','node-js','npm','ns8','nutritionix','octopus-deploy','odnoklassniki','odnoklassniki-square',
        'old-republic','opencart','openid','opera','optin-monster','orcid','osi','page4','pagelines','palfed','patreon','paypal','penny-arcade',
        'perbyte','periscope','phabricator','phoenix-framework','phoenix-squadron','php','pied-piper','pied-piper-alt','pied-piper-hat','pied-piper-pp',
        'pied-piper-square','pinterest','pinterest-p','pinterest-square','playstation','product-hunt','pushed','python','qq','quinscape','quora',
        'r-project','raspberry-pi','ravelry','react','reacteurope','readme','rebel','red-river','reddit','reddit-alien','reddit-square','redhat',
        'renren','replyd','researchgate','resolving','rev','rocketchat','rockrms','rust','safari','salesforce','sass','schlix','scribd','searchengin',
        'sellcast','sellsy','servicestack','shirtsinbulk','shopify','shopware','simplybuilt','sistrix','sith','sketch','skyatlas','skype','slack',
        'slack-hash','slideshare','snapchat','snapchat-ghost','snapchat-square','soundcloud','sourcetree','speakap','speaker-deck','spotify','squarespace',
        'stack-exchange','stack-overflow','stackpath','staylinked','steam','steam-square','steam-symbol','sticker-mule','strava','stripe','stripe-s',
        'studiovinari','stumbleupon','stumbleupon-circle','superpowers','supple','suse','swift','symfony','teamspeak','telegram','telegram-plane',
        'tencent-weibo','the-red-yeti','themeco','themeisle','think-peaks','tiktok','trade-federation','trello','tripadvisor','tumblr','tumblr-square',
        'twitch','twitter','twitter-square','typo3','uber','ubuntu','uikit','umbraco','uncharted','uniregistry','unity','unsplash','untappd','ups',
        'usb','usps','ussunnah','vaadin','viacoin','viadeo','viadeo-square','viber','vimeo','vimeo-square','vimeo-v','vine','vk','vnv','vuejs',
        'watchman-monitoring','waze','weebly','weibo','weixin','whatsapp','whatsapp-square','whmcs','wikipedia-w','windows','wix','wizards-of-the-coast',
        'wodu','wolf-pack-battalion','wordpress','wordpress-simple','wpbeginner','wpexplorer','wpforms','wpressr','xbox','xing','xing-square',
        'y-combinator','yahoo','yammer','yandex','yandex-international','yarn','yelp','yoast','youtube','youtube-square','zhihu',
    ];

    /**
     * Subset of Font Awesome 5.13 Free Regular icon names. We list these so
     * that the picker exposes them with the correct `far` prefix; Free FA
     * only supplies a handful of regular icons.
     */
    private const FA_REGULAR_NAMES = [
        'address-book','address-card','angry','arrow-alt-circle-down','arrow-alt-circle-left','arrow-alt-circle-right','arrow-alt-circle-up',
        'bell','bell-slash','bookmark','building','calendar','calendar-alt','calendar-check','calendar-minus','calendar-plus','calendar-times',
        'caret-square-down','caret-square-left','caret-square-right','caret-square-up','chart-bar','check-circle','check-square','circle',
        'clipboard','clock','clone','closed-captioning','comment','comment-alt','comment-dots','comments','compass','copy','copyright',
        'credit-card','dizzy','dot-circle','edit','envelope','envelope-open','eye','eye-slash','file','file-alt','file-archive','file-audio',
        'file-code','file-excel','file-image','file-pdf','file-powerpoint','file-video','file-word','flag','flushed','folder','folder-open',
        'frown','frown-open','futbol','gem','grimace','grin','grin-alt','grin-beam','grin-beam-sweat','grin-hearts','grin-squint','grin-squint-tears',
        'grin-stars','grin-tears','grin-tongue','grin-tongue-squint','grin-tongue-wink','grin-wink','hand-lizard','hand-paper','hand-peace',
        'hand-point-down','hand-point-left','hand-point-right','hand-point-up','hand-pointer','hand-rock','hand-scissors','hand-spock','handshake',
        'hdd','heart','hospital','hourglass','id-badge','id-card','image','images','keyboard','kiss','kiss-beam','kiss-wink-heart','laugh',
        'laugh-beam','laugh-squint','laugh-wink','lemon','life-ring','lightbulb','list-alt','map','meh','meh-blank','meh-rolling-eyes','minus-square',
        'money-bill-alt','moon','newspaper','object-group','object-ungroup','paper-plane','paste','pause-circle','play-circle','plus-square',
        'question-circle','registered','sad-cry','sad-tear','save','share-square','smile','smile-beam','smile-wink','snowflake','square','star',
        'star-half','sticky-note','stop-circle','sun','surprise','thumbs-down','thumbs-up','times-circle','tired','trash-alt','user','user-circle',
        'window-close','window-maximize','window-minimize','window-restore',
    ];

    /**
     * Returns a flat list of every icon class string supported by the picker,
     * grouped by provider key. The format consumed by the JS picker is:
     *
     *     [
     *         'fa'  => ['fas fa-home', 'fab fa-github', ...],
     *         'bx'  => ['bx bx-home', 'bx bxs-bell', 'bx bxl-github', ...],
     *         'mdi' => ['mdi mdi-home', ...],
     *         'drp' => ['dripicons dripicons-anchor', ...],
     *     ]
     */
    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $cssPath = public_path('assets/gopanel/css/icons.min.css');
            $css     = is_readable($cssPath) ? file_get_contents($cssPath) : '';

            return [
                'fa'  => self::extractFa($css),
                'bx'  => self::extractBoxicons($css),
                'mdi' => self::extractMdi($css),
                'drp' => self::extractDripicons($css),
            ];
        });
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function extractFa(string $css): array
    {
        preg_match_all('/\.(fa-[a-z0-9-]+)::?before\{content:/', $css, $m);

        $brands  = array_flip(self::FA_BRAND_NAMES);
        $regular = array_flip(self::FA_REGULAR_NAMES);

        $seen = [];
        $out  = [];

        foreach ($m[1] as $cls) {
            if (isset($seen[$cls])) {
                continue;
            }
            $seen[$cls] = true;

            $name = substr($cls, 3);

            if (isset($brands[$name])) {
                $out[] = 'fab ' . $cls;
            } elseif (isset($regular[$name])) {
                $out[] = 'fas ' . $cls;
                $out[] = 'far ' . $cls;
            } else {
                $out[] = 'fas ' . $cls;
            }
        }

        sort($out);
        return array_values(array_unique($out));
    }

    private static function extractBoxicons(string $css): array
    {
        preg_match_all('/\.(bx-[a-z0-9-]+|bxs-[a-z0-9-]+|bxl-[a-z0-9-]+)::?before\{content:/', $css, $m);

        $seen = [];
        $out  = [];
        foreach ($m[1] as $cls) {
            if (isset($seen[$cls])) {
                continue;
            }
            $seen[$cls] = true;
            $out[] = 'bx ' . $cls;
        }

        sort($out);
        return $out;
    }

    private static function extractMdi(string $css): array
    {
        preg_match_all('/\.(mdi-[a-z0-9-]+)::?before\{content:/', $css, $m);

        $seen = [];
        $out  = [];
        foreach ($m[1] as $cls) {
            if (isset($seen[$cls])) {
                continue;
            }
            $seen[$cls] = true;
            $out[] = 'mdi ' . $cls;
        }

        sort($out);
        return $out;
    }

    private static function extractDripicons(string $css): array
    {
        preg_match_all('/\.(dripicons-[a-z0-9-]+)::?before\{content:/', $css, $m);

        $seen = [];
        $out  = [];
        foreach ($m[1] as $cls) {
            if (isset($seen[$cls])) {
                continue;
            }
            $seen[$cls] = true;
            // Dripicons uses [class^=dripicons-]:before so no wrapper class
            // is needed; the icon class itself is enough.
            $out[] = $cls;
        }

        sort($out);
        return $out;
    }
}

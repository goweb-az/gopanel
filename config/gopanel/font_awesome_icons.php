<?php

/**
 * Font Awesome 5.13 Free icon classification.
 *
 * `brands` — names that ship in `fab` style only (e.g. fab fa-github).
 * `regular` — names that have a `far` variant in addition to the default `fas`.
 *
 * Anything not present in either list defaults to `fas`.
 *
 * Used by App\Helpers\Gopanel\IconPickerHelper to pick the correct
 * weight prefix when emitting the picker icon list.
 */

return [
    'brands' => [
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
    ],

    'regular' => [
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
    ],
];

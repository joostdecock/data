<?php

if(!function_exists('getReferralGroups')) {
    function getReferralGroups()
    {
        return [
            'Freesewing' => [
                'host' => 'freesewing.org',
                'link' => 'https://freesewing.org/',
            ],
            'MakeMyPattern' => [
                'host' => 'makemypattern.com',
                'link' => 'https://makemypattern.com',
            ],
            'Facebook' => [
                'host' => 'facebook.com',
                'url' => '://l.messenger.com',
                'link' => 'https://facebook.com',
            ],
            'Twitter' => [
                'host' => 'twitter.com',
                'url' => '://t.co/',
                'link' => 'https://twitter.com',
            ],
            'Instagram' => [
                'urls' => [ 
                    '://l.instagram.com',
                    '://www.instagram.com',
                ],
                'link' => 'https://instagram.com',
            ],
            'Pinterest' => [
                'host' => 'pinterest.com',
                'urls' => [
                    '://www.pinterest.',
                    '://pinterest.',
                ],
                'link' => 'https://instagram.com',
            ],
            'Reddit' => [
                'host' => 'reddit.com',
                'link' => 'https://reddit.com',
            ],
            'GitHub' => [
                'host' => 'github.com',
                'link' => 'https://reddit.com',
            ],
            'PatternReview.com' => [
                'host' => 'sewing.patternreview.com',
                'link' => 'https://patternreview.com',
            ],
            'Yandex' => [
                'host' => 'yandex.ru',
                'link' => 'http://yandex.ru/',
            ],
            'StayStrongDreamBig' => [
                'host' => 'staystrongdreambig.com',
                'link' => 'http://staystrongdreambig.com/',
            ],
            'AnnekeCaramin' => [
                'host' => 'www.annekecaramin.com',
                'link' => 'http://www.annekecaramin.com/',
            ],
            'Sewcialists' => [
                'host' => 'sewcialists.wordpress.com',
                'link' => 'https://sewcialists.wordpress.com/',
            ],
            'SewingPlums' => [
                'hosts' => [
                    'sewingplums.com',
                    'sewingplums-com.cdn.ampproject.org',
                ],
                'link' => 'https://sewingplums.com/',
            ],
            'Textillia' => [
                'host' => 'www.textillia.com',
                'link' => 'https://www.textillia.com/',
            ],
            'Cotton & Curls' => [
                'host' => 'www.cottonandcurls.com',
                'link' => 'https://www.cottonandcurls.com/',
            ],
            'HackerNews' => [
                'urls' => [
                    '://news.ycombinator.com', 
                    '://hn.algolia.com',
                ],
                'link' => 'https://news.ycombinator.com/',
            ],
            'Makery.uk' => [
                'host' => 'www.makery.uk',
                'link' => 'http://www.makery.uk/',
            ],
            'KnitWitsOwls' => [
                'url' => '://knitwits-owls.blogspot.',
                'link' => 'http://knitwits-owls.blogspot.com/',
            ],
            'MailChimp' => [
                'hosts' => [
                    '.campaign-archive2.com',
                    '.campaign-archive1.com',
                    'mailchi.mp',
                ],
                'link' => 'https://mailchimp.com/',
            ],
            'Digg' => [
                'host' => 'digg.com',
                'link' => 'http://digg.com/',
            ],
            'Tumblr' => [
                'host' => 't.umblr.com',
                'link' => 'https://www.tumblr.com/',
            ],
            '4Chan' => [
                'host' => '.4chan.org',
                'link' => 'http://4chan.org/',
            ],
            'Thread & Needles' => [
                'host' => 'www.threadandneedles.fr',
                'link' => 'http://www.threadandneedles.fr/',
            ],
            'Diary of a Chainstitcher' => [
                'url' => '://chainstitcher.blogspot.',
                'link' => 'http:/chainstitcher.blogspot.com/',
            ],
            'Google+' => [
                'hosts' => [
                    'plus.google.com',
                    'plus.url.google.com',
                    'notifications.google.com',
                ],
                'link' => 'http://4chan.org/',
            ],
            'Ravelry' => [
                'host' => 'www.ravelry.com',
                'link' => 'http://www.ravelry.com/',
            ],
            'Web Godess' => [
                'host' => 'www.web-goddess.org',
                'link' => 'https://www.web-goddess.org/',
            ],
            'Cutton & Tailor' => [
                'host' => 'www.cutterandtailor.com',
                'link' => 'http://www.cutterandtailor.com/',
            ],
            'YouTube' => [
                'host' => 'www.youtube.com',
                'link' => 'https://www.youtube.com/',
            ],
            'Email' => [
                'hosts' => [
                    'mail',
                    'outlook.live.com',
                    'mail.google.com',
                    'mail.',
                ],
                'urls' => [
                    'squirrelmail',
                    'deref-gmx.net/mail',
                    'deref-gmx.com/mail',
                    '://owa.',
                    '/owa/',
                    '/zimbra/',
                    '/mail/',
                    '.campaign-archive.com/',
                ],
            ],
            'Search' => [
                'host' => 'search.yahoo.com',
                'urls' => [
                    '://www.ask.com',
                    '://www.google.',
                    '://www.bing.com',
                    '://search.',
                    'avg.nation.com',
                    '/duckduckgo.',
                    '://www.startpage.com',
                ],
            ],
            'Feed reader' => [
                'hosts' => [
                    'feedly.com',
                    'newsblur.com',
                    'www.feedspot.com',
                    'getpocket.com',
                    'keep.google.com',
                    'theoldreader.com',
                ],
            ],
            'Mobile App' => [
                'urls' => [
                  'app://',
                  '://web.telegram.org'
                ],
            ],
            'OpenSource.com' => [
                'host' => 'opensource.com',
                'link' => 'https://opensource.com/'
            ],
            'Unknown' => [
                'urls' => [
                    '192.168',
                    'alert.scansafe.net',
                    '://secureurl.fwdcdn.com/',
                ],
            ],

        ];
    }
}

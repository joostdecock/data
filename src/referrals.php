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
            '4Chan' => [
                'host' => '.4chan.org',
                'link' => 'http://4chan.org/',
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
                    '/zimbra/',
                ],
            ],
            'Search' => [
                'host' => 'search.yahoo.com',
                'urls' => [
                    '://www.google.',
                    '://www.bing.com',
                    '://search.',
                    'avg.nation.com',
                    '/duckduckgo.',
                ],
            ],
            'Feed reader' => [
                'hosts' => [
                    'feedly.com',
                    'newsblur.com',
                    'www.feedspot.com',
                    'getpocket.com',
                    'keep.google.com',
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
                ],
            ],

        ];
    }
}

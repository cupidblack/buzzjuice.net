<?php
if (!defined('ABSPATH')) exit;

/**
 * Add policies for each element/feature you'd like runtime-gated.
 */
add_filter('bzj_bre_registry', function($registry){

    // 1. Gate: New Discussion Button
    $registry['a.btn-new-topic'] = [
        'policy_version' => 1,
        'priority' => 300,
        'extends'  => 'premium',
        'gate'     => ['verified','subscription','points'],
        'logic'    => [
            'required'=>['verified'],
            'optional_any'=>['subscription','points'],
            'override_any'=>['role'],
        ],
        'requirements' => [
            'verified'=>true,
            'subscription'=>'premium',
            'points'=>100,
            'roles'=>['administrator','bbp_moderator','staff']
        ],
        'mode'     => 'smart', // auto-blur/replace/preview
        'messages' => [
//            'above'   => 'Start discussions.',
            'main'    => 'Recharge or subscribe to create discussions.',
//            'below'   => 'Recharge or subscribe.',
        ],
        'show_main'=> true,
        'actions'  => [
            'primary'  => 'buy_points',
            'secondary'=> 'upgrade',
            'fallback' => 'login',
        ]
    ];

    // 2. Gate: Reply Button
    $registry['p.bb-topic-reply-link-wrap'] = [
        'policy_version'=>1,
        'priority'=>290,
        'gate'=>['verified','points'],
        'logic'=>[
            'required'=>['verified'],
            'optional_any'=>['points']
        ],
        'requirements'=>[
            'verified'=>true,
            'points'=>100
        ],
        'mode'=>'replace',
        'messages'=>[
            'main'=>'Recharge to reply to discussions.',
//            'below'=>'Recharge your Palmier balance to continue.',
        ],
        'show_main'=>true,
        'actions'=>[
            'primary'=>'buy_points',
            'fallback'=>'login',
        ]
    ];

    // 3. Gate: TotalPoll "Other" Input
    $registry['label.totalpoll-question-choices-other'] = [
        'policy_version'=>1,
        'priority'=>280,
        'gate'=>['subscription'],
        'requirements'=>['subscription'=>'premium'],
        'mode'=>'replace',
        'messages'=>[
            'above'=>'Premium Poll Feature',
            'main'=>'Activate a subscription to submit custom poll answers.',
        ],
        'show_main'=>true,
        'actions'=>[
            'primary'=>'upgrade'
        ]
    ];

    return $registry;
});
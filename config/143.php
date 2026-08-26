<?php
return [
    'access_token_minutes' => (int) env('ACCESS_TOKEN_MINUTES', 30),
    'refresh_token_days' => (int) env('REFRESH_TOKEN_DAYS', 30),
    'invite_hours' => (int) env('COUPLE_INVITE_HOURS', 72),
    'upload_disk' => env('UPLOAD_DISK', 's3'),
    'legal' => ['required_types' => ['terms','privacy']],
    'ice_servers' => array_values(array_filter([
        ['urls' => env('STUN_URL', 'stun:stun.l.google.com:19302')],
        env('TURN_URL') ? ['urls'=>env('TURN_URL'),'username'=>env('TURN_USERNAME'),'credential'=>env('TURN_CREDENTIAL')] : null,
    ])),
    'plans' => [
        'plus_monthly' => ['name'=>'143 Plus Monthly','amount_lkr'=>399,'interval'=>'month','paypal_plan_id'=>env('PAYPAL_PLUS_MONTHLY_PLAN_ID')],
        'plus_yearly' => ['name'=>'143 Plus Yearly','amount_lkr'=>4000,'interval'=>'year','paypal_plan_id'=>env('PAYPAL_PLUS_YEARLY_PLAN_ID')],
        'together_monthly' => ['name'=>'143 Together Monthly','amount_lkr'=>599,'interval'=>'month','paypal_plan_id'=>env('PAYPAL_TOGETHER_MONTHLY_PLAN_ID')],
        'together_yearly' => ['name'=>'143 Together Yearly','amount_lkr'=>7000,'interval'=>'year','paypal_plan_id'=>env('PAYPAL_TOGETHER_YEARLY_PLAN_ID')],
    ],
];

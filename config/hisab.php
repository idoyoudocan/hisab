<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin (owner) emails
    |--------------------------------------------------------------------------
    | Comma-separated list of emails allowed into the owner admin panel
    | (/admin). Set HISAB_ADMIN_EMAILS in .env, e.g.:
    |   HISAB_ADMIN_EMAILS="owner@example.com,partner@example.com"
    | No email is hardcoded anywhere; leave empty to disable the panel.
    */
    'admin_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('HISAB_ADMIN_EMAILS', ''))
    ))),
];

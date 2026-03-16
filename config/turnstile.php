<?php

return [
    "secret" => env("TURNSTILE_SECRET", ""),
    "site_key" => env("TURNSTILE_SITE_KEY", ""),
    "verify_url" => "https://challenges.cloudflare.com/turnstile/v0/siteverify",
];

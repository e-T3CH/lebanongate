<?php

declare(strict_types=1);

return [
    'required' => 'This field is required.',
    'email' => 'Enter a valid email address.',
    'url' => 'Enter a full web address starting with http:// or https://.',
    'between' => 'Enter a whole number from :min to :max.',
    'admin_path' => 'Use 4–41 lowercase letters, digits or hyphens (not en, ar, fr, install, assets, uploads or api).',
    'ip_list' => 'Enter valid IP addresses or CIDR ranges, one per line.',
    'ip_list_self' => 'Your current IP address (:ip) is not in the list, so you would lock yourself out.',
    'https_required' => 'Open the admin panel over https:// before forcing HTTPS.',
    'two_factor_required_self' => 'Enable two-factor authentication on your own account first.',
    'password_min' => 'Use at least :min characters.',
    'password_max' => 'The password is too long.',
    'password_common' => 'This password is too common or too simple.',
    'password_contains_email' => 'The password must not contain your email name.',
    'password_confirm' => 'The passwords do not match.',
    'language_unknown' => 'Unknown language.',
    'language_none_enabled' => 'Enable at least one language.',
    'language_default_unknown' => 'Choose a default language.',
    'language_default_disabled' => 'The default language must be enabled.',
    'max_length' => 'Use at most :max characters.',
    'min_length' => 'Use at least :min characters.',
    'phone' => 'Enter a phone number we can call, e.g. +961 3 123 456.',
    'choice' => 'Choose one of the options.',
    'consent' => 'Please accept the privacy policy so we can process your request.',
    'host' => 'Enter a valid server name.',
    'numeric' => 'Enter a number.',
    'color' => 'Enter a colour like #2F6FA8.',
    'length' => 'Enter a size in pixels, for example 16px.',
];

<?php

// Before publishing the website, the actual business operator must verify the
// contact information and retention schedule in .env. Never invent these details.
return [
    'store_name' => "CHICK'S POPS",
    'operator_name' => env('CC_LEGAL_OPERATOR_NAME'),
    'business_address' => env('CC_LEGAL_BUSINESS_ADDRESS'),
    'contact_email' => env('CC_LEGAL_CONTACT_EMAIL'),
    'contact_phone' => env('CC_LEGAL_CONTACT_PHONE'),
    'account_retention' => env('CC_LEGAL_ACCOUNT_RETENTION'),
    'transaction_retention' => env('CC_LEGAL_TRANSACTION_RETENTION'),
    'technical_retention' => env('CC_LEGAL_TECHNICAL_RETENTION'),
    'version' => '2026-11-11',
    'effective_date' => '11 November 2026',
];

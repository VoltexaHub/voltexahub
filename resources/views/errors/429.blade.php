@extends('errors.layout', [
    'code' => '429',
    'eyebrow' => 'Slow down',
    'headline' => 'Too many requests.',
    'lede' => 'You\'ve hit a rate limit. Take a breath and try again in a minute — this is how VoltexaHub keeps spam at bay.',
])

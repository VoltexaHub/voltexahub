<?php

namespace App\Services;

use s9e\TextFormatter\Bundles\Forum as ForumBundle;

class TextFormatterService
{
    public function render(string $xml): string
    {
        return ForumBundle::render($xml);
    }

    public function parse(string $text): string
    {
        return ForumBundle::parse($text);
    }

    public function renderFromText(string $text): string
    {
        return $this->render($this->parse($text));
    }

    public function unparse(string $xml): string
    {
        return ForumBundle::unparse($xml);
    }
}

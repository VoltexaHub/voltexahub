<?php

namespace App\Services;

use App\Support\Mentions;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

class Markdown
{
    private MarkdownConverter $converter;

    public function __construct()
    {
        $env = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]);
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new GithubFlavoredMarkdownExtension());

        $this->converter = new MarkdownConverter($env);
    }

    public function toHtml(string $markdown): string
    {
        $html = (string) $this->converter->convert($markdown);
        $html = $this->embedLinks($html);
        $html = $this->renderMentions($html);

        return $html;
    }

    /**
     * Replace @mentions in text (not inside code/pre/existing anchors) with profile links.
     * Walks the DOM so fenced code blocks stay untouched.
     */
    private function renderMentions(string $html): string
    {
        if (! preg_match(Mentions::PATTERN, $html)) {
            return $html;
        }

        $names = [];
        preg_match_all(Mentions::PATTERN, $html, $m);
        $names = array_unique($m[1]);
        if (empty($names)) {
            return $html;
        }

        $userMap = \App\Models\User::query()
            ->whereIn('name', $names)
            ->pluck('id', 'name')
            ->all();

        if (empty($userMap)) {
            return $html;
        }

        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"?><div id="vx-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xpath = new \DOMXPath($dom);
        $textNodes = $xpath->query('//text()[not(ancestor::code) and not(ancestor::pre) and not(ancestor::a)]');
        if (! $textNodes) {
            return $html;
        }

        foreach (iterator_to_array($textNodes) as $node) {
            $text = $node->nodeValue;
            if (! preg_match(Mentions::PATTERN, $text)) continue;

            $fragment = $dom->createDocumentFragment();
            $offset = 0;
            preg_match_all(Mentions::PATTERN, $text, $all, PREG_OFFSET_CAPTURE);

            foreach ($all[0] as $i => $match) {
                [$whole, $pos] = $match;
                $name = $all[1][$i][0];
                if (! isset($userMap[$name])) continue;

                if ($pos > $offset) {
                    $fragment->appendChild($dom->createTextNode(substr($text, $offset, $pos - $offset)));
                }

                $a = $dom->createElement('a', '@'.$name);
                $a->setAttribute('href', '/users/'.$userMap[$name]);
                $a->setAttribute('class', 'vx-mention');
                $a->setAttribute('style', 'color:var(--accent);font-weight:500;text-decoration:none;background:var(--accent-weak);padding:0 0.25em;border-radius:0.25em');
                $fragment->appendChild($a);

                $offset = $pos + strlen($whole);
            }

            if ($offset < strlen($text)) {
                $fragment->appendChild($dom->createTextNode(substr($text, $offset)));
            }

            if ($fragment->hasChildNodes()) {
                $node->parentNode->replaceChild($fragment, $node);
            }
        }

        $root = $dom->getElementById('vx-root');
        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return $out;
    }

    /**
     * Swap paragraphs that are just a bare URL for a rich embed.
     * Currently supports YouTube; everything else renders as a neutral link card.
     */
    private function embedLinks(string $html): string
    {
        return preg_replace_callback(
            '#<p><a href="([^"]+)">([^<]+)</a></p>#',
            function (array $m) {
                $url = html_entity_decode($m[1]);
                $label = $m[2];

                if ($url !== html_entity_decode($label)) {
                    // The linked text was customized ([label](url)) — keep the original paragraph.
                    return $m[0];
                }

                if ($vid = $this->youtubeId($url)) {
                    return sprintf(
                        '<div class="vx-embed vx-embed-youtube" style="position:relative;aspect-ratio:16/9;overflow:hidden;border-radius:0.5rem;border:1px solid var(--border);margin:1em 0;background:#000">'
                        .'<iframe src="https://www.youtube-nocookie.com/embed/%s" title="YouTube video" frameborder="0" allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"'
                        .' style="position:absolute;inset:0;width:100%%;height:100%%"></iframe>'
                        .'</div>',
                        htmlspecialchars($vid, ENT_QUOTES),
                    );
                }

                return $this->linkCard($url);
            },
            $html,
        ) ?? $html;
    }

    private function youtubeId(string $url): ?string
    {
        if (preg_match('#^https?://(?:www\.)?youtube\.com/watch\?(?:.+&)?v=([\w\-]{6,20})#', $url, $m)) {
            return $m[1];
        }
        if (preg_match('#^https?://youtu\.be/([\w\-]{6,20})#', $url, $m)) {
            return $m[1];
        }

        return null;
    }

    private function linkCard(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?: $url;

        return sprintf(
            '<a class="vx-embed vx-embed-link" href="%1$s" rel="nofollow noopener noreferrer" target="_blank"'
            .' style="display:block;margin:1em 0;padding:0.75rem 1rem;border:1px solid var(--border);border-radius:0.5rem;background:var(--surface-mute);text-decoration:none;transition:border-color 140ms">'
            .'<div style="font-family:\'JetBrains Mono\',ui-monospace,monospace;font-size:0.7rem;letter-spacing:0.08em;text-transform:uppercase;color:var(--text-subtle)">%2$s</div>'
            .'<div style="color:var(--accent);font-weight:500;margin-top:0.15em;word-break:break-all">%1$s</div>'
            .'</a>',
            htmlspecialchars($url, ENT_QUOTES),
            htmlspecialchars($host, ENT_QUOTES),
        );
    }
}

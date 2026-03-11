{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
{!! '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ $frontendUrl }}/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
@foreach($forums as $forum)
    <url>
        <loc>{{ $frontendUrl }}/forum/{{ $forum->slug }}</loc>
        <lastmod>{{ $forum->updated_at?->toW3cString() ?? now()->toW3cString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
@endforeach
@foreach($threads as $thread)
    <url>
        <loc>{{ $frontendUrl }}/thread/{{ $thread->slug ?? $thread->id }}</loc>
        <lastmod>{{ $thread->updated_at?->toW3cString() ?? $thread->created_at->toW3cString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
@endforeach
</urlset>

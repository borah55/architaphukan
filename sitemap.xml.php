<?php
/**
 * Dynamic XML sitemap. Output Content-Type is XML.
 */
require_once __DIR__ . '/includes/init.php';

header('Content-Type: application/xml; charset=utf-8');

$pages = [
    '/'             => '1.0',
    '/faq.php'      => '0.6',
    '/contact.php'  => '0.4',
    '/sponsors.php' => '0.5',
    '/terms.php'    => '0.3',
    '/privacy.php'  => '0.3',
    '/login.php'    => '0.5',
    '/register.php' => '0.7',
];

$lastmod = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($pages as $path => $priority) {
    echo "  <url>\n";
    echo "    <loc>" . e(url($path)) . "</loc>\n";
    echo "    <lastmod>$lastmod</lastmod>\n";
    echo "    <priority>$priority</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>';

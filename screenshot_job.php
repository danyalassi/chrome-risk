<?php

require_once __DIR__ . '/vendor/autoload.php';

use HeadlessChromium\BrowserFactory;

if ($argc < 2) {
    echo "Usage: php screenshot_job.php <url>\n";
    exit(1);
}

$url = $argv[1];
$viewportDevice = isset($argv[2]) ? $argv[2] : null;

$browserOptions = [
    'no-sandbox',
    'disable-gpu',
    'lang=en-US', 
];

if ($viewportDevice !== null) {
    $browserOptions[] = 'window-size=' . $viewportDevice;
}

$browserFactory = new BrowserFactory(null, null, null, $browserOptions);

try {
    $browser = $browserFactory->createBrowser();
    $page = $browser->createPage();
    $page->navigate($url)->waitForNavigation();

    $parsedUrl = parse_url($url);
    $domain = isset($parsedUrl['host']) ? $parsedUrl['host'] : 'unknown';
    $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';

    $filename = str_replace(['.', '/', '\\', ':', '?', '=', '&'], '_', $path) . '.png';

    $websiteFolder = 'images/' . str_replace('.', '_', $domain);
    if (!file_exists($websiteFolder)) {
        mkdir($websiteFolder, 0777, true);
    }

    $screenshotPath = $websiteFolder . '/' . $filename;

    $page->screenshot()->saveToFile($screenshotPath);

    $browser->close();


} catch (Exception $e) {
    echo "Failed to take screenshot: " . $e->getMessage() . "\n";
    exit(1);
}

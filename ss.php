<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/message_filter.php'; 

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Browser;
use HeadlessChromium\Page;

if (isset($_GET['url'])) {
    $url = $_GET['url'];
    $isPDF = isset($_GET['pdf']) ? filter_var($_GET['pdf'], FILTER_VALIDATE_BOOLEAN) : false;
    $saveScreenshot = !$isPDF; 
    $deviceScaleFactor = isset($_GET['device_scale_factor']) ? $_GET['device_scale_factor'] : 1;
    $fullPage = isset($_GET['full_page']) ? filter_var($_GET['full_page'], FILTER_VALIDATE_BOOLEAN) : false;
    $viewportWidth = isset($_GET['viewport_width']) ? $_GET['viewport_width'] : 1920;
    $viewportHeight = isset($_GET['viewport_height']) ? $_GET['viewport_height'] : 1280;
    $blockAds = isset($_GET['block_ads']) ? filter_var($_GET['block_ads'], FILTER_VALIDATE_BOOLEAN) : true;
    $blockCookieBanners = isset($_GET['block_cookie_banners']) ? filter_var($_GET['block_cookie_banners'], FILTER_VALIDATE_BOOLEAN) : true;
    $blockBannersByHeuristics = isset($_GET['block_banners_by_heuristics']) ? filter_var($_GET['block_banners_by_heuristics'], FILTER_VALIDATE_BOOLEAN) : false;
    $blockTrackers = isset($_GET['block_trackers']) ? filter_var($_GET['block_trackers'], FILTER_VALIDATE_BOOLEAN) : true;
    $delay = isset($_GET['delay']) ? $_GET['delay'] : 0;
    $timeout = isset($_GET['timeout']) ? $_GET['timeout'] : 60;

    $filteredUrl = filterUsername($url, $swearWords);

    if ($filteredUrl === null) {
        header("Content-Type: application/json");
        echo json_encode(["error" => "The provided URL contains a banned word or profanity."]);
        exit;
    }

    $browserOptions = [
        'no-sandbox',
        'disable-gpu', // faster
        'lang=en-US',
    ];

    $viewportDevice = "{$viewportWidth}x{$viewportHeight}x{$deviceScaleFactor}";

    if ($fullPage) {
        $browserOptions[] = 'full-page=true';
    }

    if ($blockAds) {
        $browserOptions[] = 'ad-block=true';
    }

    if ($blockCookieBanners) {
        $browserOptions[] = 'block-cookie-banners=true';
    }

    if ($blockBannersByHeuristics) {
        $browserOptions[] = 'block-banners-by-heuristics=true';
    }

    if ($blockTrackers) {
        $browserOptions[] = 'block-trackers=true';
    }

    if ($delay > 0) {
        $browserOptions[] = 'delay=' . $delay;
    }

    if ($timeout > 0) {
        $browserOptions[] = 'timeout=' . $timeout;
    }

    if ($viewportDevice !== null) {
        $browserOptions[] = 'window-size=' . $viewportDevice;
    }

    $browserFactory = new BrowserFactory(null, null, null, $browserOptions);

    try {
        $browser = $browserFactory->createBrowser();
        $page = $browser->createPage();
        $page->navigate($url)->waitForNavigation();

        if ($isPDF) {
            $htmlContent = $page->evaluate('document.documentElement.outerHTML')->getReturnValue();
            $pdf = new Dompdf\Dompdf();
            $pdf->loadHtml($htmlContent);
            $pdf->render();
            $pdfContent = $pdf->output();
            header('Content-Type: application/pdf');
            echo $pdfContent;
        } else {
            $parsedUrl = parse_url($url);
            $domain = isset($parsedUrl['host']) ? $parsedUrl['host'] : 'unknown';
            $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
            $filename = str_replace(['.', '/', '\\', ':', '?', '=', '&'], '_', $path) . '.png';

            $websiteFolder = 'images/' . str_replace('.', '_', $domain);
            if (!file_exists($websiteFolder)) {
                mkdir($websiteFolder, 0777, true);
            }

            $screenshotPath = $websiteFolder . '/' . $filename;

            if ($saveScreenshot) {
                $page->screenshot()->saveToFile($screenshotPath);
                header("Content-type: image/png");
                readfile($screenshotPath);
                //echo$screenshotPath;
            } else {
                header("Content-type: image/png");
                echo $page->screenshot()->getBinary();
            }
        }

        $browser->close();
    } catch (Exception $e) {
        header("Content-Type: application/json");
        echo json_encode(["error" => "Failed to capture: " . $e->getMessage()]);
    }
} else {
    header("Content-Type: application/json");
    echo json_encode(["error" => "Invalid URL. Please provide a valid URL starting with 'https://' or 'http://'."]);
}
?>

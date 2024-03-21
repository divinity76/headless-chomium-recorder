<?php

declare(strict_types=1);

error_reporting(E_ALL);
set_error_handler(
    function ($errno, $errstr, $errfile, $errline) {
        if (error_reporting() & $errno) {
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        }
    }
);
require_once __DIR__ . '/../vendor/autoload.php';

$browserFactory = new \HeadlessChromium\BrowserFactory();
$browserFactory->setOptions([
    'headless' => true,
    'noSandbox' => true,
    'customFlags' => [
        '--disable-dev-shm-usage', // docker compatibility..
    ],
    'windowSize' => [1920, 1200],
]);
$browser = $browserFactory->createBrowser();
$page = $browser->createPage();
$page->setViewport(
    width: 1920,
    height: 1080,
)->await();
$recorder = new \Divinity76\HeadlessChromiumRecorder\Recorder($page);
$recorder->startRecording(
    format: 'jpeg',
    //format: 'png', 
    quality: 100,
    everyNthFrame: 1
);
$page->navigate('https://www.youtube.com/watch?v=dQw4w9WgXcQ')->waitForNavigation(\HeadlessChromium\Page::LOAD);
// Video will not start playing until we accept cookies prompt:
for ($attempt = 0;; ++$attempt) {
    try {
        $page->mouse()->find("tp-yt-paper-dialog[class*=ytd-consent-bump] button", 3)->click();
        break;
    } catch (\HeadlessChromium\Exception\ElementNotFoundException $e) {
        if ($attempt > 1000) {
            throw $e;
        }
        //usleep(1); // should be a socket_select: https://github.com/chrome-php/wrench/pull/17
        $page->getSession()->getConnection()->readData(); // should be a socket_select...
    }
}
$targetTime = microtime(true) + 30;
while (microtime(true) < $targetTime) {
    //usleep(1); // should be a socket_select: https://github.com/chrome-php/wrench/pull/17
    $page->getSession()->getConnection()->readData();
}
$recorder->stopRecording();
$page->close();
$savepath = __DIR__ . DIRECTORY_SEPARATOR . 'test1.mp4';
$recorder->generateVideo1(
    $savepath,
    [
        //'-fps_mode' => '-fps_mode passthrough',
    ]
);
echo "video saved to: $savepath\n";

<?php
// Get channel ID from path (after /api/)
$requestUri = $_SERVER['REQUEST_URI'];
$path = substr($requestUri, strpos($requestUri, '/api/') + 5);

// Extract channel ID (remove /master.m3u8 if present)
$channelId = preg_replace('/\/master\.m3u8$/i', '', $path);

if (empty($channelId)) {
    header('HTTP/1.1 400 Bad Request');
    die('Invalid Channel ID - Usage: /api/[channel_id]/master.m3u8');
}

// Check if this is a segment or key request
if (strpos($requestUri, '/proxy/') !== false) {
    // Handle segment/key proxying
    $proxyUrl = urldecode(preg_replace('/.*\/proxy\/(.+?)(?:\/segment\.(ts|key))?$/i', '$1', $requestUri));
    
   $options = [
    'http' => [
        'method' => 'GET',
        'header' => implode("\r\n", [
            'accept: */*',
            'accept-language: en-US,en;q=0.9,ta;q=0.8',
            'authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ0eXBlIjoiYWNjZXNzIiwiaHR0cHM6Ly9yYmFjL2FwcCI6ImZ1Ym90diIsImh0dHBzOi8vcmJhYy9yb2xlcyI6WyJlbmR1c2VyIl0sImRldmljZV9pZCI6IjcwRmdyUGNUQ1ZueURVS0F5VyIsImlzcyI6Imh0dHBzOi8vYXBpLmZ1Ym8udHYiLCJzdWIiOiI2N2U2YjE0ZDJkNDFkODAwMDFlZjViNTciLCJhdWQiOlsia1FzckxKV1JZazNISTRGakxFZXNaNmlSSFlSRGVhZnIiXSwiZXhwIjoxNzQzOTUyMTU2LCJpYXQiOjE3NDM5MTYxNTZ9.LvqHVSksZU01scQ--9ipoHn4BaQyCliwLZtVHqFVSRc',
            'cache-control: no-cache',
            'origin: https://www.fubo.tv',
            'pragma: no-cache',
            'priority: u=1, i',
            'referer: https://www.fubo.tv/',
            'sec-ch-ua: "Chromium";v="134", "Not:A-Brand";v="24", "Google Chrome";v="134"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-site',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
            'x-ad-id: p1BS1TrgWLGibloRzK',
            'x-application-id: fubo',
            'x-browser: Chrome',
            'x-browser-engine: Blink',
            'x-browser-version: 134.0.0.0',
            'x-client-version: R20250404.1',
            'x-device-app: web',
            'x-device-group: desktop',
            'x-device-id: 70FgrPcTCVnyDUKAyW',
            'x-device-model: Windows NT 10.0 Chrome 134.0.0.0',
            'x-device-platform: desktop',
            'x-device-type: desktop',
            'x-drm-scheme: widevine',
            'x-os: Windows',
            'x-os-version: NT 10.0',
            'x-player-version: 4.20.3',
            'x-preferred-language: en-US',
            'x-screen-height: 730',
            'x-screen-width: 654',
            'x-supported-codecs-list: avc',
            'x-supported-features: auto_play_up_next,braze_custom_event,catalog_header,card_bottom_scrim,card_score_overlay,custom_loader,folder_sort_options,initial_focus_target,load_channels_in_guide,migrate_messaging,premium_cards,reorder_favorites,scheduled_as_nav_entry,vidai_my_stuff_moments,vidai_timeline_markers',
            'x-supported-streaming-protocols: hls,dash'
        ]),
        'ignore_errors' => true
    ]
];

    
    $context = stream_context_create($options);
    $response = @file_get_contents($proxyUrl, false, $context);
    
    if ($response === false) {
        header('HTTP/1.1 502 Bad Gateway');
        die('Failed to fetch resource');
    }
    
    // Set appropriate content type
    if (strpos($proxyUrl, '.key') !== false) {
        header('Content-Type: application/octet-stream');
    } else {
        header('Content-Type: video/MP2T');
    }
    
    echo $response;
    exit;
}

// Main HLS playlist generation
function fetchFuboStream($channelId) {
    $fuboApiUrl = 'https://api.fubo.tv/vapi/asset/v1?channelId='.$channelId.'&type=live';
    $options = [
    'http' => [
        'method' => 'GET',
        'header' => implode("\r\n", [
            'accept: */*',
            'accept-language: en-US,en;q=0.9,ta;q=0.8',
            'authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ0eXBlIjoiYWNjZXNzIiwiaHR0cHM6Ly9yYmFjL2FwcCI6ImZ1Ym90diIsImh0dHBzOi8vcmJhYy9yb2xlcyI6WyJlbmR1c2VyIl0sImRldmljZV9pZCI6IjcwRmdyUGNUQ1ZueURVS0F5VyIsImlzcyI6Imh0dHBzOi8vYXBpLmZ1Ym8udHYiLCJzdWIiOiI2N2U2YjE0ZDJkNDFkODAwMDFlZjViNTciLCJhdWQiOlsia1FzckxKV1JZazNISTRGakxFZXNaNmlSSFlSRGVhZnIiXSwiZXhwIjoxNzQzOTUyMTU2LCJpYXQiOjE3NDM5MTYxNTZ9.LvqHVSksZU01scQ--9ipoHn4BaQyCliwLZtVHqFVSRc',
            'cache-control: no-cache',
            'origin: https://www.fubo.tv',
            'pragma: no-cache',
            'priority: u=1, i',
            'referer: https://www.fubo.tv/',
            'sec-ch-ua: "Chromium";v="134", "Not:A-Brand";v="24", "Google Chrome";v="134"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-site',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
            'x-ad-id: p1BS1TrgWLGibloRzK',
            'x-application-id: fubo',
            'x-browser: Chrome',
            'x-browser-engine: Blink',
            'x-browser-version: 134.0.0.0',
            'x-client-version: R20250404.1',
            'x-device-app: web',
            'x-device-group: desktop',
            'x-device-id: 70FgrPcTCVnyDUKAyW',
            'x-device-model: Windows NT 10.0 Chrome 134.0.0.0',
            'x-device-platform: desktop',
            'x-device-type: desktop',
            'x-drm-scheme: widevine',
            'x-os: Windows',
            'x-os-version: NT 10.0',
            'x-player-version: 4.20.3',
            'x-preferred-language: en-US',
            'x-screen-height: 730',
            'x-screen-width: 654',
            'x-supported-codecs-list: avc',
            'x-supported-features: auto_play_up_next,braze_custom_event,catalog_header,card_bottom_scrim,card_score_overlay,custom_loader,folder_sort_options,initial_focus_target,load_channels_in_guide,migrate_messaging,premium_cards,reorder_favorites,scheduled_as_nav_entry,vidai_my_stuff_moments,vidai_timeline_markers',
            'x-supported-streaming-protocols: hls,dash'
        ]),
        'ignore_errors' => true
    ]
];

    
    $context = stream_context_create($options);
    $response = @file_get_contents($fuboApiUrl, false, $context);
    
    if ($response === false) {
        return false;
    }
    
    $data = json_decode($response, true);
    if (!isset($data['stream']['url'])) {
        return false;
    }
    
    return str_replace('\\u0026', '&', $data['stream']['url']);
}

function getBestStream($masterUrl) {
    $options = [
    'http' => [
        'method' => 'GET',
        'header' => implode("\r\n", [
            'accept: */*',
            'accept-language: en-US,en;q=0.9,ta;q=0.8',
            'authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ0eXBlIjoiYWNjZXNzIiwiaHR0cHM6Ly9yYmFjL2FwcCI6ImZ1Ym90diIsImh0dHBzOi8vcmJhYy9yb2xlcyI6WyJlbmR1c2VyIl0sImRldmljZV9pZCI6IjcwRmdyUGNUQ1ZueURVS0F5VyIsImlzcyI6Imh0dHBzOi8vYXBpLmZ1Ym8udHYiLCJzdWIiOiI2N2U2YjE0ZDJkNDFkODAwMDFlZjViNTciLCJhdWQiOlsia1FzckxKV1JZazNISTRGakxFZXNaNmlSSFlSRGVhZnIiXSwiZXhwIjoxNzQzOTUyMTU2LCJpYXQiOjE3NDM5MTYxNTZ9.LvqHVSksZU01scQ--9ipoHn4BaQyCliwLZtVHqFVSRc',
            'cache-control: no-cache',
            'origin: https://www.fubo.tv',
            'pragma: no-cache',
            'priority: u=1, i',
            'referer: https://www.fubo.tv/',
            'sec-ch-ua: "Chromium";v="134", "Not:A-Brand";v="24", "Google Chrome";v="134"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-site',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
            'x-ad-id: p1BS1TrgWLGibloRzK',
            'x-application-id: fubo',
            'x-browser: Chrome',
            'x-browser-engine: Blink',
            'x-browser-version: 134.0.0.0',
            'x-client-version: R20250404.1',
            'x-device-app: web',
            'x-device-group: desktop',
            'x-device-id: 70FgrPcTCVnyDUKAyW',
            'x-device-model: Windows NT 10.0 Chrome 134.0.0.0',
            'x-device-platform: desktop',
            'x-device-type: desktop',
            'x-drm-scheme: widevine',
            'x-os: Windows',
            'x-os-version: NT 10.0',
            'x-player-version: 4.20.3',
            'x-preferred-language: en-US',
            'x-screen-height: 730',
            'x-screen-width: 654',
            'x-supported-codecs-list: avc',
            'x-supported-features: auto_play_up_next,braze_custom_event,catalog_header,card_bottom_scrim,card_score_overlay,custom_loader,folder_sort_options,initial_focus_target,load_channels_in_guide,migrate_messaging,premium_cards,reorder_favorites,scheduled_as_nav_entry,vidai_my_stuff_moments,vidai_timeline_markers',
            'x-supported-streaming-protocols: hls,dash'
        ]),
        'ignore_errors' => true
    ]
];
    
    $context = stream_context_create($options);
    $masterResponse = @file_get_contents($masterUrl, false, $context);
    
    if ($masterResponse === false) {
        return false;
    }
    
    $highestRes = 0;
    $bestStream = "";
    $lines = explode("\n", $masterResponse);
    
    foreach ($lines as $line) {
        if (strpos($line, '#EXT-X-STREAM-INF') !== false) {
            if (preg_match('/RESOLUTION=(\d+)x(\d+)/', $line, $matches)) {
                $currentRes = (int)$matches[1] * (int)$matches[2];
                if ($currentRes > $highestRes) {
                    $highestRes = $currentRes;
                }
            }
        } elseif (!empty($line) && $line[0] !== '#' && $highestRes > 0) {
            $bestStream = trim($line);
            $highestRes = 0;
        }
    }
    
    return $bestStream;
}

// Get the stream URL
$masterUrl = fetchFuboStream($channelId);
if (!$masterUrl) {
    header('HTTP/1.1 502 Bad Gateway');
    die('Failed to fetch from Fubo API');
}

// Get best quality stream
$bestStream = getBestStream($masterUrl);
if (!$bestStream) {
    header('HTTP/1.1 502 Bad Gateway');
    die('Failed to fetch master playlist');
}

// Construct final URL
if (strpos($bestStream, 'http') === 0) {
    $finalUrl = $bestStream;
} else {
    $cleanPath = preg_replace('/(\.\.\/)+/', '', $bestStream);
    $cleanPath = str_replace('manifest/', '', $cleanPath);
    
    if (strpos($masterUrl, 'blue-midas.fubo.tv') !== false) {
        $domain = explode('/v1/', $masterUrl)[0];
        $finalUrl = $domain . '/v1/manifest/' . $cleanPath;
    } else {
        $finalUrl = dirname($masterUrl) . '/' . $cleanPath;
    }
}

// Fetch the final playlist
$options = [
    'http' => [
        'method' => 'GET',
        'header' => implode("\r\n", [
            'accept: */*',
            'accept-language: en-US,en;q=0.9,ta;q=0.8',
            'authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ0eXBlIjoiYWNjZXNzIiwiaHR0cHM6Ly9yYmFjL2FwcCI6ImZ1Ym90diIsImh0dHBzOi8vcmJhYy9yb2xlcyI6WyJlbmR1c2VyIl0sImRldmljZV9pZCI6IjcwRmdyUGNUQ1ZueURVS0F5VyIsImlzcyI6Imh0dHBzOi8vYXBpLmZ1Ym8udHYiLCJzdWIiOiI2N2U2YjE0ZDJkNDFkODAwMDFlZjViNTciLCJhdWQiOlsia1FzckxKV1JZazNISTRGakxFZXNaNmlSSFlSRGVhZnIiXSwiZXhwIjoxNzQzOTUyMTU2LCJpYXQiOjE3NDM5MTYxNTZ9.LvqHVSksZU01scQ--9ipoHn4BaQyCliwLZtVHqFVSRc',
            'cache-control: no-cache',
            'origin: https://www.fubo.tv',
            'pragma: no-cache',
            'priority: u=1, i',
            'referer: https://www.fubo.tv/',
            'sec-ch-ua: "Chromium";v="134", "Not:A-Brand";v="24", "Google Chrome";v="134"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-site',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
            'x-ad-id: p1BS1TrgWLGibloRzK',
            'x-application-id: fubo',
            'x-browser: Chrome',
            'x-browser-engine: Blink',
            'x-browser-version: 134.0.0.0',
            'x-client-version: R20250404.1',
            'x-device-app: web',
            'x-device-group: desktop',
            'x-device-id: 70FgrPcTCVnyDUKAyW',
            'x-device-model: Windows NT 10.0 Chrome 134.0.0.0',
            'x-device-platform: desktop',
            'x-device-type: desktop',
            'x-drm-scheme: widevine',
            'x-os: Windows',
            'x-os-version: NT 10.0',
            'x-player-version: 4.20.3',
            'x-preferred-language: en-US',
            'x-screen-height: 730',
            'x-screen-width: 654',
            'x-supported-codecs-list: avc',
            'x-supported-features: auto_play_up_next,braze_custom_event,catalog_header,card_bottom_scrim,card_score_overlay,custom_loader,folder_sort_options,initial_focus_target,load_channels_in_guide,migrate_messaging,premium_cards,reorder_favorites,scheduled_as_nav_entry,vidai_my_stuff_moments,vidai_timeline_markers',
            'x-supported-streaming-protocols: hls,dash'
        ]),
        'ignore_errors' => true
    ]
];

$context = stream_context_create($options);
$streamResponse = @file_get_contents($finalUrl, false, $context);

if ($streamResponse === false) {
    header('HTTP/1.1 502 Bad Gateway');
    die('Failed to fetch stream');
}

// Set content type as m3u8
header('Content-Type: application/vnd.apple.mpegurl');

// Process HLS playlists
$proxyBase = 'https://'.$_SERVER['HTTP_HOST'].'/api/'.$channelId.'/proxy/';
$lines = explode("\n", $streamResponse);

foreach ($lines as &$line) {
    // Handle encryption keys
    if (strpos($line, '#EXT-X-KEY') !== false) {
        if (preg_match('/URI="([^"]+)"/', $line, $matches)) {
            $keyUrl = $matches[1];
            $proxiedKeyUrl = $proxyBase . urlencode($keyUrl) . '/segment.key';
            $line = str_replace($keyUrl, $proxiedKeyUrl, $line);
        }
    }
    // Handle segments
    elseif (strpos($line, 'http') === 0) {
        $line = $proxyBase . urlencode($line) . '/segment.ts';
    }
    elseif (!empty($line) && $line[0] !== '#' && strpos($line, '://') === false) {
        $absoluteUrl = dirname($finalUrl) . '/' . $line;
        $line = $proxyBase . urlencode($absoluteUrl) . '/segment.ts';
    }
}

echo implode("\n", $lines);
?>

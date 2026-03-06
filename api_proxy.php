<?php
/**
 * STRATÉG 2026 - API PROXY v2.1
 * Opravená verze pro správný RSI výpočet
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Odpověď na preflight požadavky
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ==== KONFIGURACE ====
const FINNHUB_API_KEY = 'd6avddhr01qnr27j2em0d6avddhr01qnr27j2emg';
const TWELVE_DATA_API_KEY = 'adc12bccbf7a49bd9f61c1c56e8a7065';
const CACHE_DURATION = 60; // Cache v sekundách
const RATE_LIMIT = 60; // Max požadavků za minutu
const ALLOWED_SYMBOLS = ['MU', 'NOW', 'MSFT', 'ASML'];

// ==== RATE LIMITING ====
session_start();

if (!isset($_SESSION['api_calls'])) {
    $_SESSION['api_calls'] = [];
}

// Vyčištění starých záznamů
$_SESSION['api_calls'] = array_filter($_SESSION['api_calls'], function($timestamp) {
    return $timestamp > (time() - 60);
});

if (count($_SESSION['api_calls']) >= RATE_LIMIT) {
    http_response_code(429);
    echo json_encode([
        'error' => 'Rate limit exceeded',
        'message' => 'Překročen limit požadavků. Zkuste za chvíli.'
    ]);
    exit;
}

$_SESSION['api_calls'][] = time();

// ==== VALIDACE VSTUPU ====
$action = $_GET['action'] ?? null;
$symbol = strtoupper($_GET['symbol'] ?? '');

if (!$action) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing action parameter']);
    exit;
}

if ($symbol && !in_array($symbol, ALLOWED_SYMBOLS)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid symbol']);
    exit;
}

// ==== CACHE MANAGEMENT ====
function getCacheKey($action, $symbol) {
    return "cache_{$action}_{$symbol}";
}

function getCache($key) {
    $cacheFile = sys_get_temp_dir() . "/{$key}.json";
    
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        
        if ($data && (time() - $data['timestamp']) < CACHE_DURATION) {
            return $data['content'];
        }
    }
    
    return null;
}

function setCache($key, $content) {
    $cacheFile = sys_get_temp_dir() . "/{$key}.json";
    
    file_put_contents($cacheFile, json_encode([
        'timestamp' => time(),
        'content' => $content
    ]));
}

// ==== API CALLS ====
function curlRequest($url, $timeout = 10) {
    if (extension_loaded('curl')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response !== false ? $response : null;
    } else {
        // Fallback na file_get_contents
        $context = stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'ignore_errors' => true,
                'user_agent' => 'Strateg2026/1.0'
            ]
        ]);
        return @file_get_contents($url, false, $context);
    }
}

function callFinnhub($endpoint, $params = []) {
    $params['token'] = FINNHUB_API_KEY;
    $url = "https://finnhub.io/api/v1/{$endpoint}?" . http_build_query($params);
    
    $response = curlRequest($url);
    
    if ($response === null) {
        throw new Exception('API request failed');
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response');
    }
    
    return $data;
}

function callTwelveData($endpoint, $params = []) {
    $params['apikey'] = TWELVE_DATA_API_KEY;
    $url = "https://api.twelvedata.com/{$endpoint}?" . http_build_query($params);
    
    $response = curlRequest($url);
    
    if ($response === null) {
        return null;
    }
    
    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}

function getStooqSymbol($symbol) {
    return strtolower($symbol) . '.us';
}

function fetchStooqCandles($symbol, $count = 60) {
    $stooqSymbol = getStooqSymbol($symbol);
    $url = "https://stooq.com/q/d/l/?s={$stooqSymbol}&i=d";

    $csv = curlRequest($url);
    if ($csv === null || trim($csv) === '') {
        return null;
    }

    $lines = preg_split('/\r\n|\n|\r/', trim($csv));
    if (!$lines || count($lines) < 3) {
        return null;
    }

    $rows = array_slice($lines, 1);
    $rows = array_values(array_filter($rows, function($line) {
        return trim($line) !== '';
    }));

    if (count($rows) === 0) {
        return null;
    }

    if (count($rows) > $count) {
        $rows = array_slice($rows, -$count);
    }

    $o = [];
    $h = [];
    $l = [];
    $c = [];
    $t = [];
    $v = [];

    foreach ($rows as $row) {
        $parts = str_getcsv($row);
        if (count($parts) < 6) {
            continue;
        }

        [$date, $open, $high, $low, $close, $volume] = $parts;

        if (!is_numeric($open) || !is_numeric($high) || !is_numeric($low) || !is_numeric($close)) {
            continue;
        }

        $timestamp = strtotime($date . ' 00:00:00 UTC');
        if ($timestamp === false) {
            continue;
        }

        $o[] = (float)$open;
        $h[] = (float)$high;
        $l[] = (float)$low;
        $c[] = (float)$close;
        $t[] = (int)$timestamp;
        $v[] = is_numeric($volume) ? (float)$volume : 0.0;
    }

    if (count($c) < 2) {
        return null;
    }

    return [
        's' => 'ok',
        'o' => $o,
        'h' => $h,
        'l' => $l,
        'c' => $c,
        't' => $t,
        'v' => $v,
        '_source' => 'stooq'
    ];
}

function fetchYahooCandles($symbol, $count = 60) {
    $range = '6mo';
    if ($count > 120) {
        $range = '1y';
    }

    $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}?range={$range}&interval=1d";

    $response = curlRequest($url);
    if ($response === null || trim($response) === '') {
        return null;
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return null;
    }

    $result = $data['chart']['result'][0] ?? null;
    if (!$result) {
        return null;
    }

    $timestamps = $result['timestamp'] ?? [];
    $quote = $result['indicators']['quote'][0] ?? null;
    if (!$quote) {
        return null;
    }

    $opens = $quote['open'] ?? [];
    $highs = $quote['high'] ?? [];
    $lows = $quote['low'] ?? [];
    $closes = $quote['close'] ?? [];
    $volumes = $quote['volume'] ?? [];

    $o = [];
    $h = [];
    $l = [];
    $c = [];
    $t = [];
    $v = [];

    $length = count($timestamps);
    for ($i = 0; $i < $length; $i++) {
        $open = $opens[$i] ?? null;
        $high = $highs[$i] ?? null;
        $low = $lows[$i] ?? null;
        $close = $closes[$i] ?? null;
        $timestamp = $timestamps[$i] ?? null;

        if ($timestamp === null || !is_numeric($timestamp)) {
            continue;
        }
        if (!is_numeric($open) || !is_numeric($high) || !is_numeric($low) || !is_numeric($close)) {
            continue;
        }

        $o[] = (float)$open;
        $h[] = (float)$high;
        $l[] = (float)$low;
        $c[] = (float)$close;
        $t[] = (int)$timestamp;
        $v[] = isset($volumes[$i]) && is_numeric($volumes[$i]) ? (float)$volumes[$i] : 0.0;
    }

    if (count($c) < 2) {
        return null;
    }

    if (count($c) > $count) {
        $offset = count($c) - $count;
        $o = array_slice($o, $offset);
        $h = array_slice($h, $offset);
        $l = array_slice($l, $offset);
        $c = array_slice($c, $offset);
        $t = array_slice($t, $offset);
        $v = array_slice($v, $offset);
    }

    return [
        's' => 'ok',
        'o' => $o,
        'h' => $h,
        'l' => $l,
        'c' => $c,
        't' => $t,
        'v' => $v,
        '_source' => 'yahoo'
    ];
}

// ==== REQUEST HANDLING ====
try {
    $cacheKey = getCacheKey($action, $symbol);
    $cachedData = getCache($cacheKey);
    
    if ($cachedData !== null) {
        echo json_encode($cachedData);
        exit;
    }
    
    $result = null;
    
    switch ($action) {
        case 'quote':
            // Aktuální cena akcie
            if (!$symbol) {
                throw new Exception('Symbol required for quote');
            }
            $result = callFinnhub('quote', ['symbol' => $symbol]);
            break;
            
        case 'metrics':
            // Fundamentální metriky (P/E, PEG, atd.)
            if (!$symbol) {
                throw new Exception('Symbol required for metrics');
            }
            $result = callFinnhub('stock/metric', [
                'symbol' => $symbol,
                'metric' => 'all'
            ]);
            break;
            
        case 'candles':
            // Historická data pro RSI výpočet
            if (!$symbol) {
                throw new Exception('Symbol required for candles');
            }
            
            $resolution = $_GET['resolution'] ?? 'D';
            $count = min((int)($_GET['count'] ?? 60), 365);
            
            // OPRAVA: Použij delší historii pro lepší RSI výpočet
            $to = time();
            $from = $to - ($count * 24 * 60 * 60);
            
            $candleData = callFinnhub('stock/candle', [
                'symbol' => $symbol,
                'resolution' => $resolution,
                'from' => $from,
                'to' => $to
            ]);

            $needsFallback = isset($candleData['error'])
                || !isset($candleData['s'])
                || $candleData['s'] !== 'ok'
                || !isset($candleData['c'])
                || count($candleData['c']) < 2;

            if ($needsFallback) {
                $fallbackData = fetchYahooCandles($symbol, $count);
                if ($fallbackData === null) {
                    $fallbackData = fetchStooqCandles($symbol, $count);
                }
                if ($fallbackData !== null) {
                    $candleData = $fallbackData;
                }
            }
            
            // DEBUG: Přidej info o datech
            if (isset($candleData['s']) && $candleData['s'] === 'ok') {
                $candleData['_debug'] = [
                    'count' => count($candleData['c'] ?? []),
                    'from_date' => date('Y-m-d', $from),
                    'to_date' => date('Y-m-d', $to),
                    'first_price' => $candleData['c'][0] ?? null,
                    'last_price' => end($candleData['c']) ?? null,
                    'source' => $candleData['_source'] ?? 'finnhub'
                ];
            }
            
            $result = $candleData;
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    if ($result) {
        setCache($cacheKey, $result);
        echo json_encode($result);
    } else {
        throw new Exception('No data returned');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'API Error',
        'message' => $e->getMessage()
    ]);
}
?>

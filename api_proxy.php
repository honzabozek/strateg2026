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
function callFinnhub($endpoint, $params = []) {
    $params['token'] = FINNHUB_API_KEY;
    $url = "https://finnhub.io/api/v1/{$endpoint}?" . http_build_query($params);
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        throw new Exception('API request failed');
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response');
    }
    
    return $data;
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
            
            // DEBUG: Přidej info o datech
            if ($candleData['s'] === 'ok') {
                $candleData['_debug'] = [
                    'count' => count($candleData['c'] ?? []),
                    'from_date' => date('Y-m-d', $from),
                    'to_date' => date('Y-m-d', $to),
                    'first_price' => $candleData['c'][0] ?? null,
                    'last_price' => end($candleData['c']) ?? null
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

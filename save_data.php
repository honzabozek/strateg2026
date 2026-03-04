<?php
/**
 * STRATÉG 2026 - DATA STORAGE
 * Zabezpečené ukládání portfolia
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ==== KONFIGURACE ====
const DATA_FILE = __DIR__ . '/portfolio.json';
const AUTH_TOKEN = 'tvuj_tajny_token_2026'; // ZMĚŇ NA VLASTNÍ SILNÝ TOKEN!
const MAX_FILE_SIZE = 10240; // 10KB limit
const BACKUP_DIR = __DIR__ . '/backups/';

// ==== AUTENTIZACE (VOLITELNÉ) ====
// Pokud chceš autentizaci, odkomentuj následující řádky:
/*
$authHeader = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
if ($authHeader !== AUTH_TOKEN) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
*/

// ==== HELPER FUNKCE ====
function validatePortfolioData($data) {
    if (!is_array($data)) {
        return false;
    }
    
    $allowedSymbols = ['MU', 'NOW', 'MSFT', 'ASML'];
    
    foreach ($data as $symbol => $values) {
        // Kontrola tickeru
        if (!in_array($symbol, $allowedSymbols)) {
            return false;
        }
        
        // Kontrola struktury
        if (!isset($values['avg']) || !isset($values['shares'])) {
            return false;
        }
        
        // Kontrola hodnot
        if (!is_numeric($values['avg']) || !is_numeric($values['shares'])) {
            return false;
        }
        
        if ($values['avg'] < 0 || $values['shares'] < 0) {
            return false;
        }
    }
    
    return true;
}

function createBackup($data) {
    if (!is_dir(BACKUP_DIR)) {
        mkdir(BACKUP_DIR, 0755, true);
    }
    
    $backupFile = BACKUP_DIR . 'portfolio_' . date('Y-m-d_His') . '.json';
    file_put_contents($backupFile, json_encode($data, JSON_PRETTY_PRINT));
    
    // Smazání starých záložních souborů (ponechat jen 10 nejnovějších)
    $backups = glob(BACKUP_DIR . 'portfolio_*.json');
    if (count($backups) > 10) {
        usort($backups, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        $toDelete = array_slice($backups, 0, count($backups) - 10);
        foreach ($toDelete as $file) {
            unlink($file);
        }
    }
}

// ==== HLAVNÍ LOGIKA ====
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // ČTENÍ DAT
        if (!file_exists(DATA_FILE)) {
            // Inicializace s výchozími daty
            $defaultData = [
                "MU" => ["avg" => 407.57, "shares" => 2.2623],
                "NOW" => ["avg" => 0, "shares" => 0],
                "MSFT" => ["avg" => 0, "shares" => 0],
                "ASML" => ["avg" => 0, "shares" => 0]
            ];
            
            file_put_contents(DATA_FILE, json_encode($defaultData));
            echo json_encode($defaultData);
            exit;
        }
        
        $data = file_get_contents(DATA_FILE);
        
        if (strlen($data) > MAX_FILE_SIZE) {
            throw new Exception('Data file too large');
        }
        
        $portfolio = json_decode($data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Corrupted data file');
        }
        
        echo json_encode($portfolio);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // ZÁPIS DAT
        $input = file_get_contents('php://input');
        
        if (strlen($input) > MAX_FILE_SIZE) {
            throw new Exception('Input data too large');
        }
        
        $newData = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON');
        }
        
        if (!validatePortfolioData($newData)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid portfolio data']);
            exit;
        }
        
        // Vytvoření zálohy před uložením
        if (file_exists(DATA_FILE)) {
            $oldData = json_decode(file_get_contents(DATA_FILE), true);
            if ($oldData) {
                createBackup($oldData);
            }
        }
        
        // Uložení nových dat
        file_put_contents(DATA_FILE, json_encode($newData, JSON_PRETTY_PRINT));
        
        echo json_encode([
            'success' => true,
            'message' => 'Portfolio uloženo',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
?>

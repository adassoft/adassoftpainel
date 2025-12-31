<?php
/**
 * Adassoft System Requirements Checker
 * Run this script on your server to verify compatibility.
 */

$requirements = [
    'php_version' => '8.1.0',
    'extensions' => [
        'bcmath',
        'ctype',
        'curl',
        'dom',
        'fileinfo',
        'filter',
        'hash',
        'intl',
        'json',
        'libxml',
        'mbstring',
        'openssl',
        'pcre',
        'pdo',
        'pdo_mysql',
        'session',
        'tokenizer',
        'xml',
        'zip',
    ],
    'writable_dirs' => [
        'storage',
        'storage/app',
        'storage/framework',
        'storage/logs',
        'bootstrap/cache',
    ]
];

$results = [];
$hasErrors = false;

// 1. Check PHP Version
if (version_compare(PHP_VERSION, $requirements['php_version'], '>=')) {
    $results['php'] = ['status' => 'OK', 'msg' => 'PHP ' . PHP_VERSION];
} else {
    $results['php'] = ['status' => 'ERROR', 'msg' => 'Current: ' . PHP_VERSION . ' (Required: ' . $requirements['php_version'] . '+)'];
    $hasErrors = true;
}

// 2. Check Extensions
foreach ($requirements['extensions'] as $ext) {
    if (extension_loaded($ext)) {
        $results['extensions'][$ext] = 'OK';
    } else {
        $results['extensions'][$ext] = 'MISSING';
        $hasErrors = true;
    }
}

// 3. Check Writable Directories
$baseDir = __DIR__ . '/../'; // Assuming this file is in /public
foreach ($requirements['writable_dirs'] as $dir) {
    $fullPath = $baseDir . $dir;
    if (is_writable($fullPath)) {
        $results['permissions'][$dir] = 'OK';
    } else {
        // Try to create if doesn't exist? No, just check existence/permission
        if (!file_exists($fullPath)) {
            $results['permissions'][$dir] = 'MISSING (Create this folder)';
        } else {
            $results['permissions'][$dir] = 'NOT WRITABLE';
        }
        $hasErrors = true;
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Requirements Check</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            background: #f3f4f6;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        h1 {
            margin-top: 0;
            color: #111827;
        }

        .status-ok {
            color: #059669;
            font-weight: bold;
        }

        .status-error {
            color: #dc2626;
            font-weight: bold;
        }

        .item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .section {
            margin-top: 25px;
            margin-bottom: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #6b7280;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
        }

        .banner {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }

        .banner.success {
            background: #d1fae5;
            color: #065f46;
        }

        .banner.danger {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>

<body>
    <div class="card">
        <h1>🔍 Verificação de Requisitos</h1>

        <?php if ($hasErrors): ?>
            <div class="banner danger">❌ O servidor não atende a alguns requisitos. Verifique abaixo.</div>
        <?php else: ?>
            <div class="banner success">✅ Tudo certo! O servidor está pronto para receber o Adassoft.</div>
        <?php endif; ?>

        <div class="section">Versão do PHP</div>
        <div class="item">
            <span>PHP Version</span>
            <span class="<?= strpos($results['php']['status'], 'ERROR') !== false ? 'status-error' : 'status-ok' ?>">
                <?= $results['php']['msg'] ?>
            </span>
        </div>

        <div class="section">Extensões PHP</div>
        <?php foreach ($results['extensions'] as $ext => $status): ?>
            <div class="item">
                <span>Extensão <code><?= $ext ?></code></span>
                <span class="<?= $status == 'OK' ? 'status-ok' : 'status-error' ?>"><?= $status ?></span>
            </div>
        <?php endforeach; ?>

        <div class="section">Permissões de Escrita</div>
        <div style="font-size: 0.85rem; color: #666; mb-2;">O PHP precisa de permissão de escrita nessas pastas
            (relative to root)</div>
        <?php foreach ($results['permissions'] as $dir => $status): ?>
            <div class="item">
                <span><?= $dir ?></span>
                <span class="<?= $status == 'OK' ? 'status-ok' : 'status-error' ?>"><?= $status ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</body>

</html>
<?php
// index.php
require_once __DIR__ . '/config.php';
$configContent = [];
if (file_exists($configFile)) {
    $lines = file($configFile);
    $currentComment = "";
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // If active config
        if (preg_match('/^([^#;][^=]*?)\s*=\s*(.*)$/', $line, $matches)) {
            $key = trim($matches[1]);
            $value = trim($matches[2]);
            $configContent[] = [
                'type' => 'config',
                'status' => 'active',
                'key' => $key,
                'value' => $value,
                'description' => $currentComment
            ];
            $currentComment = ""; 
        } 
        // If commented out config (starts with # or ;, contains =)
        elseif (preg_match('/^[#;]\s*([^=\s]+)\s*=(.*)$/', $trimmed, $matches)) {
            $key = trim($matches[1]);
            $value = trim($matches[2]);
            $configContent[] = [
                'type' => 'config',
                'status' => 'inactive',
                'key' => $key,
                'value' => $value,
                'description' => $currentComment
            ];
            $currentComment = "";
        }
        // If section header [SECTION]
        elseif (preg_match('/^\[(.*)\]$/', $trimmed, $matches)) {
            $configContent[] = [
                'type' => 'header',
                'value' => $matches[1]
            ];
            $currentComment = "";
        }
        // Normal comment (no =)
        elseif (str_starts_with($trimmed, '#') || str_starts_with($trimmed, ';')) {
            $cleanComment = preg_replace('/^[#;]\s*/', '', $trimmed);
            if ($currentComment !== "") {
                $currentComment .= "<br>" . htmlspecialchars($cleanComment);
            } else {
                $currentComment = htmlspecialchars($cleanComment);
            }
        }
        elseif ($trimmed === "") {
             $currentComment = "";
        }
    }
} else {
    $error = "Config file not found at " . htmlspecialchars($configFile);
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XDOS Configuration Editor</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        fintech: {
                            bg: '#0a0a0a',
                            card: '#171717',
                            text: '#ededed',
                            accent: '#14b8a6', // Teal-500
                            hover: '#1f1f1f' // Slightly lighter for hover
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #0a0a0a;
            color: #ededed;
        }
        .fintech-input {
            background-color: #0a0a0a;
            border: 1px solid #333;
            color: #ededed;
            padding: 0.5rem;
            border-radius: 0.375rem;
            width: 100%;
            transition: border-color 0.2s;
        }
        .fintech-input:focus {
            outline: none;
            border-color: #14b8a6;
            box-shadow: 0 0 0 1px #14b8a6;
        }
    </style>
</head>
<body class="min-h-screen p-8 font-sans">

    <div class="max-w-4xl mx-auto">
        <header class="mb-8 border-b border-gray-800 pb-4 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-fintech-accent tracking-wide">XDOS Editor</h1>
                <p class="text-gray-400 mt-1 text-sm">Fintech-grade Configuration Manager</p>
                <div class="mt-2 text-xs text-gray-500 font-mono bg-gray-900/50 p-2 rounded inline-block border border-gray-800">
                    📂 <?= htmlspecialchars(realpath($configFile) ?: $configFile) ?>
                </div>
            </div>
            <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                <div class="bg-green-900/30 text-green-400 px-4 py-2 rounded border border-green-900 text-sm">
                    ✓ Configuration Saved Successfully
                </div>
            <?php endif; ?>
        </header>

        <?php if (isset($error)): ?>
            <div class="bg-red-900/50 border border-red-800 text-red-200 p-4 rounded mb-6">
                <?= $error ?>
            </div>
        <?php else: ?>
            
            <form action="save_config.php" method="POST" class="space-y-4">
                
                <?php 
                // Separate active and inactive
                $activeItems = array_filter($configContent, fn($i) => !isset($i['status']) || $i['status'] === 'active');
                $inactiveItems = array_filter($configContent, fn($i) => isset($i['status']) && $i['status'] === 'inactive');
                ?>

                <!-- Active Configuration -->
                <?php foreach ($activeItems as $item): ?>
                    
                    <?php if ($item['type'] === 'header'): ?>
                        <div class="pt-6 pb-2">
                            <h2 class="text-xl font-semibold text-fintech-accent border-l-4 border-fintech-accent pl-3">
                                [<?= htmlspecialchars($item['value']) ?>]
                            </h2>
                        </div>
                    
                    <?php elseif ($item['type'] === 'config'): ?>
                        <div class="bg-fintech-card p-4 rounded-lg border border-gray-800 hover:border-gray-700 transition-colors">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="flex-1">
                                    <label for="<?= htmlspecialchars($item['key']) ?>" class="block font-medium text-lg text-gray-200">
                                        <?= htmlspecialchars($item['key']) ?>
                                    </label>
                                    <?php if (!empty($item['description'])): ?>
                                        <p class="text-gray-500 text-sm mt-1 leading-relaxed">
                                            <?= $item['description'] ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="w-full md:w-1/3">
                                    <input type="text" 
                                           id="<?= htmlspecialchars($item['key']) ?>" 
                                           name="<?= htmlspecialchars($item['key']) ?>" 
                                           value="<?= htmlspecialchars($item['value']) ?>" 
                                           class="fintech-input"
                                           spellcheck="false">
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                <?php endforeach; ?>

                <?php if (!empty($inactiveItems)): ?>
                    <div class="mt-12 pt-8 border-t border-gray-800">
                        <h3 class="text-2xl font-bold text-gray-400 mb-6">Unused Configuration</h3>
                        <div class="space-y-4 opacity-75 grayscale-[50%]">
                            <?php foreach ($inactiveItems as $item): ?>
                                <div class="bg-fintech-card p-4 rounded-lg border border-gray-800 border-dashed">
                                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-0.5 rounded text-xs bg-gray-800 text-gray-500 font-mono">INACTIVE</span>
                                                <span class="block font-medium text-lg text-gray-400">
                                                    <?= htmlspecialchars($item['key']) ?>
                                                </span>
                                            </div>
                                            <?php if (!empty($item['description'])): ?>
                                                <p class="text-gray-600 text-sm mt-1 leading-relaxed">
                                                    <?= $item['description'] ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="w-full md:w-1/3">
                                            <input type="text" 
                                                   value="<?= htmlspecialchars($item['value']) ?>" 
                                                   class="fintech-input bg-gray-900/50 text-gray-500 border-gray-800 cursor-not-allowed"
                                                   readonly>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="fixed bottom-0 left-0 right-0 bg-fintech-card/80 backdrop-blur-md border-t border-gray-800 p-4 md:px-0">
                    <div class="max-w-4xl mx-auto flex justify-end">
                        <button type="submit" 
                                class="bg-fintech-accent text-black font-bold py-3 px-8 rounded hover:bg-teal-400 transition-colors shadow-[0_0_15px_rgba(20,184,166,0.3)]">
                            Save Changes
                        </button>
                    </div>
                </div>
                <!-- Space for fixed footer -->
                <div class="h-24"></div>

            </form>

        <?php endif; ?>
    </div>

</body>
</html>

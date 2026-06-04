<?php
header('Content-Type: application/json');
session_start();
include "koneksi.php";

// ══════════════════════════════════════════════
//  GANTI DENGAN API KEY OPENWEATHERMAP KAMU
$OWM_KEY = 'ISI_API_KEY_KAMU_DISINI';
// ══════════════════════════════════════════════

$lokasi = isset($_GET['lokasi']) ? trim($_GET['lokasi']) : '';

if (!$lokasi) {
    echo json_encode(['error' => 'Lokasi tidak boleh kosong']);
    exit;
}

// Aktifkan allow_url_fopen di php.ini jika belum
// Atau ganti file_get_contents dengan cURL (lihat fungsi owmFetch di bawah)

function owmFetch($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
    return @file_get_contents($url);
}

// ── 1. Geocoding: nama lokasi → koordinat ─────────────────────────────
$geo_url = "https://api.openweathermap.org/geo/1.0/direct"
         . "?q=" . urlencode($lokasi)
         . "&limit=1&appid=$OWM_KEY";

$geo_raw = owmFetch($geo_url);

if (!$geo_raw) {
    echo json_encode(['error' => 'Tidak dapat terhubung ke server cuaca. Periksa koneksi server.']);
    exit;
}

$geo = json_decode($geo_raw, true);

if (empty($geo) || !isset($geo[0]['lat'])) {
    echo json_encode(['error' => "Lokasi \"$lokasi\" tidak ditemukan. Coba nama kota yang lebih spesifik."]);
    exit;
}

$lat      = $geo[0]['lat'];
$lon      = $geo[0]['lon'];
$kota_res = $geo[0]['name'] ?? $lokasi;

// ── 2. Current weather ────────────────────────────────────────────────
$w_url = "https://api.openweathermap.org/data/2.5/weather"
       . "?lat=$lat&lon=$lon"
       . "&appid=$OWM_KEY"
       . "&units=metric"
       . "&lang=id";

$w_raw = owmFetch($w_url);

if (!$w_raw) {
    echo json_encode(['error' => 'Gagal mengambil data cuaca dari OpenWeatherMap.']);
    exit;
}

$w = json_decode($w_raw, true);

if (!$w || !isset($w['weather'][0])) {
    echo json_encode(['error' => 'Respons cuaca tidak valid.']);
    exit;
}

$id   = (int)$w['weather'][0]['id'];
$desc = $w['weather'][0]['description'] ?? '';
$suhu = round($w['main']['temp'] ?? 0);
$icon = $w['weather'][0]['icon'] ?? '';

// ── 3. Map weather ID → label sistem ─────────────────────────────────
// https://openweathermap.org/weather-conditions
if ($id >= 200 && $id < 300) {
    $label = 'Hujan Lebat';   // Thunderstorm
} elseif ($id >= 300 && $id < 400) {
    $label = 'Hujan Ringan';  // Drizzle
} elseif ($id >= 500 && $id < 510) {
    $label = 'Hujan Lebat';   // Rain
} elseif ($id >= 510 && $id < 600) {
    $label = 'Hujan Ringan';  // Light shower / freezing
} elseif ($id >= 600 && $id < 700) {
    $label = 'Berawan';       // Snow (diperlakukan sebagai berawan di tropis)
} elseif ($id >= 700 && $id < 800) {
    $label = 'Berawan';       // Atmosphere: fog, haze, mist, dll
} elseif ($id === 800) {
    $label = 'Cerah';         // Clear sky
} elseif ($id === 801) {
    $label = 'Cerah Berawan'; // Few clouds (11–25%)
} elseif ($id >= 802 && $id < 900) {
    $label = 'Berawan';       // Scattered / broken / overcast clouds
} else {
    $label = 'Cerah';
}

echo json_encode([
    'label'  => $label,
    'desc'   => ucfirst($desc),
    'suhu'   => $suhu,
    'icon'   => $icon ? "https://openweathermap.org/img/wn/{$icon}@2x.png" : '',
    'kota'   => $kota_res,
    'lat'    => $lat,
    'lon'    => $lon,
]);
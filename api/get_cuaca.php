<?php
header('Content-Type: application/json');
session_start();
include "koneksi.php";

$OWM_KEY = 'ISI_API_KEY_KAMU_DISINI'; // ganti dengan API key kamu

$lokasi = isset($_GET['lokasi']) ? trim($_GET['lokasi']) : '';
if (!$lokasi) {
    echo json_encode(['error' => 'Lokasi kosong']); exit;
}

// Ambil koordinat dari nama lokasi
$geo_url = "https://api.openweathermap.org/geo/1.0/direct?q=" . urlencode($lokasi) . "&limit=1&appid=$OWM_KEY";
$geo = json_decode(file_get_contents($geo_url), true);

if (empty($geo)) {
    echo json_encode(['error' => 'Lokasi tidak ditemukan']); exit;
}

$lat = $geo[0]['lat'];
$lon = $geo[0]['lon'];

// Ambil cuaca sekarang
$cuaca_url = "https://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lon&appid=$OWM_KEY&units=metric&lang=id";
$cuaca = json_decode(file_get_contents($cuaca_url), true);

if (!$cuaca || !isset($cuaca['weather'])) {
    echo json_encode(['error' => 'Gagal ambil data cuaca']); exit;
}

$id   = $cuaca['weather'][0]['id'];
$desc = $cuaca['weather'][0]['description'];
$suhu = round($cuaca['main']['temp']);
$icon = $cuaca['weather'][0]['icon'];

// Map ke pilihan cuaca di sistem kamu
if ($id >= 200 && $id < 300)       $label = 'Hujan Lebat';   // thunderstorm
elseif ($id >= 300 && $id < 400)   $label = 'Hujan Ringan';  // drizzle
elseif ($id >= 500 && $id < 510)   $label = 'Hujan Lebat';   // rain
elseif ($id == 511)                $label = 'Hujan Lebat';   // freezing rain
elseif ($id >= 520 && $id < 600)   $label = 'Hujan Ringan';  // shower
elseif ($id >= 600 && $id < 700)   $label = 'Berawan';       // snow
elseif ($id >= 700 && $id < 800)   $label = 'Berawan';       // atmosphere (fog, haze)
elseif ($id == 800)                $label = 'Cerah';         // clear sky
elseif ($id == 801)                $label = 'Cerah Berawan'; // few clouds
elseif ($id >= 802 && $id < 900)   $label = 'Berawan';       // cloudy
else                               $label = 'Cerah';

echo json_encode([
    'label' => $label,
    'desc'  => ucfirst($desc),
    'suhu'  => $suhu,
    'icon'  => "https://openweathermap.org/img/wn/{$icon}@2x.png",
    'lat'   => $lat,
    'lon'   => $lon,
]);
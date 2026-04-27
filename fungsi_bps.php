<?php
function ambilDataBPS() {
    // Menggunakan link API terbaru yang kamu berikan
    $url = "https://webapi.bps.go.id/v1/api/list/model/data/lang/ind/domain/0000/var/1470/th/126/key/279bfd3333f47740fe54cd482719d5f6";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Menambahkan timeout agar jika server BPS lambat, website kamu tidak hang
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); 
    
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
?>
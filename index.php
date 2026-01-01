<?php
// ====== BACKEND ======
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/json");

    $OPENAI_KEY = "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"; // SADECE BURADA

    $data = json_decode(file_get_contents("php://input"), true);
    $url = $data["link"] ?? "";

    if (!$url) {
        echo json_encode(["error" => "Link yok"]);
        exit;
    }

    // 1️⃣ Link içeriğini çek
    $context = stream_context_create([
        "http" => [
            "header" => "User-Agent: Mozilla/5.0"
        ]
    ]);

    $html = @file_get_contents($url, false, $context);
    if (!$html) {
        echo json_encode(["error" => "Sayfa okunamadı"]);
        exit;
    }

    // 2️⃣ Basit veri yakalama
    preg_match('/<title>(.*?)<\/title>/i', $html, $title);
    preg_match('/property="og:image" content="(.*?)"/i', $html, $img);
    preg_match('/₺\s?([\d\.,]+)/', $html, $price);

    $productTitle = $title[1] ?? "Bilinmeyen Ürün";
    $productImage = $img[1] ?? "";
    $productPrice = $price[1] ?? "Bulunamadı";

    // 3️⃣ OpenAI → kategori
    $prompt = "Ürün adı: $productTitle
Fiyat: $productPrice
Bu ürünü tek kelimelik kategoriye ayır. Sadece kategori yaz.";

    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $OPENAI_KEY",
            "Content-Type: application/json"
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            "model" => "gpt-4o-mini",
            "messages" => [
                ["role" => "user", "content" => $prompt]
            ]
        ])
    ]);

    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $category = trim($response["choices"][0]["message"]["content"] ?? "Diğer");

    echo json_encode([
        "name" => $productTitle,
        "price" => $productPrice . " TL",
        "image" => $productImage,
        "category" => $category
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Akıllı Hesapla</title>
<style>
body { font-family: Arial; background:#f4f4f4; padding:20px }
.card { background:#fff; padding:20px; border-radius:12px; max-width:420px }
input, button { width:100%; padding:12px; margin-top:10px }
button { background:#6c4cff; color:#fff; border:none; border-radius:8px }
img { width:100%; margin-top:10px }
</style>
</head>
<body>

<div class="card">
<h2>Akıllı Hesapla</h2>
<input id="link" placeholder="Ürün linkini gir">
<button onclick="analiz()">AI ile Analiz Et</button>

<div id="sonuc"></div>
</div>

<script>
function analiz() {
    fetch("", {
        method: "POST",
        headers: {"Content-Type":"application/json"},
        body: JSON.stringify({
            link: document.getElementById("link").value
        })
    })
    .then(r => r.json())
    .then(d => {
        document.getElementById("sonuc").innerHTML = `
            <p><b>Ürün:</b> ${d.name}</p>
            <p><b>Fiyat:</b> ${d.price}</p>
            <p><b>Kategori:</b> ${d.category}</p>
            <img src="${d.image}">
        `;
    });
}
</script>

</body>
</html>

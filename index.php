<?php
// ====== BACKEND ======
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/json");

    // ⚠️ OpenAI Key'i Replit Secrets'dan alıyoruz
    $OPENAI_KEY = getenv("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");

    $data = json_decode(file_get_contents("php://input"), true);
    $url = $data["link"] ?? "";

    if (!$url) {
        echo json_encode(["error" => "Lütfen bir ürün linki girin"]);
        exit;
    }

    // 1️⃣ Link içeriğini çek
    $context = stream_context_create([
        "http" => ["header" => "User-Agent: Mozilla/5.0"]
    ]);

    $html = @file_get_contents($url, false, $context);
    if (!$html) {
        echo json_encode(["error" => "Sayfa okunamadı veya site engellemiş olabilir"]);
        exit;
    }

    // 2️⃣ Basit veri yakalama
    preg_match('/<title>(.*?)<\/title>/i', $html, $title);
    preg_match('/property="og:image" content="(.*?)"/i', $html, $img);

    // Fiyat çekme: önce meta tag, yoksa regex
    preg_match('/<meta property="product:price:amount" content="(.*?)"/i', $html, $priceMeta);
    if(!empty($priceMeta[1])){
        $productPrice = $priceMeta[1];
    } else {
        preg_match('/₺?\s?([\d.,]+)\s?TL/i', $html, $price);
        $productPrice = $price[1] ?? "Bulunamadı";
    }

    $productTitle = $title[1] ?? "Bilinmeyen Ürün";
    $productImage = $img[1] ?? "";

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
.card { background:#fff; padding:20px; border-radius:12px; max-width:420px; margin:auto }
input, button { width:100%; padding:12px; margin-top:10px }
button { background:#6c4cff; color:#fff; border:none; border-radius:8px; cursor:pointer }
img { width:100%; margin-top:10px; border-radius:8px }
#sonuc p { margin:5px 0; }
#error { color:red; font-weight:bold; margin-top:10px; }
</style>
</head>
<body>

<div class="card">
<h2>Akıllı Hesapla</h2>
<input id="link" placeholder="Ürün linkini gir">
<button onclick="analiz()">AI ile Analiz Et</button>
<div id="sonuc"></div>
<div id="error"></div>
</div>

<script>
function analiz() {
    const sonuc = document.getElementById("sonuc");
    const errorDiv = document.getElementById("error");
    sonuc.innerHTML = "Yükleniyor...";
    errorDiv.innerHTML = "";

    fetch("", {
        method: "POST",
        headers: {"Content-Type":"application/json"},
        body: JSON.stringify({ link: document.getElementById("link").value })
    })
    .then(r => r.json())
    .then(d => {
        if(d.error){
            errorDiv.innerHTML = d.error;
            sonuc.innerHTML = "";
            return;
        }
        sonuc.innerHTML = `
            <p><b>Ürün:</b> ${d.name}</p>
            <p><b>Fiyat:</b> ${d.price}</p>
            <p><b>Kategori:</b> ${d.category}</p>
            ${d.image ? `<img src="${d.image}">` : ""}
        `;
    })
    .catch(e => {
        errorDiv.innerHTML = "Bir hata oluştu, tekrar deneyin";
        sonuc.innerHTML = "";
    });
}
</script>

</body>
</html>

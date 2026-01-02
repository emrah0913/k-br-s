import React, { useState, useEffect } from 'react';
import confetti from 'canvas-confetti';

// Gemini API ayarları (Preview ortamı için)
const apiKey = ""; 

const App = () => {
  const [link, setLink] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [result, setResult] = useState(null);

  const analyzeLink = async () => {
    if (!link) {
      setError("Hey! Linki unuttun 🧐");
      return;
    }

    setLoading(true);
    setError('');
    setResult(null);

    try {
      // ÖNEMLİ: Tarayıcı ortamında doğrudan scraping yapılamadığı için 
      // bu bölüm simülasyon amaçlıdır. Canlı PHP kodunda gerçek scraping yapılır.
      const systemPrompt = `Sen bir alışveriş asistanısın. Verilen URL'den ürünün ne olduğunu tahmin et. 
      ÖNEMLİ: Linkteki URL yapısına bakarak güncel piyasa fiyatlarını gerçekçi bir şekilde tahmin etmeye çalış. 
      Eğer link bilindik bir siteyse (Trendyol, Amazon vb.), o kategorideki ortalama fiyatları kullan.
      Şu JSON formatında dön: 
      {
        "name": "Ürün Adı",
        "price": "Fiyat (Sadece rakam ve TL)",
        "category": "Kategori",
        "advice": "Kısa havalı satın alma yorumu (maks 15 kelime)",
        "score": "10 üzerinden puan",
        "imageUrl": "https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&q=80&w=400"
      }`;

      const response = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-09-2025:generateContent?key=${apiKey}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          contents: [{ parts: [{ text: `Bu linkteki ürünü derinlemesine analiz et ve fiyatını gerçekçi tahmin et: ${link}` }] }],
          systemInstruction: { parts: [{ text: systemPrompt }] },
          generationConfig: { responseMimeType: "application/json" }
        })
      });

      const data = await response.json();
      const aiResponse = JSON.parse(data.candidates[0].content.parts[0].text);

      setResult(aiResponse);
      
      confetti({
        particleCount: 150,
        spread: 70,
        origin: { y: 0.6 },
        colors: ['#6c4cff', '#ff4c81', '#2ecc71']
      });

    } catch (err) {
      setError("Analiz başarısız oldu. Lütfen tekrar dene.");
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen w-full flex items-center justify-center p-4 overflow-hidden relative" 
         style={{ background: 'linear-gradient(135deg, #0f0c29, #302b63, #24243e)', fontFamily: "'Poppins', sans-serif" }}>
      
      <div className="absolute w-72 h-72 rounded-full blur-[100px] opacity-20 animate-pulse" 
           style={{ background: '#6c4cff', top: '10%', left: '10%' }}></div>

      <div className="relative z-10 w-full max-w-md p-8 rounded-[30px] border border-white/10 shadow-2xl transition-all"
           style={{ background: 'rgba(255, 255, 255, 0.05)', backdropFilter: 'blur(20px)' }}>
        
        <div className="text-[10px] text-center text-white/40 mb-2 tracking-widest uppercase">Önizleme / Simülasyon Modu</div>
        <h2 className="text-3xl font-extrabold text-center mb-8 tracking-tighter uppercase italic">
          <span className="text-white">Akıllı Hesapla</span> <span style={{ color: '#ff4c81' }}>Ultra</span>
        </h2>

        <div className="space-y-4">
          <input 
            type="text" 
            placeholder="Ürün linkini yapıştır..." 
            className="w-full p-4 rounded-xl bg-white/5 border border-white/10 text-white outline-none focus:border-[#6c4cff] transition-all"
            value={link}
            onChange={(e) => setLink(e.target.value)}
          />

          {!loading && (
            <button 
              onClick={analyzeLink}
              className="w-full py-4 rounded-xl font-bold text-lg text-white shadow-lg transition-transform active:scale-95 hover:-translate-y-1"
              style={{ background: 'linear-gradient(45deg, #6c4cff, #ff4c81)' }}>
              ANALİZİ BAŞLAT
            </button>
          )}

          {loading && (
            <div className="flex justify-center py-4">
              <div className="w-12 h-12 border-4 border-t-[#6c4cff] border-white/10 rounded-full animate-spin"></div>
            </div>
          )}

          {error && <p className="text-[#ff4c81] text-center font-bold mt-2">{error}</p>}

          {result && (
            <div className="mt-8 pt-8 border-t border-white/10 animate-in fade-in slide-in-from-bottom-4 duration-500">
              <img src={result.imageUrl} alt="Ürün" className="w-full h-48 object-cover rounded-2xl border border-white/10 mb-4" />
              
              <div className="flex justify-center gap-2 mb-4">
                <span className="px-3 py-1 bg-[#6c4cff] rounded-full text-[10px] font-bold uppercase">{result.category}</span>
                <span className="px-3 py-1 bg-[#2ecc71] rounded-full text-[10px] font-bold uppercase">PUAN: {result.score}</span>
              </div>

              <h3 className="text-white font-semibold text-center mb-1">{result.name}</h3>
              <p className="text-[#2ecc71] text-2xl font-black text-center mb-4">{result.price}</p>
              
              <div className="p-4 bg-white/5 border-l-4 border-[#ff4c81] rounded-r-xl italic text-sm text-gray-300">
                “ {result.advice} ”
              </div>
              
              <p className="text-[9px] text-white/20 text-center mt-4">* Fiyatlar simülasyon gereği tahmini olabilir.</p>
            </div>
          )}
        </div>
      </div>

      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap');
      `}</style>
    </div>
  );
};

export default App;

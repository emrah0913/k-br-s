import { getFirestore, collection, addDoc, query, where, getDocs, deleteDoc } from "https://www.gstatic.com/firebasejs/9.15.0/firebase-firestore.js";
import { getAuth, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/9.15.0/firebase-auth.js";

const db = getFirestore();
const auth = getAuth();
let currentUser = null;

onAuthStateChanged(auth, user => {
  currentUser = user;
  yorumlariGetir();
});

async function yorumEkle() {
  if(!currentUser) return alert("Önce giriş yapın.");

  const text = document.getElementById("yorum-text").value;
  const puan = parseInt(document.getElementById("yorum-puan").value);

  await addDoc(collection(db, "yorumlar"), {
    userId: currentUser.uid,
    text,
    puan,
    onay: false,
    timestamp: new Date()
  });

  document.getElementById("yorum-text").value = "";
  yorumlariGetir();
}

async function yorumlariGetir() {
  const yorumListesi = document.getElementById("yorum-listesi");
  yorumListesi.innerHTML = "";

  const q = query(collection(db, "yorumlar"), where("onay", "==", true));
  const snapshot = await getDocs(q);

  snapshot.forEach(docSnap => {
    const data = docSnap.data();
    const div = document.createElement("div");
    div.innerHTML = `
      <p>${data.text} - ${"⭐".repeat(data.puan)}</p>
      ${currentUser && data.userId === currentUser.uid ? `<button onclick="yorumSil('${docSnap.id}')">Sil</button>` : ""}
    `;
    yorumListesi.appendChild(div);
  });
}

async function yorumSil(yorumId) {
  await deleteDoc(doc(db, "yorumlar", yorumId));
  yorumlariGetir();
}

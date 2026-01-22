# 🧠 Alur Kerja Aplikasi Chat (Laravel + Reverb)

Dokumen ini menjelaskan bagaimana setiap bagian kode bekerja sama untuk membuat fitur chat real-time. Kita akan menelusuri perjalanan satu pesan dari saat Anda mengetik hingga muncul di layar lawan bicara.

---

## 1. 🔄 Gambaran Besar (The Flow)

Bayangkan sistem ini seperti **Kantor Pos**:

1.  **Anda (Frontend):** Menulis surat dan memberikannya ke loket (Kirim Request ke Server).
2.  **Petugas Loket (Route/Controller):** Menerima surat, mencatatnya di buku arsip (Database), lalu berteriak ke ruang pengumuman.
3.  **Pengumuman (Event):** "Ada surat baru untuk Grup A!".
4.  **Speaker (Reverb WebSocket):** Menyiarkan suara teriakan itu ke seluruh gedung.
5.  **Orang Lain (Frontend Lawan):** Mendengar pengumuman lewat speaker dan langsung mengambil surat itu (Update UI).

---

## 2. 🧩 Bedah Komponen (Detail File)

### A. Routes & Controller (`routes/web.php`)
Ini adalah **Pintu Masuk**. Saat Anda klik "Kirim", JavaScript mengirim data ke sini.

*   **Tugas:**
    1.  Menerima data pesan & penerima.
    2.  Menyimpan pesan ke Database (`Message::create`).
    3.  **MEMICU EVENT** (`MessageSent::dispatch`). Ini adalah langkah kunci untuk real-time!

```php
Route::post('/send-message', function (Request $request) {
    // 1. Simpan ke Database
    $msg = Message::create([...]);

    // 2. Teriakkan Event ke Reverb
    MessageSent::dispatch($msg);

    return $msg;
});
```

### B. Event (`app/Events/MessageSent.php`)
Ini adalah **Isi Pengumuman**. File ini membungkus data pesan agar bisa dikirim lewat sinyal radio (WebSocket).

*   **Tugas:**
    1.  Membawa data pesan (`public $message`).
    2.  Menentukan **Channel** (Saluran) mana yang harus disiarkan.
        *   `PresenceChannel('chat')` -> Untuk chat umum (semua orang dengar).
        *   `PrivateChannel('chat.private.1')` -> Untuk pesan rahasia (hanya User ID 1 yang dengar).

```php
class MessageSent implements ShouldBroadcastNow // 'Now' berarti kirim detik ini juga!
{
    public function broadcastOn()
    {
        // Kirim ke saluran bernama 'chat'
        return [new PresenceChannel('chat')];
    }
}
```

### C. Channels (`routes/channels.php`)
Ini adalah **Satpam**. Sebelum seseorang bisa "mendengar" saluran tertentu, satpam ini mengecek izinnya.

*   **Tugas:** Memastikan user boleh join channel tersebut.
    *   Untuk `chat` (Public): Mengembalikan info user (agar kita tahu siapa yg online).
    *   Untuk `chat.private.{id}`: Mengecek apakah ID user yang login cocok dengan ID channel.

```php
// Satpam: "Boleh masuk channel 'chat'?"
Broadcast::channel('chat', function ($user) {
    return $user; // "Boleh, ini datamu."
});
```

### D. Frontend Listener (`resources/views/chat.blade.php`)
Ini adalah **Telinga User**. Menggunakan `Laravel Echo` untuk mendengarkan saluran Reverb.

*   **Tugas:**
    1.  Konek ke Reverb.
    2.  `listen('MessageSent')`: Jika ada event `MessageSent` masuk, jalankan fungsi.
    3.  `appendMessage(...)`: Tampilkan pesan di layar tanpa refresh halaman.

```javascript
window.Echo.join('chat')
    .listen('MessageSent', (e) => {
        // e.message berisi data dari Event tadi
        tampilkanPesan(e.message.sender.name, e.message.message);
    });
```

---

## 3. 🎬 Skenario: "Apa yang terjadi saat saya kirim 'Halo'?"

1.  **Input:** Anda ketik "Halo" dan tekan Enter.
2.  **JS:** Browser mengirim request `POST /send-message` ke Laravel.
3.  **Laravel:**
    *   Terima request.
    *   Simpan "Halo" di tabel `messages` (Postgres/SQLite).
    *   Panggil `MessageSent::dispatch($pesan)`.
4.  **Event:** Event ini lari ke **Reverb Server** membawa data JSON pesan tersebut.
5.  **Reverb:** Reverb melihat channelnya adalah `'chat'`, lalu menyebarkan (broadcast) JSON itu ke **semua** browser yang sedang membuka halaman chat.
6.  **Browser Lawan:**
    *   Laravel Echo mendengar sinyal masuk.
    *   Mengambil JSON "Halo".
    *   Menambahkan bubble chat baru di layar secara instan.

**Selesai!** Semua terjadi dalam hitungan milidetik. ⚡

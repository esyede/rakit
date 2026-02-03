# WebSocket

Paket WebSocket menyediakan kemampuan komunikasi real-time untuk aplikasi Rakit menggunakan protokol WebSocket.

## Instalasi

Paket WebSocket sudah termasuk dalam core framework Rakit. Tidak diperlukan instalasi tambahan.

## Konfigurasi

Konfigurasi WebSocket di `application/config/websocket.php`:

- `max_buffer_size`: Ukuran buffer maksimal untuk menerima data.
- `origin_required`: Apakah header Origin diperlukan.
- `logging_enabled`: Aktifkan logging.
- `logging_output`: Output logging ('file' atau 'stdout').

## Menjalankan Server

Jalankan server WebSocket menggunakan command console:

```bash
php rakit websocket:run
```

Server akan berjalan di host dan port yang dikonfigurasi (default: 127.0.0.1:6001).

## Koneksi Client

Hubungkan ke server dari JavaScript:

```javascript
const ws = new WebSocket('ws://127.0.0.1:6001');
ws.onmessage = (event) => {
    console.log('Diterima:', JSON.parse(event.data));
};
```

User yang terautentikasi dapat terhubung dengan cookie session untuk data user di presence.

## Format Pesan

### Broadcasting

Kirim pesan ke semua client yang terhubung:

```javascript
ws.send(JSON.stringify({ message: 'Halo semua!' }));
```

### Pesan Privat

Kirim pesan ke client tertentu:

```javascript
ws.send(JSON.stringify({ to: 'client_id', message: 'Halo!' }));
```

### Channel

Subscribe ke channel:

```javascript
ws.send(JSON.stringify({ event: 'subscribe', channel: 'chat' }));
```

Kirim ke channel:

```javascript
ws.send(JSON.stringify({ event: 'message', channel: 'chat', data: 'Halo channel!' }));
```

### Command

Kontrol server via pesan WebSocket:

- Broadcast: `{"command": "broadcast", "message": "text"}`
- Disconnect client: `{"command": "disconnect", "client_id": "id"}`
- Update presence: `{"command": "presence"}`
- Broadcast ke channel: `{"command": "broadcast_to_channel", "channel": "name", "message": "text"}`
- Pesan privat: `{"command": "private_message", "to": "client_id", "message": "text"}`

## Presence

Presence menampilkan user online. Dipicu saat connect/disconnect.

Format respons:

```json
{
    "type": "presence",
    "users": [
        {
            "id": "client_id",
            "name": "Nama User",
            "email": "user@example.com",
            "connected_at": 1234567890
        }
    ]
}
```

## Event

Server memicu event:

- `start`: Server dimulai.
- `connect`: Client terhubung.
- `disconnect`: Client terputus.
- `receive`: Pesan diterima.
- `send`: Pesan dikirim.
- `crash`: Server crash.

## Contoh

### Chat Dasar

```javascript
const ws = new WebSocket('ws://127.0.0.1:6001');
ws.onopen = () => ws.send(JSON.stringify({ message: 'Halo!' }));
ws.onmessage = (event) => {
    const data = JSON.parse(event.data);
    if (data.type === 'presence') {
        console.log('User online:', data.users);
    } else {
        console.log('Pesan:', data);
    }
};
```

### Menggunakan Command

```javascript
// Broadcast via command
ws.send(JSON.stringify({ command: 'broadcast', message: 'Pesan server' }));
```

## Keamanan

- Aktifkan `origin_required` untuk produksi.
- Autentikasi user via session untuk data presence.
- Gunakan HTTPS untuk koneksi aman.

## Troubleshooting

- Periksa log jika `logging_enabled` aktif.
- Pastikan port tidak digunakan.
- Verifikasi URL koneksi client.

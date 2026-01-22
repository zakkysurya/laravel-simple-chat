<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reverb Super Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .chat-box { height: 400px; overflow-y: auto; background: #f0f2f5; padding: 15px; }
        .user-item { cursor: pointer; transition: 0.2s; }
        .user-item:hover, .user-item.active { background-color: #e9ecef; }
        .typing-indicator { font-style: italic; color: gray; font-size: 0.8rem; height: 20px; }
        
        /* Message Bubbles */
        .message-row { overflow: hidden; margin-bottom: 10px; }
        .message-bubble { padding: 10px 15px; border-radius: 10px; max-width: 75%; display: inline-block; word-wrap: break-word; position: relative; }
        
        /* Mine (Right) */
        .message-mine { 
            background-color: #0d6efd; 
            color: white; 
            float: right; 
            clear: both; 
            text-align: left;
            border-bottom-right-radius: 2px; /* Sharp corner for tail */
        }
        .message-mine::after {
            content: "";
            position: absolute;
            right: -8px;
            bottom: 6px;
            width: 0;
            height: 0;
            border-top: 0px solid transparent;
            border-left: 10px solid #0d6efd;
            border-bottom: 10px solid transparent;
        }

        /* Other (Left) */
        .message-other { 
            background-color: #ffffff; 
            color: black; 
            float: left; 
            clear: both; 
            border: 1px solid #dee2e6;
            border-bottom-left-radius: 2px; /* Sharp corner for tail */
        }
        .message-other::after {
            content: "";
            position: absolute;
            left: -8px;
            bottom: 6px;
            width: 0;
            height: 0;
            border-top: 0px solid transparent;
            border-right: 10px solid #ffffff;
            border-bottom: 10px solid transparent;
        }
        
        /* Message Info (Name & Time) */
        .message-info { font-size: 0.75rem; color: #6c757d; margin-bottom: 2px; clear: both; }
        .message-info-mine { text-align: right; float: right; width: 100%; }
        .message-info-other { text-align: left; float: left; width: 100%; }
    </style>
</head>
<body class="p-4">

<div class="container">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">User Online (<span id="online-count">0</span>)</div>
                <ul class="list-group list-group-flush" id="user-list">
                    <li class="list-group-item text-muted">Menunggu koneksi...</li>
                </ul>
            </div>
            <button class="btn btn-secondary w-100 mt-2" onclick="switchChat(null)">Kembali ke Grup Umum</button>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between">
                    <span id="chat-title">Grup Umum</span>
                    <small>Anda: {{ $currentUser->name }}</small>
                </div>
                
                <div class="card-body chat-box" id="message-container">
                    @foreach($messages as $msg)
                        <div class="message-row">
                            <div class="message-info {{ $msg->sender_id == $currentUser->id ? 'message-info-mine' : 'message-info-other' }}">
                                <strong>{{ $msg->sender_id == $currentUser->id ? 'Saya' : $msg->sender->name }}</strong> 
                                <span class="ms-1">{{ $msg->created_at->format('H:i') }}</span>
                            </div>
                            <div class="message-bubble {{ $msg->sender_id == $currentUser->id ? 'message-mine' : 'message-other' }}">
                                {{ $msg->message }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="card-footer">
                    <div id="typing-status" class="typing-indicator"></div>
                    <form id="chat-form">
                        <div class="input-group align-items-end">
                            <textarea id="message-input" class="form-control" rows="1" placeholder="Tulis pesan..." style="resize:none; max-height:200px; overflow-y:auto;"></textarea>
                            <button class="btn btn-primary" id="send-btn"><i class="fas fa-paper-plane"></i> Kirim</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="module">
    const currentUserId = {{ $currentUser->id }};
    const messageContainer = document.getElementById('message-container');
    const userList = document.getElementById('user-list');
    const onlineCount = document.getElementById('online-count');
    const typingStatus = document.getElementById('typing-status');
    
    // Auto-scroll to bottom on load
    messageContainer.scrollTop = messageContainer.scrollHeight;
    
    let activeReceiverId = null; // null = Public Chat, angka = Private Chat
    let typingTimer;

    // --- 1. SETUP REVERB (PRESENCE CHANNEL: 'chat') ---
    
    const channel = window.Echo.join('chat');

    channel
        .here((users) => {
            // Saat pertama connect, load siapa saja yang ada
            updateUserList(users);
        })
        .joining((user) => {
            console.log(user.name + ' bergabung.');
            // Refresh list (cara simpel: reload page atau manipulasi array, di sini kita skip manipulasi array kompleks)
            // Untuk demo, kita abaikan update realtime list user agar kode pendek, 
            // tapi 'here' di atas sudah menangani load awal.
        })
        .leaving((user) => {
            console.log(user.name + ' keluar.');
        })
        .listen('MessageSent', (e) => {
            // Jika kita sedang di Public Chat, tampilkan pesan
            if (activeReceiverId === null) {
                // Ignore own messages (since we append them manually)
                if (e.message.sender_id == currentUserId) return;
                
                appendMessage(e.message.sender.name, e.message.message, false);
            }
        })
        .listenForWhisper('typing', (e) => {
            // Fitur 1: User sedang mengetik (Public)
            if (activeReceiverId === null && e.userId != currentUserId) {
                showTyping(e.name);
            }
        });

    // --- 2. SETUP REVERB (PRIVATE CHANNEL: 'chat.private.ID') ---
    // Ini adalah "Inbox" kita sendiri
    window.Echo.private(`chat.private.${currentUserId}`)
        .listen('.PrivateMessageSent', (e) => {
            // Jika kita sedang chat dengan si Pengirim, tampilkan langsung
            if (activeReceiverId == e.message.sender_id) {
                appendMessage(e.message.sender.name, e.message.message, false);
            } else {
                if (confirm(`Pesan baru dari ${e.message.sender.name}: "${e.message.message}"\nBalas sekarang?`)) {
                    switchChat(e.message.sender_id, e.message.sender.name);
                }
            }
        })
        .listenForWhisper('typing', (e) => {
            // Fitur 1: User sedang mengetik (Private)
            // Hanya tampilkan jika kita sedang membuka chat window orang tersebut
            if (activeReceiverId == e.userId) {
                showTyping(e.name);
            }
        });

    // --- 3. FITUR LOGIC UTAMA ---

    // Fungsi Ganti Mode Chat (Public vs Private)
    window.switchChat = async (userId, userName) => {
        activeReceiverId = userId; // Set target
        messageContainer.innerHTML = 'Loading history...';
        
        // Update Judul dan Tombol
        document.getElementById('chat-title').innerText = userId ? `Private: ${userName}` : 'Grup Umum';
        document.getElementById('message-input').placeholder = userId ? `Tulis pesan ke ${userName}...` : 'Tulis pesan ke Grup...';

        if (userId) {
            // Fetch History Private (Fitur 3)
            const res = await fetch(`/private-messages/${userId}`);
            const data = await res.json();
            messageContainer.innerHTML = '';
            data.forEach(msg => {
                const isMine = msg.sender_id == currentUserId;
                const senderName = isMine ? 'Saya' : msg.sender.name;
                const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                appendMessage(senderName, msg.message, isMine, time);
            });
        } else {
            // Reload page untuk balik ke public (cara termudah load history public)
            window.location.reload(); 
        }
    }

    // Fungsi Kirim Pesan
    document.getElementById('chat-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = document.getElementById('message-input');
        const msg = input.value.trim();
        if(!msg) return;

        // Tampilkan pesan sendiri di UI langsung (biar cepat)
        appendMessage('Saya', msg, true);
        input.value = '';
        input.style.height = 'auto'; // Reset height

        // Kirim ke API
        await fetch('/send-message', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ message: msg, receiver_id: activeReceiverId })
        });
    });

    // Auto-resize Textarea & Enter to Send
    const txInput = document.getElementById('message-input');
    txInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    txInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('chat-form').dispatchEvent(new Event('submit'));
        }
    });

    // Fungsi "Sedang Mengetik..." (Fitur 1)
    const messageInput = document.getElementById('message-input');
    messageInput.addEventListener('input', () => {
        // Kirim Whisper
        if (activeReceiverId) {
            // Whisper ke channel private orang tersebut (agak tricky di reverb simple)
            // Untuk simplifikasi demo private typing:
            // Kita whisper ke public channel tapi bawa data "to_user_id"
            // Atau untuk paling mudah: Fitur typing HANYA DI PUBLIC dulu untuk tutorial ini.
            window.Echo.join('chat').whisper('typing', { name: '{{ $currentUser->name }}', userId: currentUserId });
        } else {
            window.Echo.join('chat').whisper('typing', { name: '{{ $currentUser->name }}', userId: currentUserId });
        }
    });

    // Helpers UI
    function appendMessage(sender, text, isMine = false, time = null) {
        const timeString = time || new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        const row = document.createElement('div');
        row.classList.add('message-row');
        
        const info = document.createElement('div');
        info.classList.add('message-info', isMine ? 'message-info-mine' : 'message-info-other');
        info.innerHTML = `<strong>${sender}</strong> <span class="ms-1">${timeString}</span>`;

        const bubble = document.createElement('div');
        bubble.classList.add('message-bubble', isMine ? 'message-mine' : 'message-other');
        bubble.innerText = text; // Prevent XSS
        
        row.appendChild(info);
        row.appendChild(bubble);
        messageContainer.appendChild(row);
        messageContainer.scrollTop = messageContainer.scrollHeight;
    }

    function showTyping(name) {
        typingStatus.innerText = `${name} sedang mengetik...`;
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => { typingStatus.innerText = ''; }, 1000);
    }

    function updateUserList(users) {
        onlineCount.innerText = users.length;
        userList.innerHTML = '';
        users.forEach(user => {
            if (user.id === currentUserId) return; // Jangan tampilkan diri sendiri
            const li = document.createElement('li');
            li.className = 'list-group-item user-item';
            li.innerText = user.name;
            li.onclick = () => switchChat(user.id, user.name); // Klik untuk Private Chat
            userList.appendChild(li);
        });
    }
</script>

</body>
</html>
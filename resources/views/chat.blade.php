<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reverb Super Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        /* Fix for blue icons on blue background */
        .message-mine .text-primary { color: white !important; }

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

        /* Icon Popup */
        #icon-popup {
            position: absolute;
            bottom: 70px;
            left: 15px;
            width: 220px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        .icon-option {
            font-size: 1.5rem;
            cursor: pointer;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            transition: background 0.2s;
        }
        .icon-option:hover { background-color: #f8f9fa; }
        
        /* ContentEditable Placeholder */
        [contenteditable]:empty::before {
            content: attr(placeholder);
            color: #6c757d;
            cursor: text;
            display: block; /* For Firefox */
        }
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

                <div class="card-footer position-relative">
                    <!-- Icon Popup -->
                    <div id="icon-popup" class="d-none"></div>

                    <div id="typing-status" class="typing-indicator"></div>
                    <form id="chat-form">
                        <div class="input-group align-items-end">
                            <button type="button" class="btn btn-light border" id="icon-btn" title="Pilih Icon"><i class="far fa-smile"></i></button>
                            <div id="message-input" class="form-control" contenteditable="true" placeholder="Tulis pesan..." style="max-height:200px; overflow-y:auto; white-space: pre-wrap;"></div>
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
    
    // --- KEEP-ALIVE SYSTEM ---
    // Ping server every 5 minutes to keep session alive and refresh CSRF
    setInterval(async () => {
        try {
            const res = await fetch('/keep-alive');
            if (res.ok) {
                const data = await res.json();
                document.querySelector('meta[name="csrf-token"]').content = data.csrf_token;
                console.log('Session refreshed');
            } else {
                console.warn('Keep-alive failed');
            }
        } catch (e) {
            console.error('Keep-alive error:', e);
        }
    }, 5 * 60 * 1000); // 5 Minutes
    
    let activeReceiverId = null; // null = Public Chat, angka = Private Chat
    let typingTimer;
    let onlineUsers = []; // State untuk user online

    // --- 1. SETUP REVERB (PRESENCE CHANNEL: 'chat') ---
    
    const channel = window.Echo.join('chat'); 

    channel
        .here((users) => {
            // Saat pertama connect, simpan state dan render
            onlineUsers = users;
            updateUserList(onlineUsers);
        })
        .joining((user) => {
            console.log(user.name + ' bergabung.');
            // Tambahkan user jika belum ada
            if (!onlineUsers.find(u => u.id === user.id)) {
                onlineUsers.push(user);
                updateUserList(onlineUsers);
            }
        })
        .leaving((user) => {
            console.log(user.name + ' keluar.');
            // Hapus user dari list
            onlineUsers = onlineUsers.filter(u => u.id !== user.id);
            updateUserList(onlineUsers);
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
                Swal.fire({
                    title: `Pesan dari ${e.message.sender.name}`,
                    text: e.message.message,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Balas',
                    cancelButtonText: 'Nanti',
                    confirmButtonColor: '#0d6efd',
                }).then((result) => {
                    if (result.isConfirmed) {
                        switchChat(e.message.sender_id, e.message.sender.name);
                    }
                });
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
        
        // Parse Content: HTML -> Text with Shortcodes
        const temp = document.createElement('div');
        temp.innerHTML = input.innerHTML;
        
        // Replace Icon Elements with Codes
        temp.querySelectorAll('i[data-code]').forEach(el => {
            el.replaceWith(el.dataset.code);
        });
        
        // Convert br to newline
        temp.querySelectorAll('br').forEach(el => el.replaceWith('\n'));
        temp.querySelectorAll('div').forEach(el => el.before('\n')); 
        
        const msg = temp.textContent.trim();
        if(!msg) return;

        // Tampilkan pesan sendiri di UI langsung
        appendMessage('Saya', msg, true);
        input.innerHTML = ''; // Clear div

        // Kirim ke API menggunakan FormData agar lebih stabil di Firefox
        try {
            console.log('Sending message to:', activeReceiverId);
            
            const formData = new FormData();
            formData.append('message', msg);
            if (activeReceiverId) {
                formData.append('receiver_id', activeReceiverId);
            }
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

            const res = await fetch('/send-message', {
                method: 'POST',
                cache: 'no-store',
                headers: { 
                    'Accept': 'application/json'
                    // Jangan set Content-Type, browser akan otomatis set multipart/form-data
                },
                body: formData
            });
            
            if (!res.ok) {
                throw new Error(`Gagal mengirim (Status: ${res.status})`);
            }
        } catch (err) {
            console.error(err);
            Swal.fire({
                icon: 'error',
                title: 'Koneksi Terputus',
                text: `${err.message}. Silakan muat ulang halaman.`,
                confirmButtonText: 'Muat Ulang Halaman',
                confirmButtonColor: '#0d6efd',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(() => {
                window.location.reload();
            });
        }
    });

    // Enter to Send
    const msgInput = document.getElementById('message-input');
    msgInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('chat-form').dispatchEvent(new Event('submit'));
        }
    });

    // Handle Paste (Plain Text Only)
    msgInput.addEventListener('paste', (e) => {
        e.preventDefault();
        const text = (e.originalEvent || e).clipboardData.getData('text/plain');
        document.execCommand('insertText', false, text);
    });

    // Fungsi "Sedang Mengetik..." (Fitur 1)
    msgInput.addEventListener('input', () => {
        // Kirim Whisper
        if (activeReceiverId) {
            window.Echo.join('chat').whisper('typing', { name: '{{ $currentUser->name }}', userId: currentUserId });
        } else {
            window.Echo.join('chat').whisper('typing', { name: '{{ $currentUser->name }}', userId: currentUserId });
        }
    });

    // --- ICON LOGIC ---
    const iconMap = {
        ':smile:': '<i class="fas fa-smile text-warning" data-code=":smile:"></i>',
        ':laugh:': '<i class="fas fa-laugh-beam text-warning" data-code=":laugh:"></i>',
        ':sad:': '<i class="fas fa-sad-tear text-warning" data-code=":sad:"></i>',
        ':angry:': '<i class="fas fa-angry text-danger" data-code=":angry:"></i>',
        ':love:': '<i class="fas fa-heart text-danger" data-code=":love:"></i>',
        ':thumbsup:': '<i class="fas fa-thumbs-up text-primary" data-code=":thumbsup:"></i>',
        ':star:': '<i class="fas fa-star text-warning" data-code=":star:"></i>',
        ':fire:': '<i class="fas fa-fire text-danger" data-code=":fire:"></i>',
        ':check:': '<i class="fas fa-check-circle text-success" data-code=":check:"></i>',
        ':exclaim:': '<i class="fas fa-exclamation-circle text-danger" data-code=":exclaim:"></i>',
    };

    const iconPopup = document.getElementById('icon-popup');
    const iconBtn = document.getElementById('icon-btn');

    // Generate Icons
    Object.entries(iconMap).forEach(([code, html]) => {
        const div = document.createElement('div');
        div.className = 'icon-option';
        div.innerHTML = html;
        // Use mousedown to prevent focus loss
        div.onmousedown = (e) => {
            e.preventDefault();
            const input = document.getElementById('message-input');
            input.focus();
            
            // Insert Icon using execCommand for better stability
            const htmlToInsert = html + '&nbsp;';
            if (!document.execCommand('insertHTML', false, htmlToInsert)) {
                // Fallback
                input.innerHTML += htmlToInsert;
            }
            
            iconPopup.classList.add('d-none');
        };
        iconPopup.appendChild(div);
    });

    // Toggle Popup
    iconBtn.onclick = () => iconPopup.classList.toggle('d-none');

    // Close on click outside
    document.addEventListener('click', (e) => {
        if (!iconPopup.contains(e.target) && !iconBtn.contains(e.target)) {
            iconPopup.classList.add('d-none');
        }
    });

    // Message Formatter
    function formatMessage(text) {
        // Escape HTML first
        let safeText = text.replace(/&/g, "&amp;")
                           .replace(/</g, "&lt;")
                           .replace(/>/g, "&gt;")
                           .replace(/"/g, "&quot;")
                           .replace(/'/g, "&#039;");
        
        // Replace Codes
        for (const [code, html] of Object.entries(iconMap)) {
            // Use split/join for simple global replacement without regex special char issues
            safeText = safeText.split(code).join(html);
        }
        return safeText;
    }

    // Hydrate existing messages on load
    document.querySelectorAll('.message-bubble').forEach(bubble => {
        bubble.innerHTML = formatMessage(bubble.innerText);
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
        bubble.innerHTML = formatMessage(text); // Safe HTML with icons
        
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
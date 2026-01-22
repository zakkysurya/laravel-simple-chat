<!DOCTYPE html>
<html lang="id">
<head>
    <title>Selamat Datang di Super Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { width: 400px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="card login-card">
    <div class="card-header bg-primary text-white text-center py-3">
        <h4 class="mb-0">Pilih User untuk Chat</h4>
    </div>
    <div class="card-body p-4">
        <div class="list-group">
            @foreach($users as $user)
                <a href="/chat/{{ $user->id }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h6 class="mb-0">{{ $user->name }}</h6>
                        <small class="text-muted">{{ $user->email }}</small>
                    </div>
                    <span class="badge bg-primary rounded-pill">Login</span>
                </a>
            @endforeach
        </div>
        
        @if($users->isEmpty())
            <div class="alert alert-warning text-center">
                Belum ada user. Jalankan <br><code>php artisan db:seed</code>
            </div>
        @endif
    </div>
    <div class="card-footer text-center text-muted">
        Laravel 12 + Reverb
    </div>
</div>

</body>
</html>
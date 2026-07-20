<x-guest-layout title="Teruskan Log Masuk — Diwan">
    <div class="card" style="text-align:center;">
        <h2>Teruskan ke Diwan</h2>
        <p class="muted" style="text-align:center;margin-top:0;">
            Anda akan log masuk sebagai <strong>{{ $name }}</strong>
        </p>

        <form id="magic-form" method="POST" action="{{ route('magic-login.consume', ['token' => $token]) }}">
            @csrf
            <button type="submit" class="btn">Teruskan</button>
        </form>

        <p class="muted">
            Demi keselamatan, pautan ini hanya digunakan apabila anda menekan
            <strong>Teruskan</strong>. Pautan sekali guna dan tamat tempoh secara automatik.
        </p>

        <noscript>
            <p class="muted">Sila tekan butang di atas untuk meneruskan.</p>
        </noscript>

        <script>
            // Auto-hantar untuk pelayar manusia; bot pratonton pautan (WhatsApp/
            // Telegram) tidak menjalankan JS → token tidak terbakar oleh pratonton.
            window.addEventListener('DOMContentLoaded', function () {
                setTimeout(function () {
                    var f = document.getElementById('magic-form');
                    if (f) { f.submit(); }
                }, 300);
            });
        </script>
    </div>
</x-guest-layout>

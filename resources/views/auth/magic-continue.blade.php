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
                var submitting = false;
                var f = document.getElementById('magic-form');
                var button = f ? f.querySelector('button[type="submit"]') : null;
                var submitOnce = function () {
                    if (!f || submitting) { return; }
                    submitting = true;
                    if (button) { button.disabled = true; }
                    f.submit();
                };

                if (!f) { return; }
                f.addEventListener('submit', function (event) {
                    if (submitting) {
                        event.preventDefault();
                        return;
                    }
                    submitting = true;
                    if (button) { button.disabled = true; }
                });
                setTimeout(function () {
                    submitOnce();
                }, 300);
            });
        </script>
    </div>
</x-guest-layout>

{{-- Toast sukses hilang sendiri; pesan error tetap tampil (role="alert"). --}}
@if (session('toast'))
    <div class="toast" role="status">{{ session('toast') }}</div>
@endif
@if (session('error'))
    <div class="toast toast--sticky" role="alert">{{ session('error') }}</div>
@endif

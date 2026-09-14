<x-mail::message>
# Pembayaran Gagal / Kedaluwarsa

Halo, kami ingin menginformasikan bahwa pembayaran untuk pesanan Anda berikut ini telah gagal atau waktu pembayaran telah habis (kedaluwarsa).

@include('mail.transaction-reference', ['reference' => $order->reference])

**Ringkasan Transaksi:**
- Total Pembayaran: **Rp{{ number_format($order->total, 0, ',', '.') }}**

Jika Anda masih ingin melakukan pembelian, silakan buat pesanan baru.

<x-mail::button :url="route('orders.detail', ['reference' => $order->reference, ...$order->guestRouteParameters()])" color="primary">
Cek Detail Pesanan
</x-mail::button>

Terima kasih,<br>
Tim {{ config('app.name') }}
</x-mail::message>

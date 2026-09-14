<x-mail::message>
# Pembayaran Berhasil Diterima!

Halo, kami telah menerima pembayaran Anda untuk pesanan berikut. Pesanan Anda akan segera diproses.

@include('mail.transaction-reference', ['reference' => $order->reference])

**Ringkasan Transaksi:**
- Total Pembayaran: **Rp{{ number_format($order->total, 0, ',', '.') }}**

Untuk melihat status terbaru dan detail pesanan, silakan klik tombol di bawah ini:

<x-mail::button :url="route('orders.detail', ['reference' => $order->reference, ...$order->guestRouteParameters()])" color="primary">
Cek Detail Pesanan
</x-mail::button>

Terima kasih,<br>
Tim {{ config('app.name') }}
</x-mail::message>

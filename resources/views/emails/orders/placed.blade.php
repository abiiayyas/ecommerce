<x-mail::message>
# Pesanan Anda Berhasil Dibuat!

Halo, terima kasih telah berbelanja di {{ config('app.name') }}. Kami telah menerima pesanan Anda.

@include('mail.transaction-reference', ['reference' => $order->reference])

### Rincian Pembelanjaan:

@foreach($order->orderShops as $orderShop)
<x-mail::panel>
**{{ $orderShop->shop->name ?? 'Toko' }}**

@foreach($orderShop->items as $item)
- **{{ $item->product_data['name'] ?? 'Produk' }}**
  <br> {{ $item->quantity }} x Rp{{ number_format($item->price, 0, ',', '.') }} = **Rp{{ number_format($item->total, 0, ',', '.') }}**
@endforeach
</x-mail::panel>
@endforeach

**Ringkasan Pembayaran:**
- Total Harga Barang: Rp{{ number_format($order->total_checkout, 0, ',', '.') }}
- Total Ongkos Kirim: Rp{{ number_format($order->total_shipping, 0, ',', '.') }}
@if($order->insurance_fee > 0)
- Asuransi Pengiriman: Rp{{ number_format($order->insurance_fee, 0, ',', '.') }}
@endif
@if($order->application_fee > 0)
- Biaya Jasa Aplikasi: Rp{{ number_format($order->application_fee, 0, ',', '.') }}
@endif
@if($order->payment_fee > 0)
- Biaya Layanan Pembayaran: Rp{{ number_format($order->payment_fee, 0, ',', '.') }}
@endif
- **Total Belanja: Rp{{ number_format($order->total, 0, ',', '.') }}**

Untuk melihat status terbaru dan detail pesanan, silakan klik tombol di bawah ini:

<x-mail::button :url="route('orders.detail', ['reference' => $order->reference, ...$order->guestRouteParameters()])" color="primary">
Cek Detail Pesanan
</x-mail::button>

Terima kasih,<br>
Tim {{ config('app.name') }}
</x-mail::message>

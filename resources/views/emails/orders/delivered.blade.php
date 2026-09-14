<x-mail::message>
# Pesanan Anda Telah Sampai!

Halo, pesanan Anda dari toko **{{ $orderShop->shop->name ?? 'Toko' }}** telah berhasil dikirim dan sampai ke tujuan.

@include('mail.transaction-reference', ['reference' => $orderShop->order->reference])

### Rincian Produk:
<x-mail::panel>
@foreach($orderShop->items as $item)
- **{{ $item->product_data['name'] ?? 'Produk' }}**
  <br> {{ $item->quantity }} x Rp{{ number_format($item->price, 0, ',', '.') }} = **Rp{{ number_format($item->total, 0, ',', '.') }}**
@endforeach
</x-mail::panel>

Terima kasih telah berbelanja menggunakan layanan kami! Jangan lupa berikan ulasan untuk produk dan toko ya!

<x-mail::button :url="route('orders.detail', ['reference' => $orderShop->order->reference, ...$orderShop->order->guestRouteParameters()])" color="primary">
Cek Detail Pesanan
</x-mail::button>

Terima kasih,<br>
Tim {{ config('app.name') }}
</x-mail::message>

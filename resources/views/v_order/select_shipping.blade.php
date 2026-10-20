@extends('v_layouts.app')
@section('content')
<!-- template -->


<div class="col-md-12" hidden>
    <div class="order-summary clearfix">
        <div class="section-title">
            <p>PENGIRIMAN</p>
            <h3 class="title">Produk</h3>
        </div>
        @if($order && $order->orderItems->count() > 0)
        <table class="shopping-cart-table table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th></th>
                    <th class="text-center">Harga</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-center">Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                $totalHarga = 0;
                $totalBerat = 0;
                @endphp
                @foreach($order->orderItems as $item)
                @php
                $totalHarga += $item->harga * $item->quantity;
                $totalBerat += $item->produk->berat * $item->quantity;
                @endphp
                <tr>
                    <td class="thumb"><img src="{{ asset('storage/img-produk/thumb_sm_' . $item->produk->foto) }}" alt=""></td>
                    <td class="details">
                        <a>{{ $item->produk->nama_produk }}</a>
                        <ul>
                            <li><span>Berat: {{ $item->produk->berat }} Gram</span></li>
                        </ul>
                        <ul>
                            <li><span>Stok: {{ $item->produk->stok }} Gram</span></li>
                        </ul>
                    </td>
                    <td class="price text-center"><strong>Rp. {{ number_format($item->harga, 0, ',', '.') }}</strong></td>
                    <td class="qty text-center">
                        <a> {{ $item->quantity }} </a>
                    </td>
                    <td class="total text-center"><strong class="primary-color">Rp. {{ number_format($item->harga * $item->quantity, 0, ',', '.') }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>Keranjang belanja kosong.</p>
        @endif
    </div>
</div>


<div class="col-md-12">
    <div class="order-summary clearfix">
        <div class="section-title">
            <p>PENGIRIMAN</p>
            <h3 class="title">Pilih Pengiriman</h3>
        </div>
        <form id="shippingForm">
            <!-- Kota Asal -->
            <input type="hidden" id="city_origin" name="city_origin" value="">
            <input type="hidden" id="city_origin_name" name="city_origin_name" value="">
            <!-- /Kota Asal -->


            <div class="form-group">
                <label for="province">Provinsi Tujuan:</label>
                <select name="province" id="province" class="input">
                    <option value="">Pilih Provinsi Tujuan</option>
                    <!-- Data Provinsi Tujuan akan dimuat dengan JavaScript -->
                </select>
            </div>
            <div class="form-group">
                <label for="city">Kota Tujuan:</label>
                <select name="city" id="city" class="input">
                    <option value="">Pilih Kota Tujuan</option>
                    <!-- Data Kota Tujuan akan dimuat dengan JavaScript -->
                </select>
            </div>
            <input type="hidden" name="weight" id="weight" value="{{ $totalBerat }}">
            <input type="hidden" name="province_name" id="province_name">
            <input type="hidden" name="city_name" id="city_name">
            <div class="form-group">
                <label for="courier">Kurir:</label>
                <select name="courier" id="courier" class="input">
                    <option value="">Pilih Kurir</option>
                    <option value="jne">JNE</option>
                    <option value="tiki">TIKI</option>
                    <option value="pos">POS Indonesia</option>
                </select>
            </div>
            <div class="form-group">
                <label for="">Alamat</label>
                <textarea class="input" name="alamat" id="alamat">{{ Auth::user()->alamat }}</textarea>
            </div>
            <div class="form-group">
                <label for="">Kode Pos</label>
                <input type="text" class="input" name="kode_pos" id="kode_pos" value="{{ Auth::user()->pos }}">
            </div>
            <button type="submit" class="primary-btn">Cek Ongkir</button>
        </form>
        <br>
        <div id="result">
            <table class="shopping-cart-table table">
                <thead>
                    <tr>
                        <th>Layanan</th>
                        <th>Biaya</th>
                        <th>Estimasi Pengiriman</th>
                        <th>Total Berat</th>
                        <th>Total Harga</th>
                        <th>Bayar</th>
                    </tr>
                </thead>
                <tbody id="shippingResults">
                    <!-- Hasil dari pencarian akan dimuat di sini -->
                </tbody>
            </table>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const provinceSelect = document.getElementById('province');
    const citySelect = document.getElementById('city');
    const form = document.getElementById('shippingForm');
    const resultBody = document.getElementById('shippingResults');
    const resultDiv = document.getElementById('result');

    const originCityCode = 115; // Pastikan ID kota asal sesuai database Komerce
    const originCityName = 'Depok';

    document.getElementById('city_origin').value = originCityCode;
    document.getElementById('city_origin_name').value = originCityName;

    function showMessage(message) {
        resultDiv.querySelector('.shipping-message')?.remove();

        const p = document.createElement('p');
        p.className = 'shipping-message';
        p.textContent = message;
        resultDiv.prepend(p);
    }

    async function fetchJSON(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                ...(options.headers || {})
            }
        });

        const text = await response.text();
        let data;

        try {
            data = JSON.parse(text);
        } catch {
            console.error('Respons bukan JSON:', text);
            throw new Error(
                `Server mengembalikan respons tidak valid (HTTP ${response.status}).`
            );
        }

        if (!response.ok) {
            console.error('API error:', data);
            throw new Error(
                data.message || data.detail?.message ||
                `Request gagal (HTTP ${response.status}).`
            );
        }

        return data;
    }

    // 1. Memuat provinsi
    fetchJSON('/provinces')
        .then(data => {
            console.log('Respons provinsi:', data);

            const provinces = data.data || [];

            if (!provinces.length) {
                throw new Error('Data provinsi kosong atau format respons berbeda.');
            }

            provinces.forEach(province => {
                const option = document.createElement('option');
                option.value = province.id;
                option.textContent = province.name;
                provinceSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Gagal memuat provinsi:', error);
            showMessage('Gagal memuat provinsi: ' + error.message);
        });

    // 2. Memuat kota berdasarkan provinsi
    provinceSelect.addEventListener('change', async function () {
        const provinceId = this.value;

        citySelect.innerHTML =
            '<option value="">Pilih Kota Tujuan</option>';
        citySelect.disabled = true;

        document.getElementById('province_name').value =
            this.selectedOptions[0]?.textContent || '';

        document.getElementById('city_name').value = '';
        resultBody.innerHTML = '';

        if (!provinceId) {
            citySelect.disabled = false;
            return;
        }

        try {
            // ID provinsi dimasukkan ke URL endpoint Komerce
            const data = await fetchJSON(
                `/cities?province_id=${encodeURIComponent(provinceId)}`
            );

            console.log('Respons kota:', data);

            const cities = data.data || [];

            if (!cities.length) {
                throw new Error('Daftar kota kosong atau format respons berbeda.');
            }

            cities.forEach(city => {
                const option = document.createElement('option');
                option.value = city.id;
                option.textContent = city.name;
                citySelect.appendChild(option);
            });

            citySelect.disabled = false;
        } catch (error) {
            console.error('Gagal memuat kota:', error);
            showMessage('Gagal memuat kota: ' + error.message);
        }
    });

    citySelect.addEventListener('change', function () {
        document.getElementById('city_name').value =
            this.selectedOptions[0]?.textContent || '';
    });

    // 3. Menghitung ongkos kirim
    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const destination = citySelect.value;
        const weight = Number(document.getElementById('weight').value);
        const courier = document.getElementById('courier').value;
        const alamat = document.getElementById('alamat').value.trim();
        const kodePos = document.getElementById('kode_pos').value.trim();

        if (!destination || !weight || weight < 1 || !courier) {
            alert('Pilih provinsi, kota, kurir, dan pastikan berat produk valid.');
            return;
        }

        if (!alamat || !kodePos) {
            alert('Harap lengkapi alamat dan kode pos.');
            return;
        }

        resultBody.innerHTML = '';
        showMessage('Sedang menghitung ongkos kirim...');

        try {
            // Pastikan route Laravel menggunakan POST /ongkir
            const data = await fetchJSON('/ongkir', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content
                },
                body: JSON.stringify({
                    origin: Number(originCityCode),
                    destination: Number(destination),
                    weight: weight,
                    courier: courier
                })
            });

            console.log('Respons ongkir:', data);

            const options = data.data || [];

            if (!options.length) {
                throw new Error(
                    data.meta?.message || 'Tidak ada layanan ongkir yang ditemukan.'
                );
            }

            resultDiv.querySelector('.shipping-message')?.remove();

            options.forEach(item => {
                const row = document.createElement('tr');

                const service = [
                    item.name || courier.toUpperCase(),
                    item.service || ''
                ].filter(Boolean).join(' - ');

                // Format API baru umumnya memakai cost numerik.
                // Dukungan struktur lama disediakan untuk kompatibilitas.
                const costValue = typeof item.cost === 'number'
                    ? item.cost
                    : item.cost?.[0]?.value;

                const etdValue = typeof item.etd === 'string'
                    ? item.etd
                    : item.cost?.[0]?.etd || '-';

                if (costValue == null) {
                    console.error('Format biaya tidak dikenali:', item);
                    return;
                }

                const cells = [
                    service,
                    'Rp ' + Number(costValue).toLocaleString('id-ID'),
                    etdValue + (etdValue === '-' ? '' : ' hari'),
                    weight + ' gram',
                    'Rp {{ number_format($totalHarga, 0, ",", ".") }}'
                ];

                cells.forEach(value => {
                    const td = document.createElement('td');
                    td.textContent = value;
                    row.appendChild(td);
                });

                const actionCell = document.createElement('td');
                const selectForm = document.createElement('form');

                selectForm.method = 'POST';
                selectForm.action =
                    @json(route('order.update-ongkir'));

                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = document.querySelector(
                    'meta[name="csrf-token"]'
                ).content;
                selectForm.appendChild(csrf);

                const fields = {
                    province: provinceSelect.value,
                    city: citySelect.value,
                    province_name: document.getElementById('province_name').value,
                    city_name: document.getElementById('city_name').value,
                    kurir: courier,
                    alamat: alamat,
                    pos: kodePos,
                    layanan_ongkir: item.service || service,
                    biaya_ongkir: costValue,
                    estimasi_ongkir: etdValue,
                    total_berat: weight,
                    city_origin: originCityCode,
                    city_origin_name: originCityName
                };

                Object.entries(fields).forEach(([name, value]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value ?? '';
                    selectForm.appendChild(input);
                });

                const button = document.createElement('button');
                button.type = 'submit';
                button.className = 'primary-btn';
                button.textContent = 'Pilih Pengiriman';

                selectForm.appendChild(button);
                actionCell.appendChild(selectForm);
                row.appendChild(actionCell);
                resultBody.appendChild(row);
            });

            if (!resultBody.children.length) {
                showMessage('Format biaya dari API belum dikenali. Periksa Console.');
            }
        } catch (error) {
            console.error('Gagal menghitung ongkir:', error);
            showMessage('Gagal menghitung ongkir: ' + error.message);
        }
    });
});
</script>


<!-- end template-->
@endsection


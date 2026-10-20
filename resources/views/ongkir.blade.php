<!DOCTYPE html>
<html>


<head>
    <title>Cek Ongkir</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>


<body>
    <form id="ongkirForm">
        <select name="province" id="province">
            <option value="">Pilih Provinsi</option>
        </select>
        <select name="city" id="city">
            <option value="">Pilih Kota</option>
        </select>
        <input type="number" name="weight" id="weight" placeholder="Berat (gram)">
        <select name="courier" id="courier">
            <option value="">Pilih Kurir</option>
            <option value="jne">JNE</option>
            <option value="tiki">TIKI</option>
            <option value="pos">POS Indonesia</option>
        </select>
        <button type="submit">Cek Ongkir</button>
    </form>


    <div id="result"></div>


    <script>
       
        
    document.addEventListener('DOMContentLoaded', function () {
    const provinceSelect = document.getElementById('province');
    const citySelect = document.getElementById('city');
    const form = document.getElementById('ongkirForm');
    const resultDiv = document.getElementById('result');

    function resetSelect(select, text) {
        select.innerHTML = '';
        const option = document.createElement('option');
        option.value = '';
        option.textContent = text;
        select.appendChild(option);
    }

    async function fetchJSON(url, options = {}) {
        const response = await fetch(url, options);
        const data = await response.json();

        if (!response.ok) {
            throw new Error(
                data.message || `HTTP error ${response.status}`
            );
        }

        return data;
    }

    // Ambil daftar provinsi
    fetchJSON('/provinces')
        .then(data => {
            console.log('Data provinsi:', data);

            (data.data || []).forEach(province => {
                const option = document.createElement('option');
                option.value = province.id;
                option.textContent = province.name;
                provinceSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Gagal mengambil provinsi:', error);
            resultDiv.textContent = error.message;
        });

    // Ambil daftar kota berdasarkan provinsi
    provinceSelect.addEventListener('change', async function () {
        resetSelect(citySelect, 'Memuat kota...');
        citySelect.disabled = true;

        if (!this.value) {
            resetSelect(citySelect, 'Pilih Kota');
            return;
        }

        try {
            const data = await fetchJSON(
                `/cities?province_id=${encodeURIComponent(this.value)}`
            );

            console.log('Data kota:', data);
            resetSelect(citySelect, 'Pilih Kota');

            (data.data || []).forEach(city => {
                const option = document.createElement('option');
                option.value = city.id;
                option.textContent = city.name;
                citySelect.appendChild(option);
            });

            citySelect.disabled = false;
        } catch (error) {
            console.error('Gagal mengambil kota:', error);
            resetSelect(citySelect, 'Gagal memuat kota');
        }
    });

    // Hitung ongkos kirim
    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const origin = 63; // Ganti dengan ID kota asal toko yang benar
        const destination = citySelect.value;
        const weight = Number(document.getElementById('weight').value);
        const courier = document.getElementById('courier').value;

        if (!destination || !weight || weight < 1 || !courier) {
            resultDiv.textContent =
                'Pilih kota tujuan, masukkan berat dalam gram, dan pilih kurir.';
            return;
        }

        resultDiv.textContent = 'Menghitung ongkos kirim...';

        try {
            const data = await fetchJSON('/ongkir', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute('content')
                },
                body: JSON.stringify({
                    origin: Number(origin),
                    destination: Number(destination),
                    weight: weight,
                    courier: courier
                })
            });

            console.log('Respons ongkir:', data);
            resultDiv.replaceChildren();

            // Tampilkan respons sementara untuk memastikan
            // struktur data API yang diterima.
            const pre = document.createElement('pre');
            pre.textContent = JSON.stringify(data, null, 2);
            resultDiv.appendChild(pre);
        } catch (error) {
            console.error('Gagal menghitung ongkir:', error);
            resultDiv.textContent = error.message;
        }
    });
});

    </script>
</body>


</html>

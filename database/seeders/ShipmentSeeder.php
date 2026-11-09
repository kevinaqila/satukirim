<?php

namespace Database\Seeders;

use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ShipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $merchant = User::where('email', 'merchant@satukirim.id')->first();
        if (! $merchant) {
            return;
        }

        $storeSurabaya = Store::where('user_id', $merchant->id)->where('name', 'Gudang Utama Surabaya')->first();
        $storeJakarta = Store::where('user_id', $merchant->id)->where('name', 'Cabang Jakarta Selatan')->first();

        if (! $storeSurabaya || ! $storeJakarta) {
            return;
        }

        $shipmentsData = [
            [
                'day_offset' => 0, 'status' => 'pending_payment', 'store' => $storeJakarta,
                'name' => 'Rian Hidayat', 'phone' => '087811223344', 'dest' => 'Karet Tengsin, Tanah Abang, Jakarta Pusat', 'postal' => '10220',
                'area_id' => 'IDNP6IDNC147IDND1270IDZ10220', 'item' => 'Espresso Coffee Blend 2kg', 'category' => 'Makanan & Minuman', 'val' => 320000,
                'weight' => 2000, 'courier' => 'jne', 'srv_code' => 'yes', 'srv_name' => 'JNE YES', 'fee' => 34000, 'est' => '1 hari',
                'waybill' => null, 'paid' => false,
            ],
            [
                'day_offset' => 0, 'status' => 'pending_payment', 'store' => $storeSurabaya,
                'name' => 'Dwi Handayani', 'phone' => '081255443322', 'dest' => 'Gubeng, Gubeng, Surabaya', 'postal' => '60281',
                'area_id' => 'IDNP7IDNC266IDND2777IDZ60281', 'item' => 'Syrup Vanilla 750ml & Caramel 750ml', 'category' => 'Makanan & Minuman', 'val' => 195000,
                'weight' => 1800, 'courier' => 'sicepat', 'srv_code' => 'best', 'srv_name' => 'SiCepat BEST', 'fee' => 20000, 'est' => '1 hari',
                'waybill' => null, 'paid' => false,
            ],
            [
                'day_offset' => 0, 'status' => 'waiting_pickup', 'store' => $storeSurabaya,
                'name' => 'Dewi Lestari', 'phone' => '082199887766', 'dest' => 'Klojen, Klojen, Kota Malang', 'postal' => '65111',
                'area_id' => 'IDNP7IDNC267IDND2780IDZ65111', 'item' => 'Drip Bag Coffee Box (Isi 10 sachet)', 'category' => 'Makanan & Minuman', 'val' => 75000,
                'weight' => 500, 'courier' => 'jnt', 'srv_code' => 'ez', 'srv_name' => 'J&T EZ', 'fee' => 14000, 'est' => '1 hari',
                'waybill' => 'JT9982736182', 'paid' => true,
            ],
            [
                'day_offset' => 0, 'status' => 'waiting_pickup', 'store' => $storeJakarta,
                'name' => 'Bambang Pamungkas', 'phone' => '081988776655', 'dest' => 'Kebon Jeruk, Kebon Jeruk, Jakarta Barat', 'postal' => '11530',
                'area_id' => 'IDNP6IDNC149IDND1295IDZ11530', 'item' => 'Tumbler Stainless Kopi 500ml Hitam', 'category' => 'Peralatan Rumah Tangga', 'val' => 145000,
                'weight' => 600, 'courier' => 'sicepat', 'srv_code' => 'sicepat_reg', 'srv_name' => 'SiCepat REG', 'fee' => 11000, 'est' => '1 hari',
                'waybill' => '004928173612', 'paid' => true,
            ],
            [
                'day_offset' => 0, 'status' => 'in_transit', 'store' => $storeSurabaya,
                'name' => 'Ahmad Fauzi', 'phone' => '081377889900', 'dest' => 'Dago, Coblong, Kota Bandung', 'postal' => '40135',
                'area_id' => 'IDNP9IDNC300IDND3001IDZ40135', 'item' => 'Alat Manual Brew V60 & Paper Filter', 'category' => 'Peralatan Rumah Tangga', 'val' => 250000,
                'weight' => 1500, 'courier' => 'sicepat', 'srv_code' => 'sicepat_reg', 'srv_name' => 'SiCepat REG', 'fee' => 22000, 'est' => '2-3 hari',
                'waybill' => '003928172938', 'paid' => true,
            ],
            [
                'day_offset' => 0, 'status' => 'in_transit', 'store' => $storeJakarta,
                'name' => 'Indah Permata', 'phone' => '085233445566', 'dest' => 'Menteng, Menteng, Jakarta Pusat', 'postal' => '10310',
                'area_id' => 'IDNP6IDNC147IDND1269IDZ10310', 'item' => 'Single Origin Toraja Sapan 500g', 'category' => 'Makanan & Minuman', 'val' => 125000,
                'weight' => 500, 'courier' => 'jnt', 'srv_code' => 'ez', 'srv_name' => 'J&T EZ', 'fee' => 10000, 'est' => '1 hari',
                'waybill' => 'JT8837192837', 'paid' => true,
            ],
            [
                'day_offset' => 0, 'status' => 'in_transit', 'store' => $storeSurabaya,
                'name' => 'Fajar Ramadhan', 'phone' => '081344556677', 'dest' => 'Banyumanik, Banyumanik, Kota Semarang', 'postal' => '50264',
                'area_id' => 'IDNP8IDNC280IDND2850IDZ50264', 'item' => 'Kopi Robusta Dampit Malang 1kg', 'category' => 'Makanan & Minuman', 'val' => 98000,
                'weight' => 1000, 'courier' => 'jne', 'srv_code' => 'reg', 'srv_name' => 'JNE Reguler', 'fee' => 16000, 'est' => '1-2 hari',
                'waybill' => 'JNE7738192831', 'paid' => true,
            ],
            [
                'day_offset' => 1, 'status' => 'waiting_pickup', 'store' => $storeSurabaya,
                'name' => 'Maya Safitri', 'phone' => '081266778899', 'dest' => 'Kebayoran Baru, Jakarta Selatan', 'postal' => '12110',
                'area_id' => 'IDNP6IDNC148IDND1280IDZ12110', 'item' => 'Paket Cold Brew Concentrate 1 Liter', 'category' => 'Makanan & Minuman', 'val' => 110000,
                'weight' => 1200, 'courier' => 'jnt', 'srv_code' => 'ez', 'srv_name' => 'J&T EZ', 'fee' => 24000, 'est' => '1-2 hari',
                'waybill' => 'JT7739182736', 'paid' => true,
            ],
            [
                'day_offset' => 1, 'status' => 'in_transit', 'store' => $storeJakarta,
                'name' => 'Rizky Pratama', 'phone' => '087711223344', 'dest' => 'Lowokwaru, Lowokwaru, Kota Malang', 'postal' => '65141',
                'area_id' => 'IDNP7IDNC267IDND2782IDZ65141', 'item' => 'Hand Grinder Kopi Stainless Steel', 'category' => 'Peralatan Rumah Tangga', 'val' => 215000,
                'weight' => 700, 'courier' => 'anteraja', 'srv_code' => 'reg', 'srv_name' => 'AnterAja Regular', 'fee' => 21000, 'est' => '2 hari',
                'waybill' => '100092837192', 'paid' => true,
            ],
            [
                'day_offset' => 1, 'status' => 'in_transit', 'store' => $storeSurabaya,
                'name' => 'Putri Handayani', 'phone' => '085311223344', 'dest' => 'Medan Baru, Kota Medan, Sumatera Utara', 'postal' => '20153',
                'area_id' => 'IDNP1IDNC10IDND101IDZ20153', 'item' => 'Coffee Bean Mandheling 1kg', 'category' => 'Makanan & Minuman', 'val' => 210000,
                'weight' => 1000, 'courier' => 'jne', 'srv_code' => 'reg', 'srv_name' => 'JNE Reguler', 'fee' => 42000, 'est' => '3-4 hari',
                'waybill' => 'JNE9938271625', 'paid' => true,
            ],
            [
                'day_offset' => 1, 'status' => 'in_transit', 'store' => $storeSurabaya,
                'name' => 'Hendra Wijaya', 'phone' => '081822334455', 'dest' => 'Denpasar Selatan, Kota Denpasar, Bali', 'postal' => '80222',
                'area_id' => 'IDNP17IDNC400IDND4001IDZ80222', 'item' => 'Kintamani Natural Process 500g', 'category' => 'Makanan & Minuman', 'val' => 135000,
                'weight' => 500, 'courier' => 'jnt', 'srv_code' => 'ez', 'srv_name' => 'J&T EZ', 'fee' => 18000, 'est' => '2 hari',
                'waybill' => 'JT6628192837', 'paid' => true,
            ],
            [
                'day_offset' => 1, 'status' => 'delivered', 'store' => $storeJakarta,
                'name' => 'Fitri Amalia', 'phone' => '081288990011', 'dest' => 'Cilandak, Cilandak, Jakarta Selatan', 'postal' => '12430',
                'area_id' => 'IDNP6IDNC148IDND1282IDZ12430', 'item' => 'Oat Milk Barista Edition 1 Liter (x3)', 'category' => 'Makanan & Minuman', 'val' => 140000,
                'weight' => 3200, 'courier' => 'sicepat', 'srv_code' => 'best', 'srv_name' => 'SiCepat BEST', 'fee' => 28000, 'est' => '1 hari',
                'waybill' => '009928172911', 'paid' => true,
            ],
            [
                'day_offset' => 1, 'status' => 'delivered', 'store' => $storeSurabaya,
                'name' => 'Agus Setiawan', 'phone' => '082233445566', 'dest' => 'Rungkut, Rungkut, Surabaya', 'postal' => '60293',
                'area_id' => 'IDNP7IDNC266IDND2779IDZ60293', 'item' => 'Filter Paper Kalita Wave 100 pcs', 'category' => 'Peralatan Rumah Tangga', 'val' => 85000,
                'weight' => 300, 'courier' => 'jnt', 'srv_code' => 'ez', 'srv_name' => 'J&T EZ', 'fee' => 9000, 'est' => '1 hari',
                'waybill' => 'JT5528172635', 'paid' => true,
            ],
            [
                'day_offset' => 1, 'status' => 'delivered', 'store' => $storeJakarta,
                'name' => 'Wahyu Nugroho', 'phone' => '087822334455', 'dest' => 'Serpong, Serpong, Tangerang Selatan', 'postal' => '15310',
                'area_id' => 'IDNP10IDNC500IDND5001IDZ15310', 'item' => 'Vietnam Drip Filter Kopi Stainless', 'category' => 'Peralatan Rumah Tangga', 'val' => 65000,
                'weight' => 400, 'courier' => 'sicepat', 'srv_code' => 'sicepat_reg', 'srv_name' => 'SiCepat REG', 'fee' => 11000, 'est' => '1-2 hari',
                'waybill' => '003819283719', 'paid' => true,
            ],
            [
                'day_offset' => 2, 'status' => 'delivered', 'store' => $storeSurabaya,
                'name' => 'Siti Rahmawati', 'phone' => '085712345678', 'dest' => 'Sosromenduran, Gedong Tengen, Kota Yogyakarta', 'postal' => '55271',
                'area_id' => 'IDNP4IDNC100IDND1001IDZ55271', 'item' => 'Biji Kopi Arabika Specialty 1kg', 'category' => 'Makanan & Minuman', 'val' => 185000,
                'weight' => 1000, 'courier' => 'jne', 'srv_code' => 'reg', 'srv_name' => 'JNE Reguler', 'fee' => 18000, 'est' => '1-2 hari',
                'waybill' => 'JNE8829103948', 'paid' => true,
            ],
            [
                'day_offset' => 2, 'status' => 'delivered', 'store' => $storeJakarta,
                'name' => 'Dian Anggraini', 'phone' => '081299887700', 'dest' => 'Bekasi Barat, Kota Bekasi, Jawa Barat', 'postal' => '17145',
                'area_id' => 'IDNP9IDNC310IDND3101IDZ17145', 'item' => 'French Press Kaca Borosilikat 600ml', 'category' => 'Peralatan Rumah Tangga', 'val' => 165000,
                'weight' => 900, 'courier' => 'jnt', 'srv_code' => 'ez', 'srv_name' => 'J&T EZ', 'fee' => 12000, 'est' => '1 hari',
                'waybill' => 'JT4428172635', 'paid' => true,
            ],
            [
                'day_offset' => 2, 'status' => 'delivered', 'store' => $storeSurabaya,
                'name' => 'Eko Prasetyo', 'phone' => '081355667788', 'dest' => 'Banjarsari, Kota Surakarta / Solo', 'postal' => '57138',
                'area_id' => 'IDNP8IDNC285IDND2860IDZ57138', 'item' => 'House Blend Kopi Susu 1kg', 'category' => 'Makanan & Minuman', 'val' => 120000,
                'weight' => 1000, 'courier' => 'sicepat', 'srv_code' => 'sicepat_reg', 'srv_name' => 'SiCepat REG', 'fee' => 15000, 'est' => '1-2 hari',
                'waybill' => '002918273612', 'paid' => true,
            ],
            [
                'day_offset' => 2, 'status' => 'delivered', 'store' => $storeJakarta,
                'name' => 'Tari Puspita', 'phone' => '085611223344', 'dest' => 'Bogor Tengah, Kota Bogor, Jawa Barat', 'postal' => '16121',
                'area_id' => 'IDNP9IDNC320IDND3201IDZ16121', 'item' => 'Gula Aren Cair Organik 1 Liter', 'category' => 'Makanan & Minuman', 'val' => 55000,
                'weight' => 1300, 'courier' => 'jnt', 'srv_code' => 'ez', 'srv_name' => 'J&T EZ', 'fee' => 13000, 'est' => '1 hari',
                'waybill' => 'JT3328172611', 'paid' => true,
            ],
            [
                'day_offset' => 2, 'status' => 'delivered', 'store' => $storeSurabaya,
                'name' => 'Bayu Saputra', 'phone' => '081277889900', 'dest' => 'Ilir Timur I, Kota Palembang', 'postal' => '30126',
                'area_id' => 'IDNP3IDNC40IDND401IDZ30126', 'item' => 'Gayo Wine Coffee Bean 500g', 'category' => 'Makanan & Minuman', 'val' => 175000,
                'weight' => 500, 'courier' => 'jne', 'srv_code' => 'reg', 'srv_name' => 'JNE Reguler', 'fee' => 32000, 'est' => '2-3 hari',
                'waybill' => 'JNE6637182910', 'paid' => true,
            ],
            [
                'day_offset' => 2, 'status' => 'in_transit', 'store' => $storeSurabaya,
                'name' => 'Nadia Rahmadani', 'phone' => '087788990011', 'dest' => 'Batam Kota, Kota Batam, Kepulauan Riau', 'postal' => '29461',
                'area_id' => 'IDNP5IDNC80IDND801IDZ29461', 'item' => 'Timbangan Kopi Digital Timer 3kg', 'category' => 'Peralatan Rumah Tangga', 'val' => 189000,
                'weight' => 600, 'courier' => 'sicepat', 'srv_code' => 'sicepat_reg', 'srv_name' => 'SiCepat REG', 'fee' => 38000, 'est' => '3-4 hari',
                'waybill' => '001928371928', 'paid' => true,
            ],
            [
                'day_offset' => 2, 'status' => 'cancelled', 'store' => $storeJakarta,
                'name' => 'Desta Mahendra', 'phone' => '081911223344', 'dest' => 'Tanah Abang, Jakarta Pusat', 'postal' => '10250',
                'area_id' => 'IDNP6IDNC147IDND1271IDZ10250', 'item' => 'Moka Pot 6 Cup Espresso Maker', 'category' => 'Peralatan Rumah Tangga', 'val' => 195000,
                'weight' => 800, 'courier' => 'jnt', 'srv_code' => 'ez', 'srv_name' => 'J&T EZ', 'fee' => 10000, 'est' => '1 hari',
                'waybill' => null, 'paid' => false,
            ],
            [
                'day_offset' => 3, 'status' => 'delivered', 'store' => $storeSurabaya,
                'name' => 'Budi Santoso Jr.', 'phone' => '081233449988', 'dest' => 'Sukolilo, Sukolilo, Surabaya', 'postal' => '60111',
                'area_id' => 'IDNP7IDNC266IDND2778IDZ60111', 'item' => 'Cup Plastik Kopi Sealer 14oz (500 pcs)', 'category' => 'Kemasan & Packaging', 'val' => 175000,
                'weight' => 3500, 'courier' => 'jnt', 'srv_code' => 'ez', 'srv_name' => 'J&T EZ', 'fee' => 18000, 'est' => '1 hari',
                'waybill' => 'JT2218273611', 'paid' => true,
            ],
            [
                'day_offset' => 3, 'status' => 'delivered', 'store' => $storeJakarta,
                'name' => 'Ratna Sari', 'phone' => '082155667788', 'dest' => 'Tebet, Tebet, Jakarta Selatan', 'postal' => '12810',
                'area_id' => 'IDNP6IDNC148IDND1285IDZ12810', 'item' => 'Kopi Arabika Java Preanger 500g', 'category' => 'Makanan & Minuman', 'val' => 115000,
                'weight' => 500, 'courier' => 'anteraja', 'srv_code' => 'reg', 'srv_name' => 'AnterAja Regular', 'fee' => 10000, 'est' => '1 hari',
                'waybill' => '100082736152', 'paid' => true,
            ],
            [
                'day_offset' => 3, 'status' => 'delivered', 'store' => $storeSurabaya,
                'name' => 'Gilang Dirga', 'phone' => '085722334455', 'dest' => 'Sukajadi, Kota Bandung, Jawa Barat', 'postal' => '40162',
                'area_id' => 'IDNP9IDNC300IDND3005IDZ40162', 'item' => 'Syrup Hazelnut 750ml Premium', 'category' => 'Makanan & Minuman', 'val' => 95000,
                'weight' => 1200, 'courier' => 'jne', 'srv_code' => 'reg', 'srv_name' => 'JNE Reguler', 'fee' => 20000, 'est' => '2 hari',
                'waybill' => 'JNE5538192817', 'paid' => true,
            ],
            [
                'day_offset' => 3, 'status' => 'delivered', 'store' => $storeSurabaya,
                'name' => 'Hanifah Zahra', 'phone' => '081399881122', 'dest' => 'Danurejan, Kota Yogyakarta', 'postal' => '55211',
                'area_id' => 'IDNP4IDNC100IDND1002IDZ55211', 'item' => 'Milk Steaming Pitcher 350ml Hitam', 'category' => 'Peralatan Rumah Tangga', 'val' => 89000,
                'weight' => 400, 'courier' => 'jnt', 'srv_code' => 'ez', 'srv_name' => 'J&T EZ', 'fee' => 14000, 'est' => '1 hari',
                'waybill' => 'JT1129182736', 'paid' => true,
            ],
            [
                'day_offset' => 3, 'status' => 'delivered', 'store' => $storeJakarta,
                'name' => 'Irwan Syahputra', 'phone' => '087855667788', 'dest' => 'Kelapa Gading, Jakarta Utara', 'postal' => '14240',
                'area_id' => 'IDNP6IDNC150IDND1300IDZ14240', 'item' => 'Kopi Luwak Liar Aceh 250g Box', 'category' => 'Makanan & Minuman', 'val' => 350000,
                'weight' => 400, 'courier' => 'jnt', 'srv_code' => 'super', 'srv_name' => 'J&T Super', 'fee' => 18000, 'est' => '1 hari',
                'waybill' => 'JT9918273615', 'paid' => true,
            ],
            [
                'day_offset' => 3, 'status' => 'delivered', 'store' => $storeSurabaya,
                'name' => 'Joko Widodo', 'phone' => '081211002233', 'dest' => 'Jebres, Kota Surakarta / Solo', 'postal' => '57126',
                'area_id' => 'IDNP8IDNC285IDND2861IDZ57126', 'item' => 'Kopi Robusta Temanggung 1kg', 'category' => 'Makanan & Minuman', 'val' => 90000,
                'weight' => 1000, 'courier' => 'sicepat', 'srv_code' => 'sicepat_reg', 'srv_name' => 'SiCepat REG', 'fee' => 14000, 'est' => '1-2 hari',
                'waybill' => '009819283716', 'paid' => true,
            ],
            [
                'day_offset' => 4, 'status' => 'delivered', 'store' => $storeJakarta,
                'name' => 'Kurnia Mega', 'phone' => '082266778899', 'dest' => 'Kemayoran, Jakarta Pusat', 'postal' => '10610',
                'area_id' => 'IDNP6IDNC147IDND1272IDZ10610', 'item' => 'Latte Art Pen Stainless & Thermometer', 'category' => 'Peralatan Rumah Tangga', 'val' => 60000,
                'weight' => 200, 'courier' => 'jnt', 'srv_code' => 'ez', 'srv_name' => 'J&T EZ', 'fee' => 10000, 'est' => '1 hari',
                'waybill' => 'JT8819283716', 'paid' => true,
            ],
            [
                'day_offset' => 4, 'status' => 'delivered', 'store' => $storeSurabaya,
                'name' => 'Lukman Hakim', 'phone' => '085233112244', 'dest' => 'Panakkukang, Kota Makassar, Sulsel', 'postal' => '90231',
                'area_id' => 'IDNP18IDNC600IDND6001IDZ90231', 'item' => 'Kopi Arabika Flores Bajawa 1kg', 'category' => 'Makanan & Minuman', 'val' => 195000,
                'weight' => 1000, 'courier' => 'jne', 'srv_code' => 'reg', 'srv_name' => 'JNE Reguler', 'fee' => 38000, 'est' => '2-3 hari',
                'waybill' => 'JNE4438192837', 'paid' => true,
            ],
            [
                'day_offset' => 4, 'status' => 'delivered', 'store' => $storeJakarta,
                'name' => 'Mega Utami', 'phone' => '081377665544', 'dest' => 'Cimahi Tengah, Kota Cimahi, Jawa Barat', 'postal' => '40522',
                'area_id' => 'IDNP9IDNC305IDND3051IDZ40522', 'item' => 'Kopi Decaf Arabika 500g', 'category' => 'Makanan & Minuman', 'val' => 145000,
                'weight' => 500, 'courier' => 'sicepat', 'srv_code' => 'sicepat_reg', 'srv_name' => 'SiCepat REG', 'fee' => 14000, 'est' => '1-2 hari',
                'waybill' => '008819283711', 'paid' => true,
            ],
            [
                'day_offset' => 4, 'status' => 'delivered', 'store' => $storeSurabaya,
                'name' => 'Nugraha Pratama', 'phone' => '087899001122', 'dest' => 'Denpasar Barat, Kota Denpasar, Bali', 'postal' => '80119',
                'area_id' => 'IDNP17IDNC400IDND4002IDZ80119', 'item' => 'Aeropress Go Travel Coffee Press', 'category' => 'Peralatan Rumah Tangga', 'val' => 580000,
                'weight' => 700, 'courier' => 'ninja', 'srv_code' => 'standard', 'srv_name' => 'Ninja Standard', 'fee' => 18000, 'est' => '1-2 hari',
                'waybill' => 'NLIDAP9283715', 'paid' => true,
            ],
            [
                'day_offset' => 4, 'status' => 'delivered', 'store' => $storeSurabaya,
                'name' => 'Oki Setiana', 'phone' => '081244556688', 'dest' => 'Semarang Tengah, Kota Semarang', 'postal' => '50132',
                'area_id' => 'IDNP8IDNC280IDND2851IDZ50132', 'item' => 'Kopi Susu Gula Aren 250ml Ready to Drink (x10)', 'category' => 'Makanan & Minuman', 'val' => 160000,
                'weight' => 2800, 'courier' => 'jne', 'srv_code' => 'yes', 'srv_name' => 'JNE YES', 'fee' => 26000, 'est' => '1 hari',
                'waybill' => 'JNE3328192837', 'paid' => true,
            ],
            [
                'day_offset' => 4, 'status' => 'delivered', 'store' => $storeJakarta,
                'name' => 'Pandu Wijaya', 'phone' => '085788990022', 'dest' => 'Jagakarsa, Jakarta Selatan', 'postal' => '12620',
                'area_id' => 'IDNP6IDNC148IDND1283IDZ12620', 'item' => 'Coffee Filter Cloth Nel Drip', 'category' => 'Peralatan Rumah Tangga', 'val' => 45000,
                'weight' => 150, 'courier' => 'lion', 'srv_code' => 'reg_pack', 'srv_name' => 'Lion Regpack', 'fee' => 10000, 'est' => '1-2 hari',
                'waybill' => 'LP1166192837', 'paid' => true,
            ],
            [
                'day_offset' => 4, 'status' => 'delivered', 'store' => $storeSurabaya,
                'name' => 'Qori Sandioriva', 'phone' => '081322334466', 'dest' => 'Wonosokerto, Pekalongan, Jawa Tengah', 'postal' => '51161',
                'area_id' => 'IDNP8IDNC290IDND2901IDZ51161', 'item' => 'Kopi Arabika Papua Wamena 500g', 'category' => 'Makanan & Minuman', 'val' => 165000,
                'weight' => 500, 'courier' => 'pos', 'srv_code' => 'pos_reguler', 'srv_name' => 'Pos Reguler', 'fee' => 17000, 'est' => '2 hari',
                'waybill' => 'P2600771928', 'paid' => true,
            ],
            [
                'day_offset' => 4, 'status' => 'delivered', 'store' => $storeJakarta,
                'name' => 'Reza Rahadian', 'phone' => '081299001144', 'dest' => 'Pancoran, Jakarta Selatan', 'postal' => '12780',
                'area_id' => 'IDNP6IDNC148IDND1284IDZ12780', 'item' => 'Tasting Cup Keramik Special Cupping (Set 4)', 'category' => 'Peralatan Rumah Tangga', 'val' => 135000,
                'weight' => 800, 'courier' => 'tiki', 'srv_code' => 'reg', 'srv_name' => 'TIKI REG', 'fee' => 10000, 'est' => '1-2 hari',
                'waybill' => '660072635142', 'paid' => true,
            ],
            [
                'day_offset' => 5, 'status' => 'delivered', 'store' => $storeSurabaya,
                'name' => 'Syahrul Gunawan', 'phone' => '082133445577', 'dest' => 'Genteng, Surabaya, Jawa Timur', 'postal' => '60275',
                'area_id' => 'IDNP7IDNC266IDND2775IDZ60275', 'item' => 'Paper Cup 8oz Panas (100 pcs)', 'category' => 'Kemasan & Packaging', 'val' => 48000,
                'weight' => 600, 'courier' => 'jnt', 'srv_code' => 'ez', 'srv_name' => 'J&T EZ', 'fee' => 9000, 'est' => '1 hari',
                'waybill' => 'JT5519283713', 'paid' => true,
            ],
            [
                'day_offset' => 5, 'status' => 'delivered', 'store' => $storeJakarta,
                'name' => 'Tiara Andini', 'phone' => '085344556688', 'dest' => 'Cilandak Timur, Pasar Minggu, Jaksel', 'postal' => '12560',
                'area_id' => 'IDNP6IDNC148IDND1287IDZ12560', 'item' => 'Matcha Latte Powder Jepang 250g', 'category' => 'Makanan & Minuman', 'val' => 110000,
                'weight' => 300, 'courier' => 'jnt', 'srv_code' => 'ez', 'srv_name' => 'J&T EZ', 'fee' => 10000, 'est' => '1 hari',
                'waybill' => 'JT4419283712', 'paid' => true,
            ],
            [
                'day_offset' => 5, 'status' => 'delivered', 'store' => $storeSurabaya,
                'name' => 'Umar Bakri', 'phone' => '081388990033', 'dest' => 'Kedungkandang, Kota Malang', 'postal' => '65136',
                'area_id' => 'IDNP7IDNC267IDND2783IDZ65136', 'item' => 'Kopi Luwak Java Robusta 250g', 'category' => 'Makanan & Minuman', 'val' => 95000,
                'weight' => 300, 'courier' => 'sicepat', 'srv_code' => 'sicepat_reg', 'srv_name' => 'SiCepat REG', 'fee' => 12000, 'est' => '1 hari',
                'waybill' => '006619283719', 'paid' => true,
            ],
            [
                'day_offset' => 5, 'status' => 'delivered', 'store' => $storeJakarta,
                'name' => 'Vina Panduwinata', 'phone' => '087811224455', 'dest' => 'Palmerah, Jakarta Barat', 'postal' => '11480',
                'area_id' => 'IDNP6IDNC149IDND1296IDZ11480', 'item' => 'Kopi Arabika Bali Kintamani 1kg', 'category' => 'Makanan & Minuman', 'val' => 195000,
                'weight' => 1000, 'courier' => 'jne', 'srv_code' => 'reg', 'srv_name' => 'JNE Reguler', 'fee' => 10000, 'est' => '1 hari',
                'waybill' => 'JNE2218293847', 'paid' => true,
            ],
            [
                'day_offset' => 5, 'status' => 'delivered', 'store' => $storeSurabaya,
                'name' => 'Wawan Hendrawan', 'phone' => '081266779900', 'dest' => 'Mataram, Kota Mataram, NTB', 'postal' => '83121',
                'area_id' => 'IDNP19IDNC700IDND7001IDZ83121', 'item' => 'Kopi Arabika Sembalun Rinjani 500g', 'category' => 'Makanan & Minuman', 'val' => 135000,
                'weight' => 500, 'courier' => 'jne', 'srv_code' => 'reg', 'srv_name' => 'JNE Reguler', 'fee' => 28000, 'est' => '2-3 hari',
                'waybill' => 'JNE1128392817', 'paid' => true,
            ],
            [
                'day_offset' => 5, 'status' => 'delivered', 'store' => $storeJakarta,
                'name' => 'Xaverius Chandra', 'phone' => '085699001122', 'dest' => 'Tanjung Duren, Grogol Petamburan, Jakbar', 'postal' => '11470',
                'area_id' => 'IDNP6IDNC149IDND1297IDZ11470', 'item' => 'Alat Press Kopi Minipresso Portable', 'category' => 'Peralatan Rumah Tangga', 'val' => 690000,
                'weight' => 500, 'courier' => 'jnt', 'srv_code' => 'super', 'srv_name' => 'J&T Super', 'fee' => 18000, 'est' => '1 hari',
                'waybill' => 'JT3319283711', 'paid' => true,
            ],
            [
                'day_offset' => 5, 'status' => 'delivered', 'store' => $storeSurabaya,
                'name' => 'Yuni Shara', 'phone' => '081311223399', 'dest' => 'Klojen, Kota Malang, Jawa Timur', 'postal' => '65112',
                'area_id' => 'IDNP7IDNC267IDND2780IDZ65112', 'item' => 'Biji Kopi Arabika Ijen Raung 500g', 'category' => 'Makanan & Minuman', 'val' => 110000,
                'weight' => 500, 'courier' => 'jnt', 'srv_code' => 'ez', 'srv_name' => 'J&T EZ', 'fee' => 14000, 'est' => '1 hari',
                'waybill' => 'JT2219283710', 'paid' => true,
            ],
            [
                'day_offset' => 6, 'status' => 'delivered', 'store' => $storeSurabaya,
                'name' => 'Zaskia Gotik', 'phone' => '082255667799', 'dest' => 'Tegalsari, Surabaya, Jawa Timur', 'postal' => '60262',
                'area_id' => 'IDNP7IDNC266IDND2774IDZ60262', 'item' => 'Sirup Pandan Wangi Barista 750ml', 'category' => 'Makanan & Minuman', 'val' => 85000,
                'weight' => 1200, 'courier' => 'anteraja', 'srv_code' => 'next_day', 'srv_name' => 'AnterAja Next Day', 'fee' => 18000, 'est' => '1 hari',
                'waybill' => '100088271625', 'paid' => true,
            ],
            [
                'day_offset' => 6, 'status' => 'delivered', 'store' => $storeJakarta,
                'name' => 'Aditya Pratama', 'phone' => '085299887711', 'dest' => 'Cilandak, Jakarta Selatan', 'postal' => '12430',
                'area_id' => 'IDNP6IDNC148IDND1282IDZ12430', 'item' => 'Kopi Arabika Aceh Gayo 1kg Natural', 'category' => 'Makanan & Minuman', 'val' => 180000,
                'weight' => 1000, 'courier' => 'jnt', 'srv_code' => 'ez', 'srv_name' => 'J&T EZ', 'fee' => 10000, 'est' => '1 hari',
                'waybill' => 'JT1119283709', 'paid' => true,
            ],
            [
                'day_offset' => 6, 'status' => 'delivered', 'store' => $storeSurabaya,
                'name' => 'Bella Saphira', 'phone' => '081344668800', 'dest' => 'Gedong Tengen, Kota Yogyakarta', 'postal' => '55272',
                'area_id' => 'IDNP4IDNC100IDND1001IDZ55272', 'item' => 'Kettle Leher Angsa Gooseneck 600ml', 'category' => 'Peralatan Rumah Tangga', 'val' => 175000,
                'weight' => 600, 'courier' => 'sicepat', 'srv_code' => 'sicepat_reg', 'srv_name' => 'SiCepat REG', 'fee' => 16000, 'est' => '1-2 hari',
                'waybill' => '005519283718', 'paid' => true,
            ],
            [
                'day_offset' => 6, 'status' => 'delivered', 'store' => $storeJakarta,
                'name' => 'Candra Wijaya', 'phone' => '087733445566', 'dest' => 'Setiabudi, Jakarta Selatan', 'postal' => '12920',
                'area_id' => 'IDNP6IDNC148IDND1281IDZ12920', 'item' => 'Cold Brew Bottle Kaca 500ml (x4)', 'category' => 'Peralatan Rumah Tangga', 'val' => 120000,
                'weight' => 1400, 'courier' => 'anteraja', 'srv_code' => 'reg', 'srv_name' => 'AnterAja Regular', 'fee' => 10000, 'est' => '1 hari',
                'waybill' => '100062534132', 'paid' => true,
            ],
            [
                'day_offset' => 6, 'status' => 'delivered', 'store' => $storeSurabaya,
                'name' => 'Dadan Ramdani', 'phone' => '081288002244', 'dest' => 'Coblong, Kota Bandung, Jawa Barat', 'postal' => '40132',
                'area_id' => 'IDNP9IDNC300IDND3001IDZ40132', 'item' => 'Kopi Arabika Flores Manggarai 500g', 'category' => 'Makanan & Minuman', 'val' => 125000,
                'weight' => 500, 'courier' => 'jne', 'srv_code' => 'reg', 'srv_name' => 'JNE Reguler', 'fee' => 20000, 'est' => '2 hari',
                'waybill' => 'JNE0028392816', 'paid' => true,
            ],
            [
                'day_offset' => 6, 'status' => 'delivered', 'store' => $storeSurabaya,
                'name' => 'Erna Susanti', 'phone' => '085711335577', 'dest' => 'Klojen, Kota Malang', 'postal' => '65113',
                'area_id' => 'IDNP7IDNC267IDND2780IDZ65113', 'item' => 'Sirup Karamel Monin 700ml', 'category' => 'Makanan & Minuman', 'val' => 145000,
                'weight' => 1300, 'courier' => 'jnt', 'srv_code' => 'ez', 'srv_name' => 'J&T EZ', 'fee' => 14000, 'est' => '1 hari',
                'waybill' => 'JT0019283708', 'paid' => true,
            ],
            [
                'day_offset' => 6, 'status' => 'delivered', 'store' => $storeJakarta,
                'name' => 'Farhan Maulana', 'phone' => '081366881133', 'dest' => 'Kebayoran Baru, Jakarta Selatan', 'postal' => '12130',
                'area_id' => 'IDNP6IDNC148IDND1280IDZ12130', 'item' => 'Kopi House Blend Espresso 2kg', 'category' => 'Makanan & Minuman', 'val' => 280000,
                'weight' => 2000, 'courier' => 'jnt', 'srv_code' => 'ez', 'srv_name' => 'J&T EZ', 'fee' => 12000, 'est' => '1 hari',
                'waybill' => 'JT9909283707', 'paid' => true,
            ],
            [
                'day_offset' => 6, 'status' => 'delivered', 'store' => $storeSurabaya,
                'name' => 'Gita Gutawa', 'phone' => '082199003355', 'dest' => 'Banyumanik, Semarang, Jawa Tengah', 'postal' => '50268',
                'area_id' => 'IDNP8IDNC280IDND2850IDZ50268', 'item' => 'Paper Filter V60 02 Unbleached (x3 pak)', 'category' => 'Peralatan Rumah Tangga', 'val' => 99000,
                'weight' => 450, 'courier' => 'sicepat', 'srv_code' => 'sicepat_reg', 'srv_name' => 'SiCepat REG', 'fee' => 15000, 'est' => '1-2 hari',
                'waybill' => '004419283717', 'paid' => true,
            ],
        ];

        $letters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lettersLen = strlen($letters);
        $totalShipments = count($shipmentsData);

        foreach ($shipmentsData as $index => $data) {
            $seqNumber = $totalShipments - $index;
            $seq = str_pad((string) $seqNumber, 4, '0', STR_PAD_LEFT);
            $suffix = $letters[($seqNumber * 7 + 3) % $lettersLen]
                .$letters[($seqNumber * 11 + 5) % $lettersLen]
                .$letters[($seqNumber * 17 + 9) % $lettersLen];
            $orderNumber = "SK-{$seq}{$suffix}";

            $createdAt = Carbon::now()->subDays($data['day_offset'])->subHours(rand(1, 14))->subMinutes(rand(5, 55));
            $paidAt = $data['paid'] ? (clone $createdAt)->addMinutes(rand(5, 45)) : null;

            $trackingHistory = null;
            if ($data['status'] === 'delivered') {
                $trackingHistory = [
                    ['time' => (clone $createdAt)->format('Y-m-d H:i:s'), 'status' => 'allocated', 'note' => 'Kurir ditugaskan untuk pickup paket'],
                    ['time' => (clone $createdAt)->addHours(2)->format('Y-m-d H:i:s'), 'status' => 'picking_up', 'note' => 'Paket berhasil dijemput oleh kurir'],
                    ['time' => (clone $createdAt)->addHours(8)->format('Y-m-d H:i:s'), 'status' => 'in_transit', 'note' => 'Paket sedang transit di sorting hub'],
                    ['time' => (clone $createdAt)->addHours(26)->format('Y-m-d H:i:s'), 'status' => 'delivered', 'note' => 'Paket telah diterima oleh penerima ('.$data['name'].')'],
                ];
            } elseif ($data['status'] === 'in_transit') {
                $trackingHistory = [
                    ['time' => (clone $createdAt)->format('Y-m-d H:i:s'), 'status' => 'allocated', 'note' => 'Kurir ditugaskan untuk pickup paket'],
                    ['time' => (clone $createdAt)->addHours(2)->format('Y-m-d H:i:s'), 'status' => 'picking_up', 'note' => 'Paket telah dijemput dari toko'],
                    ['time' => (clone $createdAt)->addHours(6)->format('Y-m-d H:i:s'), 'status' => 'in_transit', 'note' => 'Paket dalam perjalanan ke kota tujuan'],
                ];
            } elseif ($data['status'] === 'waiting_pickup') {
                $trackingHistory = [
                    ['time' => (clone $createdAt)->format('Y-m-d H:i:s'), 'status' => 'paid', 'note' => 'Pembayaran ongkir terkonfirmasi. Menunggu kurir menjemput paket.'],
                ];
            }

            Shipment::updateOrCreate(
                ['order_number' => $orderNumber],
                [
                    'user_id' => $merchant->id,
                    'store_id' => $data['store']->id,
                    'status' => $data['status'],
                    'recipient_name' => $data['name'],
                    'recipient_phone' => $data['phone'],
                    'destination_address' => $data['dest'],
                    'destination_postal_code' => $data['postal'],
                    'destination_area_id' => $data['area_id'],
                    'destination_area_name' => $data['dest'],
                    'weight' => $data['weight'],
                    'item_description' => $data['item'],
                    'item_category' => $data['category'],
                    'item_value' => $data['val'],
                    'courier_code' => $data['courier'],
                    'courier_service_code' => $data['srv_code'],
                    'courier_service_name' => $data['srv_name'],
                    'shipping_fee' => $data['fee'],
                    'total_amount' => $data['fee'],
                    'estimated_delivery' => $data['est'],
                    'biteship_order_id' => $data['waybill'] ? 'bite_'.strtolower(str_replace('-', '', $orderNumber)) : null,
                    'waybill_number' => $data['waybill'],
                    'tracking_history' => $trackingHistory,
                    'snap_token' => $data['status'] === 'pending_payment' ? 'mock_snap_token_'.$orderNumber : null,
                    'payment_method' => $data['paid'] ? (rand(0, 1) === 1 ? 'qris' : 'bca_va') : null,
                    'paid_at' => $paidAt,
                    'created_at' => $createdAt,
                    'updated_at' => (clone $createdAt)->addHours(rand(1, 28)),
                ]
            );
        }
    }
}


import {
    CreditCard,
    Package,
    Printer,
    Search,
    Smartphone,
    Store,
    Wallet,
    Zap,
} from '@lucide/vue';
import type { Directive } from 'vue';

export interface CourierPartner {
    name: string;
    logo: string;
}

export const courierPartners: CourierPartner[] = [
    { name: 'JNE Express', logo: '/images/couriers/jne.svg' },
    { name: 'J&T Express', logo: '/images/couriers/jnt.svg' },
    { name: 'SiCepat Ekspres', logo: '/images/couriers/sicepat.svg' },
    { name: 'AnterAja', logo: '/images/couriers/anteraja.svg' },
    { name: 'Ninja Xpress', logo: '/images/couriers/ninjaxpress.svg' },
    { name: 'Lion Parcel', logo: '/images/couriers/lionparcel.svg' },
    { name: 'Pos Indonesia', logo: '/images/couriers/posindonesia.svg' },
    { name: 'TIKI', logo: '/images/couriers/tiki.svg' },
];

export const marqueeItems: CourierPartner[] = [
    ...courierPartners,
    ...courierPartners,
    ...courierPartners,
];

export const quickRoutes = [
    { from: 'Jakarta', to: 'Surabaya' },
    { from: 'Bandung', to: 'Yogyakarta' },
    { from: 'Surabaya', to: 'Denpasar' },
];

export const quickRouteMap: Record<string, { fromId: string; toId: string; fromName: string; toName: string }> = {
    'Jakarta-Surabaya': { fromId: 'IDnp10110', toId: 'IDnp60281', fromName: 'Gambir, Jakarta Pusat', toName: 'Gubeng, Surabaya' },
    'Bandung-Yogyakarta': { fromId: 'IDnp40111', toId: 'IDnp55271', fromName: 'Coblong, Kota Bandung', toName: 'Kraton, Kota Yogyakarta' },
    'Surabaya-Denpasar': { fromId: 'IDnp60281', toId: 'IDnp80232', fromName: 'Gubeng, Surabaya', toName: 'Denpasar Barat, Kota Denpasar' },
};

export const features = [
    { icon: Search, title: 'Komparasi 8 Ekspedisi', text: 'Bandingkan ongkir dan estimasi waktu tiba 8 kurir dalam satu layar.' },
    { icon: CreditCard, title: 'Bayar Instan via Xendit', text: 'Lunasi ongkir Non-COD pakai QRIS atau Virtual Account tanpa top-up.' },
    { icon: Wallet, title: 'COD & Tarik Saldo', text: 'Terima bayar di tempat (COD) lalu cairkan dana ke rekening bank.' },
    { icon: Printer, title: 'Label Thermal A6 & Massal', text: 'Cetak resi 100×150 mm berbarcode secara satuan maupun Bulk Print.' },
    { icon: Store, title: 'Multi-Toko & Webhook', text: 'Simpan banyak lokasi jemput dan pantau status resi via webhook Biteship.' },
    { icon: Smartphone, title: 'Ekspor Excel & PWA', text: 'Unduh laporan transaksi ke Excel dan pasang aplikasi ringan di HP.' },
];

export const comparisons = [
    {
        bad: 'Buka 4–5 aplikasi kurir cuma buat cek selisih ongkir',
        good: 'Bandingkan 8 ekspedisi resmi berdampingan dalam satu layar',
    },
    {
        bad: 'Catat manual nama pembeli dan bolak-balik cari kontak WA',
        good: 'Cetak label A6 satuan/massal & chat pembeli via WA dalam 1 klik',
    },
    {
        bad: 'Repot antre di gerai drop-point sore-sore setelah packing',
        good: 'Kurir jemput paket langsung ke toko tanpa minimal jumlah paket',
    },
    {
        bad: 'Laba tergerus potongan komisi marketplace 5–10%',
        good: 'Laba utuh untuk tokomu dengan biaya flat Rp 1.000 per paket',
    },
];

export const testimonials = [
    {
        name: 'Rani Pratiwi',
        role: 'Owner Rania Hijab Store, Bandung',
        avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80',
        quote: 'Dulu tiap sore habis waktu berjam-jam cuma buat salin nomor resi ke WhatsApp pembeli satu-satu. Pakai SatuKirim tinggal klik tombol WA, pembeli langsung bisa lacak posisi paketnya sendiri.',
        courierPref: 'SiCepat & J&T',
        rating: 5,
    },
    {
        name: 'Budi Santoso',
        role: 'Founder Kopi Mandiri Roastery, Surabaya',
        avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80',
        quote: 'Bandingin ongkir jadi transparan banget. Pembeli di luar pulau bisa saya tawari mau paket reguler yang hemat atau yang kilat besok sampai. Kurir juga jemput tepat waktu.',
        courierPref: 'JNE & Lion Parcel',
        rating: 5,
    },
    {
        name: 'Sarah Wijaya',
        role: 'Dropshipper Skincare, Jakarta',
        avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=150&q=80',
        quote: 'Fitur cetak label thermalnya penyelamat hidup! Ukuran A6 pas di printer mini saya, barcode langsung bisa di-scan kurir. Sangat praktis buat jualan di Instagram.',
        courierPref: 'AnterAja & J&T',
        rating: 5,
    },
];

export const audiences = [
    { icon: Store, title: 'Pedagang Sosial Media', text: 'Kelola pengiriman order dari chat tanpa perlu catat manual.' },
    { icon: Zap, title: 'Toko Online Mandiri', text: 'Atur semua kurir di satu pintu tanpa potongan komisi jualan.' },
    { icon: Package, title: 'Dropshipper & Reseller', text: 'Kirim paket atas nama toko sendiri lengkap dengan resi resmi.' },
];

export const faqs = [
    { q: 'Apakah SatuKirim sebuah marketplace?', a: 'Bukan. SatuKirim adalah aggregator logistik bagi toko mandiri untuk cek ongkir, terbit resi, pesan kurir jemput, dan lacak paket.' },
    { q: 'Berapa biaya penggunaan SatuKirim?', a: 'Gratis tanpa langganan bulanan atau komisi produk. Hanya dikenakan biaya layanan flat Rp 1.000 per pengiriman berhasil.' },
    { q: 'Bagaimana cara bayar ongkos kirim?', a: 'Bisa bayar Non-COD instan lewat QRIS & Virtual Account via Xendit, atau pilih COD di mana dana masuk ke Dompet Saya setelah paket sampai.' },
    { q: 'Bagaimana cara mencairkan saldo COD?', a: 'Setelah paket COD berstatus delivered, dana masuk ke Saldo Siap Tarik dan bisa langsung dicairkan ke rekening bank Anda.' },
    { q: 'Apakah pembeli wajib daftar akun untuk lacak paket?', a: 'Tidak perlu. Pembeli cukup masukkan nomor resi dan kurir pada tab Lacak Status di halaman utama SatuKirim.' },
    { q: 'Apakah kurir benar-benar jemput ke toko?', a: 'Ya. Setelah order dibuat, jadwal pickup otomatis diteruskan ke kurir pilihan Anda tanpa syarat minimal jumlah paket.' },
    { q: 'Apakah bisa dipasang di HP tanpa Play Store?', a: 'Bisa. SatuKirim mendukung PWA—buka lewat browser HP lalu pilih "Tambahkan ke Layar Utama".' },
];

export const vReveal: Directive<HTMLElement, number | undefined> = {
    mounted(el, binding) {
        el.classList.add('reveal');
        el.style.setProperty('--d', `${binding.value ?? 0}ms`);
        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    el.classList.add('is-visible');
                    observer.unobserve(el);
                }
            },
            { threshold: 0.12 },
        );
        observer.observe(el);
    },
};


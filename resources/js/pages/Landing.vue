<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowRight,
    Box,
    Check,
    CheckCheck,
    CheckCircle2,
    Clock,
    CreditCard,
    FileText,
    Loader2,
    Mail,
    MapPin,
    Menu,
    MessageCircle,
    Package,
    Phone,
    Plus,
    Printer,
    QrCode,
    Search,
    ShieldCheck,
    Smartphone,
    Sparkles,
    Star,
    Store,
    Truck,
    Wallet,
    X,
    XCircle,
    Zap,
} from '@lucide/vue';
import { onMounted, onUnmounted, ref } from 'vue';
import type { Directive } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import { login, register } from '@/routes';
import '../../css/landing.css';

type WidgetTab = 'ongkir' | 'resi';

interface AreaItem {
    id: string;
    name: string;
    administrative_division_level_1_name: string;
    administrative_division_level_2_name: string;
    administrative_division_level_3_name: string;
    postal_code: number | string;
}

interface CourierRateItem {
    name: string;
    service: string;
    eta: string;
    price: string;
    rawPrice: number;
    tag: string;
    best: boolean;
}

const menuOpen = ref(false);
const tab = ref<WidgetTab>('ongkir');
const showDemo = ref(false);
const openFaq = ref<number | null>(0);

const originInput = ref('Gambir, Jakarta Pusat');
const originAreaId = ref('IDnp10110');
const destinationInput = ref('Gubeng, Surabaya');
const destinationAreaId = ref('IDnp60281');
const weightInput = ref('1');
const resiInput = ref('');
const courierInput = ref('sicepat');

const originSuggestions = ref<AreaItem[]>([]);
const destSuggestions = ref<AreaItem[]>([]);
const showOriginDropdown = ref(false);
const showDestDropdown = ref(false);
const isOriginSearching = ref(false);
const isDestSearching = ref(false);
const originHasSearched = ref(false);
const destHasSearched = ref(false);

const isSearchingRates = ref(false);
const rateErrorMessage = ref('');
const liveRates = ref<CourierRateItem[]>([]);

const isTrackingResi = ref(false);
const trackResult = ref<any>(null);
const trackErrorMessage = ref('');

interface CourierPartner {
    name: string;
    logo: string;
}

const courierPartners: CourierPartner[] = [
    { name: 'JNE Express', logo: '/images/couriers/jne.svg' },
    { name: 'J&T Express', logo: '/images/couriers/jnt.svg' },
    { name: 'SiCepat Ekspres', logo: '/images/couriers/sicepat.svg' },
    { name: 'AnterAja', logo: '/images/couriers/anteraja.svg' },
    { name: 'Ninja Xpress', logo: '/images/couriers/ninjaxpress.svg' },
    { name: 'Lion Parcel', logo: '/images/couriers/lionparcel.svg' },
    { name: 'Pos Indonesia', logo: '/images/couriers/posindonesia.svg' },
    { name: 'ID Express', logo: '/images/couriers/idexpress.svg' },
    { name: 'SAP Express', logo: '/images/couriers/sapexpress.svg' },
    { name: 'TIKI', logo: '/images/couriers/tiki.svg' },
    { name: 'Wahana Express', logo: '/images/couriers/wahana.svg' },
];

const marqueeItems = [...courierPartners, ...courierPartners];

const quickRoutes = [
    { from: 'Jakarta', to: 'Surabaya' },
    { from: 'Bandung', to: 'Yogyakarta' },
    { from: 'Surabaya', to: 'Denpasar' },
];

const quickRouteMap: Record<string, { fromId: string; toId: string; fromName: string; toName: string }> = {
    'Jakarta-Surabaya': { fromId: 'IDnp10110', toId: 'IDnp60281', fromName: 'Gambir, Jakarta Pusat', toName: 'Gubeng, Surabaya' },
    'Bandung-Yogyakarta': { fromId: 'IDnp40111', toId: 'IDnp55271', fromName: 'Coblong, Kota Bandung', toName: 'Kraton, Kota Yogyakarta' },
    'Surabaya-Denpasar': { fromId: 'IDnp60281', toId: 'IDnp80232', fromName: 'Gubeng, Surabaya', toName: 'Denpasar Barat, Kota Denpasar' },
};

function closeAllDropdowns(): void {
    showOriginDropdown.value = false;
    showDestDropdown.value = false;
}

let originDebounceTimer: ReturnType<typeof setTimeout> | null = null;
function onOriginInput(): void {
    showDestDropdown.value = false;
    originAreaId.value = '';
    originHasSearched.value = false;
    if (originDebounceTimer) clearTimeout(originDebounceTimer);

    const query = originInput.value.trim();
    if (query.length < 2) {
        originSuggestions.value = [];
        showOriginDropdown.value = false;
        isOriginSearching.value = false;
        return;
    }

    isOriginSearching.value = true;
    showOriginDropdown.value = true;

    originDebounceTimer = setTimeout(async () => {
        try {
            const res = await fetch(`/api/public/areas?q=${encodeURIComponent(query)}`);
            const data = await res.json();
            if (data.success && Array.isArray(data.areas)) {
                originSuggestions.value = data.areas;
            } else {
                originSuggestions.value = [];
            }
        } catch {
            originSuggestions.value = [];
        } finally {
            isOriginSearching.value = false;
            originHasSearched.value = true;
            showOriginDropdown.value = true;
        }
    }, 250);
}

function onOriginFocus(): void {
    showDestDropdown.value = false;
    if (originSuggestions.value.length > 0 || (originHasSearched.value && originInput.value.trim().length >= 2)) {
        showOriginDropdown.value = true;
    } else if (originInput.value.trim().length >= 2) {
        onOriginInput();
    }
}

function selectOrigin(area: AreaItem): void {
    const district = area.administrative_division_level_3_name || area.name;
    const city = area.administrative_division_level_2_name;
    const postal = area.postal_code ? ` (${area.postal_code})` : '';
    originInput.value = city ? `${district}, ${city}${postal}` : `${area.name}`;
    originAreaId.value = area.id;
    originHasSearched.value = false;
    closeAllDropdowns();
}

let destDebounceTimer: ReturnType<typeof setTimeout> | null = null;
function onDestInput(): void {
    showOriginDropdown.value = false;
    destinationAreaId.value = '';
    destHasSearched.value = false;
    if (destDebounceTimer) clearTimeout(destDebounceTimer);

    const query = destinationInput.value.trim();
    if (query.length < 2) {
        destSuggestions.value = [];
        showDestDropdown.value = false;
        isDestSearching.value = false;
        return;
    }

    isDestSearching.value = true;
    showDestDropdown.value = true;

    destDebounceTimer = setTimeout(async () => {
        try {
            const res = await fetch(`/api/public/areas?q=${encodeURIComponent(query)}`);
            const data = await res.json();
            if (data.success && Array.isArray(data.areas)) {
                destSuggestions.value = data.areas;
            } else {
                destSuggestions.value = [];
            }
        } catch {
            destSuggestions.value = [];
        } finally {
            isDestSearching.value = false;
            destHasSearched.value = true;
            showDestDropdown.value = true;
        }
    }, 250);
}

function onDestFocus(): void {
    showOriginDropdown.value = false;
    if (destSuggestions.value.length > 0 || (destHasSearched.value && destinationInput.value.trim().length >= 2)) {
        showDestDropdown.value = true;
    } else if (destinationInput.value.trim().length >= 2) {
        onDestInput();
    }
}

function selectDest(area: AreaItem): void {
    const district = area.administrative_division_level_3_name || area.name;
    const city = area.administrative_division_level_2_name;
    const postal = area.postal_code ? ` (${area.postal_code})` : '';
    destinationInput.value = city ? `${district}, ${city}${postal}` : `${area.name}`;
    destinationAreaId.value = area.id;
    destHasSearched.value = false;
    closeAllDropdowns();
}

let touchStartY = 0;
let touchStartX = 0;

function onItemPointerDown(e: PointerEvent): void {
    touchStartY = e.clientY;
    touchStartX = e.clientX;
}

function onItemPointerUp(e: PointerEvent, callback: () => void): void {
    const deltaY = Math.abs(e.clientY - touchStartY);
    const deltaX = Math.abs(e.clientX - touchStartX);
    if (deltaY < 10 && deltaX < 10) {
        callback();
    }
}

function handleDocumentClick(event: MouseEvent): void {
    const target = event.target as HTMLElement | null;
    if (!target) return;
    if (!target.closest('.field-origin') && !target.closest('.field-destination')) {
        closeAllDropdowns();
    }
}

onMounted(() => {
    document.addEventListener('click', handleDocumentClick);
});

onUnmounted(() => {
    document.removeEventListener('click', handleDocumentClick);
});

function selectQuickRoute(from: string, to: string): void {
    closeAllDropdowns();
    const key = `${from}-${to}`;
    const mapped = quickRouteMap[key];
    if (mapped) {
        originInput.value = mapped.fromName;
        originAreaId.value = mapped.fromId;
        destinationInput.value = mapped.toName;
        destinationAreaId.value = mapped.toId;
    } else {
        originInput.value = from;
        destinationInput.value = to;
    }
    submitWidget();
}


const features = [
    { icon: Wallet, title: 'Komparasi Tarif Real-Time', text: 'Bandingkan tarif dan durasi estimasi dari 8+ kurir sekaligus. Pilih yang paling hemat atau tercepat.' },
    { icon: Store, title: 'Multi-Alamat & Multi-Gudang', text: 'Punya beberapa toko atau gudang pengiriman? Simpan alamat dan tentukan lokasi penjemputan dengan mudah.' },
    { icon: ShieldCheck, title: 'Bayar Fleksibel via Midtrans', text: 'Bayar ongkir instan via QRIS semua bank atau Virtual Account tanpa kewajiban deposit saldo dompet.' },
    { icon: FileText, title: 'Rekap & Riwayat Kiriman', text: 'Catatan pengeluaran ongkir dan riwayat nomor resi tersimpan rapi, siap dipantau atau diekspor kapan saja.' },
    { icon: Search, title: 'Lacak Publik Tanpa Akun', text: 'Pembeli bisa langsung memantau perjalanan paket lewat tautan resmi tanpa diwajibkan login atau mendaftar.' },
    { icon: Smartphone, title: 'PWA Mobile-First Ringan', text: 'Pasang langsung di layar utama smartphone tanpa unduh file besar di Play Store. Cepat, hemat kuota dan memori.' },
];

const comparisons = [
    {
        bad: 'Buka 4-5 aplikasi kurir bergantian hanya untuk cek selisih ongkir Rp2.000',
        good: 'Cek dan bandingkan 8+ kurir berdampingan dalam 1 detik di satu layar',
    },
    {
        bad: 'Tulis manual nama pembeli di buku lalu bolak-balik ketik nomor resi ke chat WA',
        good: 'Cetak label thermal otomatis & kirim pesan notif resi ke WA pembeli dengan 1 klik',
    },
    {
        bad: 'Harus repot antre di gerai drop-point sore-sore setelah lelah packing pesanan',
        good: 'Kurir langsung datang menjemput ke depan rumah/toko tanpa minimum jumlah paket',
    },
    {
        bad: 'Tebak-tebakan tarif ongkir yang berisiko tekor di ongkos kirim',
        good: 'Tarif terhubung API resmi, terhitung akurat sesuai berat dan zona wilayah',
    },
];

const testimonials = [
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

const audiences = [
    { icon: Store, title: 'Pedagang Sosial Media', text: 'Kelola orderan dari chat media sosial dengan mudah tanpa perlu catat manual di buku nota.' },
    { icon: Zap, title: 'Toko Online & Web Mandiri', text: 'Kelola pengiriman dari satu pintu tanpa ketergantungan potongan biaya komisi marketplace.' },
    { icon: Package, title: 'Dropshipper & Reseller', text: 'Kirim paket atas nama toko sendiri ke seluruh Indonesia dan bagikan bukti resi resmi ke pembeli.' },
];

const faqs = [
    { q: 'Apakah SatuKirim sebuah marketplace belanja?', a: 'Bukan. SatuKirim adalah aggregator layanan pengiriman kurir khusus bagi pedagang independen (non-marketplace) untuk membandingkan ongkir, menerbitkan nomor resi, memesan kurir jemput, dan memantau status paket.' },
    { q: 'Bagaimana alur pembayaran ongkos kirim?', a: 'Pembayaran ongkir dilakukan secara non-COD di awal melalui Midtrans menggunakan QRIS (BCA, GoPay, OVO, ShopeePay) maupun Virtual Account bank.' },
    { q: 'Apakah pembeli saya wajib mendaftar akun di SatuKirim?', a: 'Tidak perlu sama sekali! Pembeli cukup membuka tautan pelacakan publik yang Anda bagikan lewat WhatsApp, dan mereka bisa memantau perjalanan paket tanpa harus login.' },
    { q: 'Apakah kurir benar-benar datang menjemput ke rumah/toko?', a: 'Ya! Setelah pembayaran ongkos kirim berhasil, sistem otomatis meneruskan jadwal penjemputan (pickup) ke kurir ekspedisi yang Anda pilih.' },
    { q: 'Apakah aplikasi ini bisa di-install di HP?', a: 'Bisa! SatuKirim dibangun sebagai Progressive Web App (PWA). Cukup buka di peramban Chrome/Safari HP dan pilih "Tambahkan ke Layar Utama" tanpa perlu unduh dari Play Store.' },
];

const vReveal: Directive<HTMLElement, number | undefined> = {
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

function setTab(next: WidgetTab): void {
    tab.value = next;
    showDemo.value = false;
    closeAllDropdowns();
}

async function submitWidget(): Promise<void> {
    closeAllDropdowns();
    showDemo.value = true;

    if (tab.value === 'ongkir') {
        isSearchingRates.value = true;
        rateErrorMessage.value = '';
        liveRates.value = [];

        try {
            let oId = originAreaId.value;
            let dId = destinationAreaId.value;

            if (!oId && originInput.value.trim().length >= 2) {
                const res = await fetch(`/api/public/areas?q=${encodeURIComponent(originInput.value.trim())}`);
                const data = await res.json();
                if (data.areas?.[0]?.id) {
                    oId = data.areas[0].id;
                    originAreaId.value = oId;
                }
            }

            if (!dId && destinationInput.value.trim().length >= 2) {
                const res = await fetch(`/api/public/areas?q=${encodeURIComponent(destinationInput.value.trim())}`);
                const data = await res.json();
                if (data.areas?.[0]?.id) {
                    dId = data.areas[0].id;
                    destinationAreaId.value = dId;
                }
            }

            const parsedWeight = parseFloat(weightInput.value);
            if (isNaN(parsedWeight) || parsedWeight < 0.1 || parsedWeight > 10) {
                rateErrorMessage.value = 'Berat paket harus antara 0.1 kg hingga maksimal 10 kg (skala parsel retail non-kargo).';
                return;
            }

            const response = await fetch('/api/public/rates', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    origin_area_id: oId || 'IDnp10110',
                    destination_area_id: dId || 'IDnp60281',
                    weight: parsedWeight,
                }),
            });

            const result = await response.json();

            if (response.status === 422) {
                rateErrorMessage.value = result.errors?.weight?.[0] || result.message || 'Validasi formulir gagal.';
                return;
            }

            if (result.success && Array.isArray(result.pricing) && result.pricing.length > 0) {
                const sorted = [...result.pricing].sort((a: any, b: any) => a.price - b.price);
                liveRates.value = sorted.map((p: any, idx: number) => ({
                    name: `${p.courier_name} ${p.courier_service_name}`,
                    service: p.courier_service_name,
                    eta: p.duration ? `${p.duration}` : '1 - 3 hari',
                    price: new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(p.price),
                    rawPrice: p.price,
                    tag: idx === 0 ? 'Termurah' : (idx === 1 ? 'Populer' : ''),
                    best: idx === 0,
                }));
            } else {
                rateErrorMessage.value = result.message || 'Tidak ada ekspedisi yang melayani rute ini. Coba pilih kelurahan/kecamatan yang lebih spesifik.';
            }
        } catch {
            rateErrorMessage.value = 'Terjadi gangguan koneksi saat menghubungi server Biteship. Silakan coba kembali.';
        } finally {
            isSearchingRates.value = false;
        }
    } else {
        if (!resiInput.value.trim()) {
            return;
        }
        isTrackingResi.value = true;
        trackErrorMessage.value = '';
        trackResult.value = null;

        try {
            const res = await fetch(`/api/public/track?courier=${encodeURIComponent(courierInput.value)}&waybill=${encodeURIComponent(resiInput.value.trim())}`);
            const data = await res.json();

            if (data.success && data.data) {
                trackResult.value = data.data;
            } else {
                trackErrorMessage.value = data.message || 'Nomor resi tidak ditemukan atau belum tercatat di ekspedisi.';
            }
        } catch {
            trackErrorMessage.value = 'Gagal memeriksa status resi saat ini. Silakan coba sesaat lagi.';
        } finally {
            isTrackingResi.value = false;
        }
    }
}

function toggleFaq(index: number): void {
    openFaq.value = openFaq.value === index ? null : index;
}

function scrollToSection(sectionId: string, event?: Event): void {
    if (event) {
        event.preventDefault();
    }
    menuOpen.value = false;

    if (sectionId === 'layanan' || sectionId === 'hero') {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setTab('ongkir');
        history.replaceState(null, '', '#layanan');
        return;
    }

    const target = document.getElementById(sectionId);
    if (target) {
        const navHeight = 72;
        const elementPosition = target.getBoundingClientRect().top + window.pageYOffset;
        const offsetPosition = Math.max(0, elementPosition - navHeight);

        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth',
        });
        history.replaceState(null, '', `#${sectionId}`);
    }
}
</script>

<template>
    <Head title="SatuKirim - Agregator Pengiriman Paket PWA untuk UMKM" />

    <main class="landing">
        <header class="site-header">
            <nav class="container nav" aria-label="Navigasi utama">
                <a href="#" class="brand group" @click="scrollToSection('layanan', $event)">
                    <AppLogo layout="horizontal" variant="white" size="md" />
                </a>
                <div class="nav-links">
                    <a href="#layanan" @click="scrollToSection('layanan', $event)">Cek Ongkir</a>
                    <a href="#perbandingan" @click="scrollToSection('perbandingan', $event)">Keunggulan</a>
                    <a href="#fitur" @click="scrollToSection('fitur', $event)">Fitur Lengkap</a>
                    <a href="#testimoni" @click="scrollToSection('testimoni', $event)">Cerita UMKM</a>
                    <a href="#faq" @click="scrollToSection('faq', $event)">Bantuan</a>
                </div>
                <div class="nav-actions">
                    <Link :href="login()">Masuk</Link>
                    <Link :href="register()" class="btn-pill btn-light">Daftar Gratis</Link>
                </div>
                <button class="burger" type="button" aria-label="Buka menu" @click="menuOpen = !menuOpen">
                    <X v-if="menuOpen" class="icon" />
                    <Menu v-else class="icon" />
                </button>
            </nav>
            <div v-if="menuOpen" class="mobile-menu">
                <a href="#layanan" @click="scrollToSection('layanan', $event)">Cek Ongkir</a>
                <a href="#perbandingan" @click="scrollToSection('perbandingan', $event)">Keunggulan</a>
                <a href="#fitur" @click="scrollToSection('fitur', $event)">Fitur Lengkap</a>
                <a href="#testimoni" @click="scrollToSection('testimoni', $event)">Cerita UMKM</a>
                <a href="#faq" @click="scrollToSection('faq', $event)">Bantuan</a>
                <div class="mobile-menu-actions">
                    <Link :href="login()" class="btn-pill btn-outline">Masuk</Link>
                    <Link :href="register()" class="btn-pill btn-light">Daftar Gratis</Link>
                </div>
            </div>
        </header>

        <section id="layanan" class="hero">
            <div class="orb orb-one" />
            <div class="orb orb-two" />
            <div class="container hero-grid">
                <div class="hero-copy">
                    <p class="kicker"><Sparkles class="icon" style="width: 1rem; height: 1rem" /> Agregator Logistik Cerdas untuk UMKM</p>
                    <h1>Kirim paket jualan, <em>tanpa drama ribet.</em></h1>
                    <p class="hero-desc">
                        Bandingkan ongkir 8+ ekspedisi, bayar instan, cetak label resi thermal, dan kabari pembeli otomatis lewat WhatsApp. Semua dari satu aplikasi ringan di HP.
                    </p>
                    <div class="hero-cta">
                        <Link :href="register()" class="btn-pill btn-berry">Mulai Kirim Sekarang <ArrowRight class="icon" /></Link>
                        <a href="#perbandingan" class="btn-pill btn-outline" @click="scrollToSection('perbandingan', $event)">Pelajari Keunggulan</a>
                    </div>
                    <div class="hero-tags">
                        <span><Check class="icon" /> Tanpa Biaya Langganan</span>
                        <span><Check class="icon" /> Ringan Dipasang di HP</span>
                    </div>
                </div>

                <div class="hero-widget">
                    <div class="widget">
                        <div class="widget-tabs">
                            <button type="button" class="tab" :class="{ active: tab === 'ongkir' }" @click="setTab('ongkir')">
                                <Wallet class="icon" style="width: 1rem; height: 1rem" /> Cek Ongkir
                            </button>
                            <button type="button" class="tab" :class="{ active: tab === 'resi' }" @click="setTab('resi')">
                                <Package class="icon" style="width: 1rem; height: 1rem" /> Lacak Status
                            </button>
                        </div>
                        <form class="widget-body" @submit.prevent="submitWidget">
                            <p class="widget-title">{{ tab === 'ongkir' ? 'Berapa ongkir paketmu hari ini?' : 'Pantau posisi barang pesanan' }}</p>
                            <p class="widget-sub">
                                {{ tab === 'ongkir' ? 'Bandingkan tarif terbaik dari JNE, J&T, SiCepat, AnterAja, dan lainnya.' : 'Masukkan nomor resi resmi untuk memantau pergerakan paket.' }}
                            </p>

                            <div v-if="tab === 'ongkir'" class="quick-routes">
                                <button
                                    v-for="route in quickRoutes"
                                    :key="`${route.from}-${route.to}`"
                                    type="button"
                                    class="route-pill"
                                    @click="selectQuickRoute(route.from, route.to)"
                                >
                                    {{ route.from }} → {{ route.to }}
                                </button>
                            </div>

                            <template v-if="tab === 'ongkir'">
                                <div class="field field-origin" :class="{ 'is-open': showOriginDropdown }">
                                    <label class="field-label" for="origin-input">Kota / Kecamatan Asal</label>
                                    <span class="input-wrap">
                                        <MapPin class="icon" />
                                        <input
                                            id="origin-input"
                                            v-model="originInput"
                                            required
                                            placeholder="Ketik nama kecamatan atau 5 digit kode pos"
                                            autocomplete="off"
                                            @input="onOriginInput"
                                            @focus="onOriginFocus"
                                        />
                                        <Loader2 v-if="isOriginSearching" class="icon-spin animate-spin shrink-0 text-[#853953]" style="width: 1rem; height: 1rem; margin-right: 0.5rem;" />
                                    </span>
                                    <ul v-if="showOriginDropdown" class="area-autocomplete-list">
                                        <li v-if="isOriginSearching" class="area-autocomplete-loading">
                                            <Loader2 class="icon-spin animate-spin shrink-0 text-[#853953]" style="width: 1rem; height: 1rem;" />
                                            <span>Mencari wilayah...</span>
                                        </li>
                                        <template v-else-if="originSuggestions.length > 0">
                                            <li
                                                v-for="area in originSuggestions"
                                                :key="area.id"
                                                class="area-autocomplete-item"
                                                @pointerdown="onItemPointerDown"
                                                @pointerup="onItemPointerUp($event, () => selectOrigin(area))"
                                                @click="selectOrigin(area)"
                                            >
                                                <div class="area-item-header">
                                                    <span class="area-item-name">{{ area.administrative_division_level_3_name || area.name }}</span>
                                                    <span v-if="area.postal_code" class="area-postal-badge">Kode Pos {{ area.postal_code }}</span>
                                                </div>
                                                <small>{{ area.administrative_division_level_2_name }}, {{ area.administrative_division_level_1_name }}</small>
                                            </li>
                                        </template>
                                        <li v-else-if="originHasSearched" class="area-autocomplete-empty">
                                            <div class="area-empty-icon">
                                                <AlertCircle style="width: 1rem; height: 1rem;" />
                                            </div>
                                            <div class="area-empty-text">
                                                <span class="area-empty-title">Wilayah tidak ditemukan</span>
                                                <span class="area-empty-desc">
                                                    Pastikan ejaan benar, ketik nama kecamatan dengan lengkap, atau gunakan <strong>5 digit kode pos</strong> (misal: <em>60188</em>).
                                                </span>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                                <div class="field field-destination" :class="{ 'is-open': showDestDropdown }">
                                    <label class="field-label" for="destination-input">Kota / Kecamatan Tujuan</label>
                                    <span class="input-wrap">
                                        <MapPin class="icon" />
                                        <input
                                            id="destination-input"
                                            v-model="destinationInput"
                                            required
                                            placeholder="Ketik nama kecamatan atau 5 digit kode pos"
                                            autocomplete="off"
                                            @input="onDestInput"
                                            @focus="onDestFocus"
                                        />
                                        <Loader2 v-if="isDestSearching" class="icon-spin animate-spin shrink-0 text-[#853953]" style="width: 1rem; height: 1rem; margin-right: 0.5rem;" />
                                    </span>
                                    <ul v-if="showDestDropdown" class="area-autocomplete-list">
                                        <li v-if="isDestSearching" class="area-autocomplete-loading">
                                            <Loader2 class="icon-spin animate-spin shrink-0 text-[#853953]" style="width: 1rem; height: 1rem;" />
                                            <span>Mencari wilayah...</span>
                                        </li>
                                        <template v-else-if="destSuggestions.length > 0">
                                            <li
                                                v-for="area in destSuggestions"
                                                :key="area.id"
                                                class="area-autocomplete-item"
                                                @pointerdown="onItemPointerDown"
                                                @pointerup="onItemPointerUp($event, () => selectDest(area))"
                                                @click="selectDest(area)"
                                            >
                                                <div class="area-item-header">
                                                    <span class="area-item-name">{{ area.administrative_division_level_3_name || area.name }}</span>
                                                    <span v-if="area.postal_code" class="area-postal-badge">Kode Pos {{ area.postal_code }}</span>
                                                </div>
                                                <small>{{ area.administrative_division_level_2_name }}, {{ area.administrative_division_level_1_name }}</small>
                                            </li>
                                        </template>
                                        <li v-else-if="destHasSearched" class="area-autocomplete-empty">
                                            <div class="area-empty-icon">
                                                <AlertCircle style="width: 1rem; height: 1rem;" />
                                            </div>
                                            <div class="area-empty-text">
                                                <span class="area-empty-title">Wilayah tidak ditemukan</span>
                                                <span class="area-empty-desc">
                                                    Pastikan ejaan benar, ketik nama kecamatan dengan lengkap, atau gunakan <strong>5 digit kode pos</strong> (misal: <em>60188</em>).
                                                </span>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                                <div class="field field-weight">
                                    <div style="display: flex; justify-content: space-between; align-items: baseline">
                                        <label class="field-label" for="weight-input">Berat Paket (kg)</label>
                                        <span class="weight-limit-badge">Maks. 10 kg</span>
                                    </div>
                                    <span class="input-wrap">
                                        <Box class="icon" />
                                        <input
                                            id="weight-input"
                                            v-model="weightInput"
                                            required
                                            min="0.1"
                                            max="10"
                                            step="0.1"
                                            type="number"
                                            placeholder="1"
                                        />
                                    </span>
                                </div>
                            </template>

                            <template v-else>
                                <label class="field">
                                    Pilihan Ekspedisi
                                    <span class="input-wrap">
                                        <Truck class="icon" />
                                        <select v-model="courierInput" style="width: 100%; border: 0; outline: 0; background: transparent; padding: 0.75rem 0; font-weight: 500; color: var(--charcoal)">
                                            <option value="sicepat">SiCepat Ekspres</option>
                                            <option value="jne">JNE Express</option>
                                            <option value="jnt">J&amp;T Express</option>
                                            <option value="anteraja">AnterAja</option>
                                            <option value="ninja">Ninja Xpress</option>
                                            <option value="lion">Lion Parcel</option>
                                            <option value="pos">Pos Indonesia</option>
                                        </select>
                                    </span>
                                </label>
                                <label class="field">
                                    Nomor Resi Pengiriman
                                    <span class="input-wrap"><Package class="icon" /><input v-model="resiInput" required placeholder="Contoh: 004281920194 atau JP0123456789" /></span>
                                </label>
                            </template>

                            <button type="submit" class="btn-pill btn-berry" :disabled="isSearchingRates || isTrackingResi">
                                <Loader2 v-if="isSearchingRates || isTrackingResi" class="icon btn-spin" style="width: 1rem; height: 1rem" />
                                <Search v-else class="icon" style="width: 1rem; height: 1rem" />
                                <span v-if="tab === 'ongkir'">{{ isSearchingRates ? 'Memeriksa Tarif Kurir...' : 'Lihat Pilihan Ongkos Kirim' }}</span>
                                <span v-else>{{ isTrackingResi ? 'Melacak Status Paket...' : 'Lacak Lokasi Paket' }}</span>
                            </button>

                            <div v-if="showDemo && tab === 'ongkir'" class="results">
                                <div v-if="isSearchingRates" style="padding: 1.5rem 0; text-align: center; color: var(--berry)">
                                    <Loader2 class="icon btn-spin" style="width: 1.8rem; height: 1.8rem; margin: 0 auto 0.5rem" />
                                    <p style="font-size: 0.9rem; font-weight: 500">Menghubungkan ke ekspedisi via Biteship...</p>
                                </div>
                                <div v-else-if="rateErrorMessage" style="padding: 0.85rem; border-radius: 0.75rem; background: rgba(239, 68, 68, 0.1); color: #dc2626; font-size: 0.88rem; display: flex; align-items: center; gap: 0.5rem">
                                    <AlertCircle class="icon" style="width: 1.25rem; height: 1.25rem; flex-shrink: 0" />
                                    <span>{{ rateErrorMessage }}</span>
                                </div>
                                <template v-else-if="liveRates.length > 0">
                                    <div class="results-header">
                                        <div class="results-heading-row">
                                            <span class="results-title">Pilihan Layanan Ekspedisi</span>
                                            <span class="results-badge">{{ liveRates.length }} Kurir Tersedia</span>
                                        </div>
                                        <div class="results-route-tag" :title="`${originInput} → ${destinationInput} (${weightInput} kg)`">
                                            <MapPin class="icon-inline" />
                                            <div class="results-route-content">
                                                <span class="results-route-text">{{ originInput }} → {{ destinationInput }}</span>
                                                <span class="results-route-weight">• {{ weightInput }} kg</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="results-list">
                                        <div v-for="rate in liveRates" :key="`${rate.name}-${rate.price}`" class="result-row" :class="{ best: rate.best }">
                                            <div class="result-info">
                                                <div class="result-title-row">
                                                    <span class="courier-name">{{ rate.name }}</span>
                                                    <span v-if="rate.tag" class="rate-tag" :class="{ highlight: rate.best }">{{ rate.tag }}</span>
                                                </div>
                                                <small>
                                                    <Clock class="icon-inline" />
                                                    <span>Estimasi tiba: {{ rate.eta }} • Pickup resmi kurir</span>
                                                </small>
                                            </div>
                                            <div class="result-price">
                                                <b>{{ rate.price }}</b>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div v-if="showDemo && tab === 'resi'" class="results">
                                <div v-if="isTrackingResi" style="padding: 1.5rem 0; text-align: center; color: var(--berry)">
                                    <Loader2 class="icon btn-spin" style="width: 1.8rem; height: 1.8rem; margin: 0 auto 0.5rem" />
                                    <p style="font-size: 0.9rem; font-weight: 500">Mengecek nomor resi ke server ekspedisi...</p>
                                </div>
                                <div v-else-if="trackErrorMessage" style="padding: 0.85rem; border-radius: 0.75rem; background: rgba(239, 68, 68, 0.1); color: #dc2626; font-size: 0.88rem; display: flex; align-items: center; gap: 0.5rem">
                                    <AlertCircle class="icon" style="width: 1.25rem; height: 1.25rem; flex-shrink: 0" />
                                    <span>{{ trackErrorMessage }}</span>
                                </div>
                                <template v-else-if="trackResult">
                                    <div class="results-header">
                                        <div class="results-heading-row">
                                            <span class="results-title">Hasil Pelacakan Resi</span>
                                            <span class="results-badge">{{ trackResult.status || 'Tercatat' }}</span>
                                        </div>
                                        <div class="results-route-tag">
                                            <Truck class="icon-inline" />
                                            <div class="results-route-content">
                                                <span class="results-route-text">{{ trackResult.courier?.name || courierInput.toUpperCase() }}</span>
                                                <span class="results-route-weight">• No. Resi: {{ resiInput }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="results-list">
                                        <div class="result-row best">
                                            <div class="result-info">
                                                <span class="courier-name">{{ trackResult.courier?.name || courierInput.toUpperCase() }} ({{ resiInput }})</span>
                                                <small>
                                                    <Truck class="icon-inline" />
                                                    <span>Status: {{ trackResult.status || 'Tercatat' }} • {{ trackResult.history?.[0]?.note || 'Paket dalam pemrosesan kurir' }}</span>
                                                </small>
                                            </div>
                                            <div class="result-price">
                                                <b style="color: #16a34a">{{ trackResult.status || 'Aktif' }}</b>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <section class="marquee" aria-label="Ekspedisi resmi terintegrasi">
            <div class="marquee-track">
                <div
                    v-for="(courier, index) in marqueeItems"
                    :key="`${courier.name}-${index}`"
                    class="marquee-item"
                >
                    <img
                        :src="courier.logo"
                        :alt="courier.name"
                        class="marquee-logo"
                        loading="lazy"
                    />
                </div>
            </div>
        </section>

        <section class="section audience-section">
            <div class="container audience-split-wrap">
                <div v-reveal class="audience-intro">
                    <p class="eyebrow">Target Pengguna</p>
                    <h2 class="title">Dirancang untuk siapa <em>SatuKirim hadir?</em></h2>
                    <p class="lead">Dibuat khusus untuk alur kerja nyata social commerce, toko independen, dan UMKM Indonesia.</p>
                </div>
                <div class="audience-list">
                    <article v-for="(item, i) in audiences" :key="item.title" v-reveal="i * 90" class="audience-item">
                        <div class="audience-body">
                            <div class="audience-title-row">
                                <component :is="item.icon" class="icon" />
                                <h3>{{ item.title }}</h3>
                            </div>
                            <p>{{ item.text }}</p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="section visual-showcase-section" aria-label="Solusi Fitur Utama">
            <div class="container">
                <div v-reveal class="head-left">
                    <p class="eyebrow">Solusi Fitur Utama</p>
                    <h2 class="title">Semua alur kirim barang, <em>dalam kendali praktis.</em></h2>
                    <p class="lead">Tiga kemudahan operasional yang langsung memangkas waktu kerja harian tokomu dari packing pesanan sampai barang tiba di pembeli.</p>
                </div>

                <div class="mockup-cards-grid">
                    <div v-reveal="0" class="mockup-card wa-mockup-card">
                        <div class="card-top-info">
                            <div class="card-feature-pill green">
                                <span class="dot-status green"></span>
                                <span>Notifikasi Otomatis</span>
                            </div>
                            <h3 class="card-feature-title">Kirim Resi ke WhatsApp 1-Klik</h3>
                            <p class="card-feature-sub">Pesan notifikasi rapi dan tautan live tracking instan langsung meluncur ke chat pembeli tanpa perlu ketik manual satu per satu.</p>
                        </div>

                        <div class="mockup-frame wa-phone-frame">
                            <div class="wa-app-header">
                                <div class="wa-avatar">
                                    <span>TK</span>
                                </div>
                                <div class="wa-contact-meta">
                                    <div class="wa-name-row">
                                        <strong>Toko Kamu Official</strong>
                                        <span class="wa-verified">✓</span>
                                    </div>
                                    <small class="wa-status-text">Online • Akun Terverifikasi</small>
                                </div>
                                <span class="wa-brand-pill">WhatsApp</span>
                            </div>

                            <div class="wa-chat-canvas">
                                <div class="wa-date-chip">Hari ini</div>
                                
                                <div class="wa-bubble-incoming">
                                    <p class="wa-text-line">Halo kak, pesanan baju katun saya sudah dikirim ya?</p>
                                    <span class="wa-time-stamp-buyer">14:28</span>
                                </div>

                                <div class="wa-bubble-bubble">
                                    <p class="wa-text-line">Halo Kak <strong>Sarah</strong>! Sudah diserahkan ke kurir ya 🙏📦</p>
                                    
                                    <div class="wa-resi-card-inner">
                                        <div class="wa-resi-card-head">
                                            <span class="courier-tag-sicepat">SiCepat REG</span>
                                            <span class="wa-resi-status-tag">Paket Dijemput</span>
                                        </div>
                                        <div class="wa-resi-data-row">
                                            <span class="wa-resi-label">Nomor Resi:</span>
                                            <code class="wa-resi-code">004281920194</code>
                                        </div>
                                        <div class="wa-track-url-box">
                                            <span class="wa-track-link-text">🔗 satukirim.id/lacak/004281920194</span>
                                        </div>
                                    </div>
                                    
                                    <div class="wa-bubble-bottom">
                                        <span class="wa-time-stamp">14:32</span>
                                        <CheckCheck class="wa-double-checks" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-reveal="100" class="mockup-card thermal-mockup-card">
                        <div class="card-top-info">
                            <div class="card-feature-pill berry">
                                <span class="dot-status berry"></span>
                                <span>Standar Ekspedisi A6</span>
                            </div>
                            <h3 class="card-feature-title">Thermal Label Siap Tempel</h3>
                            <p class="card-feature-sub">Format cetak 100×150 mm resmi dengan barcode tebal berstandar kurir. Anti basah, anti luntur, tinggal tempel di paket.</p>
                        </div>

                        <div class="mockup-frame thermal-sticker-frame">
                            <div class="thermal-sheet">
                                <div class="thermal-sheet-header">
                                    <div class="thermal-brand-badge">
                                        <span class="thermal-bold-logo">J&amp;T</span>
                                        <span class="thermal-srv-tag">EZ / REGULER</span>
                                    </div>
                                    <div class="thermal-drop-stamp">
                                        <span>PICK-UP</span>
                                    </div>
                                </div>

                                <div class="thermal-barcode-block">
                                    <div class="thermal-barcode-visual" aria-hidden="true">
                                        <span class="b-line w-2"></span>
                                        <span class="b-line w-1"></span>
                                        <span class="b-line w-3"></span>
                                        <span class="b-line w-1"></span>
                                        <span class="b-line w-4"></span>
                                        <span class="b-line w-2"></span>
                                        <span class="b-line w-1"></span>
                                        <span class="b-line w-3"></span>
                                        <span class="b-line w-2"></span>
                                        <span class="b-line w-4"></span>
                                        <span class="b-line w-1"></span>
                                        <span class="b-line w-3"></span>
                                        <span class="b-line w-2"></span>
                                        <span class="b-line w-1"></span>
                                        <span class="b-line w-4"></span>
                                        <span class="b-line w-2"></span>
                                        <span class="b-line w-3"></span>
                                        <span class="b-line w-1"></span>
                                        <span class="b-line w-2"></span>
                                        <span class="b-line w-4"></span>
                                        <span class="b-line w-1"></span>
                                        <span class="b-line w-3"></span>
                                        <span class="b-line w-2"></span>
                                    </div>
                                    <div class="thermal-barcode-digits">JT91823710839ID</div>
                                </div>

                                <div class="thermal-address-table">
                                    <div class="thermal-addr-cell receiver">
                                        <span class="addr-label">PENERIMA:</span>
                                        <strong class="addr-name">Sarah Amelia (0812-78xx-9011)</strong>
                                        <p class="addr-detail">Jl. Melati No. 14, RT 03/05, Gubeng, Surabaya [60281]</p>
                                    </div>
                                    <div class="thermal-addr-cell sender">
                                        <span class="addr-label">PENGIRIM:</span>
                                        <strong class="addr-name">Toko Busana Cantik</strong>
                                        <p class="addr-detail">Kota Bandung (0877-12xx-4567)</p>
                                    </div>
                                </div>

                                <div class="thermal-sheet-footer">
                                    <div class="thermal-meta-item">
                                        <span>Berat:</span>
                                        <strong>0.85 Kg</strong>
                                    </div>
                                    <div class="thermal-meta-item">
                                        <span>Ongkir:</span>
                                        <span class="lunas-badge">LUNAS</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-reveal="200" class="mockup-card tracking-mockup-card">
                        <div class="card-top-info">
                            <div class="card-feature-pill wine">
                                <span class="dot-status pulse-dot"></span>
                                <span>Real-Time Webhook</span>
                            </div>
                            <h3 class="card-feature-title">Live Tracking Paket Terpadu</h3>
                            <p class="card-feature-sub">Pantau alur status kiriman paket dari kurir jemput di tokomu hingga diterima pembeli secara real-time di satu layar.</p>
                        </div>

                        <div class="mockup-frame tracking-widget-frame">
                            <div class="tracking-top-bar">
                                <div class="tracking-pulse-icon">
                                    <span class="radar-dot"></span>
                                    <span class="radar-wave"></span>
                                </div>
                                <div class="tracking-headline">
                                    <strong>Kurir Menuju Alamat Penerima</strong>
                                    <small>Estimasi tiba: Hari ini sblm 17:00</small>
                                </div>
                                <span class="carrier-pill-mini">AnterAja</span>
                            </div>

                            <div class="tracking-timeline-flow">
                                <div class="flow-step current">
                                    <div class="step-marker">
                                        <span class="marker-dot current"></span>
                                        <span class="marker-line"></span>
                                    </div>
                                    <div class="step-body">
                                        <div class="step-title-row">
                                            <strong>Sedang diantar kurir (Budi S.)</strong>
                                            <span class="step-badge-now">Sekarang</span>
                                        </div>
                                        <p class="step-desc">Surabaya Barat • 13:45 WIB</p>
                                    </div>
                                </div>

                                <div class="flow-step past">
                                    <div class="step-marker">
                                        <span class="marker-dot past"><Check class="icon-step-check" /></span>
                                        <span class="marker-line"></span>
                                    </div>
                                    <div class="step-body">
                                        <strong class="step-title-done">Tiba di Sorting Hub Surabaya</strong>
                                        <p class="step-desc">Hub Logistik Surabaya • 08:20 WIB</p>
                                    </div>
                                </div>

                                <div class="flow-step past">
                                    <div class="step-marker">
                                        <span class="marker-dot past"><Check class="icon-step-check" /></span>
                                        <span class="marker-line"></span>
                                    </div>
                                    <div class="step-body">
                                        <strong class="step-title-done">Keluar dari Gateway Transit Jakarta</strong>
                                        <p class="step-desc">Gateway Logistik • Kemarin 22:40 WIB</p>
                                    </div>
                                </div>

                                <div class="flow-step past">
                                    <div class="step-marker">
                                        <span class="marker-dot past"><Check class="icon-step-check" /></span>
                                    </div>
                                    <div class="step-body">
                                        <strong class="step-title-done">Paket dijemput kurir dari tokomu</strong>
                                        <p class="step-desc">Pick-up Bandung • Kemarin 16:15 WIB</p>
                                    </div>
                                </div>
                            </div>

                            <div class="tracking-bottom-eta">
                                <Clock class="icon-inline" style="width: 0.85rem; height: 0.85rem; color: #10b981;" />
                                <span>Estimasi tiba hari ini sebelum 17:00 WIB</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section story-showcase">
            <div class="container showcase-grid">
                <div v-reveal class="showcase-content">
                    <p class="eyebrow">Solusi Nyata Pengiriman</p>
                    <h2 class="title">Fokus jualan produkmu, <em>biar kami yang urus kirimnya.</em></h2>
                    <p class="lead">
                        Bukan cuma membandingkan harga. SatuKirim menjembatani seluruh alur dari pencetakan resi sampai konfirmasi barang diterima oleh pembeli.
                    </p>
                    <div class="benefit-points">
                        <div class="benefit-item">
                            <span class="benefit-icon"><CheckCircle2 class="icon" /></span>
                            <div>
                                <h4>Efisiensi Waktu Operasional</h4>
                                <p>Dari pencetakan label hingga kurir tiba menjemput, seluruh alur packing harian tokomu beres jauh lebih cepat tanpa lembur.</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <span class="benefit-icon"><CheckCircle2 class="icon" /></span>
                            <div>
                                <h4>Gratis Jemput Tanpa Minimum Paket</h4>
                                <p>Kirim 1 paket saja kurir tetap datang menjemput ke depan pintu tokomu, tanpa biaya pick-up tambahan.</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <span class="benefit-icon"><CheckCircle2 class="icon" /></span>
                            <div>
                                <h4>Bebas Potongan Komisi Penjualan</h4>
                                <p>Didesain untuk pedagang mandiri. Seluruh keuntungan penjualan utuh untuk tokomu tanpa potongan admin marketplace 5–10%.(INGAT INGAT BLOM FIX)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-reveal="140" class="photo-collage-wrapper">
                    <div class="photo-collage-frame">
                        <figure class="photo-large">
                            <img
                                src="/images/landing/seller_packing.jpg"
                                alt="Pemilik UMKM mengemas paket pesanan pelanggan dengan rapi"
                                loading="lazy"
                            />
                        </figure>
                        <figure class="photo-floating-thumb">
                            <img
                                src="/images/landing/courier_pickup.jpg"
                                alt="Kurir logistik menjemput paket langsung di depan pintu toko"
                                loading="lazy"
                            />
                        </figure>

                        <div class="floating-live-chip">
                            <div class="chip-icon-box">
                                <Truck class="icon" />
                            </div>
                            <div class="chip-text">
                                <div class="chip-status-row">
                                    <span class="live-dot" />
                                    <strong>Kurir Menjemput</strong>
                                </div>
                                <small>SiCepat • Tiba dalam 8 menit</small>
                            </div>
                        </div>

                        <div class="floating-sticker-pill">
                            <Printer class="icon-inline" /> Resi Thermal 100×150 Siap Tempel!
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="perbandingan" class="section comparison-section">
            <div class="container">
                <div v-reveal class="head-center">
                    <h2 class="title light">
                        Masih kirim paket dengan<br class="title-br" />
                        cara <em>lama yang&nbsp;melelahkan?</em>
                    </h2>
                    <p class="lead" style="color: rgba(255, 255, 255, 0.8)">Lihat bagaimana SatuKirim memotong kerumitan operasional harian tokomu.</p>
                </div>
                <div class="comparison-grid">
                    <div v-reveal class="comparison-card bad">
                        <div class="card-top-header">
                            <h3 class="card-heading">Cara Konvensional</h3>
                            <p class="card-subheading bad">Ribet &amp; Melelahkan</p>
                        </div>
                        <ul class="comparison-list">
                            <li v-for="(item, i) in comparisons" :key="`bad-${i}`">
                                <XCircle class="icon-check bad" />
                                <span>{{ item.bad }}</span>
                            </li>
                        </ul>
                    </div>
                    <div v-reveal="100" class="comparison-card good">
                        <div class="card-top-header">
                            <h3 class="card-heading">Dengan SatuKirim</h3>
                            <p class="card-subheading good">Cepat &amp; Tenang</p>
                        </div>
                        <ul class="comparison-list">
                            <li v-for="(item, i) in comparisons" :key="`good-${i}`">
                                <CheckCircle2 class="icon-check good" />
                                <span>{{ item.good }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section id="fitur" class="section features">
            <div class="container">
                <div v-reveal class="head-split">
                    <div class="head-split-left">
                        <p class="eyebrow">Fitur Unggulan Platform</p>
                        <h2 class="title">Semua alat yang dibutuhkan pedagang <em>mandiri.</em></h2>
                    </div>
                    <div class="head-split-right">
                        <p class="lead">Dirancang khusus sesuai alur kerja nyata social commerce dan online store UMKM tanpa sistem yang rumit.</p>
                    </div>  
                </div>
                <div class="feature-grid">
                    <article v-for="(item, i) in features" :key="item.title" v-reveal="i * 70" class="feature">
                        <div class="feature-head-row">
                            <component :is="item.icon" class="icon" />
                            <h3>{{ item.title }}</h3>
                        </div>
                        <p>{{ item.text }}</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="testimoni" class="section testimonials-section">
            <div class="container">
                <div v-reveal class="head-split">
                    <div class="head-split-left">
                        <p class="eyebrow">Ulasan Pengguna</p>
                        <h2 class="title">Dibuat dari keresahan pedagang, <em>disukai oleh ribuan paket.</em></h2>
                    </div>
                    <div class="head-split-right">
                        <p class="lead">Inilah pengalaman teman-teman UMKM dan online seller yang memangkas kerumitan packing dan kelola resi pengiriman setiap hari.</p>
                        <div class="testi-trust-card">
                            <div class="trust-card-avatars">
                                <img
                                    src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=100&q=80"
                                    alt="Seller UMKM"
                                    class="mini-avatar"
                                    loading="lazy"
                                />
                                <img
                                    src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=100&q=80"
                                    alt="Seller UMKM"
                                    class="mini-avatar"
                                    loading="lazy"
                                />
                                <img
                                    src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=100&q=80"
                                    alt="Seller UMKM"
                                    class="mini-avatar"
                                    loading="lazy"
                                />
                                <span class="mini-avatar-badge">+1.2k</span>
                            </div>
                            <div class="trust-card-info">
                                <div class="stars-row">
                                    <Star v-for="s in 5" :key="s" class="star-icon mini" />
                                    <strong class="rating-score">4.9 / 5.0</strong>
                                </div>
                                <span class="rating-sub">Kepuasan 1.200+ seller aktif di seluruh Indonesia</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="testimonials-grid">
                    <article v-for="(item, i) in testimonials" :key="item.name" v-reveal="i * 100" class="testi-card">
                        <div class="testi-card-head">
                            <div class="testi-stars-row">
                                <Star v-for="s in item.rating" :key="s" class="star-icon mini" />
                            </div>
                        </div>

                        <blockquote class="testi-quote">
                            “{{ item.quote }}”
                        </blockquote>

                        <div class="testi-card-foot">
                            <div class="testi-author-row">
                                <img :src="item.avatar" :alt="item.name" class="testi-avatar" loading="lazy" />
                                <div class="testi-author-meta">
                                    <div class="testi-author-name">
                                        <strong>{{ item.name }}</strong>
                                        <span class="verified-dot" title="Seller Terverifikasi">
                                            <Check class="icon-tiny" />
                                        </span>
                                    </div>
                                    <p class="testi-author-role">{{ item.role }}</p>
                                </div>
                            </div>
                            <div class="testi-courier-chip">
                                <Truck class="chip-truck-icon" />
                                <span>Kurir Favorit: <strong>{{ item.courierPref }}</strong></span>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section id="faq" class="section faq-section">
            <div class="container faq-wrap">
                <div v-reveal>
                    <p class="eyebrow">Pertanyaan Populer</p>
                    <h2 class="title">Ada yang ingin <em>ditanyakan?</em></h2>
                    <p class="lead">Berikut jawaban atas pertanyaan yang paling sering diajukan sebelum mulai mengirim paket.</p>
                </div>
                <div v-reveal="100">
                    <div v-for="(item, i) in faqs" :key="item.q" class="faq-item" :class="{ open: openFaq === i }">
                        <button type="button" class="faq-q" :aria-expanded="openFaq === i" @click="toggleFaq(i)">
                            {{ item.q }}
                            <Plus class="icon" />
                        </button>
                        <div class="faq-a">
                            <div><p>{{ item.a }}</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="cta-wrap">
            <div v-reveal class="cta-panel">
                <div class="cta-grid">
                    <div class="cta-content">
                        <div class="cta-badge">
                            <span>Mulai Tanpa Komitmen</span>
                        </div>
                        <h2>Siap kirim paket pertamamu <em>hari ini?</em></h2>
                        <p class="sub">
                            Daftar akun gratis sekarang dalam 2 menit. Bandingkan ongkir hemat, terbitkan resi resmi, dan kurir ekspedisi siap jemput langsung ke depan pintu tokomu.
                        </p>
                        <div class="cta-actions">
                            <Link :href="register()" class="btn-pill btn-light">
                                <span>Daftar & Kirim Sekarang</span>
                                <ArrowRight class="btn-icon" />
                            </Link>
                            <Link :href="login()" class="btn-pill btn-outline">
                                Sudah punya akun? Masuk
                            </Link>
                        </div>
                    </div>

                    <div class="cta-visual-col">
                        <div class="cta-art-wrapper">
                            <img
                                src="/images/landing/cta_illustration.jpg"
                                alt="Ilustrasi Layanan Pengiriman Terpadu SatuKirim"
                                class="cta-art-img"
                                loading="lazy"
                            />
                            <div class="cta-floating-chip chip-top">
                                <div class="chip-icon-circle green">
                                    <CheckCircle2 class="chip-svg" />
                                </div>
                                <div class="chip-content">
                                    <strong>Kurir Siap Jemput</strong>
                                    <small>Tanpa antre ke gerai agen</small>
                                </div>
                            </div>
                            <div class="cta-floating-chip chip-bottom">
                                <div class="chip-icon-circle wine">
                                    <Truck class="chip-svg" />
                                </div>
                                <div class="chip-content">
                                    <strong>Resi Resmi Terbit</strong>
                                    <small>Live tracking instan via WA</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <footer class="site-footer">
            <div class="container footer-content-wrap">
                <div class="footer-grid">
                    <div class="footer-brand-wrap">
                        <a href="#" class="brand footer-brand group" @click="scrollToSection('layanan', $event)">
                            <AppLogo layout="horizontal" variant="white" size="lg" />
                        </a>

                        <p class="footer-desc">
                            Platform agregator logistik cerdas berbasis PWA untuk jutaan pelaku UMKM dan pebisnis digital Indonesia. Bandingkan tarif ongkir, cetak label resi otomatis, dan nikmati layanan jemput paket tanpa biaya tambahan.
                        </p>

                        <div class="footer-address-card">
                            <div class="footer-address-row">
                                <MapPin class="footer-address-icon" />
                                <div>
                                    <strong>Kantor Operasional & Hub Logistik:</strong>
                                    <span>Gedung Graha SatuKirim, Jl. Jenderal Sudirman Kav. 52-53, Kawasan Niaga SCBD, Senayan, Kebayoran Baru, Jakarta Selatan, DKI Jakarta 12190</span>
                                </div>
                            </div>
                            <div class="footer-contacts">
                                <div class="footer-contact-item">
                                    <Phone class="icon-tiny text-[#c98da4]" />
                                    <span>WhatsApp CS: <strong>+62 812-9900-5050</strong> (24/7)</span>
                                </div>
                                <div class="footer-contact-item">
                                    <Mail class="icon-tiny text-[#c98da4]" />
                                    <span>Email Dukungan: <strong>halo@satukirim.id</strong></span>
                                </div>
                                <div class="footer-contact-item">
                                    <Clock class="icon-tiny text-[#c98da4]" />
                                    <span>Operasional Pickup: <strong>Senin – Minggu, 08:00 – 21:00 WIB</strong></span>
                                </div>
                            </div>
                        </div>

                        <div class="footer-socials">
                            <span class="footer-social-title">Media Sosial & Komunitas UMKM</span>
                            <div class="social-buttons">
                                <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Instagram" title="Instagram @satukirim">
                                    <svg viewBox="0 0 24 24" class="social-svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="20" height="20" x="2" y="2" rx="5" ry="5"/>
                                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                                        <line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/>
                                    </svg>
                                </a>
                                <a href="https://wa.me/6281299005050" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="WhatsApp" title="WhatsApp Customer Service">
                                    <svg viewBox="0 0 24 24" class="social-svg" fill="currentColor">
                                        <path d="M17.472 14.382c-.301-.15-1.78-.878-2.056-.979-.276-.1-.476-.15-.676.15-.2.301-.776.979-.952 1.18-.175.2-.351.226-.652.075-.301-.15-1.272-.469-2.423-1.496-.896-.799-1.5-1.787-1.676-2.088-.175-.3-.019-.463.132-.613.136-.135.301-.351.451-.527.15-.175.2-.3.301-.501.101-.2.05-.376-.025-.526-.075-.15-.676-1.629-.927-2.231-.244-.587-.492-.507-.676-.516l-.576-.01c-.2 0-.526.075-.802.376-.276.301-1.053 1.028-1.053 2.507s1.078 2.908 1.229 3.109c.15.2 2.122 3.24 5.141 4.544.718.31 1.279.496 1.716.635.721.23 1.377.197 1.896.12.578-.087 1.78-.727 2.031-1.429.251-.702.251-1.304.176-1.429-.076-.126-.276-.201-.577-.352zm-5.462 7.618a9.96 9.96 0 0 1-5.088-1.393l-.365-.217-3.782.992 1.009-3.687-.238-.379a9.948 9.948 0 0 1-1.526-5.314c0-5.514 4.486-10 10-10 2.671 0 5.183 1.04 7.071 2.929A9.94 9.94 0 0 1 22 12.002c0 5.514-4.486 10-10 10z"/>
                                    </svg>
                                </a>
                                <a href="https://tiktok.com" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="TikTok" title="TikTok @satukirim.id">
                                    <svg viewBox="0 0 24 24" class="social-svg" fill="currentColor">
                                        <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.24 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                                    </svg>
                                </a>
                                <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="LinkedIn" title="LinkedIn SatuKirim">
                                    <svg viewBox="0 0 24 24" class="social-svg" fill="currentColor">
                                        <path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.28 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.75M6.46 10.9h2.79v8.37H6.46v-8.37M7.86 6.72a1.62 1.62 0 1 0 0 3.24 1.62 1.62 0 0 0 0-3.24z"/>
                                    </svg>
                                </a>
                                <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Facebook" title="Facebook SatuKirim">
                                    <svg viewBox="0 0 24 24" class="social-svg" fill="currentColor">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </a>
                                <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="YouTube" title="YouTube Channel">
                                    <svg viewBox="0 0 24 24" class="social-svg" fill="currentColor">
                                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="footer-nav-columns">
                        <div class="footer-col">
                            <h3 class="footer-col-title">
                                Layanan & Fitur
                            </h3>
                            <ul class="footer-col-links">
                                <li><a href="#layanan" @click="scrollToSection('layanan', $event)">Cek Tarif Ongkir Real-Time</a></li>
                                <li><a href="#layanan" @click="scrollToSection('layanan', $event)">Pelacakan Resi (Live AWB)</a></li>
                                <li><a href="#layanan" @click="scrollToSection('layanan', $event)">Booking Pickup Otomatis</a></li>
                                <li><a href="#fitur" @click="scrollToSection('fitur', $event)">Pengiriman Massal (Bulk)</a></li>
                                <li><a href="#fitur" @click="scrollToSection('fitur', $event)">Cetak Label Thermal 10x10</a></li>
                                <li><a href="#fitur" @click="scrollToSection('fitur', $event)">Layanan Bayar Ditempat (COD)</a></li>
                                <li><a href="#fitur" @click="scrollToSection('fitur', $event)">Asuransi & Proteksi Paket</a></li>
                                <li><a href="#fitur" @click="scrollToSection('fitur', $event)">Layanan Muatan Kargo</a></li>
                            </ul>
                        </div>

                        <div class="footer-col">
                            <h3 class="footer-col-title">
                                Ekspedisi Mitra
                            </h3>
                            <ul class="footer-col-links">
                                <li><a href="#layanan" @click="scrollToSection('layanan', $event)">JNE Express (Reg, YES, JTR)</a></li>
                                <li><a href="#layanan" @click="scrollToSection('layanan', $event)">J&T Express (EZ, Super, DOC)</a></li>
                                <li><a href="#layanan" @click="scrollToSection('layanan', $event)">SiCepat (SIUNTUNG, GOKIL)</a></li>
                                <li><a href="#layanan" @click="scrollToSection('layanan', $event)">AnterAja (Reguler, Cargo)</a></li>
                                <li><a href="#layanan" @click="scrollToSection('layanan', $event)">Ninja Xpress (Standard, COD)</a></li>
                                <li><a href="#layanan" @click="scrollToSection('layanan', $event)">Lion Parcel (REGPACK, ONEPACK)</a></li>
                                <li><a href="#layanan" @click="scrollToSection('layanan', $event)">Wahana Prestasi Logistik</a></li>
                                <li><a href="#layanan" @click="scrollToSection('layanan', $event)">POS Indonesia (Kilat Khusus)</a></li>
                            </ul>
                        </div>

                        <div class="footer-col">
                            <h3 class="footer-col-title">
                                Solusi Bisnis
                            </h3>
                            <ul class="footer-col-links">
                                <li><a href="#perbandingan" @click="scrollToSection('perbandingan', $event)">Toko Online & Social Seller</a></li>
                                <li><a href="#perbandingan" @click="scrollToSection('perbandingan', $event)">UMKM Fashion & Konveksi</a></li>
                                <li><a href="#perbandingan" @click="scrollToSection('perbandingan', $event)">Makanan Kering & Camilan</a></li>
                                <li><a href="#perbandingan" @click="scrollToSection('perbandingan', $event)">Dropshipper & Reseller</a></li>
                                <li><a href="#fitur" @click="scrollToSection('fitur', $event)">Program Diskon Ongkir UMKM</a></li>
                                <li><a href="#fitur" @click="scrollToSection('fitur', $event)">Integrasi Toko & Marketplace</a></li>
                                <li><a href="#fitur" @click="scrollToSection('fitur', $event)">API Pengiriman untuk Developer</a></li>
                                <li><a href="#fitur" @click="scrollToSection('fitur', $event)">Instal Aplikasi Web PWA</a></li>
                            </ul>
                        </div>

                        <div class="footer-col">
                            <h3 class="footer-col-title">
                                Bantuan & Legal
                            </h3>
                            <ul class="footer-col-links">
                                <li><a href="#faq" @click="scrollToSection('faq', $event)">Pusat Bantuan & Panduan</a></li>
                                <li><a href="#faq" @click="scrollToSection('faq', $event)">Panduan Packing Standar Ekspedisi</a></li>
                                <li><a href="#faq" @click="scrollToSection('faq', $event)">Tanya Jawab Populer (FAQ)</a></li>
                                <li><a href="#faq" @click="scrollToSection('faq', $event)">Prosedur Klaim Kerusakan/Kehilangan</a></li>
                                <li><a href="#faq" @click="scrollToSection('faq', $event)">Syarat & Ketentuan Layanan</a></li>
                                <li><a href="#faq" @click="scrollToSection('faq', $event)">Kebijakan Privasi & Data Pribadi</a></li>
                                <li><a href="#faq" @click="scrollToSection('faq', $event)">Tentang SatuKirim Indonesia</a></li>
                                <li><a href="#faq" @click="scrollToSection('faq', $event)">Hubungi Tim Customer Success</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="footer-bottom-bar">
                    <p class="copyright-text">
                        © {{ new Date().getFullYear() }} <strong>SatuKirim (PT SatuKirim Digital Nusantara)</strong> <br>Hak Cipta Dilindungi Undang-Undang. Rancang Bangun Agregator Logistik Berbasis PWA untuk UMKM Indonesia
                    </p>
                    <div class="footer-legal-links">
                        <a href="#faq" @click="scrollToSection('faq', $event)">Syarat & Ketentuan</a>
                        <span>•</span>
                        <a href="#faq" @click="scrollToSection('faq', $event)">Kebijakan Privasi</a>
                        <span>•</span>
                        <a href="#faq" @click="scrollToSection('faq', $event)">Keamanan Data</a>
                        </div>
                </div>
            </div>
        </footer>
    </main>
</template>

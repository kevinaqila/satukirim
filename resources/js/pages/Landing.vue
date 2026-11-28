<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Check,
    CheckCircle2,
    Menu,
    Plus,
    Printer,
    Sparkles,
    Star,
    Truck,
    X,
    XCircle,
} from '@lucide/vue';
import { ref } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import LandingFooter from '@/components/landing/LandingFooter.vue';
import LandingHeroWidget from '@/components/landing/LandingHeroWidget.vue';
import LandingVisualShowcase from '@/components/landing/LandingVisualShowcase.vue';
import {
    audiences,
    comparisons,
    faqs,
    features,
    marqueeItems,
    testimonials,
    vReveal,
} from '@/data/landing';
import { login, register } from '@/routes';
import '../../css/landing.css';

const menuOpen = ref(false);
const openFaq = ref<number | null>(0);
const heroWidgetRef = ref<InstanceType<typeof LandingHeroWidget> | null>(null);

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
        heroWidgetRef.value?.resetTab('ongkir');
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
                        Cek ongkir 8 ekspedisi, bayar QRIS atau COD, cetak label A6, dan pantau riwayat pengiriman dari satu aplikasi ringan di HP.
                    </p>
                    <div class="hero-cta">
                        <Link :href="register()" class="btn-pill btn-berry">Mulai Kirim Sekarang <ArrowRight class="icon" /></Link>
                        <a href="#perbandingan" class="btn-pill btn-outline" @click="scrollToSection('perbandingan', $event)">Pelajari Keunggulan</a>
                    </div>
                    <div class="hero-tags">
                        <span><Check class="icon" /> Biaya Flat Rp 1.000</span>
                        <span><Check class="icon" /> Bebas Komisi Persen</span>
                    </div>
                </div>

                <LandingHeroWidget ref="heroWidgetRef" />
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

        <LandingVisualShowcase />

        <section class="section story-showcase">
            <div class="container showcase-grid">
                <div v-reveal class="showcase-content">
                    <p class="eyebrow">Solusi Nyata Pengiriman</p>
                    <h2 class="title">Fokus jualan produkmu, <em>biar kami yang urus kirimnya.</em></h2>
                    <p class="lead">
                        Dari cetak resi hingga paket sampai, semua alur pengiriman tokomu tertata rapi.
                    </p>
                    <div class="benefit-points">
                        <div class="benefit-item">
                            <span class="benefit-icon"><CheckCircle2 class="icon" /></span>
                            <div>
                                <h4>Efisiensi Waktu Operasional</h4>
                                <p>Cetak label dan jadwalkan pickup kurir jauh lebih cepat tanpa catat manual.</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <span class="benefit-icon"><CheckCircle2 class="icon" /></span>
                            <div>
                                <h4>Gratis Jemput Tanpa Minimum Paket</h4>
                                <p>Kirim 1 paket pun kurir tetap datang menjemput langsung ke alamat tokomu.</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <span class="benefit-icon"><CheckCircle2 class="icon" /></span>
                            <div>
                                <h4>Bebas Potongan Komisi Penjualan</h4>
                                <p>Laba jualan utuh tanpa komisi persen marketplace, hanya biaya sistem flat Rp 1.000/paket.</p>
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
                                <small>SiCepat — Penjemputan Terjadwal</small>
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
                                    <small>Pantau real-time via webhook</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <LandingFooter @scroll-to="scrollToSection" />
    </main>
</template>

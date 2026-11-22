<script setup lang="ts">
import { Check, CheckCheck, Clock } from '@lucide/vue';
import { vReveal } from '@/data/landing';
</script>

<template>
    <section class="section visual-showcase-section" aria-label="Solusi Fitur Utama">
        <div class="container">
            <div v-reveal class="head-left">
                <p class="eyebrow">Solusi Fitur Utama</p>
                <h2 class="title">Semua alur kirim barang, <em>dalam kendali praktis.</em></h2>
                <p class="lead">Tiga kemudahan harian dari packing pesanan hingga paket diterima pembeli.</p>
            </div>

            <div class="mockup-cards-grid">
                <div v-reveal="0" class="mockup-card wa-mockup-card">
                    <div class="card-top-info">
                        <div class="card-feature-pill green">
                            <span class="dot-status green"></span>
                            <span>Integrasi Chat Pembeli</span>
                        </div>
                        <h3 class="card-feature-title">Hubungi Pembeli ke WhatsApp 1-Klik</h3>
                        <p class="card-feature-sub">Buka chat WhatsApp pembeli dari halaman detail pengiriman untuk mengirim resi secara praktis.</p>
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
                                <small class="wa-status-text">Online — Akun Terverifikasi</small>
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
                                        <span class="wa-track-link-text">🔗 Cek riwayat resi di: satukirim.id</span>
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
                        <p class="card-feature-sub">Format A6 (100×150 mm) berbarcode kurir, mendukung cetak satuan maupun Bulk Print sekaligus.</p>
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
                        <p class="card-feature-sub">Pantau status kiriman dari kurir jemput hingga paket diterima pembeli di satu layar.</p>
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
                                    <p class="step-desc">Surabaya Barat, 13:45 WIB</p>
                                </div>
                            </div>

                            <div class="flow-step past">
                                <div class="step-marker">
                                    <span class="marker-dot past"><Check class="icon-step-check" /></span>
                                    <span class="marker-line"></span>
                                </div>
                                <div class="step-body">
                                    <strong class="step-title-done">Tiba di Sorting Hub Surabaya</strong>
                                    <p class="step-desc">Hub Logistik Surabaya, 08:20 WIB</p>
                                </div>
                            </div>

                            <div class="flow-step past">
                                <div class="step-marker">
                                    <span class="marker-dot past"><Check class="icon-step-check" /></span>
                                    <span class="marker-line"></span>
                                </div>
                                <div class="step-body">
                                    <strong class="step-title-done">Keluar dari Gateway Transit Jakarta</strong>
                                    <p class="step-desc">Gateway Logistik, Kemarin 22:40 WIB</p>
                                </div>
                            </div>

                            <div class="flow-step past">
                                <div class="step-marker">
                                    <span class="marker-dot past"><Check class="icon-step-check" /></span>
                                </div>
                                <div class="step-body">
                                    <strong class="step-title-done">Paket dijemput kurir dari tokomu</strong>
                                    <p class="step-desc">Pick-up Bandung, Kemarin 16:15 WIB</p>
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
</template>


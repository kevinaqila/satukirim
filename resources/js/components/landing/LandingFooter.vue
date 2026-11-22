<script setup lang="ts">
import { Clock, Mail, MapPin, Phone } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';

const emit = defineEmits<{
    scrollTo: [sectionId: string, event: Event];
}>();
</script>

<template>
    <footer class="site-footer">
        <div class="container footer-content-wrap">
            <div class="footer-grid">
                <div class="footer-brand-wrap">
                    <a href="#" class="brand footer-brand group" @click="emit('scrollTo', 'layanan', $event)">
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
                                    <rect width="20" height="20" x="2" y="2" rx="5" ry="5" />
                                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                                    <line x1="17.5" x2="17.51" y1="6.5" y2="6.5" />
                                </svg>
                            </a>
                            <a href="https://wa.me/6281299005050" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="WhatsApp" title="WhatsApp Customer Service">
                                <svg viewBox="0 0 24 24" class="social-svg" fill="currentColor">
                                    <path d="M17.472 14.382c-.301-.15-1.78-.878-2.056-.979-.276-.1-.476-.15-.676.15-.2.301-.776.979-.952 1.18-.175.2-.351.226-.652.075-.301-.15-1.272-.469-2.423-1.496-.896-.799-1.5-1.787-1.676-2.088-.175-.3-.019-.463.132-.613.136-.135.301-.351.451-.527.15-.175.2-.3.301-.501.101-.2.05-.376-.025-.526-.075-.15-.676-1.629-.927-2.231-.244-.587-.492-.507-.676-.516l-.576-.01c-.2 0-.526.075-.802.376-.276.301-1.053 1.028-1.053 2.507s1.078 2.908 1.229 3.109c.15.2 2.122 3.24 5.141 4.544.718.31 1.279.496 1.716.635.721.23 1.377.197 1.896.12.578-.087 1.78-.727 2.031-1.429.251-.702.251-1.304.176-1.429-.076-.126-.276-.201-.577-.352zm-5.462 7.618a9.96 9.96 0 0 1-5.088-1.393l-.365-.217-3.782.992 1.009-3.687-.238-.379a9.948 9.948 0 0 1-1.526-5.314c0-5.514 4.486-10 10-10 2.671 0 5.183 1.04 7.071 2.929A9.94 9.94 0 0 1 22 12.002c0 5.514-4.486 10-10 10z" />
                                </svg>
                            </a>
                            <a href="https://tiktok.com" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="TikTok" title="TikTok @satukirim.id">
                                <svg viewBox="0 0 24 24" class="social-svg" fill="currentColor">
                                    <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.24 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z" />
                                </svg>
                            </a>
                            <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="LinkedIn" title="LinkedIn SatuKirim">
                                <svg viewBox="0 0 24 24" class="social-svg" fill="currentColor">
                                    <path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.28 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.75M6.46 10.9h2.79v8.37H6.46v-8.37M7.86 6.72a1.62 1.62 0 1 0 0 3.24 1.62 1.62 0 0 0 0-3.24z" />
                                </svg>
                            </a>
                            <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Facebook" title="Facebook SatuKirim">
                                <svg viewBox="0 0 24 24" class="social-svg" fill="currentColor">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                </svg>
                            </a>
                            <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="YouTube" title="YouTube Channel">
                                <svg viewBox="0 0 24 24" class="social-svg" fill="currentColor">
                                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="footer-nav-columns">
                    <div class="footer-col">
                        <h3 class="footer-col-title">Layanan & Fitur</h3>
                        <ul class="footer-col-links">
                            <li><a href="#layanan" @click="emit('scrollTo', 'layanan', $event)">Cek Tarif Ongkir Real-Time</a></li>
                            <li><a href="#layanan" @click="emit('scrollTo', 'layanan', $event)">Riwayat Pengiriman Resi (AWB)</a></li>
                            <li><a href="#layanan" @click="emit('scrollTo', 'layanan', $event)">Booking Kurir Jemput Gratis</a></li>
                            <li><a href="#fitur" @click="emit('scrollTo', 'fitur', $event)">Layanan Bayar Ditempat (COD)</a></li>
                            <li><a href="#fitur" @click="emit('scrollTo', 'fitur', $event)">Dompet Saldo & Pencairan Dana</a></li>
                            <li><a href="#fitur" @click="emit('scrollTo', 'fitur', $event)">Cetak Label Thermal A6 (Satuan & Massal)</a></li>
                            <li><a href="#fitur" @click="emit('scrollTo', 'fitur', $event)">Integrasi Chat WhatsApp Pembeli</a></li>
                            <li><a href="#fitur" @click="emit('scrollTo', 'fitur', $event)">Simpan Banyak Alamat Toko</a></li>
                        </ul>
                    </div>

                    <div class="footer-col">
                        <h3 class="footer-col-title">8 Ekspedisi Mitra Resmi</h3>
                        <ul class="footer-col-links">
                            <li><a href="#layanan" @click="emit('scrollTo', 'layanan', $event)">JNE Express (Reguler & YES)</a></li>
                            <li><a href="#layanan" @click="emit('scrollTo', 'layanan', $event)">J&T Express (EZ Reguler)</a></li>
                            <li><a href="#layanan" @click="emit('scrollTo', 'layanan', $event)">SiCepat Ekspres (SIUNTUNG & BEST)</a></li>
                            <li><a href="#layanan" @click="emit('scrollTo', 'layanan', $event)">AnterAja (Reguler & Next Day)</a></li>
                            <li><a href="#layanan" @click="emit('scrollTo', 'layanan', $event)">Ninja Xpress (Standard & COD)</a></li>
                            <li><a href="#layanan" @click="emit('scrollTo', 'layanan', $event)">Lion Parcel (REGPACK & ONEPACK)</a></li>
                            <li><a href="#layanan" @click="emit('scrollTo', 'layanan', $event)">POS Indonesia (Pos Reguler)</a></li>
                            <li><a href="#layanan" @click="emit('scrollTo', 'layanan', $event)">TIKI (Reguler Service & ONS)</a></li>
                        </ul>
                    </div>

                    <div class="footer-col">
                        <h3 class="footer-col-title">Solusi Bisnis UMKM</h3>
                        <ul class="footer-col-links">
                            <li><a href="#perbandingan" @click="emit('scrollTo', 'perbandingan', $event)">Toko Online & Social Seller</a></li>
                            <li><a href="#perbandingan" @click="emit('scrollTo', 'perbandingan', $event)">UMKM Fashion & Konveksi</a></li>
                            <li><a href="#perbandingan" @click="emit('scrollTo', 'perbandingan', $event)">Produk Kosmetik & Perawatan</a></li>
                            <li><a href="#perbandingan" @click="emit('scrollTo', 'perbandingan', $event)">Makanan Kering & Camilan</a></li>
                            <li><a href="#perbandingan" @click="emit('scrollTo', 'perbandingan', $event)">Dropshipper & Reseller</a></li>
                            <li><a href="#fitur" @click="emit('scrollTo', 'fitur', $event)">Bebas Potongan Komisi Persentase</a></li>
                            <li><a href="#fitur" @click="emit('scrollTo', 'fitur', $event)">Biaya Layanan Flat Rp 1.000</a></li>
                            <li><a href="#fitur" @click="emit('scrollTo', 'fitur', $event)">Aplikasi Web PWA Mobile</a></li>
                        </ul>
                    </div>

                    <div class="footer-col">
                        <h3 class="footer-col-title">Bantuan & Legal</h3>
                        <ul class="footer-col-links">
                            <li><a href="#faq" @click="emit('scrollTo', 'faq', $event)">Pusat Bantuan & Panduan</a></li>
                            <li><a href="#faq" @click="emit('scrollTo', 'faq', $event)">Panduan Packing Standar Ekspedisi</a></li>
                            <li><a href="#faq" @click="emit('scrollTo', 'faq', $event)">Tanya Jawab Populer (FAQ)</a></li>
                            <li><a href="#faq" @click="emit('scrollTo', 'faq', $event)">Syarat & Ketentuan Layanan</a></li>
                            <li><a href="#faq" @click="emit('scrollTo', 'faq', $event)">Kebijakan Privasi & Data Pribadi</a></li>
                            <li><a href="#faq" @click="emit('scrollTo', 'faq', $event)">Keamanan Transaksi & Data</a></li>
                            <li><a href="#faq" @click="emit('scrollTo', 'faq', $event)">Tentang SatuKirim Indonesia</a></li>
                            <li><a href="#faq" @click="emit('scrollTo', 'faq', $event)">Hubungi Customer Success</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="footer-bottom-bar">
                <p class="copyright-text">
                    © {{ new Date().getFullYear() }} <strong>SatuKirim (PT SatuKirim Digital Nusantara)</strong> <br>Hak Cipta Dilindungi Undang-Undang. Rancang Bangun Agregator Logistik Berbasis PWA untuk UMKM Indonesia
                </p>
                <div class="footer-legal-links">
                    <a href="#faq" @click="emit('scrollTo', 'faq', $event)">Syarat & Ketentuan</a>
                    <span>|</span>
                    <a href="#faq" @click="emit('scrollTo', 'faq', $event)">Kebijakan Privasi</a>
                    <span>|</span>
                    <a href="#faq" @click="emit('scrollTo', 'faq', $event)">Keamanan Data</a>
                </div>
            </div>
        </div>
    </footer>
</template>


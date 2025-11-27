import { Telegraf } from 'telegraf';
import axios from 'axios';
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

// --- KONFIGURASI PATH (Wajib untuk ES Module/Type Module) ---
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Load .env dari folder root (naik satu level)
dotenv.config({ path: path.resolve(__dirname, '../.env') });

// --- KONFIGURASI BOT ---
const token = process.env.TELEGRAM_BOT_TOKEN;
const apiUrl = process.env.APP_API_URL;
const apiSecret = process.env.BOT_API_SECRET;

// Cek Token
if (!token) {
    console.error('❌ ERROR: TELEGRAM_BOT_TOKEN belum diisi di file .env');
    process.exit(1);
}

const bot = new Telegraf(token);

// --- FUNGSI AMBIL DATA ---
const getWitelData = async () => {
    try {
        const response = await axios.get(`${apiUrl}/progress-witel`, {
            headers: { 'Authorization': apiSecret } // Kirim secret key
        });
        return response.data;
    } catch (error) {
        console.error('⚠️ Gagal ambil data API:', error.message);
        return null;
    }
};

// --- COMMAND: DIGITAL PRODUCT (HYBRID) ---
// Fitur 1: /dp KPI [Nama PO]
// Fitur 2: /dp [Witel] [Bulan] [Tahun]
bot.command('dp', async (ctx) => {
    // 1. Parsing Input Awal
    const args = ctx.message.text.split(' ');
    // args[0] = "/dp"
    // args[1] = Bisa "KPI", bisa "JATIM", bisa "BALI", dll.

    // ============================================================
    // MODE 1: CEK KPI PER PO (Account Officer)
    // Trigger: Kata kedua adalah "KPI" (Case Insensitive)
    // ============================================================
    if (args[1] && args[1].toUpperCase() === 'KPI') {
        // Ambil nama PO (gabungkan kata setelah "KPI")
        const poName = args.slice(2).join(' ');

        if (!poName) {
            return ctx.reply('⚠️ Nama PO belum dimasukkan.\nContoh: `/dp KPI Alfonsus`', { parse_mode: 'Markdown' });
        }

        ctx.reply(`🔍 Sedang mencari KPI PO: "${poName}"...`);

        try {
            // Request ke API Laravel
            const response = await axios.get(`${apiUrl}/bot/digital-product/kpi-po`, {
                params: { name: poName },
                headers: { 'Authorization': apiSecret }
            });

            const data = response.data.data;

            if (!data || data.length === 0) {
                return ctx.reply(`❌ PO dengan nama "${poName}" tidak ditemukan.`);
            }

            // Loop hasil (bisa jadi ada nama mirip, misal "Dwi")
            for (const item of data) {
                let msg = `👤 **KPI PO: ${item.name}**\n`;
                msg += `📍 Unit: ${item.witel || '-'}\n`;
                msg += `━━━━━━━━━━━━━━━━━━\n`;
                msg += `**PRODIGI DONE**\n`;
                msg += `   • NCX   : ${item.done_ncx}\n`;
                msg += `   • SCONE : ${item.done_scone}\n`;
                msg += `**PRODIGI OGP**\n`;
                msg += `   • NCX   : ${item.ogp_ncx}\n`;
                msg += `   • SCONE : ${item.ogp_scone}\n`;
                msg += `━━━━━━━━━━━━━━━━━━\n`;
                msg += `**TOTAL : ${item.total} Order**\n`;

                // Indikator Warna ACH (Optional aesthetic)
                const iconYtd = item.ach_ytd >= 100 ? '🟢' : (item.ach_ytd >= 90 ? '🟡' : '🔴');
                const iconQ3  = item.ach_q3 >= 100 ? '🟢' : (item.ach_q3 >= 90 ? '🟡' : '🔴');

                msg += `**ACH YTD : ${item.ach_ytd}%** ${iconYtd}\n`;
                msg += `**ACH Q3  : ${item.ach_q3}%** ${iconQ3}\n`;

                await ctx.replyWithMarkdown(msg);
            }

        } catch (error) {
            console.error('Error KPI PO:', error.message);
            ctx.reply('❌ Gagal mengambil data KPI PO. Pastikan API Server aktif.');
        }

        return; // BERHENTI DI SINI (Jangan jalankan kode Witel di bawah)
    }

    // ============================================================
    // MODE 2: CEK PROGRESS WITEL (OGP & REVENUE)
    // Trigger: Kata kedua BUKAN "KPI" (Default)
    // ============================================================

    // Logic Parsing Witel, Bulan, Tahun (Smart Parsing)
    let year = new Date().getFullYear(); // Default Tahun Ini
    let lastArg = args[args.length - 1];

    // Cek apakah argumen terakhir adalah Tahun (4 digit angka)
    if (lastArg && lastArg.length === 4 && !isNaN(lastArg)) {
        year = args.pop();
    }

    let month = new Date().getMonth() + 1; // Default Bulan Ini
    lastArg = args[args.length - 1]; // Cek lagi argumen terakhir setelah tahun diambil

    // Cek apakah argumen terakhir sekarang adalah Bulan (1-2 digit angka)
    if (lastArg && lastArg.length <= 2 && !isNaN(lastArg)) {
        month = args.pop();
    }

    // Sisa argumen adalah Nama Witel
    const witelName = args.slice(1).join(' ').toUpperCase();

    if (!witelName) {
        return ctx.reply('⚠️ Perintah tidak dikenali atau Nama Witel kosong.\nGunakan:\n1. `/dp KPI [Nama PO]`\n2. `/dp [Nama Witel] [Bulan] [Tahun]`', { parse_mode: 'Markdown' });
    }

    ctx.reply(`Mengambil data Progress Digital Product...\n ${witelName} | ${month}-${year}`);

    try {
        // Request ke API Laravel
        const response = await axios.get(`${apiUrl}/bot/digital-product/progress`, {
            params: { witel: witelName, month: month, year: year },
            headers: { 'Authorization': apiSecret }
        });

        const r = response.data;
        const d = r.data;

        // Validasi jika data kosong/error dari logic backend
        if (!d) {
             return ctx.reply('❌ Data tidak ditemukan atau format Witel salah.');
        }

        // Format Pesan agar Rapi
        let msg = `📊 **REPORT DIGITAL PRODUCT**\n`;
        msg += `📍 **${r.witel}**\n`;
        msg += `🗓 **${r.period_text}**\n`;
        msg += `========================\n`;

        // Helper Function untuk format baris
        const fmt = (lbl, key, icon) => {
            const row = d[key];
            if(!row) return ''; // Jaga-jaga jika key tidak ada
            return `${icon} *${lbl}*\n` +
                   `   ├ OGP  : ${row.ogp}\n` +
                   `   ├ Done : ${row.closed}\n` +
                   `   └ Rev  : ${row.revenue} Jt\n`;
        };

        msg += fmt('Netmonk', 'Netmonk', '');
        msg += fmt('OCA', 'OCA', '');
        msg += fmt('Antares', 'Antares', '');
        msg += fmt('Pijar', 'Pijar', '');
        msg += fmt('Lainnya', 'Lainnya', ''); // Tambahan jika ada produk lain

        msg += `========================\n`;

        // Hitung Grand Total
        let totalOGP = (d.Netmonk?.ogp || 0) + (d.OCA?.ogp || 0) + (d.Antares?.ogp || 0) + (d.Pijar?.ogp || 0) + (d.Lainnya?.ogp || 0);
        let totalRev = (d.Netmonk?.revenue || 0) + (d.OCA?.revenue || 0) + (d.Antares?.revenue || 0) + (d.Pijar?.revenue || 0) + (d.Lainnya?.revenue || 0);

        msg += `**TOTAL OGP: ${totalOGP}**\n`;
        msg += `**TOTAL REV: ${totalRev.toFixed(2)} Jt**`;

        ctx.replyWithMarkdown(msg);

    } catch (error) {
        console.error('Error Witel Progress:', error.message);
        // Cek spesifik error 404/500
        if (error.response && error.response.status === 500) {
            ctx.reply('❌ Terjadi kesalahan di Server Laravel (Error 500). Cek log backend.');
        } else {
            ctx.reply('❌ Gagal mengambil data. Pastikan Nama Witel benar.');
        }
    }
});

// --- COMMAND: ANALYSIS JT (4 FITUR LENGKAP) ---
bot.command('jt', async (ctx) => {
    const text = ctx.message.text.trim();
    const upperText = text.toUpperCase();

    // ============================================================
    // MODE 1: CEK NON GO LIVE (TOC REPORT)
    // Command: /jt NON GO LIVE [Witel]
    // ============================================================
    if (upperText.includes('NON GO LIVE')) {
        const witelName = upperText.replace('/JT', '').replace('NON GO LIVE', '').trim();
        if (!witelName) return ctx.reply('⚠️ Harap masukkan nama Witel.\nContoh: `/jt NON GO LIVE BALI`', { parse_mode: 'Markdown' });

        ctx.reply(`📉 Menganalisis TOC Project NON GO LIVE: **${witelName}**...`, { parse_mode: 'Markdown' });

        try {
            const response = await axios.get(`${apiUrl}/bot/jt/non-golive`, {
                params: { witel: witelName }, headers: { 'Authorization': apiSecret }
            });

            if (!response.data.found) return ctx.reply(`❌ Data tidak ditemukan untuk "**${witelName}**".`);

            let msg = `🚨 **PROJECT BELUM GO LIVE (TOC)**\n📍 Induk: **${witelName}**\n━━━━━━━━━━━━━━━━━━\n`;
            let grandTotalLop = 0;
            let grandTotalDalam = 0;

            response.data.data.forEach(item => {
                const anak = item.witel_anak.replace('WITEL ', '');
                const total = parseInt(item.dalam_toc) + parseInt(item.lewat_toc);
                const persen = total > 0 ? ((item.dalam_toc / total) * 100).toFixed(1) : 0;
                const icon = persen >= 80 ? '🟢' : (persen >= 50 ? '🟡' : '🔴');

                msg += `🏙 **${anak}**\n   Dalam: ${item.dalam_toc} | ⚠️ Lewat: ${item.lewat_toc}\n   📊 % Dalam: ${persen}% ${icon} (Total: ${total})\n------------------\n`;
                grandTotalLop += total;
                grandTotalDalam += parseInt(item.dalam_toc);
            });
            const grandPersen = grandTotalLop > 0 ? ((grandTotalDalam / grandTotalLop) * 100).toFixed(1) : 0;
            msg += `📈 **TOTAL SUCCESS RATE: ${grandPersen}%**`;
            await ctx.replyWithMarkdown(msg);
        } catch (error) { ctx.reply('❌ Gagal mengambil data.'); }
        return;
    }

    // ============================================================
    // MODE 2: TOP 3 PROGRESS BY WITEL
    // Command: /jt TOP 3 PROGRESS [Witel]
    // ============================================================
    if (upperText.includes('TOP 3 PROGRESS')) {
        const witelName = upperText.replace('/JT', '').replace('TOP 3 PROGRESS', '').trim();
        if (!witelName) return ctx.reply('⚠️ Harap masukkan nama Witel.\nContoh: `/jt TOP 3 PROGRESS BALI`', { parse_mode: 'Markdown' });

        ctx.reply(`Mengambil Top 3 Project (Witel): **${witelName}**...`, { parse_mode: 'Markdown' });

        try {
            const response = await axios.get(`${apiUrl}/bot/jt/top3-progress`, {
                params: { witel: witelName }, headers: { 'Authorization': apiSecret }
            });

            if (!response.data.found) return ctx.reply(`❌ Tidak ada project On Progress di "**${witelName}**".`);

            let msg = `**TOP 3 PROJEK ON PROGRESS (WITEL)**\n📍 Witel: **${witelName}**\n━━━━━━━━━━━━━━━━━━\n`;
            response.data.data.forEach((item, index) => {
                msg += `${index + 1}. **${item.nama_project}**\n   🆔 ${item.ihld} | 📅 ${item.tgl_mom}\n   💰 ${item.revenue}\n   🚧 ${item.status_tomps}\n   ⚠️ **Usia: ${item.usia_hari} Hari**\n\n`;
            });
            await ctx.replyWithMarkdown(msg);
        } catch (error) { ctx.reply('❌ Gagal mengambil data Top 3 Witel.'); }
        return;
    }

    // ============================================================
    // MODE 3: TOP 3 PROGRESS BY PO (FITUR BARU)
    // Command: /jt TOP 3 [Nama PO]
    // ============================================================
    // Kita cek jika mengandung "TOP 3" TAPI TIDAK mengandung "PROGRESS"
    if (upperText.includes('TOP 3') && !upperText.includes('PROGRESS')) {
        const poName = upperText.replace('/JT', '').replace('TOP 3', '').trim();
        if (!poName) return ctx.reply('⚠️ Harap masukkan nama PO.\nContoh: `/jt TOP 3 ANDRE YANA`', { parse_mode: 'Markdown' });

        ctx.reply(`👤 Mengambil Top 3 Project Tertua (PO): **"${poName}"**...`, { parse_mode: 'Markdown' });

        try {
            const response = await axios.get(`${apiUrl}/bot/jt/top3-po`, {
                params: { name: poName }, headers: { 'Authorization': apiSecret }
            });

            if (!response.data.found) return ctx.reply(`❌ Tidak ada project On Progress untuk PO "**${poName}**".`);

            // Ambil nama lengkap PO dari data pertama
            const realName = response.data.data[0].po_name;

            let msg = `👤 **TOP 3 PROJEK ON PROGRESS (PO)**\n👮‍♂️ PO: **${realName}**\n━━━━━━━━━━━━━━━━━━\n`;
            response.data.data.forEach((item, index) => {
                msg += `${index + 1}. **${item.nama_project}**\n   🆔 ${item.ihld} | 📅 ${item.tgl_mom}\n   💰 ${item.revenue}\n   🚧 ${item.status_tomps}\n   ⚠️ **Usia: ${item.usia_hari} Hari**\n\n`;
            });
            await ctx.replyWithMarkdown(msg);
        } catch (error) {
            console.error(error);
            ctx.reply('❌ Gagal mengambil data Top 3 PO.');
        }
        return;
    }

    // ============================================================
    // MODE 4: PROGRESS DEPLOY (DEFAULT)
    // Command: /jt [Witel]
    // ============================================================
    const args = ctx.message.text.split(' ');
    const witelName = args.slice(1).join(' ').toUpperCase();

    if (!witelName) {
        return ctx.reply(
            '⚠️ **MENU JT ANALYSIS**\n\n' +
            '1️⃣ **Progress Deploy:**\n`/jt [Nama Witel]`\n\n' +
            '2️⃣ **Status TOC (Non Go Live):**\n`/jt NON GO LIVE [Nama Witel]`\n\n' +
            '3️⃣ **Top 3 By Witel:**\n`/jt TOP 3 PROGRESS [Nama Witel]`\n\n' +
            '4️⃣ **Top 3 By PO:**\n`/jt TOP 3 [Nama PO]`',
            { parse_mode: 'Markdown' }
        );
    }

    ctx.reply(`🔍 Menganalisis Progress Deploy: **${witelName}**...`, { parse_mode: 'Markdown' });

    try {
        const response = await axios.get(`${apiUrl}/bot/jt/progress`, {
            params: { witel: witelName }, headers: { 'Authorization': apiSecret }
        });

        if (!response.data.found) return ctx.reply(`❌ Data tidak ditemukan untuk "**${witelName}**".`);

        let msg = `📊 **ANALYSIS JT REPORT (DEPLOY)**\n📍 Witel: **${witelName}**\n━━━━━━━━━━━━━━━━━━\n`;
        let grandTotalGoLive = 0;
        response.data.data.forEach(item => {
            const anak = item.witel_anak.replace('WITEL ', '');
            msg += `**${anak}**\n ├ Initial: ${item.initial} \n ├ Survey & DRM: ${item.survey_drm}\n ├ Perizinan & MOS: ${item.perizinan_mos} \n ├ Instalasi: ${item.instalasi}\n ├ FI-OGP Live: ${item.fi_ogp_live} \n └ **GO LIVE: ${item.golive}** | Drop: ${item.drop}\n------------------\n`;
            grandTotalGoLive += parseInt(item.golive);
        });
        msg += `**TOTAL GO LIVE: ${grandTotalGoLive}**`;
        await ctx.replyWithMarkdown(msg);
    } catch (error) { ctx.reply('❌ Terjadi kesalahan saat mengambil data.'); }
});

// --- COMMAND: DATIN REPORT (AOMO & SODORO) ---
bot.command('datin', async (ctx) => {
    const args = ctx.message.text.split(' ').filter(a => a); // Filter untuk buang spasi kosong
    const segment = args[1] ? args[1].toUpperCase() : null;
    const witel = args[2] ? args[2].toUpperCase() : 'ALL';

    if (!segment) {
        return ctx.reply('⚠️ Harap masukkan segmen atau perintah yang valid.\nContoh:\n1. `/datin SME BALI`\n2. `/datin GALAKSI [Nama PO]`', { parse_mode: 'Markdown' });
    }

    // ============================================================
    // MODE 2: CEK REPORT GALAKSI PO
    // Trigger: Kata kedua adalah "GALAKSI"
    // ============================================================
    if (segment === 'GALAKSI') {
        const poName = args.slice(2).join(' ');
        if (!poName) {
            return ctx.reply('⚠️ Nama PO untuk Galaksi belum dimasukkan.\nContoh: `/datin GALAKSI ALFONSUS`', { parse_mode: 'Markdown' });
        }

        ctx.reply(`🔍 Sedang mencari data Galaksi SOS untuk PO: **${poName}**...`, { parse_mode: 'Markdown' });

        try {
            const response = await axios.get(`${apiUrl}/bot/datin/galaksi-po`, {
                params: { name: poName },
                headers: { 'Authorization': apiSecret }
            });

            const data = response.data.data;

            if (!response.data.found || data.length === 0) {
                return ctx.reply(`❌ Data Galaksi untuk PO "${poName}" tidak ditemukan.`);
            }

            let msg = `**REPORT GALAKSI SOS**\n`;
            msg += `========================\n`;

            for (const item of data) {
                const totalLt3 = item.ao_lt_3bln + item.so_lt_3bln + item.do_lt_3bln + item.mo_lt_3bln + item.ro_lt_3bln;
                const totalGt3 = item.ao_gt_3bln + item.so_gt_3bln + item.do_gt_3bln + item.mo_gt_3bln + item.ro_gt_3bln;

                msg += `👤 **${item.po}**\n`;
                msg += `━━━━━━━━━━━━━━━━━━\n`;

                msg += `🟢 **< 3 BLN (Total: ${totalLt3})**\n`;
                msg += `   ├ AO: ${item.ao_lt_3bln} | SO: ${item.so_lt_3bln}\n`;
                msg += `   ├ DO: ${item.do_lt_3bln} | MO: ${item.mo_lt_3bln} | RO: ${item.ro_lt_3bln}\n`;

                msg += `🔴 **> 3 BLN (Total: ${totalGt3})**\n`;
                msg += `   └ AO: ${item.ao_gt_3bln} | SO: ${item.so_gt_3bln}\n`;
                msg += `     DO: ${item.do_gt_3bln} | MO: ${item.mo_gt_3bln} | RO: ${item.ro_gt_3bln}\n`;

                const iconAch = item.achievement === '100%' ? '🏆' : (parseFloat(item.achievement) >= 80 ? '👍' : '⚠️');
                msg += `✨ **ACHIEVEMENT: ${item.achievement}** ${iconAch}\n`;
                msg += `========================\n`;
            }

            await ctx.replyWithMarkdown(msg);
            return; // Berhenti di sini
        } catch (error) {
            console.error('Error Galaksi PO:', error.message);
            return ctx.reply('❌ Gagal mengambil data Galaksi PO. Pastikan API Server aktif.');
        }
    }


    // ============================================================
    // MODE 1: CEK REPORT DATIN STANDAR (AOMO & SODORO)
    // (Logika ini tetap sama)
    // ============================================================

    // Tentukan pesan loading
    const filterText = witel !== 'ALL' ? `(Hanya Witel: **${witel}**)` : '(Semua Witel)';
    ctx.reply(`📉 Sedang mengambil Report Datin (AOMO & SODORO) untuk Segmen: **${segment}** ${filterText}...`, { parse_mode: 'Markdown' });

    try {
        const response = await axios.get(`${apiUrl}/bot/datin/report`, {
            params: {
                segment: segment,
                witel: witel
            },
            headers: { 'Authorization': apiSecret }
        });

        const r = response.data;
        if (!r.found) {
            return ctx.reply(`❌ Segmen "${segment}" tidak ditemukan atau tidak valid.`);
        }

        if (witel !== 'ALL' && r.data.length === 0) {
            return ctx.reply(`❌ Data untuk Segmen ${segment} di Witel **${witel}** kosong.`, { parse_mode: 'Markdown' });
        }

        let msg = `📊 **REPORT DATIN: ${r.segment}** ${filterText}\n`;
        msg += `(Prov: Provide | Proc: Process | Bill: Ready Bill)\n`;
        msg += `========================\n`;

        // Logic formatting
        r.data.forEach(item => {
            msg += `📍 **${item.witel}**\n`;

            // AOMO
            msg += `💰 **AOMO (Revenue dlm Juta)**\n`;
            msg += `   📌 **<3 Bln**: Total ${item.aomo.less.total}\n`;
            msg += `      Prov: ${item.aomo.less.prov} | Proc: ${item.aomo.less.proc} | Bill: ${item.aomo.less.bill}\n`;
            msg += `   ⚠️ **>3 Bln**: Total ${item.aomo.more.total}\n`;
            msg += `      Prov: ${item.aomo.more.prov} | Proc: ${item.aomo.more.proc} | Bill: ${item.aomo.more.bill}\n`;

            // SODORO
            msg += `📦 **SODORO (Jumlah Order)**\n`;
            msg += `   📌 **<3 Bln**: Total ${item.sodoro.less.total}\n`;
            msg += `      Prov: ${item.sodoro.less.prov} | Proc: ${item.sodoro.less.proc} | Bill: ${item.sodoro.less.bill}\n`;
            msg += `   ⚠️ **>3 Bln**: Total ${item.sodoro.more.total}\n`;
            msg += `      Prov: ${item.sodoro.more.prov} | Proc: ${item.sodoro.more.proc} | Bill: ${item.sodoro.more.bill}\n`;

            msg += `------------------------\n`;
        });

        // Hapus garis terakhir jika ada
        msg = msg.replace(/------------------------\n$/, '');

        // Handle Telegram limit (4096 char)
        if (msg.length > 4000) {
            const chunks = msg.match(/.{1,4000}/g);
            for (const chunk of chunks) {
                await ctx.replyWithMarkdown(chunk);
            }
        } else {
            await ctx.replyWithMarkdown(msg);
        }

    } catch (error) {
        console.error('Error Datin:', error.message);
        ctx.reply('❌ Gagal mengambil data Datin. Cek server.');
    }
});

// --- COMMANDS ---
bot.start((ctx) => ctx.reply('Selamat datang di bot RSO 2 Telkom! Ketik /help untuk melihat command yang tersedia.'));

bot.command('help', (ctx) => {
    // ESCAPE SEMUA KARAKTER RESERVED UNTUK MARKDOWNV2:
    // * (bold), _ (italic), ` (code), [ ] (link), ( ) (link), ~ (strikethrough), > (quote), # (header), + (list), - (list), = (header), | (table), { } (inline link), . (dot), ! (exclamation)
    let helpMessage = `
*Pusat Bantuan NASWatchBot*
\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-

*Perintah Dasar & Umum:*
/start \\- Mulai ulang bot dan lihat menu utama\\.
/help \\- Menampilkan daftar perintah ini\\.

\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-

*Digital Product \\(DP\\) Analysis:*
/dp \\[nama witel\\] \\[bulan\\] \\[tahun\\]
     Contoh: /dp BALI 7 2025
     \\- Mengecek status OGP, Prov Complete, dan Revenue \\(Rp Juta\\) produk\\.

/dp KPI \\[nama PO\\]
     Contoh: /dp KPI Alfonsus
     \\- Mengecek rincian performa \\(NCX/Scone\\) dan Achievement \\(ACH\\) untuk Account Officer \\.

\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-

*Jaringan Tambahan \\(JT\\) Analysis:*
/jt \\[witel induk\\]
     \\- Mengecek progress deploy \\(Initial, Survey, Instalasi, Go Live, Drop\\) per witel anak\\.

/jt NON GO LIVE \\[witel\\]
     \\- Mengecek status proyek yang lewat TOC \\(Dalam TOC vs Lewat TOC\\)\\.

/jt TOP 3 PROGRESS \\[witel\\]
     \\- Mengecek 3 proyek tertua yang masih On Progress \\(berdasarkan Witel\\)\\.

/jt TOP 3 \\[nama PO\\]
     \\- Mengecek 3 proyek tertua yang masih On Progress \\(berdasarkan PO\\)\\.

\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-\\-

*Datin \\(SOS\\) Analysis:*
/datin \\[segmen\\] \\[witel/ALL\\]
     Contoh: /datin GOV ALL
     \\- Mengecek laporan AOMO \\(Revenue\\) dan SODORO \\(Count\\) per witel, dibagi berdasarkan usia order \\(\\<3 Bln vs \\>3 Bln\\)\\.
`;
    // Trim untuk menghilangkan spasi/newline di awal dan akhir
    ctx.reply(helpMessage.trim(), { parse_mode: 'MarkdownV2' });
});

bot.command('cek', async (ctx) => {
    ctx.reply('Sedang mengambil data server...');

    const data = await getWitelData();

    if (!data) {
        return ctx.reply('❌ Gagal terhubung ke Laravel. Pastikan "php artisan serve" jalan.');
    }

    // Format pesan (Sesuaikan dengan format JSON dari Laravel Anda)
    let message = '📊 **PROGRESS WITEL**\n\n';

    // Jika data adalah array (banyak witel)
    if (Array.isArray(data)) {
        data.forEach(item => {
            // Pastikan nama field sesuai dengan JSON response Laravel Anda
            const nama = item.witel || 'Tanpa Nama';
            const progress = item.fi_ogp_live || 0;
            message += `📍 **${nama}**\n   🚀 Live: ${progress}\n\n`;
        });
    } else {
        // Jika data cuma 1 objek atau format lain
        message += JSON.stringify(data);
    }

    ctx.replyWithMarkdown(message);
});

console.log('🤖 Bot Telegram Berjalan...');
bot.launch();

// Graceful Stop
process.once('SIGINT', () => bot.stop('SIGINT'));
process.once('SIGTERM', () => bot.stop('SIGTERM'));

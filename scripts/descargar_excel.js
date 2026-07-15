const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

(async () => {
    let browser;

    try {
        const downloadPath = path.resolve(__dirname, 'downloads');

        // Crear carpeta si no existe
        if (!fs.existsSync(downloadPath)) {
            fs.mkdirSync(downloadPath, { recursive: true });
        }

        for (const file of fs.readdirSync(downloadPath)) {
            if (file.endsWith('.xls') || file.endsWith('.xlsx') || file.endsWith('.crdownload')) {
                fs.unlinkSync(path.join(downloadPath, file));
            }
        }

        browser = await puppeteer.launch({
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox']
        });

        const page = await browser.newPage();

        // Permitir descargas
        const client = await page.target().createCDPSession();
        await client.send('Page.setDownloadBehavior', {
            behavior: 'allow',
            downloadPath: downloadPath,
        });

        await page.goto('https://cargapanel.cargacar.com/envios/listado.php', {
            waitUntil: 'networkidle2',
            timeout: 60000
        });

        // Login
        await page.waitForSelector('input[name="username"]');
        await page.waitForSelector('input[name="password"]');

        await page.type('input[name="username"]', 'francisco.aguilar');
        await page.type('input[name="password"]', 'Abril-2026');

        await Promise.all([
            page.click('button[type="submit"]'),
            page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 60000 })
        ]);

        await page.waitForSelector('#tblDatos');

        
        const hoy = new Date();
        const ayer = new Date();
        ayer.setDate(hoy.getDate() - 1);

        const formatFecha = (fecha) => {
            const dia = String(fecha.getDate()).padStart(2, '0');
            const mes = String(fecha.getMonth() + 1).padStart(2, '0');
            const anio = fecha.getFullYear();
            return `${dia}/${mes}/${anio}`;
        };

        const fechaHoy = formatFecha(hoy);
        const fechaAyer = formatFecha(ayer);

        await page.evaluate((fechaAyer, fechaHoy) => {

            const from = document.querySelector('#from');
            const to = document.querySelector('#to');

            if (from) from.value = fechaAyer;
            if (to) to.value = fechaHoy;

            const select = document.querySelector('#cbNumero');
            if (select) {
                select.value = 'ALL';
            }

            // Recargar tabla
            if (typeof load === 'function') {
                load(1);
            }

        }, fechaAyer, fechaHoy);

        // Esperar recarga
        await new Promise(resolve => setTimeout(resolve, 3000));

        await page.waitForFunction(() => {
            const rows = document.querySelectorAll('#tblDatos tbody tr');
            return rows.length > 0;
        });

        // Descargar Excel
        await page.waitForSelector('button[onclick*="tableToExcel"]');
        await page.click('button[onclick*="tableToExcel"]');

        const start = Date.now();
        const timeout = 20000;
        let downloadedFile = null;

        while (Date.now() - start < timeout) {
            const files = fs.readdirSync(downloadPath);

            const file = files.find(f =>
                (f.endsWith('.xls') || f.endsWith('.xlsx')) &&
                !f.endsWith('.crdownload')
            );

            if (file) {
                downloadedFile = path.join(downloadPath, file);
                break;
            }

            await new Promise(resolve => setTimeout(resolve, 500));
        }

        if (!downloadedFile) {
            throw new Error('No se descargó el Excel');
        }

        const fechaArchivo = new Date().toISOString().split('T')[0];
        const finalPath = path.join(downloadPath, `envios_${fechaArchivo}.xls`);

        fs.renameSync(downloadedFile, finalPath);


        await browser.close();
        process.exit(0);

    } catch (error) {
        if (browser) await browser.close();
        process.exit(1);
    }
})(); 
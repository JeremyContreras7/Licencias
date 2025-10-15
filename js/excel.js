class ExcelExporter {
    constructor() {
        this.initEventListeners();
    }

    initEventListeners() {
        // Botón para exportar inventario a Excel
        document.getElementById('exportInventarioExcel')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.exportarInventario();
        });

        // Si tienes botón para licencias
        document.getElementById('exportLicenciasExcel')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.exportarLicencias();
        });
    }

    exportarInventario() {
        this.mostrarCarga('Generando Excel...');
        
        const establecimiento = document.getElementById('filtroEstablecimiento')?.value || '';
        let url = 'export/exportar_excel.php';
        
        if (establecimiento) {
            url += '?establecimiento=' + establecimiento;
        }

        this.descargarArchivo(url);
    }

    exportarLicencias() {
        this.mostrarCarga('Generando Excel...');
        
        const establecimiento = document.getElementById('filtroEstablecimiento')?.value || '';
        let url = 'export/exportar_excel.php?tipo=licencias';
        
        if (establecimiento) {
            url += '&establecimiento=' + establecimiento;
        }

        this.descargarArchivo(url);
    }

    descargarArchivo(url) {
        // Método simple: redirección directa
        window.location.href = url;
        
        // Ocultar carga después de un tiempo
        setTimeout(() => {
            this.ocultarCarga();
        }, 1000);
    }

    mostrarCarga(mensaje) {
        let overlay = document.getElementById('excelLoadingOverlay');
        
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'excelLoadingOverlay';
            overlay.innerHTML = `
                <div class="export-loading">
                    <div class="spinner"></div>
                    <p>${mensaje}</p>
                </div>
            `;
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                backdrop-filter: blur(5px);
            `;
            document.body.appendChild(overlay);
        }
    }

    ocultarCarga() {
        const overlay = document.getElementById('excelLoadingOverlay');
        if (overlay) {
            overlay.remove();
        }
    }
}

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    new ExcelExporter();
});
// Búsqueda en tiempo real mejorada
    document.getElementById('search').addEventListener('input', function(e) {
      const term = e.target.value.toLowerCase().trim();
      const rows = document.querySelectorAll('tbody tr');
      let visibleCount = 0;
      
      rows.forEach(row => {
        if (row.classList.contains('no-data')) return;
        
        const text = row.textContent.toLowerCase();
        const isVisible = text.includes(term);
        row.style.display = isVisible ? '' : 'none';
        
        if (isVisible) visibleCount++;
      });
      
      // Mostrar mensaje si no hay resultados
      const noDataRow = document.querySelector('.no-data');
      if (noDataRow) {
        if (visibleCount === 0 && term !== '') {
          noDataRow.style.display = '';
          noDataRow.innerHTML = `
            <i class="fas fa-search"></i>
            <p>No se encontraron licencias</p>
            <small>Intenta con otros términos de búsqueda</small>
          `;
        } else if (term === '') {
          noDataRow.style.display = visibleCount === 0 ? '' : 'none';
          if (visibleCount === 0) {
            noDataRow.innerHTML = `
              <i class="fas fa-file-contract"></i>
              <p>No hay licencias asignadas a tu usuario</p>
              <small>Contacta al encargado de informática para asignarte licencias de software.</small>
            `;
          }
        } else {
          noDataRow.style.display = 'none';
        }
      }
    });

    // Efectos de interacción
    document.addEventListener('DOMContentLoaded', function() {
      // Animación de aparición para las filas
      const rows = document.querySelectorAll('tbody tr:not(.no-data)');
      rows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateY(20px)';
        row.style.transition = `all 0.5s ease ${index * 0.1}s`;
        
        setTimeout(() => {
          row.style.opacity = '1';
          row.style.transform = 'translateY(0)';
        }, 100);
      });

      // Efecto hover mejorado para estadísticas
      const statCards = document.querySelectorAll('.stat-card');
      statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
          this.style.transform = 'translateY(-8px)';
        });
        
        card.addEventListener('mouseleave', function() {
          this.style.transform = 'translateY(-5px)';
        });
      });

      // Actualizar fecha en tiempo real (opcional)
      function updateDateTime() {
        const now = new Date();
        const dateTimeString = now.toLocaleDateString('es-CL', {
          weekday: 'long',
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        }) + ' - ' + now.toLocaleTimeString('es-CL');
        
        // Podrías mostrar esto en algún lugar si quieres
        console.log('Fecha actualizada:', dateTimeString);
      }

      // Actualizar cada minuto
      setInterval(updateDateTime, 60000);
      updateDateTime();
    });

    // Mejorar experiencia en móviles
    if (window.innerWidth <= 768) {
      // Aumentar área táctil para elementos interactivos
      const interactiveElements = document.querySelectorAll('.btn-back, .badge');
      interactiveElements.forEach(el => {
        el.style.minHeight = '44px';
        el.style.minWidth = '44px';
      });
    }
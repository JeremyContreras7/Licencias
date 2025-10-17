// Efecto de partículas de fondo
    document.addEventListener('DOMContentLoaded', function() {
      const particlesContainer = document.getElementById('particles');
      const particleCount = 15;
      
      for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        
        // Posición y tamaño aleatorio
        const size = Math.random() * 60 + 20;
        const left = Math.random() * 100;
        const top = Math.random() * 100;
        const delay = Math.random() * 5;
        
        particle.style.width = `${size}px`;
        particle.style.height = `${size}px`;
        particle.style.left = `${left}%`;
        particle.style.top = `${top}%`;
        particle.style.animationDelay = `${delay}s`;
        particle.style.opacity = Math.random() * 0.3 + 0.1;
        
        particlesContainer.appendChild(particle);
      }

      // Efecto de carga suave
      const cards = document.querySelectorAll('.card');
      cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.2}s`;
      });

      // Actualizar hora en tiempo real
      function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('es-CL', { 
          hour: '2-digit', 
          minute: '2-digit',
          hour12: false 
        });
        const dateString = now.toLocaleDateString('es-CL', {
          weekday: 'long',
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });
        
        const timeElement = document.querySelector('.header p');
        if (timeElement) {
          timeElement.innerHTML = `<i class="fas fa-calendar-day"></i> ${dateString} - ${timeString} - Bienvenido a tu panel de control`;
        }
      }

      updateTime();
      setInterval(updateTime, 60000);
    });
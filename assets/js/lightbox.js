/**
 * Lightbox - Visualizador de Fotos com Navegação
 * Suporta swipe touch para mobile e navegação por teclado para desktop
 */

class Lightbox {
    constructor() {
        this.overlay = null;
        this.imageElement = null;
        this.counterElement = null;
        this.prevButton = null;
        this.nextButton = null;
        this.photos = [];
        this.currentIndex = 0;
        this.touchStartX = 0;
        this.touchEndX = 0;
        this.minSwipeDistance = 50;

        this.init();
    }

    init() {
        this.createElements();
        this.bindEvents();
    }

    createElements() {
        // Criar overlay do lightbox
        this.overlay = document.createElement('div');
        this.overlay.className = 'lightbox-overlay';
        this.overlay.innerHTML = `
            <button class="lightbox-close" aria-label="Fechar">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            <button class="lightbox-nav prev" aria-label="Foto anterior">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
            <div class="lightbox-container">
                <img class="lightbox-image" src="" alt="Foto ampliada">
            </div>
            <button class="lightbox-nav next" aria-label="Próxima foto">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
            <div class="lightbox-counter">1 / 1</div>
        `;

        document.body.appendChild(this.overlay);

        // Guardar referências aos elementos
        this.imageElement = this.overlay.querySelector('.lightbox-image');
        this.counterElement = this.overlay.querySelector('.lightbox-counter');
        this.prevButton = this.overlay.querySelector('.lightbox-nav.prev');
        this.nextButton = this.overlay.querySelector('.lightbox-nav.next');
    }

    bindEvents() {
        // Fechar ao clicar no botão X
        this.overlay.querySelector('.lightbox-close').addEventListener('click', () => this.close());

        // Fechar ao clicar no overlay (fora da imagem)
        this.overlay.addEventListener('click', (e) => {
            if (e.target === this.overlay) {
                this.close();
            }
        });

        // Navegação por botões
        this.prevButton.addEventListener('click', (e) => {
            e.stopPropagation();
            this.prev();
        });

        this.nextButton.addEventListener('click', (e) => {
            e.stopPropagation();
            this.next();
        });

        // Navegação por teclado
        document.addEventListener('keydown', (e) => {
            if (!this.overlay.classList.contains('active')) return;

            switch (e.key) {
                case 'Escape':
                    this.close();
                    break;
                case 'ArrowLeft':
                    this.prev();
                    break;
                case 'ArrowRight':
                    this.next();
                    break;
            }
        });

        // Suporte a touch/swipe para mobile
        this.overlay.addEventListener('touchstart', (e) => {
            this.touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        this.overlay.addEventListener('touchend', (e) => {
            this.touchEndX = e.changedTouches[0].screenX;
            this.handleSwipe();
        }, { passive: true });
    }

    handleSwipe() {
        const diff = this.touchStartX - this.touchEndX;

        if (Math.abs(diff) < this.minSwipeDistance) return;

        if (diff > 0) {
            // Swipe para esquerda = próxima
            this.next();
        } else {
            // Swipe para direita = anterior
            this.prev();
        }
    }

    open(photos, startIndex = 0) {
        this.photos = photos;
        this.currentIndex = startIndex;

        // Atualizar visibilidade dos botões de navegação
        const hasMultiple = photos.length > 1;
        this.prevButton.classList.toggle('hidden', !hasMultiple);
        this.nextButton.classList.toggle('hidden', !hasMultiple);
        this.counterElement.style.display = hasMultiple ? 'block' : 'none';

        this.updateImage();
        this.overlay.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevenir scroll no body
    }

    close() {
        this.overlay.classList.remove('active');
        document.body.style.overflow = ''; // Restaurar scroll
    }

    prev() {
        if (this.photos.length <= 1) return;
        this.currentIndex = (this.currentIndex - 1 + this.photos.length) % this.photos.length;
        this.updateImage();
    }

    next() {
        if (this.photos.length <= 1) return;
        this.currentIndex = (this.currentIndex + 1) % this.photos.length;
        this.updateImage();
    }

    updateImage() {
        const photo = this.photos[this.currentIndex];

        // Animação de transição
        this.imageElement.style.opacity = '0';
        this.imageElement.style.transform = 'scale(0.9)';

        setTimeout(() => {
            this.imageElement.src = photo;
            this.imageElement.onload = () => {
                this.imageElement.style.opacity = '1';
                this.imageElement.style.transform = 'scale(1)';
            };
        }, 150);

        // Atualizar contador
        this.counterElement.textContent = `${this.currentIndex + 1} / ${this.photos.length}`;
    }
}

// Instância global do lightbox
let lightbox = null;

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    lightbox = new Lightbox();
});

/**
 * Abre o lightbox com as fotos especificadas
 * @param {string[]} photos - Array de URLs das fotos
 * @param {number} startIndex - Índice inicial (qual foto mostrar primeiro)
 */
function openLightbox(photos, startIndex = 0) {
    if (!lightbox) {
        lightbox = new Lightbox();
    }
    lightbox.open(photos, startIndex);
}

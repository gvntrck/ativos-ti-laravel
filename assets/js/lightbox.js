/**
 * Lightbox - Visualizador de Fotos com Navegação e Zoom
 * Suporta swipe touch para mobile, navegação por teclado e zoom/pan
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

        // Zoom properties
        this.currentZoom = 1;
        this.minZoom = 0.5;
        this.maxZoom = 3;
        this.isDragging = false;
        this.startX = 0;
        this.startY = 0;
        this.translateX = 0;
        this.translateY = 0;

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
            <div class="lightbox-toolbar">
                <button class="lightbox-tool zoom-out" aria-label="Diminuir Zoom">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"></path>
                    </svg>
                </button>
                <button class="lightbox-tool zoom-reset" aria-label="Resetar Zoom">
                    <span style="font-weight: bold; font-size: 14px;">1x</span>
                </button>
                <button class="lightbox-tool zoom-in" aria-label="Aumentar Zoom">
                     <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"></path>
                    </svg>
                </button>
                <button class="lightbox-close" aria-label="Fechar">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
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
        // Toolbar actions
        this.overlay.querySelector('.lightbox-close').addEventListener('click', () => this.close());
        this.overlay.querySelector('.zoom-in').addEventListener('click', (e) => { e.stopPropagation(); this.zoomIn(); });
        this.overlay.querySelector('.zoom-out').addEventListener('click', (e) => { e.stopPropagation(); this.zoomOut(); });
        this.overlay.querySelector('.zoom-reset').addEventListener('click', (e) => { e.stopPropagation(); this.resetZoom(); });

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
                case '+':
                case '=':
                    this.zoomIn();
                    break;
                case '-':
                    this.zoomOut();
                    break;
                case '0':
                    this.resetZoom();
                    break;
            }
        });

        // Eventos de Mouse para Zoom e Pan
        this.imageElement.addEventListener('wheel', (e) => {
            if (e.ctrlKey || true) { // Permitir zoom com scroll direto
                e.preventDefault();
                if (e.deltaY < 0) this.zoomIn();
                else this.zoomOut();
            }
        }, { passive: false });

        this.imageElement.addEventListener('dblclick', (e) => {
            e.stopPropagation();
            if (this.currentZoom > 1) this.resetZoom();
            else this.setZoom(2);
        });

        // Pan Logic (Mouse)
        this.imageElement.addEventListener('mousedown', (e) => this.startDrag(e.clientX, e.clientY));
        window.addEventListener('mousemove', (e) => this.onDrag(e.clientX, e.clientY));
        window.addEventListener('mouseup', () => this.endDrag());

        // Pan Logic (Touch) & Swipe
        this.overlay.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) {
                this.touchStartX = e.changedTouches[0].screenX;
                this.startDrag(e.touches[0].clientX, e.touches[0].clientY);
            }
        }, { passive: false }); // passive: false to allow preventDefault if needed

        this.overlay.addEventListener('touchmove', (e) => {
            if (this.isDragging) {
                e.preventDefault(); // Impede scroll da página enquanto arrasta
                this.onDrag(e.touches[0].clientX, e.touches[0].clientY);
            }
        }, { passive: false });

        this.overlay.addEventListener('touchend', (e) => {
            this.touchEndX = e.changedTouches[0].screenX;
            this.endDrag();

            // Só processa swipe se não estiver com zoom (ou se o zoom for 1)
            // Se estiver com zoom, o movimento é interpretado como Pan
            if (this.currentZoom === 1 && !this.isDragging) {
                this.handleSwipe();
            }
        }, { passive: true });
    }

    // Zoom Methods
    setZoom(value) {
        this.currentZoom = Math.min(Math.max(value, this.minZoom), this.maxZoom);
        this.updateTransform();
        this.updateCursor();
    }

    zoomIn() {
        this.setZoom(this.currentZoom + 0.5);
    }

    zoomOut() {
        this.setZoom(this.currentZoom - 0.5);
    }

    resetZoom() {
        this.currentZoom = 1;
        this.translateX = 0;
        this.translateY = 0;
        this.setZoom(1);
    }

    updateTransform() {
        this.imageElement.style.transform = `translate(${this.translateX}px, ${this.translateY}px) scale(${this.currentZoom})`;
    }

    updateCursor() {
        if (this.currentZoom > 1) {
            this.imageElement.style.cursor = this.isDragging ? 'grabbing' : 'grab';
        } else {
            this.imageElement.style.cursor = 'default';
        }
    }

    // Drag / Pan Methods
    startDrag(clientX, clientY) {
        if (this.currentZoom <= 1) return;

        this.isDragging = true;
        this.startX = clientX - this.translateX;
        this.startY = clientY - this.translateY;
        this.imageElement.style.transition = 'none'; // Desativa transição para arraste suave
        this.updateCursor();
    }

    onDrag(clientX, clientY) {
        if (!this.isDragging) return;

        this.translateX = clientX - this.startX;
        this.translateY = clientY - this.startY;
        this.updateTransform();
    }

    endDrag() {
        if (!this.isDragging) return;

        this.isDragging = false;
        this.imageElement.style.transition = 'transform 0.1s ease'; // Reativa transição
        this.updateCursor();
    }

    handleSwipe() {
        const diff = this.touchStartX - this.touchEndX;

        if (Math.abs(diff) < this.minSwipeDistance) return;

        if (diff > 0) {
            this.next();
        } else {
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
        document.body.style.overflow = 'hidden';
    }

    close() {
        this.overlay.classList.remove('active');
        document.body.style.overflow = '';
        this.resetZoom(); // Resetar zoom ao fechar
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

        // Resetar zoom ao trocar de imagem
        this.resetZoom();

        // Animação de transição (opacidade apenas, já que transform é usado pelo zoom)
        this.imageElement.style.opacity = '0';
        this.imageElement.style.transition = 'opacity 0.2s ease';

        // Pequeno delay para permitir o reset visual antes de carregar a nova
        setTimeout(() => {
            this.imageElement.src = photo;
            this.imageElement.onload = () => {
                this.imageElement.style.opacity = '1';
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

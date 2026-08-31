const modalTriggers = document.querySelectorAll('[data-modal]');
const modalOverlay = document.getElementById('modal-overlay');
const closeButtons = document.querySelectorAll('.modal-close');

const closeModal = () => {
    if (modalOverlay) {
        modalOverlay.classList.remove('active');
        document.querySelectorAll('.modal-window').forEach(m => m.classList.remove('active'));
        document.body.style.overflow = '';
    }
};

modalTriggers.forEach(trigger => {
    trigger.addEventListener('click', (e) => {
        e.preventDefault();
        const modalId = trigger.getAttribute('data-modal');
        const targetModal = document.getElementById(modalId);

        if (modalOverlay && targetModal) {
            document.querySelectorAll('.modal-window').forEach(m => m.classList.remove('active'));
            modalOverlay.classList.add('active');
            targetModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    });
});

closeButtons.forEach(btn => btn.addEventListener('click', closeModal));

if (modalOverlay) {
    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) closeModal();
    });
}

// Close modal when CTA links inside modals are clicked
document.querySelectorAll('.modal-cta').forEach(link => {
    link.addEventListener('click', closeModal);
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
});

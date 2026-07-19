import './bootstrap';

const progress = () => document.getElementById('nav-progress');

document.addEventListener('livewire:navigating', () => {
    const el = progress();
    if (! el) return;
    el.classList.add('scale-x-100');
    el.classList.remove('scale-x-0');
});

document.addEventListener('livewire:navigated', () => {
    const el = progress();
    if (! el) return;
    window.setTimeout(() => {
        el.classList.remove('scale-x-100');
        el.classList.add('scale-x-0');
    }, 180);
});

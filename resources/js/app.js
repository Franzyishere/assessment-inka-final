import './bootstrap';
import Alpine from 'alpinejs';
import { initializeLiveSearch } from './components/live-search';
import { initializeDashboardCharts } from './components/dashboard-charts';

window.Alpine = Alpine;

Alpine.start();

// Initialize components on DOM ready
document.addEventListener('DOMContentLoaded', async () => {
    initializeLiveSearch();
    if (document.querySelector('[data-rich-text-editor]')) {
        import('./components/rich-text-editor').then(module => module.initializeRichTextEditors());
    }
    if (document.querySelector('[data-dashboard-chart]')) {
        const { default: ApexCharts } = await import('apexcharts');
        window.ApexCharts = ApexCharts;
        initializeDashboardCharts();
    }
});

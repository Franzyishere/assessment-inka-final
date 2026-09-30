import './bootstrap';
import Alpine from 'alpinejs';
import { initializeLiveSearch } from './components/live-search';
import { initializeDashboardCharts } from './components/dashboard-charts';
import { registerDateTimePicker } from './components/datepicker';

window.Alpine = Alpine;
registerDateTimePicker();
Alpine.start();

// Initialize components on DOM ready
document.addEventListener('DOMContentLoaded', async () => {
    initializeLiveSearch();
    const renderDocumentPreviews = () => {
        if (document.querySelector('[data-answer-scene]')) {
            import('./components/document-shapes').then(module => module.initializeDocumentPreviews());
        }
    };
    renderDocumentPreviews();
    document.addEventListener('page-content-updated', renderDocumentPreviews);
    if (document.querySelector('[data-answer-diagram]')) {
        import('./components/answer-diagram').then(module => module.initializeAnswerDiagrams());
    }
    if (document.querySelector('[data-rich-text-editor]')) {
        import('./components/rich-text-editor').then(module => module.initializeRichTextEditors());
    }
    if (document.querySelector('[data-dashboard-chart]')) {
        const { default: ApexCharts } = await import('apexcharts');
        window.ApexCharts = ApexCharts;
        initializeDashboardCharts();
    }
    if (document.querySelector('[data-secure-pdf-viewer]')) {
        import('./components/secure-pdf-viewer').then(module => module.initializeSecurePdfViewers());
    }
});

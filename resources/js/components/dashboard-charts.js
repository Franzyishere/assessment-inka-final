const palette = ['#ed1b2f', '#111827', '#f59e0b', '#64748b', '#f87171'];

function emptyState(element) {
    element.innerHTML = '<div class="flex min-h-72 items-center justify-center text-sm text-gray-500">Belum ada data untuk ditampilkan.</div>';
}

async function waitForStableLayout() {
    await document.fonts?.ready;
    await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
}

export async function initializeDashboardCharts(root = document) {
    await waitForStableLayout();

    root.querySelectorAll('[data-dashboard-chart]').forEach(element => {
        if (element.dataset.chartInitialized) return;

        const source = document.getElementById(element.dataset.dashboardChart);
        if (!source) return;

        let config;
        try {
            config = JSON.parse(source.textContent);
        } catch {
            emptyState(element);
            return;
        }

        config.labels = (config.labels ?? []).map(label => String(label));
        config.series = config.type === 'donut'
            ? (config.series ?? []).map(value => Number(value) || 0)
            : (config.series ?? []).map(series => ({
                ...series,
                data: (series.data ?? []).map(value => Number(value) || 0),
            }));
        const values = config.type === 'donut'
            ? config.series
            : config.series.flatMap(series => series.data);

        if (!values.some(value => Number(value) > 0)) {
            emptyState(element);
            return;
        }

        const shared = {
            chart: {
                fontFamily: 'Outfit, sans-serif',
                toolbar: { show: false },
                animations: { enabled: true, speed: 450 },
                parentHeightOffset: 0,
                redrawOnParentResize: true,
                redrawOnWindowResize: true,
                foreColor: '#334155',
            },
            colors: palette,
            dataLabels: {
                enabled: true,
                style: { fontSize: '12px', fontWeight: 700, colors: ['#ffffff'] },
                dropShadow: { enabled: false },
                background: { enabled: false },
            },
            noData: { text: 'Belum ada data' },
            tooltip: { theme: 'dark',    },
        };

        const options = config.type === 'donut'
            ? {
                ...shared,
                chart: { ...shared.chart, type: 'donut', height: 300 },
                labels: config.labels,
                series: config.series,
                legend: { position: 'bottom', fontSize: '13px', fontWeight: 600, labels: { colors: '#334155' } },
                stroke: { colors: ['#ffffff'], width: 3 },
                plotOptions: {
                    pie: {
                        dataLabels: { minAngleToShowLabel: 10 },
                        donut: {
                            size: '68%',
                            labels: {
                                show: true,
                                name: { show: true, color: '#64748b', fontSize: '13px', fontWeight: 600 },
                                value: { show: true, color: '#111827', fontSize: '24px', fontWeight: 700 },
                                total: { show: true, label: 'Total', color: '#64748b', fontSize: '13px', fontWeight: 600 },
                            },
                        },
                    },
                },
            }
            : {
                ...shared,
                chart: { ...shared.chart, type: 'bar', height: Math.max(300, config.labels.length * 48) },
                series: config.series,
                xaxis: {
                    categories: config.labels,
                    min: 0,
                    forceNiceScale: true,
                    labels: { formatter: value => Math.round(value), style: { colors: '#64748b', fontSize: '12px' } },
                },
                yaxis: { labels: { maxWidth: 190, style: { colors: '#475569', fontSize: '12px' } } },
                grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
                plotOptions: { bar: { horizontal: true, borderRadius: 5, barHeight: '55%' } },
                dataLabels: {
                    enabled: true,
                    textAnchor: 'start',
                    offsetX: 8,
                    formatter: value => Math.round(value),
                    style: { fontSize: '12px', fontWeight: 700, colors: ['#111827'] },
                },
                tooltip: { theme: 'light' },
                legend: { position: 'top', horizontalAlign: 'right', fontSize: '13px', fontWeight: 600, labels: { colors: '#334155' } },
            };

        if (typeof window.ApexCharts !== 'function') {
            emptyState(element);
            return;
        }

        const chart = new window.ApexCharts(element, options);
        chart.render().then(() => {
            element.dataset.chartInitialized = 'true';

            // Sidebar memakai transisi lebar 300 ms. Ukur ulang setelah transisi
            // agar pusat donut tetap mengikuti lebar kontainer yang sebenarnya.
            window.setTimeout(() => {
                chart.updateOptions({ chart: { width: '100%' } }, false, false);
            }, 350);
        }).catch(() => emptyState(element));
    });
}

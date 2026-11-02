const colors = []

colors['success'] = '#16A34A'
colors['danger'] = '#DC2626'
colors['info'] = '#0878C8'
colors['primary'] = '#1868E8'
colors['warning'] = '#F2643D'

// gráficos aparecem prontos, sem animação de entrada; mesma fonte do sistema
if (window.Chart && Chart.defaults) {
    Chart.defaults.animation = false
    Chart.defaults.font.family = document.body.id === 'admin' ? 'Inter, system-ui, sans-serif' : 'Nunito, system-ui, sans-serif'
    Chart.defaults.color = '#5B6B84'
}

//-------------------------------------------

// Portais docente/aluno: cores lidas do tema (claro/escuro) e dica de gráfico sem o quadradinho de cor
window.MATER_CHART = {
    theme() {
        const css = getComputedStyle(document.body)
        return {
            text: css.getPropertyValue('--m-text').trim() || '#2A2754',
            muted: css.getPropertyValue('--m-muted').trim() || '#6D6A8C',
            grid: css.getPropertyValue('--m-border').trim() || '#DCE5F5',
            surface: css.getPropertyValue('--m-surface').trim() || '#FFFFFF',
        }
    },
    tooltip(theme, extra) {
        return Object.assign({
            backgroundColor: theme.surface, titleColor: theme.text, bodyColor: theme.muted,
            borderColor: theme.grid, borderWidth: 1, padding: 10, cornerRadius: 12, caretSize: 0, displayColors: false,
            titleFont: { family: 'Nunito', weight: '900', size: 13 }, bodyFont: { family: 'Nunito', weight: '700', size: 13 },
        }, extra || {})
    },
    // redesenha ao trocar entre claro e escuro
    onThemeChange(callback) {
        new MutationObserver(callback).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
    },
}

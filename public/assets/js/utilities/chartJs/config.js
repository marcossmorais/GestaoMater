const colors = []

colors['success'] = '#16A34A'
colors['danger'] = '#DC2626'
colors['info'] = '#0878C8'
colors['primary'] = '#1868E8'
colors['warning'] = '#F8A808'

// gráficos aparecem prontos, sem animação de entrada; mesma fonte do sistema
if (window.Chart && Chart.defaults) {
    Chart.defaults.animation = false
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif'
    Chart.defaults.color = '#5B6B84'
}

//-------------------------------------------
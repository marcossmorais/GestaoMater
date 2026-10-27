// só a home do docente tem o gráfico
if (document.getElementById('index-disciplines')) {

$.ajax({
  type: "GET",
  url: "/portal-docente/aproveitamento-alunos-base-media-final",
  dataType: 'json',
  success: data => {

    let labels = []
    let datas = []

    $.each(data, i => {

        labels.push(data[i].acronym)
        datas.push(data[i].total)
      }),

      setTimeout(function () {

        new Chart(
          document.getElementById('index-disciplines'), {
            type: 'doughnut',
            data: {
              labels: labels,
              datasets: [{
                borderRadius: 5,
                data: datas,
                backgroundColor: [colors['success'], colors['danger'], colors['info'] , colors['primary'], colors['warning']],
                padding: {
                  top: 10,
                  bottom: 10
                },
                hoverOffset: 4
              }]
            },
            options: {
                maintainAspectRatio: false,
              cutoutPercentage: 30,
              plugins: {
                title: {
                        display: false,
                  text: 'Aproveitamento dos alunos com base na média final',
                  padding: {
                    top: 10,
                    bottom: 10
                  }
                }
              },
              responsive: true,
              maintainAspectRatio: false
            }
          })

      }, 1500)
  }
})

}
